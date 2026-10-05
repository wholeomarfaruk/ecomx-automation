<?php

namespace App\OrderIntake;

use App\Models\OrderIntakeLog;
use App\Models\Product;
use App\Models\Setting;
use App\Models\ShippingMethod;
use App\OrderIntake\Ai\AiExtractionException;
use App\OrderIntake\Ai\AiOrderExtractor;
use App\Services\Shipping\ShippingCalculator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Smalot\PdfParser\Parser as PdfParser;

/**
 * AI Order, end to end: text / screenshots / PDFs → resolved order drafts →
 * bulk sheet rows. The deterministic parser always runs first; the AI
 * fallback runs only when a critical field (phone, name, address,
 * products/quantity, delivery area) is still unresolved, when there are
 * attachments the parser can't read, or when asked to retry with AI —
 * and only if it's switched on, has a key and is within its limits. Its
 * reply is merged under what the parser was already sure of and resolved
 * through the same DraftResolver, so ids, prices and totals always come
 * from the database. Every run is logged (OrderIntakeLog) — usage/cost,
 * the cached AI reply for a repeat of the same input, and later the
 * orders placed from it.
 */
class OrderIntakeService
{
    public const MAX_TEXT = 20000;

    /** Seconds a request can safely run behind a typical nginx/PHP-FPM/Cloudflare setup. */
    public const REQUEST_LIMIT = 58;

    public function __construct(
        protected IntakeSettings $settings,
        protected AiOrderExtractor $ai,
    ) {}

