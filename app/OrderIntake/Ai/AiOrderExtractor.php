<?php

namespace App\OrderIntake\Ai;

use App\OrderIntake\IntakeSettings;
use App\OrderIntake\Text;

/**
 * The AI fallback: sends the message text, screenshots/PDFs, what the parser
 * already found, a short list of candidate products and the delivery zones,
 * and gets back orders in this project's raw-draft shape through a strict
 * JSON schema. The AI only reads — it never sets ids, prices or totals:
 * product text/codes, amounts and the zone come back as "what the message
 * says" and are resolved by DraftResolver against the database.
 *
 * @phpstan-import-type RawDraft from \App\OrderIntake\TextOrderParser
 */
class AiOrderExtractor
{
    public function __construct(protected OpenRouterClient $client, protected IntakeSettings $settings) {}

    /**
     * @param  list<array{mime: string, name: string, data: string}>  $files  base64 data, already size/type checked
     * @param  list<array<string, mixed>>  $parserDrafts  resolved drafts from the parser (hints)
     * @param  list<array{code: string, name: string, variants: list<string>}>  $candidates
     * @param  list<string>  $zones
     * @return array{orders: list<RawDraft>, raw: array<string, mixed>, model: string, usage: array<string, mixed>, latency_ms: int}
     *
     * @param  ?float  $deadline  unix time all attempts must finish by (null = one attempt, configured timeout)
     *
     * @throws AiExtractionException
     */
    public function extract(string $text, array $files, array $parserDrafts, array $candidates, array $zones, ?float $deadline = null): array
    {
        $messages = $this->messages($text, $files, $parserDrafts, $candidates, $zones);
        $usage = ['prompt_tokens' => 0, 'completion_tokens' => 0, 'cost' => 0.0];
        $latency = 0;
        $last = null;
        $tries = 0;

        $attempts = $this->attempts();

        foreach ($attempts as $i => $model) {
            $remaining = $deadline !== null ? (int) floor($deadline - microtime(true)) : $this->settings->timeout();

            // Not enough time left for another useful try — keep the last error.
            if ($i > 0 && $remaining < 10) {
                break;
            }

            $tries++;

            try {
                // Leave room for the next try while one is still planned.
                $cap = $deadline !== null && $i < count($attempts) - 1 ? max(15, (int) floor($remaining * 0.6)) : $remaining;
                $reply = $this->client->chat($messages, self::schema(), 'order_drafts', $model, min($this->settings->timeout(), $cap));
                $usage = self::addUsage($usage, $reply['usage']);
                $latency += $reply['latency_ms'];

                $data = self::decode($reply['content']);
                if ($data === null) {
                    throw new AiExtractionException('AI reply was not valid JSON', $reply['model'], [], $reply['latency_ms']);
                }

                $orders = self::validate($data);
                if ($orders === null) {
                    throw new AiExtractionException('AI reply did not match the order format', $reply['model'], [], $reply['latency_ms']);
                }

                // A model that can't actually see images answers "no orders" — try another.
                if ($files !== [] && ! array_filter($orders, fn ($o) => ! empty($o['phone']) || ! empty($o['items']) || ! empty($o['customer_name']) || ! empty($o['address']))) {
                    throw new AiExtractionException('AI found no order in the screenshot', $reply['model'], [], $reply['latency_ms']);
                }

                return [
                    'orders'     => array_map(fn ($o) => self::toRawDraft($o, $text), $orders),
                    'raw'        => ['orders' => $orders],
                    'model'      => $reply['model'],
                    'usage'      => $usage,
                    'latency_ms' => $latency,
                ];
            } catch (AiExtractionException $e) {
                $usage = self::addUsage($usage, $e->usage);
                $latency += (int) $e->latencyMs;
                $last = $e;

                // A bad key or no credit won't get better on another model.
                if (preg_match('/rejected the API key|out of credit/i', $e->getMessage())) {
                    break;
                }
            }
        }

        throw new AiExtractionException(
            ($last?->getMessage() ?? 'AI request failed') . ($tries > 1 ? " (tried {$tries} times)" : ''),
            $last?->model,
            $usage,
            $latency,
        );
    }

