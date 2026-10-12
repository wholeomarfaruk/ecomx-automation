<?php

namespace App\Services\Accounts;

use App\Enums\Accounts\JournalEntryStatus;
use App\Models\Account;
use App\Models\CashCount;
use App\Models\DayClosing;
use App\Models\Installment;
use App\Models\JournalEntry;
use App\Models\Order;
use App\Models\ReportArchive;
use App\Services\Profiles\LedgerAccounts;
use App\Support\AccountsSettings;
use App\Support\Bdt;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * দিনের হিসাব (spec §5.11 / §7.20): the day's tiles, opening / in / out /
 * closing per money account, the checklist before closing, and close /
 * reopen (each close stores a snapshot and an archived copy; reopening
 * needs permission and the next close is a new version). Open days
 * auto-close at 23:59.
 */
class DailyReport
{
    public function __construct(protected LedgerAccounts $ledgers, protected Reports $reports) {}

    public function data(CarbonImmutable $date): array
    {
        $d = $date->toDateString();
        $cash = collect($this->ledgers->cashAccounts());
        $cashIds = $cash->pluck('id')->all();
        $accounts = Account::whereIn('id', $cashIds)->get()->keyBy('id');
        $before = $this->reports->sums(null, $date->subDay());

        // Per money account: opening / in / out / closing.
        $day = DB::table('journal_entry_lines as l')->join('journal_entries as e', 'e.id', '=', 'l.journal_entry_id')
            ->where('e.status', JournalEntryStatus::POSTED->value)->whereDate('e.entry_date', $d)
            ->get(['l.journal_entry_id', 'l.account_id', 'l.debit', 'l.credit', 'e.voucher_type', 'e.transaction_type']);
        $byEntry = $day->groupBy('journal_entry_id');

        $rows = $cash->map(function ($a) use ($accounts, $before, $day) {
            $acc = $accounts[$a['id']];
            $s = $before[$a['id']] ?? null;
            $open = $s ? $acc->normal_balance->signedDelta((float) $s->d, (float) $s->c) : 0;
            $mine = $day->where('account_id', $a['id']);
            $in = (float) $mine->sum('debit');
            $out = (float) $mine->sum('credit');

            return ['id' => $a['id'], 'name' => $a['name'], 'open' => round($open, 2), 'in' => $in, 'out' => $out, 'close' => round($open + $in - $out, 2)];
        })->values();

        // Money in / out excluding transfers between our own accounts.
        $courier = Account::where('subtype', 'courier')->pluck('id')->all();
        $in = $out = $transfer = $cod = 0;
        $kinds = ['expense' => 0, 'payment' => 0];
        foreach ($byEntry as $ls) {
            $cashLines = $ls->filter(fn ($l) => in_array($l->account_id, $cashIds));
            if ($cashLines->isEmpty()) {
                continue;
            }
            $other = $ls->reject(fn ($l) => in_array($l->account_id, $cashIds));
            $delta = round($cashLines->sum(fn ($l) => (float) $l->debit - (float) $l->credit), 2);
            if ($other->isEmpty()) {
                $transfer += (float) $cashLines->sum('debit');

                continue;
            }
            if ($delta > 0) {
                $in += $delta;
                if ($other->contains(fn ($l) => in_array($l->account_id, $courier))) {
                    $cod += $delta;
                }
            } elseif ($delta < 0) {
                $out += -$delta;
                $t = $ls->first()->voucher_type;
                if (isset($kinds[$t])) {
                    $kinds[$t]++;
                }
            }
        }
        $kisti = (float) JournalEntry::where('status', JournalEntryStatus::POSTED->value)->whereDate('entry_date', $d)
            ->whereIn('id', Installment::whereNotNull('journal_entry_id')->select('journal_entry_id'))->sum('amount');

        // Sales, returns, new dues.
        $acc = Account::get(['id', 'code', 'parent_id']);
        $codes = fn (array $c) => $acc->filter(fn ($a) => in_array($a->code, $c, true) || in_array($acc->firstWhere('id', $a->parent_id)?->code, $c, true))->pluck('id')->all();
        $salesIds = $codes(['4000', '4050']);
        $arIds = $codes(['1100']);
        $sales = (float) $day->whereIn('account_id', $salesIds)->sum(fn ($l) => (float) $l->credit - (float) $l->debit);
        $orders = JournalEntry::where('status', JournalEntryStatus::POSTED->value)->whereDate('entry_date', $d)->where('source_type', Order::class)->where('purpose', 'like', 'sale%')->count();
        $returns = $day->whereIn('account_id', $codes(['4900']))->pluck('journal_entry_id')->unique()->count();
        $dueLines = $day->whereIn('account_id', $arIds)->where('debit', '>', 0);
        $newDue = (float) $dueLines->sum('debit');

        // Stock movement (Inventory module) + value.
        $stockIn = $stockOut = null;
        if (! app(\App\Services\StockService::class)->usesOwnStock()) {
            $mv = DB::table('inventory_stock_movements')->whereDate('created_at', $d)->selectRaw('SUM(CASE WHEN quantity > 0 THEN quantity ELSE 0 END) as i, SUM(CASE WHEN quantity < 0 THEN -quantity ELSE 0 END) as o')->first();
            $stockIn = (float) ($mv->i ?? 0);
            $stockOut = (float) ($mv->o ?? 0);
        }
        $inventory = Account::where('code', '1200')->first();
        $sv = $inventory ? ($this->reports->sums(null, $date)[$inventory->id] ?? null) : null;
        $stockValue = $sv ? $inventory->normal_balance->signedDelta((float) $sv->d, (float) $sv->c) : 0;

        $closing = DayClosing::latestFor($d);

        return [
            'date' => $date, 'rows' => $rows, 'transfer' => $transfer,
            'tiles' => [
                'sales' => [$sales, "{$orders} orders · {$returns} returns"],
                'in' => [$in, 'COD ' . Bdt::money($cod) . ' · কিস্তি ' . Bdt::money($kisti)],
                'out' => [$out, "Expense {$kinds['expense']} · payment {$kinds['payment']}"],
                'due' => [$newDue, 'Credit sale ' . $dueLines->pluck('journal_entry_id')->unique()->count()],
                'stock' => [$stockIn === null ? null : '+' . (float) $stockIn . ' / −' . (float) $stockOut, 'Value ' . Bdt::money($stockValue)],
            ],
            'checklist' => $this->checklist($date, $cash->all()),
            'closing' => $closing, 'closed' => $closing && $closing->status !== 'open',
        ];
    }