    /**
     * @param  list<UploadedFile>  $files
     * @return array<string, mixed>
     */
    public function extract(string $text, array $files, string $source, bool $forceAi = false, ?int $userId = null): array
    {
        [$text, $media] = $this->readFiles(mb_substr(trim($text), 0, self::MAX_TEXT), $files);

        $hash = hash('sha256', Text::norm($text) . '|' . implode('|', array_map(fn ($f) => sha1($f['data']), $media)));

        $catalog = Catalog::fromDatabase();
        $areas = DeliveryAreas::fromDatabase();
        $defaultMethodId = collect($areas->zones())->firstWhere('is_default', true)['method_id'] ?? ($areas->zones()[0]['method_id'] ?? null);

        $parsed = $text !== '' ? (new TextOrderParser($catalog, $areas, ...$this->shopIdentity()))->parse($text) : [];
        $resolver = $this->resolver($catalog, $areas, $parsed, $defaultMethodId);
        $drafts = array_map(fn ($r) => $resolver->resolve($r), $parsed);

        Log::info('AI Order: input', [
            'user'      => $userId,
            'text_len'  => mb_strlen($text),
            'uploaded'  => array_map(fn ($f) => ['name' => $f->getClientOriginalName(), 'mime' => $f->getMimeType(), 'kb' => (int) round($f->getSize() / 1024)], $files),
            'media'     => array_map(fn ($f) => ['mime' => $f['mime'], 'kb' => (int) round(strlen($f['data']) * 3 / 4 / 1024)], $media),
            'parsed'    => count($drafts),
            'force_ai'  => $forceAi,
        ]);

        $reasons = $this->aiReasons($text, $media, $drafts, $forceAi);
        Log::info('AI Order: ai decision', ['reasons' => $reasons, 'ai_available' => $this->settings->aiAvailable(), 'blocked' => $reasons ? $this->aiBlockedReason($userId) : null]);
        $ai = ['available' => $this->settings->aiAvailable(), 'used' => false, 'cached' => false, 'model' => null, 'error' => null, 'reasons' => $reasons];
        $resolution = OrderIntakeLog::RESOLUTION_PARSER;
        $usage = [];
        $aiResult = null;

        if ($reasons !== []) {
            $blocked = $this->aiBlockedReason($userId);

            if ($blocked !== null) {
                $resolution = OrderIntakeLog::RESOLUTION_AI_SKIPPED;
                $ai['error'] = $blocked;
            } else {
                $cached = $forceAi ? null : OrderIntakeLog::query()
                    ->where('input_hash', $hash)->whereNotNull('ai_result')
                    // Only an answer that gave usable orders is worth reusing.
                    ->where('orders_ready', '>', 0)
                    ->where('created_at', '>=', now()->subDays(7))
                    ->latest('id')->first();

                try {
                    if ($cached) {
                        $aiOrders = array_map(fn ($o) => AiOrderExtractor::toRawDraft($o, $text), $cached->ai_result['orders'] ?? []);
                        $aiResult = $cached->ai_result;
                        $ai['cached'] = true;
                        $ai['model'] = $cached->model;
                    } else {
                        RateLimiter::hit($this->rateKey($userId), 60);
                        $reply = $this->ai->extract(
                            $text,
                            $media,
                            $drafts,
                            $this->candidates($catalog, $text),
                            array_column($areas->zones(), 'zone'),
                            $this->aiDeadline(),
                        );
                        $aiOrders = $reply['orders'];
                        $aiResult = $reply['raw'];
                        $ai['model'] = $reply['model'];
                        $usage = $reply['usage'] + ['latency_ms' => $reply['latency_ms']];
                    }

                    $merged = $this->merge($parsed, $drafts, $aiOrders);
                    $resolver = $this->resolver($catalog, $areas, $merged, $defaultMethodId);
                    $drafts = array_map(fn ($r) => $resolver->resolve($r), $merged);
                    $resolution = OrderIntakeLog::RESOLUTION_AI;
                    $ai['used'] = true;
                } catch (AiExtractionException $e) {
                    Log::warning('AI Order: ai failed', ['error' => $e->getMessage(), 'model' => $e->model, 'ms' => $e->latencyMs]);
                    report($e);
                    $resolution = OrderIntakeLog::RESOLUTION_AI_FAILED;
                    $ai['error'] = $e->getMessage();
                    $ai['model'] = $e->model;
                    $usage = $e->usage + ['latency_ms' => $e->latencyMs];
                }
            }
        }

        Log::info('AI Order: result', [
            'resolution' => $resolution,
            'model'      => $ai['model'],
            'cached'     => $ai['cached'],
            'error'      => $ai['error'],
            'drafts'     => count($drafts),
            'ready'      => count(array_filter($drafts, fn ($d) => $d['ready'])),
        ]);

        $log = OrderIntakeLog::create([
            'user_id'           => $userId,
            'input_hash'        => $hash,
            'source_type'       => $this->sourceType($text, $media),
            'input_text'        => $text !== '' ? $text : null,
            'file_count'        => count($files),
            'resolution'        => $resolution,
            'orders_found'      => count($drafts),
            'orders_ready'      => count(array_filter($drafts, fn ($d) => $d['ready'])),
            'model'             => $ai['cached'] ? null : $ai['model'],
            'prompt_tokens'     => $usage['prompt_tokens'] ?? null,
            'completion_tokens' => $usage['completion_tokens'] ?? null,
            'cost'              => isset($usage['cost']) ? (float) $usage['cost'] : null,
            'latency_ms'        => $usage['latency_ms'] ?? null,
            'error'             => $ai['error'] ? mb_substr($ai['error'], 0, 1000) : null,
            'ai_result'         => $ai['cached'] ? null : $aiResult,
        ]);

        $previous = OrderIntakeLog::query()
            ->where('input_hash', $hash)->where('id', '!=', $log->id)
            ->whereNotNull('order_ids')
            ->latest('id')->first();

        return [
            'intake_id'  => $log->id,
            'resolution' => $resolution,
            'ai'         => $ai,
            'drafts'     => $drafts,
            'rows'       => array_map(fn ($d) => DraftResolver::toSheetRow($d, $source, $log->id), $drafts),
            'previous'   => $previous ? [
                'order_ids' => $previous->order_ids,
                'at'        => $previous->created_at->diffForHumans(),
            ] : null,
        ];
    }

    /** Orders placed from an extraction — the bulk sheet reports them after placing. */
    public function recordPlaced(int $intakeId, int $orderId): void
    {
        $log = OrderIntakeLog::find($intakeId);

        if ($log) {
            $log->update(['order_ids' => array_values(array_unique([...($log->order_ids ?? []), $orderId]))]);
        }
    }

    // ---------------------------------------------------------------

