<?php

namespace App\OrderIntake;

/**
 * Deterministic first pass of AI Order: free text (a typed order, a copied
 * Messenger/WhatsApp chat, a list of orders) → one raw draft per order.
 * Nothing here is AI — labelled fields ("Name: …", "ঠিকানা: …"), phone
 * numbers, catalogue codes/names, amounts with their keywords, and
 * line-shape heuristics for unlabelled lines. Each value carries a
 * confidence; DraftResolver decides what's good enough.
 *
 * "unused" lists the lines nothing was read from — the only text an AI
 * could still find a missing name/address/product in (OrderIntakeService
 * skips the AI when it's empty).
 *
 * @phpstan-type RawItem array{text: string, said?: string, qty: ?float, price: ?float, price_hint?: ?float, variant_text: ?string, variant_context?: ?string, product_id: ?int, variant_id: ?int, match: ?string}
 * @phpstan-type RawDraft array{
 *     name: ?string, name_conf: float, phone: ?string, alt_phone: ?string,
 *     address: ?string, address_conf: float, area_hint: ?string,
 *     items: list<RawItem>, items_labelled: bool,
 *     discount: ?array{type: string, value: float, text: string},
 *     delivery_charge: ?float, advance: ?float,
 *     stated_total: ?array{value: float, kind: string, text: string},
 *     payment: ?string, note: ?string, unused: list<string>, source_text: string, via: string,
 * }
 */
final class TextOrderParser
{
    /**
     * Field label → alternatives, matched at a line start before ":" / "-" /
     * "=" / ">". Order matters: "alt" before "phone", "addr_part" after
     * "address". A "no"/"নং" after the label is allowed where noted in NUMBERED.
     */
    private const LABELS = [
        'alt'       => 'alt phone|alternative number|alternative phone|alternative|alternate|another number|2nd number|second number|alt|বিকল্প নাম্বার|বিকল্প নম্বর|বিকল্প|অন্য নাম্বার',
        'name'      => 'customer name|receiver name|recipient name|full name|name|customer|receiver|recipient|nam|naam|গ্রাহকের নাম|প্রাপকের নাম|নাম|গ্রাহক|প্রাপক',
        'phone'     => 'phone number|mobile number|contact number|whatsapp number|phone|mobile|contact|number|cell|whatsapp|imo|ph|mob|ফোন নাম্বার|মোবাইল নাম্বার|ফোন নম্বর|মোবাইল নম্বর|ফোন|মোবাইল|নাম্বার|নম্বর',
        'address'   => 'full address|delivery address|shipping address|address|addr|location|thikana|পূর্ণ ঠিকানা|ঠিকানা|এড্রেস|অ্যাড্রেস',
        'addr_part' => 'house|holding|road|flat|floor|block|sector|village|vill|gram|post office|post|p\.?o|union|ward|para|area|district|zilla|zila|thana|upazila|upozila|city|বাসা|বাড়ি|হোল্ডিং|রোড|ফ্ল্যাট|ব্লক|সেক্টর|গ্রাম|পোস্ট অফিস|পোস্ট|ডাকঘর|ইউনিয়ন|ওয়ার্ড|এলাকা|জেলা|থানা|উপজেলা',
        'product'   => 'product name|product code|item name|item code|dress code|design code|design|model|products|product|items|item|order(?!\s*(?:no|id|number|#|নং))|code|sku|পণ্যের নাম|পণ্য|প্রোডাক্ট|অর্ডার|কোড',
        'qty'       => 'qty|quantity|pcs|piece|pieces|পরিমাণ|পিস',
        'variant'   => 'size|color|colour|variant|সাইজ|কালার|রং|রঙ',
        'price'     => 'unit price|price|dam|daam|rate|দাম|মূল্য',
        'note'      => 'note|notes|remark|remarks|instruction|comment|নোট|মন্তব্য',
    ];

    /** Fields whose label may carry "no"/"no."/"নং" ("Mobile No.:", "House No:", "মোবাইল নং:"). */
    private const NUMBERED = ['alt', 'phone', 'addr_part', 'product'];

