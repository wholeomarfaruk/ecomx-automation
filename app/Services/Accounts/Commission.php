<?php

namespace App\Services\Accounts;

use App\Enums\Accounts\JournalEntryStatus;
use App\Models\CommissionAccrual;
use App\Models\CommissionExclusion;
use App\Models\CommissionScheme;
use App\Models\CommissionSettlement;
use App\Models\Customer;
use App\Models\JournalEntry;
use App\Models\MasterProfile;
use App\Models\Order;
use App\Services\Profiles\LedgerAccounts;
use App\Support\AccountsPeriod;
use App\Support\Bdt;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;

/**
 * Customer commission / rebate engine (spec §7.17).
 *
 *   eligible = posted sales in the period − returns − excluded − unpaid
 *   whole    eligible × rate of the tier reached
 *   slab     Σ band × band rate
 *   hold     dues older than hold_days → accrual 0, amount shown "(hold)"
 *   project  commission(eligible × days in period / days elapsed)
 *
 * Basis: net sales (৳, rate %), gross profit (৳, rate %), or quantity
 * (pcs, rate = ৳ per pc). Sales = orders with a posted sale journal.
 */
class Commission
{
    public const TYPES = ['rebate' => 'Rebate (বছরশেষে ফেরত)', 'commission' => 'Commission (agent / referral)', 'instant' => 'Instant discount'];
    public const BASES = ['net_sales' => 'Net sales amount (৳)', 'quantity' => 'Quantity (pcs)', 'gross_profit' => 'Gross profit'];
    public const PERIODS = ['yearly' => 'Yearly (Jul–Jun)', 'half' => 'Half-yearly', 'quarter' => 'Quarterly', 'month' => 'Monthly', 'custom' => 'Custom dates'];
    public const WHO = ['group' => 'Price group', 'tag' => 'Tag', 'selected' => 'Selected customers', 'all' => 'All customers'];
    public const METHODS = ['credit_note' => 'Credit note (পরের কেনায় adjust)', 'deduct_due' => 'বাকি থেকে কাটবো', 'cash' => 'Cash / bKash', 'choose' => 'Customer বেছে নেবে'];
    public const ACCOUNTING = ['rebate' => 'Sales rebate (বিক্রি থেকে কমবে)', 'expense' => 'Commission expense (খরচ)'];

    public const REBATE = '4950';
    public const EXPENSE = '5810';
    public const PAYABLE = '2450';

    public function __construct(protected LedgerAccounts $ledgers, protected Vouchers $vouchers) {}

    /* ------------------------------------------------------------ period */

    /** [start, end] of the scheme period containing $at (FY-relative). */
    public static function period(CommissionScheme $s, ?CarbonImmutable $at = null): array
    {
        $at ??= CarbonImmutable::today();
        if ($s->period === 'custom' && $s->starts_on && $s->ends_on) {
            return [CarbonImmutable::parse($s->starts_on), CarbonImmutable::parse($s->ends_on)];
        }

        $fy = AccountsPeriod::fyStart($at);
        $months = ['half' => 6, 'quarter' => 3, 'month' => 1][$s->period] ?? 12;
        $i = intdiv((int) $fy->diffInMonths($at->startOfMonth()), $months);
        $start = $fy->addMonthsNoOverflow($i * $months);

        return [$start, $start->addMonthsNoOverflow($months)->subDay()];
    }

    /* ------------------------------------------------------------ tiers */

    public static function tierIndex(array $tiers, float $v): int
    {
        $idx = 0;
        foreach ($tiers as $i => $t) {
            if ($v >= (float) $t['from'] - 0.004) {
                $idx = $i;
            }
        }

        return $idx;
    }

    /** Commission on a value for the scheme's mode; quantity basis = ৳/pc, else %. */
    public static function amount(array $tiers, string $mode, float $v, string $basis = 'net_sales'): float
    {
        if (! $tiers || $v <= 0) {
            return 0.0;
        }
        $unit = $basis === 'quantity' ? 1 : 100;

        if ($mode === 'whole') {
            return round($v * (float) $tiers[self::tierIndex($tiers, $v)]['rate'] / $unit, 2);
        }

        $sum = 0.0;
        foreach ($tiers as $t) {
            $to = $t['to'] === null ? INF : (float) $t['to'];
            if ($v > (float) $t['from']) {
                $sum += (min($v, $to) - (float) $t['from']) * (float) $t['rate'] / $unit;
            }
        }

        return round($sum, 2);
    }