    /**
     * Text files are read into the text (the parser handles them); images
     * and PDFs are only readable by the AI.
     *
     * @param  list<UploadedFile>  $files
     * @return array{0: string, 1: list<array{mime: string, name: string, data: string}>}
     */
    protected function readFiles(string $text, array $files): array
    {
        $media = [];
        $allowed = $this->settings->allowedMimes();

        foreach (array_slice($files, 0, $this->settings->maxFiles()) as $file) {
            $mime = (string) $file->getMimeType();

            if (! in_array($mime, $allowed, true) || $file->getSize() > $this->settings->maxFileKb() * 1024) {
                Log::warning('AI Order: file skipped', ['name' => $file->getClientOriginalName(), 'mime' => $mime, 'kb' => (int) round($file->getSize() / 1024), 'allowed' => $allowed]);
                continue;
            }

            $contents = (string) file_get_contents($file->getRealPath());

            if ($mime === 'text/plain') {
                $text = trim($text . "\n\n" . mb_substr($contents, 0, self::MAX_TEXT));
                continue;
            }

            // A PDF with real text (an order slip, an exported sheet) is read
            // by the parser; only a scanned/image PDF needs the AI.
            if ($mime === 'application/pdf' && ($pdfText = $this->pdfText($contents)) !== null) {
                $text = trim($text . "\n\n" . mb_substr($pdfText, 0, self::MAX_TEXT));
                continue;
            }

            $media[] = ['mime' => $mime, 'name' => $file->getClientOriginalName(), 'data' => base64_encode($contents)];
        }

        return [mb_substr($text, 0, self::MAX_TEXT), $media];
    }

    /**
     * When every AI attempt must be done: inside PHP's own time limit and the
     * ~60 s most web servers/proxies allow a request, with room left to
     * resolve and log the result. A request cut off there loses everything
     * — the parser's drafts included — so the AI gets less time instead.
     */
    protected function aiDeadline(): float
    {
        $started = defined('LARAVEL_START') ? LARAVEL_START : microtime(true);
        $phpLimit = (int) ini_get('max_execution_time');
        $limit = $phpLimit > 0 ? min($phpLimit, self::REQUEST_LIMIT) : self::REQUEST_LIMIT;

        return $started + $limit - 8;
    }

    /** The PDF's text layer, or null when it has none worth reading (scanned pages). */
    protected function pdfText(string $contents): ?string
    {
        try {
            $text = trim((new PdfParser())->parseContent($contents)->getText());
        } catch (\Throwable) {
            return null;
        }

        return preg_match_all('/[\p{L}\p{N}]/u', $text) >= 20 ? $text : null;
    }

    protected function resolver(Catalog $catalog, DeliveryAreas $areas, array $raws, ?int $defaultMethodId): DraftResolver
    {
        $phones = array_values(array_filter(array_map(
            fn ($r) => $r['phone'] !== null && PhoneExtractor::isValid(PhoneExtractor::national($r['phone'])) ? PhoneExtractor::national($r['phone']) : null,
            $raws,
        )));
        $customers = collect(CustomerDirectory::lookup($phones))->keyBy('national')->all();

        $methods = ShippingMethod::with('zone')->where('is_active', true)->get()->keyBy('id');
        $calculator = app(ShippingCalculator::class);

        $quote = function (int $methodId, array $items, float $subtotal) use ($methods, $calculator): ?float {
            $method = $methods->get($methodId);

            if (! $method) {
                return null;
            }

            $products = Product::whereIn('id', array_column($items, 'product_id'))->get()->keyBy('id');
            $lines = collect($items)
                ->map(fn ($i) => ['product' => $products->get($i['product_id']), 'quantity' => (float) $i['quantity'], 'is_gift' => false])
                ->filter(fn ($l) => $l['product'] && $l['quantity'] > 0)
                ->values()->all();

            return $calculator->quote($method, $lines, $subtotal)->amount;
        };

        return new DraftResolver($catalog, $areas, $quote, $customers, $defaultMethodId);
    }

    /**
     * Why the AI should look at this input — empty when the parser settled
     * everything, or when what's missing can't be in the input anyway:
     * from plain text the AI can't produce a phone number that isn't
     * written there, or a name/address/product when every line was already
     * read (draft "unused" is empty). Screenshots/PDFs always go to the AI —
     * the parser can't read them.
     *
     * @return list<string>
     */
    protected function aiReasons(string $text, array $media, array $drafts, bool $forceAi): array
    {
        $reasons = [];

        if ($forceAi) {
            $reasons[] = 'retry requested';
        }
        if ($media !== []) {
            $reasons[] = 'screenshots/files';
        }
        if ($text !== '' && $drafts === [] && Text::words($text) !== []) {
            $reasons[] = 'no order found in the text';
        }

        foreach ($drafts as $d) {
            $leftover = ! empty($d['unused']);

            foreach ($d['needs_ai'] as $field) {
                $useful = match ($field) {
                    'phone'             => false,
                    'name', 'address'   => $leftover,
                    // Unmatched/ambiguous product text is there to interpret; no product text at all only helps if lines are left.
                    'products'          => $d['items'] !== [] || $leftover,
                    default             => true,
                };

                if ($useful) {
                    $reasons[] = $field;
                }
            }
        }

        return array_values(array_unique($reasons));
    }

