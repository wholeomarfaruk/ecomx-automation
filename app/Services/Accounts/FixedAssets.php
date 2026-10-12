<?php

namespace App\Services\Accounts;

use App\Enums\Accounts\JournalEntryStatus;
use App\Enums\Accounts\TransactionType;
use App\Models\FixedAsset;
use App\Models\FixedAssetDepreciationEntry;
use App\Models\FixedAssetDisposal;
use App\Models\JournalEntry;
use Carbon\CarbonImmutable;
use RuntimeException;

/**
 * Fixed Assets page (spec §5.9 / §7.15). Straight line by months on
 * (cost − salvage). The monthly run, a repair and a disposal are drafts;
 * the asset itself (accumulated depreciation, disposal) only changes when
 * the draft posts — see Vouchers::afterPost → self::afterPost.
 */
class FixedAssets
{
    public const CATEGORIES = ['Machinery', 'Furniture', 'Vehicle', 'Electronics', 'Building', 'Other'];

    public const FIXED = '1500';
    public const ACCUMULATED = '1510';
    public const EXPENSE = '5500';
    public const REPAIRS = '5160';
    public const GAIN = '4200';
    public const LOSS = '5700';

    public function __construct(protected Vouchers $vouchers, protected \App\Services\Profiles\LedgerAccounts $ledgers) {}

    /** monthly, months total / done / left, accumulated, book value, % depreciated */
    public static function calc(FixedAsset $a): array
    {
        $base = max(0, (float) $a->cost - (float) $a->salvage_value);
        $life = max(1, (int) $a->useful_life_months);
        $monthly = round($base / $life, 2);
        $acc = (float) $a->accumulated_depreciation;
        $done = $monthly > 0 ? min($life, (int) round($acc / $monthly)) : 0;

        return [
            'monthly' => $monthly, 'life' => $life, 'done' => $done, 'left' => max(0, $life - $done),
            'accum' => $acc, 'book' => round((float) $a->cost - $acc, 2),
            'pct' => $base > 0 ? (int) min(100, round($acc / $base * 100)) : 100,
            'full' => $acc >= $base - 0.01,
        ];
    }

    /** Book value at the end of each year from purchase to end of life (max 8 bars). */
    public static function years(FixedAsset $a): array
    {
        $c = self::calc($a);
        $start = CarbonImmutable::parse($a->purchase_date)->startOfMonth();
        $out = [];

        for ($y = $start->year; count($out) < 8; $y++) {
            $months = min($c['life'], ($y - $start->year) * 12 + (12 - $start->month + 1));
            $out[] = ['year' => $y, 'book' => max((float) $a->salvage_value, (float) $a->cost - $c['monthly'] * $months), 'past' => $y <= today()->year];
            if ($months >= $c['life']) {
                break;
            }
        }

        return $out;
    }

    /** The run waiting (draft) or done (posted) for a month, if any. */
    public function runFor(CarbonImmutable $month): ?JournalEntry
    {
        return JournalEntry::where('purpose', 'depreciation_' . $month->startOfMonth()->toDateString())
            ->whereIn('status', [JournalEntryStatus::DRAFT->value, JournalEntryStatus::SCHEDULED->value, JournalEntryStatus::POSTED->value])
            ->latest('id')->first();
    }

    /** asset id → this month's amount, for assets in use not yet depreciated for the month. */
    public function due(CarbonImmutable $month): array
    {
        $period = $month->startOfMonth()->toDateString();
        $done = FixedAssetDepreciationEntry::where('period', $period)->pluck('fixed_asset_id')->all();

        return FixedAsset::where('status', 'active')->whereDate('purchase_date', '<=', $month->endOfMonth()->toDateString())->whereNotIn('id', $done ?: [0])->get()
            ->mapWithKeys(fn ($a) => [$a->id => $a->monthlyDepreciationAmount()])->filter(fn ($v) => $v > 0)->all();
    }

    /** One draft journal for the month: Dr Depreciation (a line per asset) / Cr Accumulated depreciation. */
    public function run(CarbonImmutable $month, bool $system = false): JournalEntry
    {
        if ($existing = $this->runFor($month)) {
            throw new RuntimeException($month->format('F') . " depreciation is already {$existing->status->label()} ({$existing->ref()}).");
        }

        $due = $this->due($month);
        if (! $due) {
            throw new RuntimeException('Nothing to depreciate for ' . $month->format('F') . '.');
        }

        $names = FixedAsset::whereIn('id', array_keys($due))->pluck('name', 'id');
        $exp = $this->ledgers->require(self::EXPENSE);
        $total = round(array_sum($due), 2);
        $lines = [];
        foreach ($due as $id => $amount) {
            $lines[] = ['account_id' => $exp, 'debit' => $amount, 'memo' => $names[$id] ?? "Asset #{$id}"];
        }
        $lines[] = ['account_id' => $this->ledgers->require(self::ACCUMULATED), 'credit' => $total];

        return $this->vouchers->draft([
            'type' => 'journal', 'transaction_type' => TransactionType::DEPRECIATION->value, 'source' => $system ? 'system' : 'manual',
            'date' => min($month->endOfMonth()->toDateString(), today()->toDateString()),
            'description' => $month->format('F Y') . ' depreciation · ' . count($due) . ' assets',
            'purpose' => 'depreciation_' . $month->startOfMonth()->toDateString(),
            'meta' => ['depreciation' => $due, 'period' => $month->startOfMonth()->toDateString()], 'lines' => $lines,
        ]);
    }