    public static function tiersOf(CommissionScheme $s): array
    {
        return $s->tiers->map(fn ($t) => ['from' => (float) $t->from_amount, 'to' => $t->to_amount === null ? null : (float) $t->to_amount, 'rate' => (float) $t->rate])->values()->all();
    }

    /* ------------------------------------------------------------ who */

    /** Customer ids (customers table) the scheme covers, or null = all. */
    public function customerIds(CommissionScheme $s): ?array
    {
        $v = (array) ($s->who_value ?? []);

        return match ($s->who) {
            'group' => Customer::whereIn('customer_group_id', $v ?: [0])->pluck('id')->all(),
            'tag' => Customer::whereIn('master_profile_id', MasterProfile::where(fn ($q) => collect($v)->each(fn ($tag) => $q->orWhereJsonContains('tags', $tag)))->pluck('id'))->pluck('id')->all(),
            'selected' => Customer::whereIn('master_profile_id', $v ?: [0])->pluck('id')->all(),
            default => null,
        };
    }

    /* ------------------------------------------------------------ compute */

    /**
     * Per customer profile: gross, returns, excluded, unpaid, eligible, tier,
     * commission, hold, projection — plus every excluded invoice / line.
     *
     * @return array{rows: Collection, exclusions: Collection, start: CarbonImmutable, end: CarbonImmutable, elapsed: int, days: int}
     */
    public function compute(CommissionScheme $s, ?CarbonImmutable $at = null): array
    {
        $s->loadMissing('tiers');
        [$start, $end] = self::period($s, $at);
        $today = CarbonImmutable::today();
        $upto = $today->lt($end) ? $today : $end;
        $days = $start->diffInDays($end) + 1;
        $elapsed = max(1, min($days, $start->diffInDays($upto) + 1));
        $tiers = self::tiersOf($s);

        $ids = $this->customerIds($s);
        $orders = Order::with(['items.product.categories', 'customer:id,master_profile_id'])
            ->whereNotNull('customer_id')->when($ids !== null, fn ($q) => $q->whereIn('customer_id', $ids ?: [0]))
            ->whereIn('id', JournalEntry::where('source_type', Order::class)->where('status', JournalEntryStatus::POSTED->value)
                ->where('purpose', 'like', 'sale%')->whereBetween('entry_date', [$start->toDateString(), $upto->toDateString()])->select('source_id'))
            ->get();

        $manual = CommissionExclusion::where('commission_scheme_id', $s->id)->get();
        $back = $manual->where('included_back', true);
        $isBack = fn ($orderId, $itemId = null) => $back->first(fn ($e) => $e->order_id == $orderId && ($e->order_item_id === null || $e->order_item_id == $itemId)) !== null;

        $rules = collect(array_keys(CommissionScheme::RULES))->mapWithKeys(fn ($k) => [$k => $s->rule($k)]);
        $exList = collect();
        $per = [];

        foreach ($orders as $o) {
            $pid = $o->customer?->master_profile_id;
            if (! $pid) {
                continue;
            }
            $p = &$per[$pid];
            $p ??= ['gross' => 0, 'returns' => 0, 'excluded' => 0, 'unpaid' => 0, 'eligible' => 0, 'invoices' => []];

            $ref = 'INV-' . str_pad((string) $o->id, 6, '0', STR_PAD_LEFT);
            $subtotal = max(0.01, (float) $o->items->sum(fn ($i) => $i->is_gift ? 0 : (float) $i->total_amount));   // = order subtotal
            $orderDisc = (float) $o->discount_amount;
            $discPct = round($orderDisc / $subtotal * 100, 1);
            $paid = ! $s->paid_only || (float) $o->due_amount <= 0.5;

            // Order-level rules: excluded by hand, or a big invoice discount.
            $orderRules = [];
            $manualRow = $manual->first(fn ($e) => $e->order_id == $o->id && $e->order_item_id === null && ! $e->included_back && $e->source === 'manual');
            if ($manualRow) {
                $orderRules[] = ['manual', 'Manual', $manualRow->reason];
            }
            if ($discPct > $rules['discount']['value'] && ! $isBack($o->id)) {
                $orderRules[] = ['discount', 'Auto', 'Discount ' . (int) $rules['discount']['value'] . '%-এর বেশি'];
            }

            foreach ($o->items as $it) {
                $qty = max(0, (float) $it->quantity);
                $line = $it->is_gift ? 0.0 : (float) $it->total_amount;
                $share = $line * (1 - $orderDisc / $subtotal);    // line net after the invoice discount
                $retQty = $s->returns_reverse ? min($qty, (float) $it->returned_quantity) : 0;
                $retFrac = $qty > 0 ? $retQty / $qty : 0;
                $cost = (float) $it->purchase_price * $qty;
                $value = match ($s->basis) {
                    'quantity' => $qty,
                    'gross_profit' => $share - $cost,
                    default => $share,
                };
                $returns = $value * $retFrac;
                $kept = $value - $returns;
                $p['gross'] += $value;
                $p['returns'] += $returns;

                // Line-level rules (every one that matches; the first that is on decides).
                $lineRules = [];
                if (! $isBack($o->id, $it->id)) {
                    $unitNet = $qty > 0 ? $share / $qty : 0;
                    $m = $manual->first(fn ($e) => $e->order_id == $o->id && $e->order_item_id == $it->id && ! $e->included_back && $e->source === 'manual');
                    if ($m) {
                        $lineRules[] = ['manual', 'Manual', $m->reason];
                    }
                    if ($it->product?->categories?->contains(fn ($c) => preg_match('/clearance|sale/i', (string) $c->name))) {
                        $lineRules[] = ['clearance', 'Auto', 'Item tag: Clearance'];
                    }
                    if ($it->is_gift || (float) $it->unit_price <= 0) {
                        $lineRules[] = ['sample', 'Auto', 'Free / sample item'];
                    }
                    if ((float) $it->purchase_price > 0 && $qty > 0 && $unitNet < (float) $it->purchase_price * (1 + $rules['cost']['value'] / 100)) {
                        $lineRules[] = ['cost', 'Auto', 'Cost + ' . (int) $rules['cost']['value'] . '%-এর নিচে বিক্রি'];
                    }
                }

                $orderOn = collect($orderRules)->first(fn ($r) => $rules[$r[0]]['on']);
                $lineOn = collect($lineRules)->first(fn ($r) => $rules[$r[0]]['on']);
                $out = (bool) ($orderOn ?? $lineOn);
                // Shown in the list: the rule that excluded it, else the first one that matched (struck through — rule off).
                [$rule, $whole] = $orderOn ? [$orderOn, true] : ($lineOn ? [$lineOn, false] : ($orderRules ? [$orderRules[0], true] : ($lineRules ? [$lineRules[0], false] : [null, false])));
                if ($rule) {
                    $exList->push([
                        'order_id' => $o->id, 'item_id' => $whole ? null : $it->id, 'ref' => $ref, 'date' => $o->placed_at ?? $o->created_at,
                        'profile_id' => $pid, 'what' => $whole ? ($rule[0] === 'discount' ? "Invoice discount {$discPct}%" : 'Whole invoice') : ($it->product_name . ', ' . (float) $qty . ' pcs'),
                        'rule' => $rule[0], 'src' => $rule[1], 'reason' => $rule[2], 'amount' => $kept, 'out' => $out,
                    ]);
                }

                if ($out) {
                    $p['excluded'] += $kept;
                } elseif (! $paid) {
                    $p['unpaid'] += $kept;
                } else {
                    $p['eligible'] += $kept;
                }
            }
            unset($p);
        }

        // Whole-invoice exclusions are one row per invoice in the list.
        $exList = $exList->groupBy(fn ($e) => $e['item_id'] === null ? "o{$e['order_id']}" : "i{$e['item_id']}")
            ->map(fn ($g) => $g->first() + ['amount' => $g->sum('amount')])->values()->sortByDesc('date')->values();

        $aging = $s->hold_days ? app(Aging::class)->receivables()->keyBy('profile_id') : collect();
        $profiles = MasterProfile::whereIn('id', array_keys($per))->get(['id', 'display_name', 'phone'])->keyBy('id');
        $accrued = $this->postedAccruals($s, $start)->all();
        $paidOut = CommissionSettlement::where('commission_scheme_id', $s->id)->whereDate('period_start', $start->toDateString())
            ->whereHas('journalEntry', fn ($q) => $q->whereIn('status', [JournalEntryStatus::POSTED->value, JournalEntryStatus::DRAFT->value, JournalEntryStatus::SCHEDULED->value]))->get()->groupBy('master_profile_id')->map->sum('amount');   // posted or waiting — never settle twice

        $rows = collect($per)->map(function ($p, $pid) use ($s, $tiers, $aging, $profiles, $days, $elapsed, $accrued, $paidOut) {
            $elig = max(0, round($p['eligible'], 2));
            $ti = self::tierIndex($tiers, $elig);
            $amt = self::amount($tiers, $s->tier_mode, $elig, $s->basis);
            $proj = self::amount($tiers, $s->tier_mode, $elig * $days / $elapsed, $s->basis);
            $hold = null;
            if ($s->hold_days && ($a = $aging[$pid] ?? null) && $a['oldest'] && $a['oldest']->diffInDays(CarbonImmutable::today()) > $s->hold_days) {
                $hold = 'Hold: ' . (int) $a['oldest']->diffInDays(CarbonImmutable::today()) . ' দিনের বাকি ' . Bdt::money($a['total']) . ' — পরিশোধ হলে commission চালু হবে।';
            }
            $next = $tiers[$ti + 1] ?? null;

            return [
                'profile_id' => $pid, 'name' => $profiles[$pid]?->display_name ?? '—', 'phone' => $profiles[$pid]?->phone,
                'gross' => round($p['gross'], 2), 'returns' => round($p['returns'], 2), 'excluded' => round($p['excluded'], 2), 'unpaid' => round($p['unpaid'], 2),
                'eligible' => $elig, 'tier' => $ti, 'rate' => (float) ($tiers[$ti]['rate'] ?? 0), 'amount' => $amt, 'projected' => $hold ? 0 : $proj, 'projectedShown' => $proj,
                'projTier' => self::tierIndex($tiers, $elig * $days / $elapsed),
                'hold' => $hold, 'accrue' => $hold ? 0 : $amt, 'accrued' => (float) ($accrued[$pid] ?? 0), 'settled' => (float) ($paidOut[$pid] ?? 0),
                'next' => $next, 'gap' => $next ? max(0, $next['from'] - $elig) : 0,
                'pct' => $next ? (int) min(100, max(0, round(($elig - $tiers[$ti]['from']) / max(1, $next['from'] - $tiers[$ti]['from']) * 100))) : 100,
            ];
        })->sortByDesc('eligible')->values();

        return ['rows' => $rows, 'exclusions' => $exList, 'start' => $start, 'end' => $end, 'elapsed' => $elapsed, 'days' => $days];
    }