    /** [state ok|warn|bad, text, link label, route name, route params] */
    public function checklist(CarbonImmutable $date, array $cash): array
    {
        $d = $date->toDateString();
        $out = [];

        $drafts = JournalEntry::whereIn('status', [JournalEntryStatus::DRAFT->value, JournalEntryStatus::SCHEDULED->value])->whereDate('entry_date', '<=', $d);
        $n = (clone $drafts)->count();
        $out[] = $n ? ['bad', "{$n}টা draft এখনো post হয়নি (" . Bdt::money((clone $drafts)->sum('amount')) . ')', 'Review', 'accounts.posting', []]
            : ['ok', 'সব entry post হয়েছে', 'View', 'accounts.posting', []];

        $counts = CashCount::whereDate('count_date', $d)->get();
        $diff = $counts->whereNull('adjustment_journal_entry_id')->filter(fn ($c) => abs((float) $c->difference) >= 1);
        $out[] = match (true) {
            $counts->isEmpty() => ['warn', 'আজ cash count হয়নি', 'Count', 'accounts.cash-bank', []],
            $diff->isNotEmpty() => ['warn', 'Cash count-এ ' . Bdt::money(abs((float) $diff->sum('difference'))) . ((float) $diff->sum('difference') < 0 ? ' কম' : ' বেশি') . ', এখনো record হয়নি', 'Record', 'accounts.cash-bank', []],
            default => ['ok', 'Cash count মিলেছে', 'View', 'accounts.cash-bank', []],
        };

        $stale = collect($cash)->filter(fn ($a) => ($a['subtype'] ?? '') !== 'cash' && (! $a['last_reconciled_at'] || CarbonImmutable::parse($a['last_reconciled_at'])->lt($date->subDays(7))));
        $out[] = $stale->isEmpty() ? ['ok', 'Bank / MFS balance মিলানো আছে', 'View', 'accounts.cash-bank', []]
            : ['warn', $stale->pluck('name')->take(3)->implode(', ') . ' — ৭ দিনে reconcile হয়নি', 'Match', 'accounts.cash-bank', []];

        $limit = AccountsSettings::attachmentThreshold();
        $missing = JournalEntry::where('voucher_type', 'expense')->whereDate('entry_date', $d)->whereIn('status', [JournalEntryStatus::POSTED->value, JournalEntryStatus::DRAFT->value])
            ->when($limit > 0, fn ($q) => $q->where('amount', '>', $limit))->doesntHave('attachments')->count();
        $out[] = $missing ? ['warn', "{$missing}টা expense-এ receipt নেই", 'Fix', 'accounts.expenses', []] : ['ok', 'আজকের সব expense-এ receipt আছে', 'View', 'accounts.expenses', []];

        return $out;
    }