    /**
     * Models to try, in order: the primary, the fallback, and — when the
     * primary is a router that picks a random model ("openrouter/free") —
     * the router once more, which usually lands on a different model.
     *
     * @return list<string>
     */
    protected function attempts(): array
    {
        $primary = $this->settings->model();
        $fallback = $this->settings->fallbackModel();
        $list = array_values(array_unique(array_filter([$primary, $fallback])));

        if (str_starts_with($primary, 'openrouter/') && count($list) < 3) {
            $list[] = $primary;
        }

        return array_slice($list, 0, 3);
    }

    private static function addUsage(array $total, array $usage): array
    {
        return [
            'prompt_tokens'     => $total['prompt_tokens'] + (int) ($usage['prompt_tokens'] ?? 0),
            'completion_tokens' => $total['completion_tokens'] + (int) ($usage['completion_tokens'] ?? 0),
            'cost'              => $total['cost'] + (float) ($usage['cost'] ?? 0),
        ];
    }

    /** @return list<array<string, mixed>> */
    protected function messages(string $text, array $files, array $parserDrafts, array $candidates, array $zones): array
    {
        $system = <<<'PROMPT'
You read customer order messages for a Bangladeshi online shop (text in Bangla, English or Banglish; chat screenshots from Messenger/WhatsApp; order slips) and extract the orders in them.

Rules:
- Report ONLY what the input actually says. Never invent or guess a name, phone, address, product, quantity, price, discount or charge. Use null when it isn't there.
- One entry in "orders" per customer/delivery. Several products for one customer = one order with several items.
- In a chat, the shop's own messages (prices, delivery charge, totals the seller quotes) count as order information; greetings and questions don't.
- phone: the customer's mobile number exactly as written (any format). alt_phone: a second number for the same customer.
- address: the full delivery address as written (house/road/area/thana/district). area: the most specific district/thana/area name in it.
- delivery_zone: copy one of the given zone names only if the message makes it clear (e.g. "inside Dhaka"); otherwise null.
- items[].product: the product as the customer wrote it. items[].candidate_code: the code of the product from the candidate list ONLY when it clearly is that product; otherwise null. items[].variant: size/colour/option text. quantity: a number, or null if not stated. unit_price: only a price stated in the message.
- discount: an amount or percentage stated as a discount; type "none" when there isn't one.
- stated_total: a total/payable amount stated in the message ("total", "COD", "condition"); kind "cod" for an amount to collect on delivery.
- advance: an amount stated as already paid (advance/bKash/Nagad).
- unresolved: list field names you could not find or are unsure about. confidence: 0..1 for the whole order.
- The typed text and the screenshots/files are parts of the SAME conversation: combine them. E.g. the product typed in the text and the name/phone/address in a screenshot are one order, not two.
PROMPT;

        if ($extra = trim($this->settings->extraPrompt())) {
            $system .= "\n\nShop notes:\n" . $extra;
        }

        $context = [
            'candidate_products' => $candidates,
            'delivery_zones'     => $zones,
            'already_found'      => array_map(fn ($d) => array_filter([
                'phone'    => $d['phone']['display'] ?? null,
                'name'     => $d['name']['value'] ?? null,
                'address'  => $d['address']['value'] ?? null,
                'products' => array_map(fn ($i) => trim(($i['product_name'] ?? $i['text']) . ' x' . $i['qty']), $d['items'] ?? []),
                'unsure'   => $d['needs_ai'] ?? [],
            ]), $parserDrafts),
        ];

        $parts = [['type' => 'text', 'text' => "Shop data (JSON):\n" . json_encode($context, JSON_UNESCAPED_UNICODE)]];

        if (trim($text) !== '') {
            $parts[] = ['type' => 'text', 'text' => "Customer message:\n<<<\n" . mb_substr($text, 0, 12000) . "\n>>>"];
        }

        foreach ($files as $f) {
            $dataUrl = "data:{$f['mime']};base64,{$f['data']}";
            $parts[] = $f['mime'] === 'application/pdf'
                ? ['type' => 'file', 'file' => ['filename' => $f['name'], 'file_data' => $dataUrl]]
                : ['type' => 'image_url', 'image_url' => ['url' => $dataUrl]];
        }

        $parts[] = ['type' => 'text', 'text' => 'Extract the orders as JSON matching the schema.'];

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => $parts],
        ];
    }

    /** JSON Schema (strict mode: every property required, nullable where optional). */
    public static function schema(): array
    {
        $str = ['type' => ['string', 'null']];
        $num = ['type' => ['number', 'null']];

        return [
            'type'                 => 'object',
            'additionalProperties' => false,
            'required'             => ['orders'],
            'properties'           => [
                'orders' => [
                    'type'  => 'array',
                    'items' => [
                        'type'                 => 'object',
                        'additionalProperties' => false,
                        'required'             => ['customer_name', 'phone', 'alt_phone', 'address', 'area', 'delivery_zone', 'items', 'discount', 'delivery_charge', 'advance', 'stated_total', 'payment_method', 'note', 'confidence', 'unresolved'],
                        'properties'           => [
                            'customer_name'   => $str,
                            'phone'           => $str,
                            'alt_phone'       => $str,
                            'address'         => $str,
                            'area'            => $str,
                            'delivery_zone'   => $str,
                            'items'           => [
                                'type'  => 'array',
                                'items' => [
                                    'type'                 => 'object',
                                    'additionalProperties' => false,
                                    'required'             => ['product', 'candidate_code', 'variant', 'quantity', 'unit_price'],
                                    'properties'           => [
                                        'product'        => ['type' => 'string'],
                                        'candidate_code' => $str,
                                        'variant'        => $str,
                                        'quantity'       => $num,
                                        'unit_price'     => $num,
                                    ],
                                ],
                            ],
                            'discount'        => [
                                'type'                 => 'object',
                                'additionalProperties' => false,
                                'required'             => ['type', 'value'],
                                'properties'           => ['type' => ['type' => 'string', 'enum' => ['none', 'amount', 'percent']], 'value' => $num],
                            ],
                            'delivery_charge' => $num,
                            'advance'         => $num,
                            'stated_total'    => [
                                'type'                 => 'object',
                                'additionalProperties' => false,
                                'required'             => ['kind', 'value'],
                                'properties'           => ['kind' => ['type' => 'string', 'enum' => ['none', 'total', 'cod']], 'value' => $num],
                            ],
                            'payment_method'  => ['type' => ['string', 'null'], 'enum' => ['cod', 'bkash', 'nagad', 'rocket', 'bank', 'cash', null]],
                            'note'            => $str,
                            'confidence'      => ['type' => 'number'],
                            'unresolved'      => ['type' => 'array', 'items' => ['type' => 'string']],
                        ],
                    ],
                ],
            ],
        ];
    }

    /** JSON out of the reply — tolerates code fences / text around it from models that ignore the schema. */
    public static function decode(string $content): ?array
    {
        $content = trim($content);
        $data = json_decode($content, true);

        if (! is_array($data) && preg_match('/\{.*\}/s', $content, $m)) {
            $data = json_decode($m[0], true);
        }

        return is_array($data) ? $data : null;
    }

    /**
     * Checks the decoded reply against the schema's shape (types, bounds)
     * — a reply that doesn't fit is rejected as a whole.
     *
     * @return list<array<string, mixed>>|null
     */
    public static function validate(array $data): ?array
    {
        if (! isset($data['orders']) || ! is_array($data['orders']) || ! array_is_list($data['orders']) || count($data['orders']) > 100) {
            return null;
        }

        $nullableString = fn ($v) => $v === null || is_string($v);
        $nullableNumber = fn ($v) => $v === null || is_int($v) || is_float($v) || (is_string($v) && is_numeric($v));
        $orders = [];

        foreach ($data['orders'] as $o) {
            if (! is_array($o) || ! isset($o['items']) || ! is_array($o['items'])) {
                return null;
            }

            foreach (['customer_name', 'phone', 'alt_phone', 'address', 'area', 'delivery_zone', 'note'] as $k) {
                if (! $nullableString($o[$k] ?? null)) {
                    return null;
                }
            }
            foreach (['delivery_charge', 'advance'] as $k) {
                if (! $nullableNumber($o[$k] ?? null)) {
                    return null;
                }
            }

            $items = [];
            foreach ($o['items'] as $i) {
                if (! is_array($i) || ! is_string($i['product'] ?? null) || trim($i['product']) === ''
                    || ! $nullableString($i['candidate_code'] ?? null) || ! $nullableString($i['variant'] ?? null)
                    || ! $nullableNumber($i['quantity'] ?? null) || ! $nullableNumber($i['unit_price'] ?? null)) {
                    return null;
                }
                $items[] = [
                    'product'        => mb_substr(trim($i['product']), 0, 200),
                    'candidate_code' => isset($i['candidate_code']) ? mb_substr(trim($i['candidate_code']), 0, 100) : null,
                    'variant'        => isset($i['variant']) ? mb_substr(trim($i['variant']), 0, 100) : null,
                    'quantity'       => isset($i['quantity']) && (float) $i['quantity'] > 0 && (float) $i['quantity'] <= 1000 ? (float) $i['quantity'] : null,
                    'unit_price'     => isset($i['unit_price']) && (float) $i['unit_price'] > 0 ? (float) $i['unit_price'] : null,
                ];
            }

            $discount = is_array($o['discount'] ?? null) ? $o['discount'] : ['type' => 'none', 'value' => null];
            $total = is_array($o['stated_total'] ?? null) ? $o['stated_total'] : ['kind' => 'none', 'value' => null];

            if (! in_array($discount['type'] ?? null, ['none', 'amount', 'percent'], true) || ! $nullableNumber($discount['value'] ?? null)
                || ! in_array($total['kind'] ?? null, ['none', 'total', 'cod'], true) || ! $nullableNumber($total['value'] ?? null)) {
                return null;
            }

            $orders[] = [
                ...array_map(fn ($v) => is_string($v) ? mb_substr(trim($v), 0, 1000) : $v, array_intersect_key($o, array_flip(['customer_name', 'phone', 'alt_phone', 'address', 'area', 'delivery_zone', 'note', 'payment_method']))),
                'items'           => $items,
                'discount'        => ['type' => $discount['type'], 'value' => isset($discount['value']) ? (float) $discount['value'] : null],
                'delivery_charge' => isset($o['delivery_charge']) ? max(0.0, (float) $o['delivery_charge']) : null,
                'advance'         => isset($o['advance']) ? max(0.0, (float) $o['advance']) : null,
                'stated_total'    => ['kind' => $total['kind'], 'value' => isset($total['value']) ? (float) $total['value'] : null],
                'confidence'      => max(0.0, min(1.0, (float) ($o['confidence'] ?? 0))),
                'unresolved'      => array_values(array_filter((array) ($o['unresolved'] ?? []), 'is_string')),
            ];
        }

        return $orders;
    }

    /**
     * A validated AI order → the parser's raw-draft shape, every field
     * marked as coming from the AI.
     *
     * @return RawDraft
     */
    public static function toRawDraft(array $o, string $text): array
    {
        $conf = max(0.6, min(0.8, (float) $o['confidence']));
        $unsure = array_map(fn ($u) => Text::norm($u), $o['unresolved']);
        $fieldConf = fn (string ...$names) => array_intersect($names, $unsure) ? 0.5 : $conf;
        $discount = $o['discount']['type'] !== 'none' && ($o['discount']['value'] ?? 0) > 0
            ? ['type' => $o['discount']['type'], 'value' => (float) $o['discount']['value'], 'text' => 'AI']
            : null;
        $total = $o['stated_total']['kind'] !== 'none' && ($o['stated_total']['value'] ?? 0) > 0
            ? ['value' => (float) $o['stated_total']['value'], 'kind' => $o['stated_total']['kind'], 'text' => 'AI']
            : null;

        return [
            'name'            => $o['customer_name'] ?: null,
            'name_conf'       => $fieldConf('customer_name', 'name'),
            'phone'           => $o['phone'] ?: null,
            'alt_phone'       => $o['alt_phone'] ?: null,
            'address'         => $o['address'] ?: null,
            'address_conf'    => $fieldConf('address'),
            'area_hint'       => $o['area'] ?: null,
            'zone_hint'       => $o['delivery_zone'] ?: null,
            'items'           => array_map(fn ($i) => [
                'text' => $i['product'], 'code' => $i['candidate_code'], 'qty' => $i['quantity'], 'price' => $i['unit_price'],
                'variant_text' => $i['variant'], 'product_id' => null, 'variant_id' => null, 'match' => null,
            ], $o['items']),
            'items_labelled'  => true,
            'discount'        => $discount,
            'delivery_charge' => $o['delivery_charge'],
            'advance'         => $o['advance'],
            'stated_total'    => $total,
            'payment'         => $o['payment_method'] ?? null,
            'note'            => $o['note'] ?: null,
            'source_text'     => $text,
            'via'             => 'ai',
            'sources'         => array_fill_keys(['name', 'phone', 'address', 'items', 'area'], 'ai'),
        ];
    }
}
