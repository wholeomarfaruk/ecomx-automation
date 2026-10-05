<?php

namespace App\OrderIntake;

use App\Models\City;
use App\Models\PoliceStation;
use App\Models\ShippingMethod;

/**
 * Picks the Settings → Shipping zone/method for an address. Zones aren't
 * linked to locations, so each zone is classified by its name (inside
 * Dhaka / Dhaka sub-area / outside Dhaka — or a zone named after a place),
 * and the address is placed with what's known about Bangladesh: the 64
 * districts, Dhaka city areas/thanas (plus the Police Stations table), the
 * Dhaka-district upazilas and the Cities (upazila) table.
 *
 * An explicit "inside/outside Dhaka" in the message wins; a non-Dhaka
 * district wins over a Dhaka area name (Mirpur, Kushtia is outside); only
 * "Dhaka" with nothing more specific is a low-confidence inside.
 *
 * @phpstan-type Zone array{method_id: int, zone: string, label: string, kind: ?string, is_default: bool}
 * @phpstan-type AreaResult array{method_id: ?int, zone: ?string, area: ?string, confidence: float, source: string, note: ?string}
 */
final class DeliveryAreas
{
    public const INSIDE = 'inside';
    public const SUB = 'sub';
    public const OUTSIDE = 'outside';

    /** Dhaka city areas/thanas people write in addresses (English + Bangla). */
    private const DHAKA_CITY = [
        'dhanmondi', 'mirpur', 'uttara', 'gulshan', 'banani', 'mohammadpur', 'mohammedpur', 'badda', 'rampura', 'khilgaon', 'motijheel',
        'paltan', 'ramna', 'shahbag', 'lalbagh', 'sutrapur', 'wari', 'jatrabari', 'demra', 'shyampur', 'kadamtali', 'gendaria',
        'chawkbazar', 'bangshal', 'hazaribagh', 'kamrangirchar', 'kalabagan', 'new market', 'tejgaon', 'farmgate', 'karwan bazar',
        'kawran bazar', 'mohakhali', 'cantonment', 'kafrul', 'pallabi', 'shah ali', 'rupnagar', 'darus salam', 'adabor',
        'sher-e-bangla nagar', 'agargaon', 'shyamoli', 'kallyanpur', 'kalyanpur', 'gabtoli', 'bashundhara', 'baridhara', 'vatara',
        'bhatara', 'khilkhet', 'dakshinkhan', 'uttarkhan', 'turag', 'malibagh', 'mouchak', 'moghbazar', 'mogbazar', 'eskaton',
        'banglamotor', 'segunbagicha', 'shantinagar', 'basabo', 'bashabo', 'mugda', 'sabujbagh', 'shahjahanpur', 'kamalapur',
        'sayedabad', 'jurain', 'azimpur', 'lalmatia', 'jigatola', 'kazipara', 'shewrapara', 'aftabnagar', 'banasree', 'banashree',
        'merul badda', 'nikunja', 'dhanmandi', 'motijhil', 'mohammedpur', 'jatrabari', 'shymoli', 'khilgao', 'bosundhara', 'diabari', 'niketan', 'old dhaka', 'puran dhaka', 'gulistan', 'nawabpur', 'sadarghat',
        'bakshibazar', 'goran', 'kuril', 'notun bazar', 'nadda', 'hatirpool', 'elephant road', 'science lab', 'nilkhet', 'palashi',
        'katabon', 'panthapath', 'green road', 'tolarbag', 'ibrahimpur', 'beraid', 'matuail', 'konapara', 'dhalpur', 'postogola',
        'ধানমন্ডি', 'মিরপুর', 'উত্তরা', 'গুলশান', 'বনানী', 'মোহাম্মদপুর', 'বাড্ডা', 'রামপুরা', 'খিলগাঁও', 'মতিঝিল', 'পল্টন',
        'যাত্রাবাড়ী', 'যাত্রাবাড়ি', 'ডেমরা', 'লালবাগ', 'তেজগাঁও', 'ফার্মগেট', 'মহাখালী', 'বসুন্ধরা', 'বারিধারা', 'খিলক্ষেত', 'শ্যামলী',
        'আদাবর', 'মালিবাগ', 'মগবাজার', 'বাসাবো', 'মুগদা', 'সবুজবাগ', 'শান্তিনগর', 'আজিমপুর', 'কাফরুল', 'পল্লবী', 'ক্যান্টনমেন্ট',
        'হাজারীবাগ', 'ওয়ারী', 'কামরাঙ্গীরচর', 'গেন্ডারিয়া', 'পুরান ঢাকা', 'নিউমার্কেট', 'কলাবাগান', 'আগারগাঁও', 'ভাটারা', 'শাহবাগ',
        'রমনা', 'কদমতলী', 'শ্যামপুর', 'চকবাজার', 'বংশাল', 'গাবতলী', 'কল্যাণপুর', 'মোহাম্মাদপুর', 'দক্ষিণখান', 'উত্তরখান', 'তুরাগ',
    ];

