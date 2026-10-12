<?php

namespace App\Services\Accounts;

use App\Enums\Accounts\JournalEntryStatus;
use App\Models\Customer;
use App\Models\MasterProfile;
use App\Models\Supplier;
use App\Services\Profiles\LedgerAccounts;
use App\Services\Profiles\ProfileBalances;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Receivable / payable aging (spec §7.8): each party's posted balance is
 * aged first-in-first-out over its own debits (receivable) or credits
 * (payable) — payments settle the oldest first, so what is left is the
 * newest part. Buckets 0–30 / 31–60 / 61–90 / 90+ days.
 */
class Aging
{
    public const BUCKETS = ['0–30', '31–60', '61–90', '90+'];

    public function __construct(protected LedgerAccounts $ledgers, protected ProfileBalances $balances) {}

    /**
     * @return Collection<int, array{profile_id: int, total: float, buckets: array<int, float>, oldest: ?CarbonImmutable, items: int}>
     */
    public function receivables(): Collection
    {
        return $this->run(LedgerAccounts::RECEIVABLE, 'receivable', 'debit');
    }

    public function payables(): Collection
    {
        return $this->run(LedgerAccounts::PAYABLE, 'payable', 'credit');
    }

    protected function run(string $code, string $column, string $side): Collection
    {
        $due = DB::query()->fromSub($this->balances->query(), 'pb')->where("pb.{$column}", '>', 0.5)
            ->pluck("pb.{$column}", 'pb.profile_id');

        if ($due->isEmpty()) {
            return collect();
        }

        $accountId = $this->ledgers->id($code);
        $expr = $this->balances->profileIdExpression();
        $today = CarbonImmutable::today();
        $out = collect();

        foreach ($due->keys()->chunk(1000) as $ids) {
            $lines = DB::table('journal_entry_lines as jl')
                ->join('journal_entries as je', 'je.id', '=', 'jl.journal_entry_id')
                ->leftJoin('customers as c', fn ($j) => $j->on('c.id', '=', 'jl.subledger_id')->where('jl.subledger_type', Customer::class))
                ->leftJoin('suppliers as s', fn ($j) => $j->on('s.id', '=', 'jl.subledger_id')->where('jl.subledger_type', Supplier::class))
                ->leftJoin('loans as l', 'l.loan_payable_account_id', '=', 'jl.account_id')
                ->where('jl.account_id', $accountId)
                ->where("jl.{$side}", '>', 0)
                ->where('je.status', JournalEntryStatus::POSTED->value)
                ->whereIn(DB::raw($expr), $ids->all())
                ->orderByDesc('je.entry_date')->orderByDesc('je.id')
                ->get([DB::raw("{$expr} as pid"), 'je.entry_date', "jl.{$side} as amount"])
                ->groupBy('pid');

            foreach ($ids as $pid) {
                $remaining = round((float) $due[$pid], 2);
                $buckets = [0.0, 0.0, 0.0, 0.0];
                $oldest = null;
                $items = 0;

                foreach ($lines->get($pid, collect()) as $line) {
                    if ($remaining <= 0.004) {
                        break;
                    }
                    $take = min($remaining, (float) $line->amount);
                    $date = CarbonImmutable::parse($line->entry_date);
                    $buckets[$this->bucket($date, $today)] += $take;
                    $remaining = round($remaining - $take, 2);
                    $oldest = $date;
                    $items++;
                }

                if ($remaining > 0.004) {
                    // Balance older than any debit on record (opening balance carried in).
                    $buckets[3] += $remaining;
                }

                $out->push([
                    'profile_id' => (int) $pid,
                    'total'      => round((float) $due[$pid], 2),
                    'buckets'    => array_map(fn ($b) => round($b, 2), $buckets),
                    'oldest'     => $oldest,
                    'items'      => max(1, $items),
                ]);
            }
        }

        return $out;
    }

    public function bucket(CarbonImmutable $date, CarbonImmutable $today): int
    {
        $days = (int) $date->diffInDays($today);

        return $days <= 30 ? 0 : ($days <= 60 ? 1 : ($days <= 90 ? 2 : 3));
    }

    /** Totals + counts per bucket across all rows. */
    public static function summarize(Collection $rows): array
    {
        return collect(range(0, 3))->map(fn ($i) => [
            'amount' => round($rows->sum(fn ($r) => $r['buckets'][$i]), 2),
            'count'  => $rows->filter(fn ($r) => $r['buckets'][$i] > 0.5)->count(),
        ])->all();
    }
}