    public function summaryText(array $data): string
    {
        $t = $data['tiles'];

        return 'দিনের হিসাব ' . $data['date']->format('d M') . ': বিক্রি ' . Bdt::money($t['sales'][0]) . ', আসলো ' . Bdt::money($t['in'][0])
            . ', গেলো ' . Bdt::money($t['out'][0]) . ', cash+bank ' . Bdt::money($data['rows']->sum('close')) . ' — ' . AccountsSettings::businessName();
    }

    public function close(CarbonImmutable $date, ?string $note, bool $auto = false): DayClosing
    {
        $d = $date->toDateString();
        $latest = DayClosing::latestFor($d);
        if ($latest && $latest->status !== 'open') {
            throw new RuntimeException($date->format('d M') . ' is already closed.');
        }

        $data = $this->data($date);
        $short = (float) CashCount::whereDate('count_date', $d)->get()->sum(fn ($c) => min(0, (float) $c->difference));
        $version = $latest ? $latest->version + 1 : 1;

        $path = 'report-archives/daily/' . $date->format('Y/m') . "/daily-{$d}-v{$version}.html";
        Storage::disk('local')->put($path, view('accounts.reports.daily-print', ['data' => $data, 'note' => $note, 'version' => $version, 'auto' => $auto])->render());

        $closing = DayClosing::create([
            'date' => $d, 'version' => $version, 'status' => $auto ? 'auto' : 'closed', 'note' => $note, 'short_amount' => abs($short),
            'snapshot' => ['tiles' => $data['tiles'], 'rows' => $data['rows']->all(), 'transfer' => $data['transfer'], 'checklist' => $data['checklist']],
            'pdf_path' => $path, 'closed_by' => $auto ? null : auth()->id(), 'closed_at' => now(),
        ]);
        ReportArchive::create(['report_key' => 'daily', 'kind' => 'daily', 'title' => 'Daily report · ' . $date->format('d M Y') . ($version > 1 ? " · v{$version}" : ''), 'period' => $d, 'path' => $path]);

        return $closing;
    }

    public function reopen(CarbonImmutable $date): void
    {
        $latest = DayClosing::latestFor($date->toDateString());
        if (! $latest || $latest->status === 'open') {
            throw new RuntimeException('Day is not closed.');
        }
        $latest->update(['status' => 'open', 'reopened_by' => auth()->id(), 'reopened_at' => now()]);
    }
}