    /** Dhaka-district upazilas and the neighbouring city areas couriers price as "sub area". */
    private const DHAKA_SUB = [
        'savar', 'ashulia', 'keraniganj', 'dhamrai', 'dohar', 'tongi', 'gazipur', 'narayanganj', 'fatullah',
        'siddhirganj', 'kanchpur', 'rupganj', 'board bazar',
        'সাভার', 'আশুলিয়া', 'কেরানীগঞ্জ', 'ধামরাই', 'দোহার', 'টঙ্গী', 'গাজীপুর', 'নারায়ণগঞ্জ', 'ফতুল্লা', 'সিদ্ধিরগঞ্জ',
    ];

    /** The 64 districts except Dhaka/Gazipur/Narayanganj (handled above), English + Bangla. */
    private const DISTRICTS = [
        'bagerhat', 'bandarban', 'barguna', 'barishal', 'barisal', 'bhola', 'bogura', 'bogra', 'brahmanbaria', 'chandpur',
        'chapainawabganj', 'chapai nawabganj', 'chattogram', 'chittagong', 'chuadanga', 'cumilla', 'comilla', "cox's bazar",
        'coxs bazar', 'cox bazar', 'dinajpur', 'faridpur', 'feni', 'gaibandha', 'gopalganj', 'habiganj', 'jamalpur', 'jashore',
        'jessore', 'jhalokati', 'jhalakathi', 'jhenaidah', 'joypurhat', 'khagrachhari', 'khagrachari', 'khulna', 'kishoreganj',
        'kurigram', 'kushtia', 'lakshmipur', 'lalmonirhat', 'madaripur', 'magura', 'manikganj', 'meherpur', 'moulvibazar',
        'munshiganj', 'mymensingh', 'naogaon', 'narail', 'narsingdi', 'natore', 'netrokona', 'nilphamari', 'noakhali', 'pabna',
        'panchagarh', 'patuakhali', 'pirojpur', 'rajbari', 'rajshahi', 'rangamati', 'rangpur', 'satkhira', 'shariatpur',
        'sherpur', 'sirajganj', 'sunamganj', 'sylhet', 'tangail', 'thakurgaon',
        // Common spellings / short forms.
        'ctg', 'chottogram', 'chatgram', 'b.baria', 'b baria', 'bbaria', 'coxsbazar', 'coxbazar', 'mymensing', 'moymonsingh',
        'moymonsing', 'sylet', 'kishorganj', 'netrakona', 'narshingdi', 'narsindi', 'shirajganj', 'panchagar', 'jessor', 'bogura',
        'rongpur', 'nator', 'kustia', 'faridpor', 'potuakhali', 'kumilla', 'noakhli',
        'বাগেরহাট', 'বান্দরবান', 'বরগুনা', 'বরিশাল', 'ভোলা', 'বগুড়া', 'ব্রাহ্মণবাড়িয়া', 'চাঁদপুর', 'চাঁপাইনবাবগঞ্জ', 'চট্টগ্রাম',
        'চুয়াডাঙ্গা', 'কুমিল্লা', 'কক্সবাজার', 'দিনাজপুর', 'ফরিদপুর', 'ফেনী', 'গাইবান্ধা', 'গোপালগঞ্জ', 'হবিগঞ্জ', 'জামালপুর',
        'যশোর', 'ঝালকাঠি', 'ঝিনাইদহ', 'জয়পুরহাট', 'খাগড়াছড়ি', 'খুলনা', 'কিশোরগঞ্জ', 'কুড়িগ্রাম', 'কুষ্টিয়া', 'লক্ষ্মীপুর',
        'লালমনিরহাট', 'মাদারীপুর', 'মাগুরা', 'মানিকগঞ্জ', 'মেহেরপুর', 'মৌলভীবাজার', 'মুন্সিগঞ্জ', 'ময়মনসিংহ', 'নওগাঁ', 'নড়াইল',
        'নরসিংদী', 'নাটোর', 'নেত্রকোনা', 'নীলফামারী', 'নোয়াখালী', 'পাবনা', 'পঞ্চগড়', 'পটুয়াখালী', 'পিরোজপুর', 'রাজবাড়ী',
        'রাজশাহী', 'রাঙ্গামাটি', 'রংপুর', 'সাতক্ষীরা', 'শরীয়তপুর', 'শেরপুর', 'সিরাজগঞ্জ', 'সুনামগঞ্জ', 'সিলেট', 'টাঙ্গাইল', 'ঠাকুরগাঁও',
    ];

