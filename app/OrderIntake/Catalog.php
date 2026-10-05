<?php

namespace App\OrderIntake;

use App\Enums\Product\ProductType;
use App\Models\Product;

/**
 * The orderable catalogue as plain arrays, with the matching the AI Order
 * parser runs before any AI: variant SKU / product code (exact, also with
 * the punctuation dropped), product name, meta-keyword aliases, then a
 * typo-tolerant word match. Mirrors the bulk sheet's resolveRef()/
 * pickVariant()/parseToken() (bulk-order-create.blade.php) so a row the
 * parser resolves resolves the same way once it lands on the sheet.
 *
 * Never guesses: when two products score alike the result is ambiguous.
 *
 * @phpstan-type CatalogVariant array{id: int, sku: string, label: string, values: list<string>, price: float}
 * @phpstan-type CatalogProduct array{id: int, name: string, code: string, variable: bool, price: float, keywords: list<string>, variants: list<CatalogVariant>}
 */
final class Catalog
{
    /** Spoken quantities in Bangla / Banglish / English. */
    private const NUMBER_WORDS = [
        'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5,
        'ek' => 1, 'ekta' => 1, 'ekti' => 1, 'dui' => 2, 'duita' => 2, 'duiti' => 2, 'tin' => 3, 'tinta' => 3, 'tinti' => 3,
        'char' => 4, 'charta' => 4, 'pach' => 5, 'pachta' => 5, 'panch' => 5,
        'এক' => 1, 'একটা' => 1, 'একটি' => 1, 'দুই' => 2, 'দুইটা' => 2, 'দুইটি' => 2, 'দুটো' => 2, 'তিন' => 3, 'তিনটা' => 3, 'তিনটি' => 3,
        'চার' => 4, 'চারটা' => 4, 'চারটি' => 4, 'পাঁচ' => 5, 'পাঁচটা' => 5, 'পাঁচটি' => 5,
    ];

    /** Unit words after a number that mean "this many pieces". */
    private const QTY_UNITS = 'pcs?|pieces?|ta|ti|to|টা|টি|টো|পিস|nos?|units?|sets?|সেট|joda|জোড়া';

    /** @var array<int, CatalogProduct> */
    private array $byId = [];

    /** @var array<string, array{0: int, 1: ?int}> compacted code/SKU → [product id, variant id] */
    private array $byRef = [];

    /** @var array<string, int> name word → how many products use it */
    private array $wordFrequency = [];

    /** @param list<CatalogProduct> $products */
    public function __construct(private array $products)
    {
        foreach ($products as $p) {
            $this->byId[$p['id']] = $p;

            if (($k = Text::compact($p['code'])) !== '') {
                $this->byRef[$k] ??= [$p['id'], null];
            }

            foreach ($p['variants'] as $v) {
                if (($k = Text::compact($v['sku'])) !== '') {
                    $this->byRef[$k] ??= [$p['id'], $v['id']];
                }
            }

            foreach (array_unique(Text::words($p['name'])) as $w) {
                $this->wordFrequency[$w] = ($this->wordFrequency[$w] ?? 0) + 1;
            }
        }
    }

    /**
     * The words of a product's name that tell it apart — not numbers, not
     * short words, not words most of the catalogue shares ("pcs", "dress").
     *
     * @return list<string>
     */
    private function distinctiveWords(array $product): array
    {
        $limit = max(2, (int) ceil(count($this->products) * 0.15));

        return array_values(array_filter(Text::words($product['name']), fn ($w) => mb_strlen($w) >= 3
            && ! preg_match('/^\d+$/', $w)
            && ($this->wordFrequency[$w] ?? 0) <= $limit));
    }

    /**
     * A product named by its distinctive words: the first two of them
     * ("sunset charm" → "Sunset Charm 3 Pcs Unstitched Dress"), or one long
     * word no other product has ("safina ta nibo"). When several products
     * qualify, the one with clearly more of its words in the text wins;
     * otherwise nothing does (partialMatches() has the tied ones).
     *
     * @return array{product: CatalogProduct, text: string}|null
     */
    private function partialIn(string $norm): ?array
    {
        $hits = array_values(array_filter($this->partialMatches($norm), fn ($h) => ! $h['weak']));

        return match (true) {
            count($hits) === 1 => $hits[0],
            count($hits) > 1 && $hits[0]['score'] > $hits[1]['score'] => $hits[0],
            default => null,
        };
    }