    /** Draft expense in Repairs & maintenance for the asset. */
    public function repair(FixedAsset $a, float $amount, int $accountId, string $date, ?string $note, bool $stillInRepair): JournalEntry
    {
        $repairs = $this->ledgers->id(self::REPAIRS) ?: $this->ledgers->require('5100');
        $entry = $this->vouchers->draft([
            'type' => 'expense', 'date' => $date, 'description' => 'Repair — ' . $a->name . ($note ? " · {$note}" : ''),
            'money_account_id' => $accountId, 'source_type' => FixedAsset::class, 'source_id' => $a->id, 'purpose' => 'repair_' . now()->format('YmdHis'),
            'lines' => [['account_id' => $repairs, 'debit' => $amount], ['account_id' => $accountId, 'credit' => $amount]],
        ]);
        $a->update(['in_repair_since' => $stillInRepair ? ($a->in_repair_since ?? $date) : null]);

        return $entry;
    }

    /** Draft disposal: remove cost and accumulated depreciation, money in, gain / loss as the plug. */
    public function dispose(FixedAsset $a, float $proceeds, ?int $accountId, string $date): JournalEntry
    {
        $book = $a->bookValue();
        $gl = round($proceeds - $book, 2);
        $lines = [];
        if ($proceeds > 0) {
            $lines[] = ['account_id' => $accountId, 'debit' => $proceeds];
        }
        if ((float) $a->accumulated_depreciation > 0) {
            $lines[] = ['account_id' => $this->ledgers->require(self::ACCUMULATED), 'debit' => (float) $a->accumulated_depreciation];
        }
        if ($gl < 0) {
            $lines[] = ['account_id' => $this->ledgers->require(self::LOSS), 'debit' => abs($gl)];
        }
        $lines[] = ['account_id' => $a->category_account_id, 'credit' => (float) $a->cost];
        if ($gl > 0) {
            $lines[] = ['account_id' => $this->ledgers->require(self::GAIN), 'credit' => $gl];
        }

        return $this->vouchers->draft([
            'type' => 'journal', 'transaction_type' => TransactionType::ASSET_DISPOSAL->value, 'date' => $date,
            'description' => ($proceeds > 0 ? 'Asset sold' : 'Asset disposed') . " — {$a->name}", 'money_account_id' => $proceeds > 0 ? $accountId : null,
            'source_type' => FixedAsset::class, 'source_id' => $a->id, 'purpose' => 'disposal_' . now()->format('YmdHis'),
            'meta' => ['disposal' => ['asset_id' => $a->id, 'proceeds' => $proceeds, 'book_value' => $book, 'gain_loss' => $gl]], 'lines' => $lines,
        ]);
    }

    /** Called when a voucher posts. */
    public static function afterPost(JournalEntry $entry, array $meta): void
    {
        foreach ((array) ($meta['depreciation'] ?? []) as $id => $amount) {
            $period = $meta['period'];
            if (FixedAssetDepreciationEntry::where('fixed_asset_id', $id)->where('period', $period)->exists()) {
                continue;
            }
            FixedAssetDepreciationEntry::create(['fixed_asset_id' => $id, 'period' => $period, 'amount' => $amount, 'journal_entry_id' => $entry->id]);
            FixedAsset::whereKey($id)->increment('accumulated_depreciation', $amount);
        }

        if ($d = $meta['disposal'] ?? null) {
            FixedAssetDisposal::create([
                'fixed_asset_id' => $d['asset_id'], 'disposed_at' => $entry->entry_date->toDateString(), 'proceeds' => $d['proceeds'],
                'book_value' => $d['book_value'], 'gain_loss_amount' => $d['gain_loss'], 'journal_entry_id' => $entry->id,
            ]);
            FixedAsset::whereKey($d['asset_id'])->update(['status' => 'disposed', 'in_repair_since' => null]);
        }
    }

    public static function nextCode(): string
    {
        $n = (int) FixedAsset::where('code', 'like', 'FA-%')->get(['code'])->map(fn ($a) => (int) substr($a->code, 3))->max();

        return 'FA-' . str_pad((string) ($n + 1), 3, '0', STR_PAD_LEFT);
    }
}