    /**
     * @param  list<Zone>  $zones
     * @param  list<string>  $dhakaAreas  extra Dhaka city names (Police Stations table)
     * @param  list<string>  $upazilas  Cities table names outside Dhaka district
     */
    public function __construct(private array $zones, private array $dhakaAreas = [], private array $upazilas = [])
    {
        foreach ($this->zones as &$z) {
            $z['kind'] ??= self::kindOf($z['zone']);
        }
    }

    public static function fromDatabase(): self
    {
        $methods = ShippingMethod::query()
            ->where('is_active', true)
            ->whereHas('zone', fn ($q) => $q->where('is_active', true))
            ->with('zone')
            ->get()
            ->sortBy(fn ($m) => [$m->zone->sort_order, $m->zone->id, $m->sort_order, $m->id])
            ->values();

        // One method per zone — the first, as the bulk sheet's zone list orders them.
        $zones = $methods->unique('shipping_zone_id')->map(fn ($m) => [
            'method_id'  => $m->id,
            'zone'       => (string) $m->zone->name,
            'label'      => $m->zone->name . ($methods->where('shipping_zone_id', $m->shipping_zone_id)->count() > 1 ? " — {$m->name}" : ''),
            'kind'       => null,
            'is_default' => (bool) $m->zone->is_default,
        ])->values()->all();

        $dhakaAreas = PoliceStation::query()->active()
            ->whereHas('city', fn ($q) => $q->where('name', 'like', 'Dhaka%'))
            ->get(['name', 'local_name'])
            ->flatMap(fn ($ps) => [$ps->name, $ps->local_name])
            ->filter()->values()->all();

        $upazilas = City::query()->active()
            ->where('name', 'not like', 'Dhaka%')
            ->get(['name', 'local_name'])
            ->flatMap(fn ($c) => [preg_replace('/\s+sadar$/i', '', (string) $c->name), $c->local_name])
            ->filter(fn ($n) => mb_strlen((string) $n) >= 4)
            ->reject(fn ($n) => in_array(Text::norm((string) $n), [...self::DHAKA_SUB, ...self::DHAKA_CITY], true))
            ->values()->all();

        return new self($zones, $dhakaAreas, $upazilas);
    }

    /** @return list<Zone> */
    public function zones(): array
    {
        return $this->zones;
    }

    /** What a zone name says it covers. */
    public static function kindOf(string $zoneName): ?string
    {
        $n = Text::norm($zoneName);

        if (preg_match('/sub|উপ|suburb|nearby|আশেপাশে/u', $n)) {
            return self::SUB;
        }
        if (preg_match('/outside|বাইরে|all over|nationwide|whole country|সারা দেশ|সারাদেশ|out of/u', $n)) {
            return self::OUTSIDE;
        }
        if (preg_match('/inside|ভিতরে|ভেতরে|within|city|metro|সিটি/u', $n)) {
            return self::INSIDE;
        }

        return null;
    }