    /**
     * Every product partialIn() would accept, best first.
     *
     * @return list<array{product: CatalogProduct, text: string, score: int}>
     */
    public function partialMatches(string $norm): array
    {
        $words = Text::words($norm);
        $hits = [];

        foreach ($this->products as $p) {
            $distinct = $this->distinctiveWords($p);
            $present = array_values(array_intersect($distinct, $words));

            if ($present === []) {
                continue;
            }

            $firstTwo = count($distinct) >= 2 && array_diff(array_slice($distinct, 0, 2), $words) === [];
            $unique = (bool) array_filter($present, fn ($w) => mb_strlen($w) >= 4 && ($this->wordFrequency[$w] ?? 0) === 1);

            // A shared distinctive word ("safina" in two products) makes it a
            // candidate only — never a match on its own (see resolve()).
            $weak = ! $firstTwo && ! $unique && array_filter($present, fn ($w) => mb_strlen($w) >= 4);

            if ($firstTwo || $unique || $weak) {
                $hits[] = ['product' => $p, 'text' => implode(' ', array_unique($present)), 'score' => count($present), 'weak' => (bool) $weak];
            }
        }

        // Strong before weak, then by how many of its words are there.
        usort($hits, fn ($a, $b) => [$a['weak'], $b['score']] <=> [$b['weak'], $a['score']]);

        return $hits;
    }

    /**
     * The product whose code ends in this number — "156", "0156" or
     * "#156" for SF-0156 — when exactly one does.
     *
     * @return array{product: CatalogProduct, variant: null}|null
     */
    private function byCodeNumber(string $digits): ?array
    {
        $n = ltrim(Text::asciiDigits($digits), '0');

        if ($n === '' || strlen($n) < 2) {
            return null;
        }

        $hits = array_values(array_filter($this->products, function ($p) use ($n) {
            return preg_match('/(\d+)$/', Text::compact($p['code']), $m) && ltrim($m[1], '0') === $n;
        }));

        return count($hits) === 1 ? ['product' => $hits[0], 'variant' => null] : null;
    }
    /**
     * Active, non-combo products with their active variants — the same set
     * the bulk sheet loads (BulkOrderCreate::catalog()), minus stock.
     */
    public static function fromDatabase(): self
    {
        $products = Product::active()
            ->where('product_type', '!=', ProductType::COMBO)
            ->with([
                'variants' => fn ($q) => $q->where('status', 'active')->orderBy('sort_order'),
                'variants.values.productAttributeValue.attributeValue.attribute',
            ])
            ->get();

        return new self($products->map(fn (Product $p) => [
            'id'       => $p->id,
            'name'     => (string) $p->name,
            'code'     => (string) $p->code,
            'variable' => $p->product_type === ProductType::VARIABLE,
            'price'    => $p->sellingPrice(),
            'keywords' => array_values(array_filter(array_map('trim', explode(',', (string) $p->meta_keywords)), fn ($k) => mb_strlen($k) >= 3)),
            'variants' => $p->product_type === ProductType::VARIABLE
                ? $p->variants->map(fn ($v) => [
                    'id'     => $v->id,
                    'sku'    => (string) $v->sku,
                    'label'  => implode(' / ', $v->options_map) ?: (string) $v->sku,
                    'values' => array_values(array_map(fn ($o) => Text::norm((string) $o), $v->options_map)),
                    'price'  => $p->sellingPrice($v),
                ])->values()->all()
                : [],
        ])->values()->all());
    }

    /** @return CatalogProduct|null */
    public function product(int $id): ?array
    {
        return $this->byId[$id] ?? null;
    }

    /** @return list<CatalogProduct> */
    public function all(): array
    {
        return $this->products;
    }

    /** @return CatalogVariant|null */
    public function variant(int $productId, ?int $variantId): ?array
    {
        foreach ($this->byId[$productId]['variants'] ?? [] as $v) {
            if ($v['id'] === $variantId) {
                return $v;
            }
        }

        return null;
    }

    public function priceOf(int $productId, ?int $variantId): float
    {
        return $this->variant($productId, $variantId)['price'] ?? (float) ($this->byId[$productId]['price'] ?? 0);
    }