    /** Labels that work without a separator ("Name Rahima", "ঠিকানা মিরপুর ১০") — checked against the value's shape. */
    private const SPACED = ['name' => 'name|nam|naam|নাম', 'phone' => 'phone|mobile|ফোন|মোবাইল', 'address' => 'address|thikana|ঠিকানা'];

    private const ADDRESS_MARKERS = '/\b(house|home|holding|road|rd|flat|floor|block|sector|lane|village|vill|gram|post|p\.?o|thana|upazila|upozila|district|zilla|zila|para|bazar|bazaar|more|mor|sadar|union|ward|area|avenue|street|college|school|madrasa|mosque|hospital|market|tower|plaza|bari|nagar|pur|ganj|gonj)\b|বাসা|বাড়ি|রোড|গ্রাম|থানা|জেলা|উপজেলা|পোস্ট|পোঃ|বাজার|মোড়|সদর|ইউনিয়ন|ওয়ার্ড|সেক্টর|ব্লক|ফ্ল্যাট|মহল্লা|পাড়া|নগর/iu';

    /** Lines that are chat chrome, never order data. */
    private const NOISE = '/^(you sent|you replied|seen|delivered|sent|enter|reply|forwarded|edited|typing|active now|\d{1,2}:\d{2}\s*(am|pm)?|(today|yesterday|mon|tue|wed|thu|fri|sat|sun)\w*\s*(at\s*)?\d{1,2}:\d{2}\s*(am|pm)?)$/iu';

    /** Small talk / chat words — never a name, and not worth an AI call when left over. */
    private const SMALL_TALK = '/^(hi|hello|hey|salam|assalamu?\s*alaikum|ass?alamualaikum|apu|apa|vai|bhai|vaiya|bhaiya|sir|madam|ok|okay|thanks|thank you|ji|jee|ha|hae|yes|no|na|price|dam|koto|order|confirm|confirmed|done|please|pls|plz|available|stock|size|color|delivery|cod|bkash|nagad|total|kobe|kokhon|lagbe|nibo|dibo|den|diben|pathan|pathaben|ji apu|ok apu|জি|হ্যাঁ|না|ধন্যবাদ|আসসালামু আলাইকুম|সালাম|আপু|ভাই|অর্ডার|কনফার্ম|দাম|কত|নিবো|দিবেন|লাগবে|পাঠান)\b/iu';

    /**
     * @param  list<string>  $shopPhones  national numbers of the shop itself — never a customer's
     * @param  list<string>  $shopNames  the shop's own names — never a customer's
     */
    public function __construct(
        private Catalog $catalog,
        private DeliveryAreas $areas,
        private array $shopPhones = [],
        private array $shopNames = [],
    ) {
        $this->shopNames = array_values(array_filter(array_map(fn ($n) => Text::norm((string) $n), $shopNames)));
    }

    /** @return list<RawDraft> */
    public function parse(string $text): array
    {
        $blocks = $this->blocks($this->clean($text));

        return array_values(array_filter(array_map(fn ($b) => $this->parseBlock($b), $blocks), fn ($d) => $d !== null));
    }

    // ---------------------------------------------------------------
    // Cleaning & splitting
    // ---------------------------------------------------------------

    /** @return list<string> lines, '' for a paragraph break */
    private function clean(string $text): array
    {
        $out = [];

        foreach (explode("\n", str_replace(["\r\n", "\r"], "\n", $text)) as $line) {
            // WhatsApp export prefixes: "[05/10/26, 3:12 PM] Rahim: …" / "05/10/26, 3:12 pm - Rahim: …"
            $line = preg_replace('/^\[?\d{1,2}[\/.\-]\d{1,2}[\/.\-]\d{2,4},?\s+\d{1,2}:\d{2}(?::\d{2})?\s*(?:[ap]\.?m\.?)?\]?\s*(?:-\s*)?[^:]{1,40}:\s*/iu', '', $line) ?? $line;
            $line = trim(preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $line) ?? $line);

            if ($line === '' || preg_match('/^[-=_*~.]{3,}$/', $line)) {
                $out[] = '';
                continue;
            }
            if (preg_match(self::NOISE, $line)) {
                continue;
            }

            $out[] = $line;
        }