    /**
     * The shop's own phone numbers and names (Settings → Contacts /
     * Company / General) — a chat quoting them must not read them as the
     * customer's.
     *
     * @return array{0: list<string>, 1: list<string>}
     */
    protected function shopIdentity(): array
    {
        $phones = collect([
            Setting::get('support_phone', '', 'contact'),
            Setting::get('support_whatsapp', '', 'contact'),
            Setting::get('company_phone', '', 'company'),
            Setting::get('company_mobile', '', 'company'),
        ])->filter(fn ($v) => is_string($v) && $v !== '')
            ->flatMap(fn ($v) => array_column(PhoneExtractor::find($v), 'national'))
            ->unique()->values()->all();

        $names = collect([
            Setting::get('site_name', ''),
            Setting::get('site_short_name', ''),
            Setting::get('company_name', '', 'company'),
        ])->filter(fn ($v) => is_string($v) && trim($v) !== '')->values()->all();

        return [$phones, $names];
    }
    protected function aiBlockedReason(?int $userId): ?string
    {
        if (! $this->settings->enabled()) {
            return 'AI fallback is turned off (Settings → AI Order) — fix the highlighted fields by hand.';
        }
        if ($this->settings->apiKey() === '') {
            return 'No OpenRouter API key is set (Settings → AI Order).';
        }

        $limit = $this->settings->dailyLimit();
        if ($limit > 0 && OrderIntakeLog::query()->where('created_at', '>=', now()->startOfDay())
            ->whereIn('resolution', [OrderIntakeLog::RESOLUTION_AI, OrderIntakeLog::RESOLUTION_AI_FAILED])
            ->whereNotNull('model')->count() >= $limit) {
            return "Today's AI request limit ({$limit}) is used up — fix the highlighted fields by hand.";
        }

        if (RateLimiter::tooManyAttempts($this->rateKey($userId), $this->settings->perMinute())) {
            return 'Too many AI requests — wait ' . RateLimiter::availableIn($this->rateKey($userId)) . 's and retry.';
        }

        return null;
    }

    private function rateKey(?int $userId): string
    {
        return 'order-intake-ai:' . ($userId ?? 'guest');
    }

    /** @return list<array{code: string, name: string, variants: list<string>}> */
    protected function candidates(Catalog $catalog, string $text): array
    {
        return array_map(fn ($p) => [
            'code'     => $p['code'] !== '' ? $p['code'] : (string) $p['id'],
            'name'     => $p['name'],
            'variants' => array_slice(array_column($p['variants'], 'label'), 0, 12),
        ], $catalog->candidates($text, $this->settings->maxCandidates()));
    }

    /**
     * The AI's orders laid over the parser's: an order is paired by phone
     * (or one-to-one when each side found a single order), and each field
     * keeps the parser's value when the parser was sure of it. Parser-only
     * orders are kept; AI-only orders are added.
     *
     * @param  list<array>  $parsed  raw parser drafts
     * @param  list<array>  $resolved  the same, resolved (to know which fields were sure)
     * @param  list<array>  $aiOrders  raw AI drafts
     * @return list<array>
     */
    protected function merge(array $parsed, array $resolved, array $aiOrders): array
    {
        $phoneOf = fn ($r) => $r['phone'] !== null && PhoneExtractor::isValid(PhoneExtractor::national($r['phone'])) ? PhoneExtractor::national($r['phone']) : null;
        $out = [];
        $usedAi = [];

        foreach ($parsed as $i => $p) {
            $match = null;

            foreach ($aiOrders as $j => $a) {
                if (! isset($usedAi[$j]) && $phoneOf($p) !== null && $phoneOf($p) === $phoneOf($a)) {
                    $match = $j;
                    break;
                }
            }
            if ($match === null && count($parsed) === 1 && count($aiOrders) === 1 && ($phoneOf($p) === null || $phoneOf($aiOrders[0]) === null)) {
                $match = 0;
            }

            if ($match === null) {
                $out[] = $p;
                continue;
            }

            $usedAi[$match] = true;
            $out[] = $this->mergeOne($p, $resolved[$i], $aiOrders[$match]);
        }

        foreach ($aiOrders as $j => $a) {
            if (! isset($usedAi[$j])) {
                $out[] = $a;
            }
        }

        return $out;
    }