    /**
     * Splits a product cell into item texts ("SF-1 x2, SF-2" → two) — same
     * separators as the sheet's splitTokens().
     *
     * @return list<string>
     */
    public static function splitItems(string $text): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[\n,;|]+|\s\+\s/u', Text::asciiDigits($text)) ?: []), fn ($t) => $t !== ''));
    }

    /**
     * Pulls a trailing/leading quantity and an "@price" off an item text.
     *
     * @return array{ref: string, qty: ?float, price: ?float}
     */
    public function parseItem(string $raw): array
    {
        $s = trim(preg_replace('/\s+/u', ' ', Text::asciiDigits($raw)) ?? '');
        $qty = null;
        $price = null;

        if (preg_match('/\s*@\s*(\d+(?:\.\d+)?)\s*(?:tk|taka|৳|টাকা)?\s*$/iu', $s, $m, PREG_OFFSET_CAPTURE)) {
            $price = (float) $m[1][0];
            $s = trim(substr($s, 0, $m[0][1]));
        }

        // A whole code/SKU is never cut into a quantity (e.g. "BOX2").
        if (isset($this->byRef[Text::compact($s)])) {
            return ['ref' => $s, 'qty' => $qty, 'price' => $price];
        }

        $units = self::QTY_UNITS;

        if (preg_match('/(?:\s+[x×*]\s*|\s*[×*]\s*)(\d+(?:\.\d+)?)\s*$/iu', $s, $m, PREG_OFFSET_CAPTURE)
            || preg_match("/\\s+(\\d+(?:\\.\\d+)?)\\s*(?:{$units})\\s*$/iu", $s, $m, PREG_OFFSET_CAPTURE)
            || preg_match('/\s*\(\s*(\d+(?:\.\d+)?)\s*\)\s*$/u', $s, $m, PREG_OFFSET_CAPTURE)
            || preg_match('/\s*(?:qty|quantity|পরিমাণ)\s*[:=\-]?\s*(\d+(?:\.\d+)?)\s*$/iu', $s, $m, PREG_OFFSET_CAPTURE)) {
            $qty = (float) $m[1][0];
            $s = trim(substr($s, 0, $m[0][1]));
        } elseif (preg_match("/^(\\d+(?:\\.\\d+)?)\\s*(?:x|\\*|×|{$units})?\\s+/iu", $s, $m)) {
            $qty = (float) $m[1];
            $s = trim(substr($s, strlen($m[0])));
        } elseif (($q = $this->leadingNumberWord($s)) !== null) {
            [$qty, $s] = $q;
        }

        return ['ref' => $s, 'qty' => $qty, 'price' => $price];
    }

    /** "duita Zareen" / "দুইটা Zareen" → [2, "Zareen"]. */
    private function leadingNumberWord(string $s): ?array
    {
        $first = mb_strtolower(strtok($s, ' ') ?: '');

        return isset(self::NUMBER_WORDS[$first]) && str_contains($s, ' ')
            ? [(float) self::NUMBER_WORDS[$first], trim(mb_substr($s, mb_strlen($first)))]
            : null;
    }

    /**
     * Best catalogue match for a product reference.
     *
     * match: exact (code/SKU or the full name), name (the name is inside the
     * text, or most of its words are), keyword (a meta-keyword alias),
     * code (the number of its code, "code 156"), partial (its distinctive
     * words, e.g. "sunset charm"), fuzzy (words
     * match only with typos — treated as unconfirmed).
     *
     * @return array{product: CatalogProduct, variant: ?CatalogVariant, hint: string, match: string}|array{ambiguous: true, options: list<string>}|null
     */
    public function resolve(string $ref): ?array
    {
        $key = Text::norm($ref);

        if ($key === '') {
            return null;
        }

        if ($hit = $this->refHit(Text::compact($key))) {
            return [...$hit, 'hint' => '', 'match' => 'exact'];
        }

        // "156", "code 156", "#156", "কোড ১৫৬" — the number at the end of one product's code.
        if (preg_match('/^(?:code|কোড|design|model|item|product|dress|#|no\.?)?\s*(?:no\.?|নং)?\s*[#:\-]?\s*(\d{2,6})(?:\s+(.*))?$/u', $key, $m)
            && ($hit = $this->byCodeNumber($m[1]))) {
            return [...$hit, 'hint' => trim($m[2] ?? ''), 'match' => 'code'];
        }

        // A code/SKU written as one of the words: "SF-0156 Red XL", "Zareen SF-0156".
        $parts = explode(' ', $key);
        for ($n = count($parts) - 1; $n >= 1; $n--) {
            for ($start = 0; $start + $n <= count($parts); $start++) {
                $piece = implode(' ', array_slice($parts, $start, $n));
                if (mb_strlen(Text::compact($piece)) >= 3 && ($hit = $this->refHit(Text::compact($piece)))) {
                    $rest = implode(' ', [...array_slice($parts, 0, $start), ...array_slice($parts, $start + $n)]);

                    return [...$hit, 'hint' => $rest, 'match' => 'exact'];
                }
            }
        }

        $refWords = Text::words($key);
        $scored = [];

        foreach ($this->products as $p) {
            [$score, $hint, $match] = $this->scoreName($p, $key, $refWords);
            if ($score > 0) {
                $scored[] = ['p' => $p, 'score' => $score, 'hint' => $hint, 'match' => $match];
            }
        }

        if ($scored === []) {
            $partials = $this->partialMatches($key);
            $hintFor = fn ($hit) => trim(implode(' ', array_diff(Text::words($key), explode(' ', $hit['text']))));

            if ($partials === []) {
                return null;
            }
            $strong = array_values(array_filter($partials, fn ($h) => ! $h['weak']));
            if ($strong !== [] && (count($strong) === 1 || $strong[0]['score'] > $strong[1]['score'])) {
                return ['product' => $strong[0]['product'], 'variant' => null, 'hint' => $hintFor($strong[0]), 'match' => 'partial'];
            }

            // Tied strong matches, or only weak ones: let price/context decide.
            $pool = $strong !== [] ? $strong : $partials;
            $tied = array_values(array_filter($pool, fn ($h) => $h['score'] === $pool[0]['score']));

            return [
                'ambiguous'  => true,
                'options'    => array_map(fn ($h) => $h['product']['name'], array_slice($tied, 0, 4)),
                'candidates' => array_map(fn ($h) => ['product' => $h['product'], 'hint' => $hintFor($h), 'match' => 'partial'], array_slice($tied, 0, 6)),
            ];
        }

        usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);
        $best = $scored[0];
        $second = $scored[1]['score'] ?? 0;

        if ($best['score'] < 100 && $best['score'] - $second < 1) {
            $tied = array_values(array_filter($scored, fn ($x) => $best['score'] - $x['score'] < 1));

            return [
                'ambiguous'  => true,
                'options'    => array_map(fn ($x) => $x['p']['name'], array_slice($tied, 0, 4)),
                'candidates' => array_map(fn ($x) => ['product' => $x['p'], 'hint' => trim($x['hint']), 'match' => $x['match']], array_slice($tied, 0, 6)),
            ];
        }

        return ['product' => $best['p'], 'variant' => null, 'hint' => trim($best['hint']), 'match' => $best['match']];
    }

    /** @return array{product: CatalogProduct, variant: ?CatalogVariant}|null */
    private function refHit(string $compact): ?array
    {
        if ($compact === '' || ! isset($this->byRef[$compact])) {
            return null;
        }

        [$pid, $vid] = $this->byRef[$compact];

        return ['product' => $this->byId[$pid], 'variant' => $vid ? $this->variant($pid, $vid) : null];
    }

    /**
     * @param  list<string>  $refWords
     * @return array{0: float, 1: string, 2: string}
     */
    private function scoreName(array $p, string $key, array $refWords): array
    {
        $name = Text::norm($p['name']);

        if ($name === $key) {
            return [100, '', 'exact'];
        }
        if (mb_strlen($name) >= 4 && str_contains($key, $name)) {
            return [60 + mb_strlen($name) / 10, str_replace($name, ' ', $key), 'name'];
        }
        if (mb_strlen($key) >= 4 && str_contains($name, $key)) {
            return [50 + mb_strlen($key) / 10, '', 'name'];
        }

        foreach ($p['keywords'] as $alias) {
            $alias = Text::norm($alias);
            if ($alias !== '' && ($alias === $key || preg_match('/(?<![\p{L}\p{M}\p{N}])' . preg_quote($alias, '/') . '(?![\p{L}\p{M}\p{N}])/u', $key))) {
                return [45 + mb_strlen($alias) / 10, str_replace($alias, ' ', $key), 'keyword'];
            }
        }

        $nameWords = Text::words($name);
        if ($nameWords === []) {
            return [0, '', ''];
        }

        $exact = 0;
        $fuzzy = 0;
        $used = [];

        foreach ($nameWords as $w) {
            if (in_array($w, $refWords, true)) {
                $exact++;
                $used[] = $w;
                continue;
            }
            foreach ($refWords as $r) {
                if (self::similar($w, $r)) {
                    $fuzzy++;
                    $used[] = $r;
                    break;
                }
            }
        }

        $common = $exact + $fuzzy;
        $ratio = $common / count($nameWords);

        if ($common >= 2 && $ratio >= 0.6) {
            $hint = implode(' ', array_filter($refWords, fn ($w) => ! in_array($w, $used, true) && ! in_array($w, $nameWords, true)));

            return [30 + $ratio * 20 - $fuzzy, $hint, $fuzzy ? 'fuzzy' : 'name'];
        }

        return [0, '', ''];
    }

    /** Typo-tolerant word equality for words long enough to carry meaning. */
    private static function similar(string $a, string $b): bool
    {
        $len = min(mb_strlen($a), mb_strlen($b));

        if ($len < 4 || preg_match('/\d/', $a . $b)) {
            return false;
        }

        $distance = levenshtein($a, $b);

        return $distance <= ($len >= 7 ? 2 : 1);
    }

    /**
     * The variant a hint ("red xl") points to. Short values (S, M, XL) count
     * only as whole words; longer ones ("light blue") anywhere in the hint.
     *
     * @return array{variant: ?CatalogVariant, problem: ?string}
     */
    public function pickVariant(array $product, string $hint): array
    {
        if (! $product['variable']) {
            return ['variant' => null, 'problem' => null];
        }

        $variants = $product['variants'];

        if ($variants === []) {
            return ['variant' => null, 'problem' => 'has no active variants'];
        }

        $h = Text::norm($hint);

        if ($h !== '') {
            $hw = Text::words($h);
            $scored = array_map(fn ($v) => [
                'v' => $v,
                'score' => count(array_filter($v['values'], fn ($val) => in_array($val, $hw, true) || (mb_strlen($val) >= 3 && str_contains($h, $val))
                        || (bool) array_filter($hw, fn ($w) => self::similar($w, $val))))
                    + ($v['sku'] !== '' && str_contains(Text::compact($h), Text::compact($v['sku'])) ? 5 : 0),
            ], $variants);
            usort($scored, fn ($a, $b) => $b['score'] <=> $a['score']);

            if ($scored[0]['score'] > 0 && (count($scored) === 1 || $scored[0]['score'] > $scored[1]['score'])) {
                return ['variant' => $scored[0]['v'], 'problem' => null];
            }
        }

        if (count($variants) === 1) {
            return ['variant' => $variants[0], 'problem' => null];
        }

        return ['variant' => null, 'problem' => 'choose a variant (' . implode(', ', array_slice(array_column($variants, 'label'), 0, 6)) . ')'];
    }

    /**
     * Products mentioned anywhere in a free-text line, for chat messages that
     * don't put products in a labelled field: a code/SKU word, a full product
     * name, or a keyword alias. Partial-word matches aren't taken here — that
     * is left to resolve() on a labelled product field, or to the AI.
     *
     * @return list<array{product: CatalogProduct, variant: ?CatalogVariant, text: string, match: string}>
     */
    public function mentionsIn(string $line): array
    {
        $norm = Text::norm($line);
        $found = [];

        foreach (preg_split('/[\s,;|]+/u', $norm) ?: [] as $word) {
            $compact = Text::compact($word);
            if (mb_strlen($compact) >= 3 && preg_match('/\d/', $compact) && ($hit = $this->refHit($compact))) {
                $found[$hit['product']['id'] . ':' . ($hit['variant']['id'] ?? '')] = [...$hit, 'text' => $word, 'match' => 'exact'];
            }
        }

        if (preg_match_all('/(?:code|কোড|design|model|dress|ড্রেস|#)\s*(?:no\.?|নং)?\s*[:\-#]?\s*(\d{2,6})(?!\d)/u', $norm, $codeMatches, PREG_SET_ORDER)) {
            foreach ($codeMatches as $cm) {
                if ($hit = $this->byCodeNumber($cm[1])) {
                    $found[$hit['product']['id'] . ':'] ??= [...$hit, 'text' => $cm[0], 'match' => 'code'];
                }
            }
        }

        foreach ($this->products as $p) {
            if (isset($found[$p['id'] . ':'])) {
                continue;
            }

            $name = Text::norm($p['name']);
            if (mb_strlen($name) >= 5 && str_contains($norm, $name)) {
                $found[$p['id'] . ':'] = ['product' => $p, 'variant' => null, 'text' => $name, 'match' => 'name'];
                continue;
            }

            foreach ($p['keywords'] as $alias) {
                $alias = Text::norm($alias);
                if (mb_strlen($alias) >= 4 && preg_match('/(?<![\p{L}\p{M}\p{N}])' . preg_quote($alias, '/') . '(?![\p{L}\p{M}\p{N}])/u', $norm)) {
                    $found[$p['id'] . ':'] = ['product' => $p, 'variant' => null, 'text' => $alias, 'match' => 'keyword'];
                    break;
                }
            }
        }

        if ($found === [] && ($partial = $this->partialIn($norm))) {
            $found[$partial['product']['id'] . ':'] = ['product' => $partial['product'], 'variant' => null, 'text' => $partial['text'], 'match' => 'partial'];
        }

        // Drop a product found by name when a longer product name containing
        // it was found too ("Zareen" inside "Zareen 4 Pcs").
        $found = array_values($found);

        return array_values(array_filter($found, fn ($f) => ! array_filter($found, fn ($o) => $o !== $f
            && $o['match'] !== 'exact' && $f['match'] !== 'exact'
            && mb_strlen($o['text']) > mb_strlen($f['text']) && str_contains($o['text'], $f['text']))));
    }

    /**
     * A quantity written on a line next to a product ("x2", "2 pcs", "২টা",
     * "qty 3", "duita"), or null.
     */
    public static function quantityIn(string $line): ?float
    {
        $s = Text::norm($line);
        $units = self::QTY_UNITS;

        if (preg_match('/(?:^|\s)[x×*]\s*(\d{1,3})(?!\d)/u', $s, $m)
            || preg_match('/(?<![\d.])(\d{1,3})\s*(?:' . $units . ')(?![\p{L}\p{M}])/iu', $s, $m)
            || preg_match('/(?:qty|quantity|পরিমাণ|piece|pcs)\s*[:=\-]?\s*(\d{1,3})(?!\d)/iu', $s, $m)) {
            return (float) $m[1];
        }

        foreach (Text::words($s) as $w) {
            if (isset(self::NUMBER_WORDS[$w])) {
                return (float) self::NUMBER_WORDS[$w];
            }
        }

        return null;
    }

    /**
     * The products most likely meant by some text — what the AI fallback is
     * shown instead of the whole catalogue.
     *
     * @return list<CatalogProduct>
     */
    public function candidates(string $text, int $limit, bool $fill = true): array
    {
        $words = array_filter(Text::words($text), fn ($w) => mb_strlen($w) >= 3);
        $compactText = Text::compact($text);
        $scored = [];

        foreach ($this->products as $p) {
            $hay = Text::words($p['name'] . ' ' . implode(' ', $p['keywords']));
            $score = count(array_intersect($hay, $words)) * 2;

            foreach ($words as $w) {
                foreach ($hay as $h) {
                    if ($w !== $h && self::similar($w, $h)) {
                        $score++;
                    }
                }
            }

            foreach ([$p['code'], ...array_column($p['variants'], 'sku')] as $ref) {
                if (($c = Text::compact($ref)) !== '' && str_contains($compactText, $c)) {
                    $score += 10;
                }
            }

            if ($score > 0) {
                $scored[] = [$score, $p];
            }
        }

        usort($scored, fn ($a, $b) => $b[0] <=> $a[0]);
        $picked = array_column(array_slice($scored, 0, $limit), 1);

        // Nothing in the text points anywhere (e.g. only a screenshot): fill
        // with the start of the catalogue so the AI has names to map to.
        if ($fill && count($picked) < $limit) {
            $ids = array_column($picked, 'id');
            foreach ($this->products as $p) {
                if (count($picked) >= $limit) {
                    break;
                }
                if (! in_array($p['id'], $ids, true)) {
                    $picked[] = $p;
                }
            }
        }

        return $picked;
    }
}