    /** profile id → accrued amount already posted for the period. */
    public function postedAccruals(CommissionScheme $s, CarbonImmutable $start): Collection
    {
        return CommissionAccrual::where('commission_scheme_id', $s->id)->whereDate('month', '>=', $start->toDateString())
            ->whereHas('journalEntry', fn ($q) => $q->whereIn('status', [JournalEntryStatus::POSTED->value, JournalEntryStatus::DRAFT->value, JournalEntryStatus::SCHEDULED->value]))
            ->get()->groupBy('master_profile_id')->map->sum('amount');
    }

    /* ------------------------------------------------------------ exclusions */

    public function exclude(CommissionScheme $s, int $orderId, ?int $itemId, string $reason): void
    {
        CommissionExclusion::where('commission_scheme_id', $s->id)->where('order_id', $orderId)->where('order_item_id', $itemId)->delete();
        CommissionExclusion::create(['commission_scheme_id' => $s->id, 'order_id' => $orderId, 'order_item_id' => $itemId, 'source' => 'manual', 'rule' => 'manual', 'reason' => $reason, 'user_id' => auth()->id()]);
        $this->log($orderId, "Commission ({$s->name}): excluded — {$reason}");
    }

    public function includeBack(CommissionScheme $s, int $orderId, ?int $itemId, string $reason): void
    {
        CommissionExclusion::where('commission_scheme_id', $s->id)->where('order_id', $orderId)->where('order_item_id', $itemId)->delete();
        CommissionExclusion::create(['commission_scheme_id' => $s->id, 'order_id' => $orderId, 'order_item_id' => $itemId, 'source' => 'auto', 'rule' => null, 'reason' => $reason, 'included_back' => true, 'user_id' => auth()->id()]);
        $this->log($orderId, "Commission ({$s->name}): included back — {$reason}");
    }