        return $out;
    }

    /**
     * Splits into one block per order: blank-line paragraphs are grouped
     * until a paragraph brings a different phone number; a paragraph holding
     * several orders one-per-line is split per line. A paragraph that is
     * only a second number is that order's alternative phone.
     *
     * @param  list<string>  $lines
     * @return list<list<string>>
     */
    private function blocks(array $lines): array
    {
        $paragraphs = [];
        $current = [];
        foreach ($lines as $line) {
            if ($line === '') {
                if ($current) {
                    $paragraphs[] = $current;
                }
                $current = [];
                continue;
            }
            $current[] = $line;
        }
        if ($current) {
            $paragraphs[] = $current;
        }

        $units = [];
        foreach ($paragraphs as $p) {
            foreach ($this->splitParagraph($p) as $u) {
                $units[] = $u;
            }
        }

        $blocks = [];
        $block = [];
        $blockPhones = [];

        foreach ($units as $u) {
            $phones = $this->primaryPhones($u);
            $onlyPhone = count($u) === 1 && trim(PhoneExtractor::strip($this->stripLabel($u[0])), " \t,;:-/") === '';
            $isNew = $phones !== [] && $blockPhones !== [] && array_diff($phones, $blockPhones) !== [] && ! $onlyPhone;

            if ($isNew) {
                $blocks[] = $block;
                $block = [];
                $blockPhones = [];
            }

            $block = [...$block, ...$u];
            $blockPhones = array_values(array_unique([...$blockPhones, ...$phones]));
        }
        if ($block) {
            $blocks[] = $block;
        }

        return $blocks;
    }

    /**
     * @param  list<string>  $lines
     * @return list<list<string>>
     */
    private function splitParagraph(array $lines): array
    {
        $phoneLines = array_filter($lines, fn ($l) => $this->primaryPhones([$l]) !== []);

        if (count($this->primaryPhones($phoneLines)) < 2) {
            return [$lines];
        }

        // One order per line: "Rahim 01711… Dhanmondi SF-1 x2".
        $selfContained = array_filter($phoneLines, fn ($l) => count(Text::words(PhoneExtractor::strip($l))) >= 2);
        if (count($selfContained) === count($phoneLines) && count($phoneLines) >= count($lines) * 0.5) {
            $out = [];
            foreach ($lines as $l) {
                if ($this->primaryPhones([$l]) !== [] || $out === []) {
                    $out[] = [$l];
                } else {
                    $out[count($out) - 1][] = $l;
                }
            }

            return $out;
        }

        // A form repeated without blank lines: a new "Name:" (or a new phone
        // once this block has both) starts the next order.
        $out = [[]];
        $has = ['name' => false, 'phone' => false];
        foreach ($lines as $l) {
            $label = $this->labelOf($l);
            $phone = $this->primaryPhones([$l]) !== [];

            if (($label === 'name' && $has['name'] && $has['phone']) || ($phone && $has['phone'] && $label !== 'alt')) {
                $out[] = [];
                $has = ['name' => false, 'phone' => false];
            }

            $out[count($out) - 1][] = $l;
            $has['name'] = $has['name'] || $label === 'name';
            $has['phone'] = $has['phone'] || ($phone && $label !== 'alt');
        }

        return array_values(array_filter($out));
    }

    /**
     * Customer phone numbers on these lines — not alternative numbers, not
     * the shop's own.
     *
     * @param  list<string>  $lines
     * @return list<string>
     */
    private function primaryPhones(array $lines): array
    {
        $out = [];
        foreach ($lines as $l) {
            if ($this->labelOf($l) === 'alt') {
                continue;
            }
            foreach ($this->phonesIn($l) as $p) {
                $out[] = $p;
            }
        }

        return array_values(array_unique($out));
    }

    /** @return list<string> national numbers, the shop's own left out */
    private function phonesIn(string $text): array
    {
        return array_values(array_filter(
            array_column(PhoneExtractor::find($text), 'national'),
            fn ($n) => ! in_array($n, $this->shopPhones, true),
        ));
    }

    // ---------------------------------------------------------------
    // Labels
    // ---------------------------------------------------------------

    private function labelOf(string $line): ?string
    {
        return $this->splitLabel($line)[0];
    }

    /** @return array{0: ?string, 1: string, 2: string} [field, value, the label as typed] */
    private function splitLabel(string $line): array
    {
        // Emoji / bullets / numbering in front of a label: "📞 Phone:", "• Name -", "1) Address:".
        $l = preg_replace('/^(?:[^\p{L}\p{N}+]+|\d{1,2}[.)]\s+)+/u', '', $line) ?? $line;

        foreach (self::LABELS as $field => $alts) {
            $numbered = in_array($field, self::NUMBERED, true) ? '(?:\s*(?:no|nong|নং|নম্বর|number|#)\.?)?' : '';
            if (preg_match('/^(' . $alts . ')' . $numbered . '\s*[:：=\-–>]+\s*(.*)$/iu', $l, $m)) {
                return [$field, trim($m[2], " \t-–:"), $m[1]];
            }
        }

        // "Name Rahima", "ঠিকানা মিরপুর ১০", "Mobile 01711…" — only when the value has the field's shape.
        foreach (self::SPACED as $field => $alts) {
            if (preg_match('/^(' . $alts . ')\s+(.+)$/iu', $l, $m)) {
                $value = trim($m[2]);
                $fits = match ($field) {
                    'name'    => $this->looksLikeName($value),
                    'phone'   => $this->phonesIn($value) !== [],
                    'address' => $this->addressScore($value) >= 1,
                };
                if ($fits) {
                    return [$field, $value, $m[1]];
                }
            }
        }

        return [null, $line, ''];
    }

    private function stripLabel(string $line): string
    {
        return $this->splitLabel($line)[1];
    }

    // ---------------------------------------------------------------
    // One order
    // ---------------------------------------------------------------

    /**
     * @param  list<string>  $lines
     * @return RawDraft|null
     */
    private function parseBlock(array $lines): ?array
    {
        $blockText = implode("\n", $lines);

        $draft = [
            'name' => null, 'name_conf' => 0.0, 'phone' => null, 'alt_phone' => null,
            'address' => null, 'address_conf' => 0.0, 'area_hint' => null,
            'items' => [], 'items_labelled' => false,
            'discount' => null, 'delivery_charge' => null, 'advance' => null, 'stated_total' => null,
            'payment' => null, 'note' => null, 'unused' => [], 'source_text' => $blockText, 'via' => 'parser',
        ];

        $labelled = [];   // field → list of values
        $free = [];       // unlabelled lines
        $lastField = null;

        foreach ($lines as $line) {
            [$field, $value, $label] = $this->splitLabel($line);

            if ($field === 'addr_part') {
                // "House: 12" → "House 12"; "Thana: Mirpur" → "Mirpur".
                $value = preg_match('/^[\d০-৯]/u', $value) ? "{$label} {$value}" : $value;
            }
            if ($field !== null) {
                if ($value !== '') {
                    $labelled[$field][] = $value;
                }
                $lastField = $field;
                continue;
            }

            // An address often wraps onto the next line(s).
            if (in_array($lastField, ['address', 'addr_part'], true) && $this->phonesIn($line) === [] && $this->catalog->mentionsIn($line) === []
                && ! $this->amountLine($line) && $this->addressScore($line) >= 1) {
                $labelled[$lastField][] = $line;
                continue;
            }

            $lastField = null;
            $free[] = $line;
        }

        // Phones — the shop's own numbers never count.
        $phones = $this->phonesIn(implode("\n", [...($labelled['phone'] ?? []), ...$free, ...($labelled['name'] ?? []), ...($labelled['address'] ?? [])]));
        $alt = $this->phonesIn(implode("\n", $labelled['alt'] ?? []));
        $draft['phone'] = $phones[0] ?? ($alt[0] ?? null);
        $draft['alt_phone'] = collect([...$phones, ...$alt])->unique()->reject(fn ($p) => $p === $draft['phone'])->first();

        // Amounts — from the whole block (amount keywords are unambiguous).
        $this->amounts($blockText, $draft);
        $priceHint = $this->priceHint($blockText);

        // Delimited one-liner: "Rahim, 01711…, Dhanmondi, SF-1 x2".
        if (! $labelled && count($free) === 1 && preg_match('/[,|;\t]/', $free[0]) && $this->phonesIn($free[0])) {
            $this->delimitedLine($free[0], $draft, $labelled);
            $free = [];
        }

        // Inline "Rahim 01711223344 House 5, Dhanmondi" — split at the phone.
        $free = $this->splitAtPhone($free);

        // Products.
        $variantText = implode(' ', $labelled['variant'] ?? []);
        $qtyLabel = isset($labelled['qty']) ? Text::number($labelled['qty'][0]) : null;
        $priceLabel = isset($labelled['price']) ? Text::number($labelled['price'][0]) : null;

        if (! empty($labelled['product'])) {
            $draft['items_labelled'] = true;
            $texts = Catalog::splitItems(implode("\n", $labelled['product']));

            foreach ($texts as $t) {
                $parsed = $this->catalog->parseItem($t);
                $draft['items'][] = [
                    'text' => $parsed['ref'], 'qty' => $parsed['qty'] ?? (count($texts) === 1 ? $qtyLabel : null),
                    'price' => $parsed['price'] ?? (count($texts) === 1 ? $priceLabel : null),
                    'price_hint' => count($texts) === 1 ? $priceHint : null,
                    'variant_text' => $variantText ?: null, 'product_id' => null, 'variant_id' => null, 'match' => null,
                ];
            }
        }

        // Free lines naming products; what's left of such a line may be the address.
        $leftover = [];
        if (! $draft['items']) {
            foreach ($free as $i => $line) {
                $mentions = $this->catalog->mentionsIn($line);
                if (! $mentions) {
                    continue;
                }
                $rest = Text::norm($line);
                foreach ($mentions as $m) {
                    $rest = str_replace($m['text'], ' ', $rest);
                }
                $qty = count($mentions) === 1 ? Catalog::quantityIn($rest) : null;
                // Only quantity/price expressions go — "Mirpur 10" keeps its 10.
                $rest = trim(preg_replace('/(?<![\p{L}\p{M}\d])\d+\s*(?:pcs?|pieces?|ta|ti|to|টা|টি|টো|পিস)(?![\p{L}\p{M}\d])|[x×*]\s*\d+|@\s*\d+/iu', ' ', $rest) ?? '');
                $leftover[$i] = $rest;

                foreach ($mentions as $m) {
                    $draft['items'][] = [
                        'text' => $m['variant'] ? $m['variant']['sku'] : ($m['product']['code'] ?: $m['product']['name']),
                        'said' => $m['text'],
                        'qty' => $qty ?? $qtyLabel,
                        'price' => count($mentions) === 1 ? $priceLabel : null,
                        'price_hint' => count($mentions) === 1 ? $priceHint : null,
                        'variant_text' => trim($rest . ' ' . $variantText) ?: null,
                        'product_id' => $m['product']['id'], 'variant_id' => $m['variant']['id'] ?? null, 'match' => $m['match'],
                    ];
                }
            }
        }

        // Name.
        if (! empty($labelled['name'])) {
            $name = trim(PhoneExtractor::strip($labelled['name'][0]), " \t,;-");
            if ($name !== '' && ! $this->isShopName($name)) {
                $draft['name'] = $this->titleCase($name);
                $draft['name_conf'] = 0.95;
            }
        }

        // Address: labelled, plus labelled parts ("House: 12", "Thana: Mirpur").
        $parts = array_map(fn ($a) => trim(PhoneExtractor::strip($a), " \t,;"), [...($labelled['address'] ?? []), ...($labelled['addr_part'] ?? [])]);
        $parts = array_values(array_filter($parts, fn ($p) => $p !== ''));
        if ($parts !== []) {
            $draft['address'] = $this->tidyAddress(implode(', ', $parts));
            $draft['address_conf'] = ! empty($labelled['address']) ? 0.95 : 0.85;
            $draft['area_hint'] = ! empty($labelled['addr_part']) ? implode(', ', $labelled['addr_part']) : null;
        }

        // Unlabelled lines: address and name by shape. A product line's
        // leftover counts only when it reads like an address.
        $candidates = [];
        foreach ($free as $i => $line) {
            $text = $leftover[$i] ?? $line;
            if (isset($leftover[$i]) && $this->addressScore($text) < 2) {
                continue;
            }
            $text = trim(PhoneExtractor::strip($text), " \t,;:-");
            if ($text !== '' && ! $this->amountLine($text)) {
                $candidates[] = $text;
            }
        }

        if ($draft['address'] === null) {
            $best = null;
            $bestScore = 0;
            foreach ($candidates as $l) {
                $score = $this->addressScore($l);
                if ($score > $bestScore && (count(Text::words($l)) >= 2 || $this->areas->mentionsPlace($l))) {
                    $best = $l;
                    $bestScore = $score;
                }
            }
            if ($best !== null && $bestScore >= 1) {
                $draft['address'] = $this->tidyAddress($best);
                $draft['address_conf'] = $bestScore >= 2.5 ? 0.8 : 0.6;
                $candidates = array_values(array_filter($candidates, fn ($l) => $l !== $best));
            }
        }

        if ($draft['name'] === null) {
            foreach ($candidates as $k => $l) {
                if ($this->looksLikeName($l)) {
                    $draft['name'] = $this->titleCase($l);
                    $draft['name_conf'] = 0.7;
                    unset($candidates[$k]);
                    break;
                }
            }
        }

        // Size/colour/words said anywhere in the block — the resolver's second
        // try for a variant, or to tell two similar products apart.
        $context = trim(PhoneExtractor::strip(implode(' ', [...$free, $variantText])));
        foreach ($draft['items'] as &$item) {
            $item['variant_context'] = $context !== '' ? $context : null;
        }
        unset($item);

        // Note.
        if (! empty($labelled['note'])) {
            $draft['note'] = mb_substr(implode(' | ', $labelled['note']), 0, 500);
        }

        // What nothing was read from — small talk doesn't count.
        $draft['unused'] = array_values(array_slice(array_filter($candidates, fn ($l) => ! preg_match(self::SMALL_TALK, Text::norm($l))
            && ! $this->isShopName($l) && preg_match('/[\p{L}]{2,}/u', $l)), 0, 10));

        $hasAnything = $draft['phone'] || $draft['items'] || $draft['address'] || $draft['name'];

        return $hasAnything ? $draft : null;
    }

    /** Pulls discount / delivery / advance / total / payment out of the text. */
    private function amounts(string $text, array &$draft): void
    {
        $t = Text::norm(str_replace(',', '', Text::asciiDigits($text)));
        // Phone numbers aren't amounts.
        $t = PhoneExtractor::strip($t);
        $tk = '(?:tk\.?|taka|৳|টাকা|bdt)?';
        // "delivery 24 hours" / "3 din" is a time, not money.
        $notTime = '(?!\s*(?:hours?|hrs?|h\b|days?|din|ঘন্টা|ঘণ্টা|দিন|minutes?|min))';

        // Discount — percent first.
        if (preg_match('/(\d+(?:\.\d+)?)\s*%\s*(?:off|discount|disc|less|ছাড়|ছাড|ডিসকাউন্ট|কম|kom)/u', $t, $m)
            || preg_match('/(?:discount|disc|ছাড়|ছাড|ডিসকাউন্ট|off|less)\s*[:=\-]*\s*(\d+(?:\.\d+)?)\s*%/u', $t, $m)) {
            if ((float) $m[1] > 0 && (float) $m[1] < 100) {
                $draft['discount'] = ['type' => 'percent', 'value' => (float) $m[1], 'text' => trim($m[0])];
            }
        } elseif (preg_match("/(?:discount|disc|ছাড়|ছাড|ডিসকাউন্ট|less)\s*(?:dilam|diben|den|dilen|দিলাম|দিবেন|দেন|দিলেন|amount)?\s*[:=\-]*\s*{$tk}\s*(\d+(?:\.\d+)?)/u", $t, $m)
            || preg_match("/(\d+(?:\.\d+)?)\s*{$tk}\s*(?:discount|disc|ছাড়|ছাড|ডিসকাউন্ট|off|less|কম|kom)/u", $t, $m)) {
            $draft['discount'] = ['type' => 'amount', 'value' => (float) $m[1], 'text' => trim($m[0])];
        }

        // Delivery charge.
        if (preg_match('/free\s*(?:home\s*)?delivery|delivery\s*(?:charge\s*)?free|ফ্রি\s*ডেলিভারি|ডেলিভারি\s*(?:চার্জ\s*)?ফ্রি/u', $t)) {
            $draft['delivery_charge'] = 0.0;
        } elseif (preg_match("/(?:delivery|ডেলিভারি|shipping|courier|কুরিয়ার)\s*(?:charge|fee|cost|চার্জ|খরচ|ফি)?\s*[:=\-]*\s*{$tk}\s*(\d{2,4})(?!\d){$notTime}/u", $t, $m)
            || preg_match("/(?<![\d.])(\d{2,4})\s*{$tk}\s*(?:delivery|ডেলিভারি)\s*(?:charge|চার্জ)/u", $t, $m)) {
            $draft['delivery_charge'] = (float) $m[1];
        }

        // Advance.
        if (preg_match("/(?:advance|adv|অগ্রিম|এডভান্স|অ্যাডভান্স)\s*(?:paid|payment|dilam|দিলাম|পেমেন্ট)?\s*[:=\-]*\s*{$tk}\s*(\d{2,6})(?!\d)/u", $t, $m)
            || preg_match("/(?<![\d.])(\d{2,6})\s*{$tk}\s*(?:advance|adv|অগ্রিম|এডভান্স)/u", $t, $m)
            || preg_match("/(?:bkash|bikash|nagad|rocket|বিকাশ|নগদ)\s*(?:e|a|এ)?\s*(?:paid|sent|send|korechi|dilam|pathaisi|pathiyechi|করেছি|দিলাম|দিয়েছি|পাঠিয়েছি)?\s*[:=\-]*\s*{$tk}\s*(\d{2,6})(?!\d)/u", $t, $m)) {
            $draft['advance'] = (float) $m[1];
        }

        // Stated total / COD amount.
        if (preg_match("/(?:cod|condition|কন্ডিশন|collect|due)\s*(?:amount|charge|টাকা)?\s*[:=\-]*\s*{$tk}\s*(\d{2,7})(?!\d)/u", $t, $m)) {
            $draft['stated_total'] = ['value' => (float) $m[1], 'kind' => 'cod', 'text' => trim($m[0])];
        } elseif (preg_match("/(?:grand\s*total|total|মোট|সর্বমোট|bill|payable)\s*(?:amount|bill|taka|টাকা|price)?\s*[:=\-]*\s*{$tk}\s*(\d{2,7})(?!\d)/u", $t, $m)) {
            $draft['stated_total'] = ['value' => (float) $m[1], 'kind' => 'total', 'text' => trim($m[0])];
        }

        // Payment.
        if (preg_match('/\b(cod|cash on delivery)\b|ক্যাশ অন ডেলিভারি|হাতে পেয়ে/u', $t)) {
            $draft['payment'] = 'cod';
        } elseif (preg_match('/\b(bkash|bikash|nagad|rocket)\b|বিকাশ|নগদ|রকেট/u', $t, $m)) {
            $draft['payment'] = match (true) {
                str_contains($m[0], 'nagad') || str_contains($m[0], 'নগদ') => 'nagad',
                str_contains($m[0], 'rocket') || str_contains($m[0], 'রকেট') => 'rocket',
                default => 'bkash',
            };
        }
    }

    /** A price quoted anywhere ("dam 2200", "price 1950 tk") — only to tell products/variants apart. */
    private function priceHint(string $text): ?float
    {
        $t = PhoneExtractor::strip(Text::norm(str_replace(',', '', Text::asciiDigits($text))));

        return preg_match('/(?:price|dam|daam|rate|দাম|মূল্য)\s*(?:koto|কত)?\s*[:=\-]*\s*(?:tk\.?|৳)?\s*(\d{2,6})(?!\d)/u', $t, $m)
            || preg_match('/(?<![\d.])(\d{3,6})\s*(?:tk|taka|৳|টাকা)(?!\s*(?:discount|ছাড়|advance|অগ্রিম|delivery|ডেলিভারি))/u', $t, $m)
            ? (float) $m[1] : null;
    }

    /** A line that is only about money (so it isn't an address/name). */
    private function amountLine(string $line): bool
    {
        return (bool) preg_match('/\b(total|discount|delivery|charge|advance|cod|price|dam|bill|paid|tk|taka)\b|টাকা|মোট|ছাড়|ডেলিভারি|অগ্রিম|দাম|৳/iu', $line)
            && count(Text::words(preg_replace('/[\d.,]+/', ' ', $line) ?? '')) <= 5;
    }

    /** How much a line reads like an address: markers, a known place, numbers, commas. */
    private function addressScore(string $line): float
    {
        return preg_match_all(self::ADDRESS_MARKERS, $line)
            + ($this->areas->mentionsPlace($line) ? 2 : 0)
            + (preg_match('/\d/', Text::asciiDigits($line)) ? 0.5 : 0)
            + (str_contains($line, ',') ? 0.5 : 0);
    }

    /**
     * @param  list<string>  $free
     * @return list<string>
     */
    private function splitAtPhone(array $free): array
    {
        $out = [];
        foreach ($free as $line) {
            if (! PhoneExtractor::find($line) || trim(PhoneExtractor::strip($line), " \t,;:-") === '') {
                $out[] = $line;
                continue;
            }
            foreach (preg_split(PhoneExtractor::PATTERN, Text::asciiDigits($line)) ?: [] as $p) {
                $p = trim($p, " \t,;-:");
                if ($p !== '') {
                    $out[] = $p;
                }
            }
        }

        return $out;
    }

    /** "Rahim, 01711…, House 5 Dhanmondi, SF-1 x2" — classify each part. */
    private function delimitedLine(string $line, array &$draft, array &$labelled): void
    {
        $parts = array_values(array_filter(array_map('trim', preg_split('/[,|;\t]/u', $line) ?: []), fn ($p) => $p !== ''));
        $address = [];

        foreach ($parts as $p) {
            if (PhoneExtractor::find($p)) {
                continue;
            }
            $parsed = $this->catalog->parseItem($p);
            $hit = $this->catalog->resolve($parsed['ref']);
            if ($hit && ! isset($hit['ambiguous']) && in_array($hit['match'], ['exact', 'code', 'name', 'keyword', 'partial'], true)) {
                $labelled['product'][] = $p;
                continue;
            }
            if ($draft['name'] === null && $address === [] && $this->looksLikeName($p)) {
                $draft['name'] = $this->titleCase($p);
                $draft['name_conf'] = 0.8;
                continue;
            }
            $address[] = $p;
        }

        if ($address) {
            $draft['address'] = $this->tidyAddress(implode(', ', $address));
            $draft['address_conf'] = 0.8;
        }
    }

    private function looksLikeName(string $line): bool
    {
        $l = trim($line);
        $words = Text::words($l);

        return count($words) >= 1 && count($words) <= 4
            && mb_strlen($l) >= 3 && mb_strlen($l) <= 40
            && ! preg_match('/\d/', Text::asciiDigits($l))
            && ! preg_match(self::SMALL_TALK, Text::norm($l))
            && ! preg_match(self::ADDRESS_MARKERS, $l)
            && ! $this->areas->mentionsPlace($l)
            && ! preg_match('/[?!@:]/', $l)
            && ! $this->isShopName($l)
            && $this->catalog->mentionsIn($l) === [];
    }

    private function isShopName(string $s): bool
    {
        $n = Text::norm($s);

        return $n !== '' && in_array($n, $this->shopNames, true);
    }

    private function titleCase(string $s): string
    {
        return preg_match('/[a-z]/i', $s) && ($s === mb_strtolower($s) || $s === mb_strtoupper($s))
            ? mb_convert_case(mb_strtolower($s), MB_CASE_TITLE)
            : trim($s);
    }

    private function tidyAddress(string $s): string
    {
        return mb_substr(trim(preg_replace(['/\s+/u', '/\s*,\s*(,\s*)+/u'], [' ', ', '], $s) ?? $s, " ,;"), 0, 1000);
    }
}