    protected function mergeOne(array $p, array $resolved, array $a): array
    {
        $sources = [];
        $pick = function (string $field, bool $parserSure) use ($p, $a, &$sources) {
            if ($parserSure || $a[$field] === null) {
                $sources[$field] = 'parser';

                return $p[$field];
            }
            $sources[$field] = 'ai';

            return $a[$field];
        };

        $itemsSure = $p['items'] !== [] && ! array_filter($resolved['items'], fn ($i) => $i['confidence'] < DraftResolver::SURE);
        $phoneSure = $resolved['phone']['confidence'] >= 1.0;

        $merged = [
            'phone'           => $pick('phone', $phoneSure),
            'alt_phone'       => $p['alt_phone'] ?? $a['alt_phone'],
            'name'            => $pick('name', $p['name'] !== null && $p['name_conf'] >= 0.9),
            'address'         => $pick('address', $p['address'] !== null && $p['address_conf'] >= 0.8),
            'area_hint'       => $p['area_hint'] ?? $a['area_hint'],
            'zone_hint'       => $a['zone_hint'] ?? null,
            'items'           => $itemsSure || $a['items'] === [] ? $p['items'] : $a['items'],
            'items_labelled'  => true,
            'discount'        => $p['discount'] ?? $a['discount'],
            'delivery_charge' => $p['delivery_charge'] ?? $a['delivery_charge'],
            'advance'         => $p['advance'] ?? $a['advance'],
            'stated_total'    => $p['stated_total'] ?? $a['stated_total'],
            'payment'         => $p['payment'] ?? $a['payment'],
            'note'            => $p['note'] ?? $a['note'],
            'source_text'     => $p['source_text'] ?: $a['source_text'],
            'via'             => 'ai',
        ];
        $merged['name_conf'] = $sources['name'] === 'ai' ? $a['name_conf'] : $p['name_conf'];
        $merged['address_conf'] = $sources['address'] === 'ai' ? $a['address_conf'] : $p['address_conf'];
        $sources['items'] = $itemsSure || $a['items'] === [] ? 'parser' : 'ai';
        $sources['area'] = $a['zone_hint'] ? 'ai' : 'parser';
        $merged['sources'] = $sources;

        return $merged;
    }

    protected function sourceType(string $text, array $media): string
    {
        $kinds = array_unique(array_map(fn ($f) => $f['mime'] === 'application/pdf' ? 'pdf' : 'image', $media));

        return match (true) {
            $media === []                   => 'text',
            $text === '' && count($kinds) === 1 => $kinds[0],
            default                         => 'mixed',
        };
    }

    /**
     * AI Order usage for Settings → AI Order.
     *
     * @return array<string, mixed>
     */
    public static function usageStats(): array
    {
        $since = now()->subDays(30);
        $base = fn () => OrderIntakeLog::query()->where('created_at', '>=', $since);

        $aiCalls = $base()->whereNotNull('model');

        return [
            'extractions'   => $base()->count(),
            'parser_only'   => $base()->where('resolution', OrderIntakeLog::RESOLUTION_PARSER)->count(),
            'ai_calls'      => (clone $aiCalls)->count(),
            'ai_failed'     => $base()->where('resolution', OrderIntakeLog::RESOLUTION_AI_FAILED)->count(),
            'ai_today'      => OrderIntakeLog::query()->where('created_at', '>=', now()->startOfDay())->whereNotNull('model')->count(),
            'tokens'        => (int) (clone $aiCalls)->sum('prompt_tokens') + (int) (clone $aiCalls)->sum('completion_tokens'),
            'cost'          => (float) (clone $aiCalls)->sum('cost'),
            'avg_latency'   => (int) (clone $aiCalls)->avg('latency_ms'),
            'orders_placed' => $base()->whereNotNull('order_ids')->get(['order_ids'])->sum(fn ($l) => count($l->order_ids ?? [])),
            'recent'        => OrderIntakeLog::query()->with('user:id,name')->latest('id')->limit(10)
                ->get(['id', 'user_id', 'source_type', 'resolution', 'orders_found', 'orders_ready', 'model', 'cost', 'latency_ms', 'error', 'order_ids', 'created_at']),
        ];
    }
}