    protected function log(int $orderId, string $text): void
    {
        $pid = Order::with('customer:id,master_profile_id')->find($orderId)?->customer?->master_profile_id;
        if ($pid) {
            MasterProfile::find($pid)?->logActivity('note', 'INV-' . str_pad((string) $orderId, 6, '0', STR_PAD_LEFT) . ' · ' . $text);
        }
    }

    /* ------------------------------------------------------------ accrual & settlement */

    protected function costLedger(CommissionScheme $s): int
    {
        return $this->ledgers->require($s->accounting === 'expense' ? self::EXPENSE : self::REBATE);
    }

    /** Month-end draft: Dr Sales rebate (or Commission expense) / Cr Rebate payable for the change since the last accrual. */
    public function accrue(CommissionScheme $s, CarbonImmutable $month, bool $system = false): ?JournalEntry
    {
        $c = $this->compute($s, $month->endOfMonth()->min(CarbonImmutable::today()));
        $lines = [];
        $made = [];
        $payable = $this->ledgers->require(self::PAYABLE);
        $cost = $this->costLedger($s);

        foreach ($c['rows'] as $r) {
            $delta = round($r['accrue'] - $r['accrued'], 2);
            if (abs($delta) < 1) {
                continue;
            }
            $profile = MasterProfile::find($r['profile_id']);
            $made[] = $r + ['delta' => $delta];
            $lines[] = $delta > 0
                ? ['account_id' => $cost, 'debit' => $delta, 'memo' => $r['name']]
                : ['account_id' => $cost, 'credit' => abs($delta), 'memo' => $r['name']];
            $lines[] = ($delta > 0 ? ['account_id' => $payable, 'credit' => $delta] : ['account_id' => $payable, 'debit' => abs($delta)]) + $this->vouchers->partyFields($payable, $profile);
        }

        if (! $lines) {
            return null;
        }

        return DB::transaction(function () use ($s, $month, $system, $lines, $made) {
            $entry = $this->vouchers->draft([
                'type' => 'journal', 'transaction_type' => \App\Enums\Accounts\TransactionType::COMMISSION->value, 'source' => $system ? 'system' : 'manual',
                'date' => min($month->endOfMonth()->toDateString(), today()->toDateString()),
                'description' => "{$s->name} — accrual " . $month->format('M Y'),
                'lines' => $lines,
            ]);
            foreach ($made as $r) {
                CommissionAccrual::create([
                    'commission_scheme_id' => $s->id, 'master_profile_id' => $r['profile_id'], 'month' => $month->startOfMonth()->toDateString(),
                    'eligible' => $r['eligible'], 'rate' => $r['rate'], 'amount' => $r['delta'], 'journal_entry_id' => $entry->id,
                ]);
            }

            return $entry;
        });
    }