    /**
     * @param  string  $address  the address text
     * @param  string  $context  the whole message — for "inside/outside Dhaka" said elsewhere
     * @return AreaResult
     */
    public function resolve(string $address, string $context = ''): array
    {
        $none = ['method_id' => null, 'zone' => null, 'area' => null, 'confidence' => 0.0, 'source' => 'none', 'note' => null];

        if ($this->zones === []) {
            return $none;
        }

        $all = self::placeText($context . ' ' . $address);
        $addr = self::placeText($address);

        // 1. Said outright: "inside dhaka", "ঢাকার বাইরে", or a zone's own name.
        if (preg_match('/(inside|within)\s+dhaka|dhaka\s*(city|r\s*(vitore|bhitore|moddhe))|ঢাকা(র)?\s*(ভিতরে|ভেতরে|মধ্যে|সিটি)/u', $all)) {
            return $this->pick(self::INSIDE, 'inside Dhaka (stated)', 0.95, 'stated') ?? $none;
        }
        if (preg_match('/(outside|out of)\s+dhaka|dhaka\s*r\s*(baire|bahire)|ঢাকা(র)?\s*(বাইরে)/u', $all)) {
            return $this->pick(self::OUTSIDE, 'outside Dhaka (stated)', 0.95, 'stated') ?? $none;
        }
        foreach ($this->zones as $z) {
            $zn = Text::norm($z['zone']);
            // A zone named after a place ("Chattogram City"); a zone called
            // just "Dhaka" is the inside zone and goes through the steps below.
            if ($z['kind'] === null && mb_strlen($zn) >= 4 && ! in_array($zn, ['dhaka', 'ঢাকা'], true) && self::has($all, $zn)) {
                return ['method_id' => $z['method_id'], 'zone' => $z['label'], 'area' => $z['zone'], 'confidence' => 0.9, 'source' => 'stated', 'note' => null];
            }
        }

        if ($addr === '') {
            return $none;
        }

        // 2. A district outside Dhaka.
        if ($place = self::firstIn($addr, self::DISTRICTS)) {
            return $this->pick(self::OUTSIDE, $place, 0.9, 'district') ?? $none;
        }

        // 3. Dhaka sub-area (Savar, Gazipur, Narayanganj…) — outside when there's no sub-area zone.
        if ($place = self::firstIn($addr, self::DHAKA_SUB)) {
            return $this->pick(self::SUB, $place, 0.85, 'area')
                ?? $this->pick(self::OUTSIDE, $place, 0.7, 'area', 'No sub-area zone — priced as outside Dhaka')
                ?? $none;
        }

        // 4. A Dhaka city area/thana.
        if ($place = self::firstIn($addr, [...self::DHAKA_CITY, ...array_map(fn ($a) => self::placeText($a), $this->dhakaAreas)])) {
            return $this->pick(self::INSIDE, $place, 0.9, 'area') ?? $none;
        }

        // 5. An upazila from the Cities table.
        if ($place = self::firstIn($addr, array_map(fn ($a) => self::placeText($a), $this->upazilas))) {
            return $this->pick(self::OUTSIDE, $place, 0.75, 'upazila') ?? $none;
        }

        // 6. Only "Dhaka" — probably the city, but say so.
        if (self::has($addr, 'dhaka') || self::has($addr, 'ঢাকা')) {
            return $this->pick(self::INSIDE, 'Dhaka', 0.6, 'area', 'Only "Dhaka" in the address — check the zone') ?? $none;
        }

        return $none;
    }

    /**
     * Text as place names are compared: normalized, with the gonj/ganj and
     * similar spelling swaps people make (Narayangonj, Keranigonj).
     */
    private static function placeText(string $s): string
    {
        return str_replace(['gonj', 'gong'], ['ganj', 'ganj'], Text::norm($s));
    }

    /** Whether a line names a known place — how the parser spots an unlabelled address line. */
    public function mentionsPlace(string $text): bool
    {
        $t = self::placeText($text);

        return self::firstIn($t, self::DISTRICTS) !== null
            || self::firstIn($t, self::DHAKA_SUB) !== null
            || self::firstIn($t, self::DHAKA_CITY) !== null
            || self::has($t, 'dhaka') || self::has($t, 'ঢাকা')
            || self::firstIn($t, array_map(fn ($a) => self::placeText($a), [...$this->dhakaAreas, ...$this->upazilas])) !== null;
    }

    /** @return AreaResult|null */
    private function pick(string $kind, string $area, float $confidence, string $source, ?string $note = null): ?array
    {
        $zone = collect($this->zones)->first(fn ($z) => $z['kind'] === $kind);

        // "Inside Dhaka" zones are sometimes named just "Dhaka".
        if (! $zone && $kind === self::INSIDE) {
            $zone = collect($this->zones)->first(fn ($z) => $z['kind'] === null && self::has(Text::norm($z['zone']), 'dhaka'));
        }

        return $zone ? [
            'method_id'  => $zone['method_id'],
            'zone'       => $zone['label'],
            'area'       => $area,
            'confidence' => $confidence,
            'source'     => $source,
            'note'       => $note,
        ] : null;
    }

    /** @param list<string> $places */
    private static function firstIn(string $text, array $places): ?string
    {
        foreach ($places as $p) {
            if ($p !== '' && self::has($text, $p)) {
                return $p;
            }
        }

        return null;
    }

    /** Whole-word containment that also works for Bangla (combining marks aren't \p{L}). */
    private static function has(string $text, string $needle): bool
    {
        return (bool) preg_match('/(?<![\p{L}\p{M}\p{N}])' . preg_quote($needle, '/') . '(?![\p{L}\p{M}\p{N}])/u', $text);
    }
}