    /**
     * Settle one customer: Dr Rebate payable (what was accrued) + Dr rebate /
     * expense (the rest) / Cr customer credit (credit note), receivable
     * (deduct from due) or cash.
     */
    public function settle(CommissionScheme $s, int $profileId, float $amount, string $method, ?int $cashAccountId, CarbonImmutable $start, CarbonImmutable $end, float $rate = 0, bool $advance = false): JournalEntry
    {
        if ($amount < 1) {
            throw new RuntimeException('Nothing to settle.');
        }
        $profile = MasterProfile::findOrFail($profileId);
        $payable = $this->ledgers->require(self::PAYABLE);
        $accrued = (float) ($this->postedAccruals($s, $start)[$profileId] ?? 0);
        $settled = (float) CommissionSettlement::where('commission_scheme_id', $s->id)->where('master_profile_id', $profileId)->whereDate('period_start', $start->toDateString())
            ->whereHas('journalEntry', fn ($q) => $q->whereIn('status', [JournalEntryStatus::POSTED->value, JournalEntryStatus::DRAFT->value, JournalEntryStatus::SCHEDULED->value]))->sum('amount');
        $fromPayable = round(min($amount, max(0, $accrued - $settled)), 2);
        $rest = round($amount - $fromPayable, 2);
        $method = $method === 'choose' ? 'credit_note' : $method;

        $lines = [];
        if ($fromPayable > 0) {
            $lines[] = ['account_id' => $payable, 'debit' => $fromPayable] + $this->vouchers->partyFields($payable, $profile);
        }
        if ($rest > 0) {
            $lines[] = ['account_id' => $this->costLedger($s), 'debit' => $rest];
        }
        $credit = match ($method) {
            'cash' => $cashAccountId ?: throw new RuntimeException('Choose the account to pay from.'),
            'deduct_due' => $this->ledgers->require(LedgerAccounts::RECEIVABLE),
            default => $this->ledgers->require(LedgerAccounts::CUSTOMER_CREDIT),
        };
        $lines[] = ['account_id' => $credit, 'credit' => $amount] + ($method === 'cash' ? [] : $this->vouchers->partyFields($credit, $profile));

        return DB::transaction(function () use ($s, $profile, $amount, $method, $cashAccountId, $start, $end, $rate, $advance, $lines) {
            $entry = $this->vouchers->draft([
                'type' => $method === 'cash' ? 'payment' : 'journal', 'transaction_type' => \App\Enums\Accounts\TransactionType::COMMISSION->value,
                'date' => today()->toDateString(), 'profile_id' => $profile->id, 'money_account_id' => $method === 'cash' ? $cashAccountId : null,
                'description' => ($advance ? 'Advance commission' : 'Commission settlement') . " — {$s->name} · {$profile->display_name}",
                'lines' => $lines,
            ]);
            CommissionSettlement::create([
                'commission_scheme_id' => $s->id, 'master_profile_id' => $profile->id, 'period_start' => $start->toDateString(), 'period_end' => $end->toDateString(),
                'rate' => $rate, 'amount' => $amount, 'method' => $method, 'advance' => $advance, 'journal_entry_id' => $entry->id,
            ]);
            $profile->logActivity('note', ($advance ? 'Advance commission ' : 'Commission settled ') . Bdt::money($amount) . " · {$entry->ref()}");

            return $entry;
        });
    }

    /** "Name, এই বছর commission-যোগ্য বিক্রি ৳X · tier Y% · জমা ৳Z · আর ৳W হলে N%" */
    public function statementText(CommissionScheme $s, array $r): string
    {
        $unit = $s->basis === 'quantity' ? ' pcs' : '';
        $v = fn ($n) => $s->basis === 'quantity' ? number_format($n) . $unit : Bdt::money($n);

        $rate = fn ($x) => $s->basis === 'quantity' ? '৳' . $x . '/pc' : $x . '%';

        return "{$r['name']}, {$s->name}: যোগ্য বিক্রি {$v($r['eligible'])} · rate {$rate($r['rate'])} · জমা " . Bdt::money($r['amount'])
            . ($r['next'] ? " · আর {$v($r['gap'])} হলে {$rate($r['next']['rate'])}" : '') . ' — ' . \App\Support\AccountsSettings::businessName();
    }
}
