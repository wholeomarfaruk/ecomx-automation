<?php

namespace App\Services\Accounts;

use App\Actions\Accounts\PostJournalEntry;
use App\Actions\Accounts\ReverseJournalEntry;
use App\Enums\Accounts\JournalEntryStatus;
use App\Enums\Accounts\TransactionType;
use App\Enums\Purchase\SupplierInvoiceType;
use App\Models\Account;
use App\Models\AccountsCustomerInvoice;
use App\Models\AccountsPaymentAllocation;
use App\Models\AccountsSupplierBill;
use App\Models\Customer;
use App\Models\FiscalPeriod;
use App\Models\JournalEntry;
use App\Models\MasterProfile;
use App\Models\Supplier;
use App\Models\SupplierInvoice;
use App\Models\User;
use App\Models\VoucherAttachment;
use App\Models\VoucherEvent;
use App\Services\Profiles\LedgerAccounts;
use App\Support\AccountsAccess;
use App\Support\AccountsPeriod;
use App\Support\AccountsSettings;
use App\Support\Bdt;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

/**
 * The Accounts Panel voucher workflow (spec §7.2–7.3), on journal_entries:
 *
 *   draft ──(checks pass, approved if over threshold)──► posted ──► reversed
 *     │ ▲                                                  (opposite journal)
 *     ▼ │ resubmit
 *   rejected (reason required)        scheduled ──(its date)──► posted
 *
 * Only posted entries count anywhere. Rules: lock date, maker-checker,
 * approval threshold, attachment threshold, posted = immutable. Every state
 * change writes a voucher_event.
 */
class Vouchers
{
    /** Panel voucher type => transaction type used for reporting. */
    public const TRANSACTION_TYPES = [
        'receipt' => TransactionType::PAYMENT_RECEIPT,
        'payment' => TransactionType::SUPPLIER_PAYMENT,
        'journal' => TransactionType::JOURNAL,
        'expense' => TransactionType::MANUAL_EXPENSE,
        'contra'  => TransactionType::TRANSFER,
        'emi'     => TransactionType::LOAN_REPAYMENT,
        'salary'  => TransactionType::SALARY_PAYMENT,
        'opening' => TransactionType::OPENING_BALANCE,
        'setoff'  => TransactionType::SET_OFF,
    ];

    /** Display names per voucher type (Posting Pending / Transactions "type" column). */
    public const TYPE_LABELS = [
        'receipt' => 'Receipt', 'payment' => 'Payment', 'journal' => 'Journal', 'expense' => 'Expense',
        'contra' => 'Transfer', 'emi' => 'Loan / EMI', 'salary' => 'Salary', 'opening' => 'Opening', 'setoff' => 'Set-off',
    ];

    public function __construct(
        protected PostJournalEntry $postJournalEntry,
        protected LedgerAccounts $ledgers,
    ) {}

    /* ------------------------------------------------------------------
     |  Create / edit
     * ------------------------------------------------------------------ */

    /**
     * Create a voucher as a draft (or scheduled when post-dated).
     *
     * @param  array{type: string, date: string, description?: ?string, lines: list<array>, profile_id?: ?int,
     *               party_label?: ?string, money_account_id?: ?int, source?: string, transaction_type?: string,
     *               source_type?: ?string, source_id?: ?int, purpose?: ?string}  $data
     * @param  list<UploadedFile>  $files
     */
    public function draft(array $data, array $files = [], ?User $by = null): JournalEntry
    {
        $type = $data['type'];
        abort_unless(isset(AccountsSettings::VOUCHER_TYPES[$type]), 422, "Unknown voucher type {$type}.");

        return DB::transaction(function () use ($data, $type, $files, $by) {
            $entry = $this->postJournalEntry->handle([
                'entry_date'       => $data['date'],
                'description'      => $data['description'] ?? null,
                'transaction_type' => $data['transaction_type'] ?? self::TRANSACTION_TYPES[$type]->value,
                'source_type'      => $data['source_type'] ?? null,
                'source_id'        => $data['source_id'] ?? null,
                'purpose'          => $data['purpose'] ?? null,
                'status'           => JournalEntryStatus::DRAFT->value,
            ], $data['lines']);

            $future = CarbonImmutable::parse($data['date'])->isAfter(today());

            $entry->update([
                'voucher_ref'      => $this->nextRef($type),
                'voucher_type'     => $type,
                'source'           => $data['source'] ?? 'manual',
                'amount'           => round((float) collect($data['lines'])->sum(fn ($l) => (float) ($l['debit'] ?? 0)), 2),
                'master_profile_id' => $data['profile_id'] ?? null,
                'party_label'      => $data['party_label'] ?? null,
                'money_account_id' => $data['money_account_id'] ?? null,
                'status'           => $future ? JournalEntryStatus::SCHEDULED : JournalEntryStatus::DRAFT,
                'scheduled_for'    => $future ? $data['date'] : null,
                'created_by'       => ($by ?? auth()->user())?->id,
                'meta'             => $data['meta'] ?? null,
            ]);

            foreach ($files as $file) {
                $this->attach($entry, $file, $by);
            }

            $this->event($entry, 'created', $entry->source === 'system' ? 'Auto-created' : null, $by);

            if ($future) {
                $this->event($entry, 'scheduled', 'Posts on ' . CarbonImmutable::parse($data['date'])->format('d M'), $by);
            }

            return $entry->fresh(['lines']);
        });
    }

    /**
     * A system job's voucher (payroll, depreciation, commission accrual,
     * recurring bill…): a draft, posted straight away when Settings ›
     * "System entry নিজে থেকে post" is on and every check passes.
     */
    public function system(array $data, array $files = []): JournalEntry
    {
        $entry = $this->draft(['source' => 'system'] + $data, $files);

        if (AccountsSettings::ruleOn('auto') && $entry->status === JournalEntryStatus::DRAFT) {
            try {
                $this->post($entry, system: true);
            } catch (RuntimeException) {
                // Stays in Posting Pending for a human.
            }
        }

        return $entry->fresh();
    }

    /** Replace a draft's / rejected voucher's lines and header ("Edit", "Edit & resubmit"). */
    public function update(JournalEntry $entry, array $data, array $files = []): JournalEntry
    {
        $this->assertEditable($entry);

        return DB::transaction(function () use ($entry, $data, $files) {
            $lines = $data['lines'];
            $this->assertBalanced($lines);

            $entry->lines()->delete();
            foreach ($lines as $l) {
                $entry->lines()->create([
                    'account_id'     => $l['account_id'],
                    'debit'          => $l['debit'] ?? 0,
                    'credit'         => $l['credit'] ?? 0,
                    'memo'           => $l['memo'] ?? null,
                    'subledger_type' => $l['subledger_type'] ?? null,
                    'subledger_id'   => $l['subledger_id'] ?? null,
                ]);
            }

            $future = CarbonImmutable::parse($data['date'])->isAfter(today());
            $wasRejected = $entry->status === JournalEntryStatus::REJECTED;

            $entry->update([
                'entry_date'        => $data['date'],
                'description'       => $data['description'] ?? $entry->description,
                'amount'            => round((float) collect($lines)->sum(fn ($l) => (float) ($l['debit'] ?? 0)), 2),
                'master_profile_id' => $data['profile_id'] ?? null,
                'party_label'       => $data['party_label'] ?? null,
                'money_account_id'  => $data['money_account_id'] ?? null,
                'status'            => $future ? JournalEntryStatus::SCHEDULED : JournalEntryStatus::DRAFT,
                'scheduled_for'     => $future ? $data['date'] : null,
                'approved_by'       => null,
                'approved_at'       => null,
                'rejected_by'       => null,
                'rejected_at'       => null,
                'reject_reason'     => null,
            ]);

            foreach ($files as $file) {
                $this->attach($entry, $file);
            }

            $this->event($entry, $wasRejected ? 'resubmitted' : 'edited');

            return $entry->fresh(['lines']);
        });
    }

    public function attach(JournalEntry $entry, UploadedFile $file, ?User $by = null): VoucherAttachment
    {
        $attachment = $entry->attachments()->create([
            'name'        => $file->getClientOriginalName(),
            'path'        => $file->store("voucher-attachments/{$entry->id}", 'local'),
            'mime'        => $file->getMimeType(),
            'size'        => $file->getSize(),
            'uploaded_by' => ($by ?? auth()->user())?->id,
        ]);

        $this->event($entry, 'attached', $attachment->name, $by);

        return $attachment;
    }

    /* ------------------------------------------------------------------
     |  Workflow
     * ------------------------------------------------------------------ */

    /**
     * Post a draft / scheduled voucher. A human post enforces permissions,
     * maker-checker and approval ("Approve & post" when the user may
     * approve); a system post (scheduler / auto-post) never self-approves.
     */
    public function post(JournalEntry $entry, ?User $user = null, bool $system = false): JournalEntry
    {
        $entry->loadMissing('lines');
        $user ??= auth()->user();

        if (! in_array($entry->status, [JournalEntryStatus::DRAFT, JournalEntryStatus::SCHEDULED], true)) {
            throw new RuntimeException("{$entry->ref()} is {$entry->status->label()} — only drafts can be posted.");
        }

        $blocking = collect($this->checks($entry, $system ? null : $user))->firstWhere('state', 'bad');
        if ($blocking) {
            throw new RuntimeException($blocking['text']);
        }

        if (! $system) {
            if (! AccountsAccess::can('post', $user)) {
                throw new RuntimeException('You do not have permission to post vouchers.');
            }

            if ($this->makerBlocked($entry, $user)) {
                throw new RuntimeException('নিজের entry নিজে post করা যাবে না — অন্য একজন review করে post করবে।');
            }
        }

        $approve = false;
        if ($this->needsApproval($entry)) {
            if ($system || ! AccountsAccess::can('approve', $user)) {
                throw new RuntimeException('Amount ' . Bdt::money(AccountsSettings::approvalThreshold()) . '-এর বেশি — Owner approval লাগবে।');
            }
            $approve = true;
        }

        $period = FiscalPeriod::query()->containingDate($entry->entry_date->toDateString())->first();
        if ($period?->is_locked) {
            throw new RuntimeException("{$entry->entry_date->format('d M Y')} is in a locked fiscal period.");
        }

        return DB::transaction(function () use ($entry, $user, $system, $approve, $period) {
            $fresh = JournalEntry::whereKey($entry->id)->lockForUpdate()->first();
            if (! in_array($fresh->status, [JournalEntryStatus::DRAFT, JournalEntryStatus::SCHEDULED], true)) {
                throw new RuntimeException("{$entry->ref()} was already {$fresh->status->label()}.");
            }

            $entry->update([
                'status'           => JournalEntryStatus::POSTED,
                'fiscal_period_id' => $entry->fiscal_period_id ?? $period?->id,
                'posted_by'        => $system ? null : $user?->id,
                'posted_at'        => now(),
                'approved_by'      => $approve ? $user?->id : $entry->approved_by,
                'approved_at'      => $approve ? now() : $entry->approved_at,
                'scheduled_for'    => null,
            ]);

            if ($approve) {
                $this->event($entry, 'approved', null, $user);
            }

            $this->applyOpenItems($entry);
            $this->afterPost($entry);
            $this->event($entry, 'posted', $system ? 'Auto-posted' : null, $system ? null : $user);

            activity('accounts')->causedBy($system ? null : $user)->performedOn($entry)->event('posted')
                ->log("Voucher {$entry->ref()} was posted");

            return $entry->fresh();
        });
    }

    public function reject(JournalEntry $entry, string $reason, ?User $user = null): void
    {
        $user ??= auth()->user();

        if (! AccountsAccess::can('post', $user) && ! AccountsAccess::can('approve', $user)) {
            throw new RuntimeException('You do not have permission to reject vouchers.');
        }
        if (! in_array($entry->status, [JournalEntryStatus::DRAFT, JournalEntryStatus::SCHEDULED], true)) {
            throw new RuntimeException('Only waiting vouchers can be rejected.');
        }
        if (trim($reason) === '') {
            throw new RuntimeException('কেন ফেরত পাঠাচ্ছেন — reason লাগবে।');
        }

        $entry->update([
            'status'        => JournalEntryStatus::REJECTED,
            'rejected_by'   => $user?->id,
            'rejected_at'   => now(),
            'reject_reason' => trim($reason),
        ]);

        $this->event($entry, 'rejected', trim($reason), $user);
    }

    /** Rejected → back to draft unchanged (the maker fixed it outside, or wants it re-reviewed). */
    public function resubmit(JournalEntry $entry): void
    {
        if ($entry->status !== JournalEntryStatus::REJECTED) {
            throw new RuntimeException('Only rejected vouchers can be resubmitted.');
        }

        $entry->update(['status' => JournalEntryStatus::DRAFT, 'rejected_by' => null, 'rejected_at' => null, 'reject_reason' => null]);
        $this->event($entry, 'resubmitted');
    }

    /** Change a scheduled / draft voucher's date ("Change date"). */
    public function reschedule(JournalEntry $entry, string $date): void
    {
        $this->assertEditable($entry);
        $future = CarbonImmutable::parse($date)->isAfter(today());

        $entry->update([
            'entry_date'    => $date,
            'scheduled_for' => $future ? $date : null,
            'status'        => $future ? JournalEntryStatus::SCHEDULED : JournalEntryStatus::DRAFT,
        ]);

        $this->event($entry, 'scheduled', ($future ? 'Posts on ' : 'Date changed to ') . CarbonImmutable::parse($date)->format('d M Y'));
    }

    public function delete(JournalEntry $entry, ?User $user = null): void
    {
        $user ??= auth()->user();

        if (! $entry->status->isPending()) {
            throw new RuntimeException('Posted vouchers can only be reversed, not deleted.');
        }
        if ((int) $entry->created_by !== (int) $user?->id && ! AccountsAccess::can('post', $user)) {
            throw new RuntimeException('Only the maker or a poster can delete this voucher.');
        }

        foreach ($entry->attachments as $a) {
            Storage::disk('local')->delete($a->path);
        }

        activity('accounts')->causedBy($user)->event('deleted')->log("Voucher {$entry->ref()} was deleted");
        $entry->delete();
    }

    /**
     * Reverse a posted voucher: an opposite journal (posted), with open-item
     * allocations made by the original undone. The original stays as is.
     */
    public function reverse(JournalEntry $entry, ?string $reason = null, ?User $user = null): JournalEntry
    {
        $user ??= auth()->user();

        if (! AccountsAccess::can('reverse', $user)) {
            throw new RuntimeException('You do not have permission to reverse vouchers.');
        }
        if ($entry->status !== JournalEntryStatus::POSTED) {
            throw new RuntimeException('Only posted vouchers can be reversed.');
        }
        if ($entry->reversingEntry()->exists()) {
            throw new RuntimeException("{$entry->ref()} is already reversed.");
        }
        if ($this->locked($entry->entry_date) && $this->locked(today())) {
            throw new RuntimeException('Books are locked — reversal date is inside the lock period.');
        }

        return DB::transaction(function () use ($entry, $reason, $user) {
            $reversal = app(ReverseJournalEntry::class)->handle($entry, $reason ?: "Reversal of {$entry->ref()}");

            $reversal->update([
                'voucher_ref'       => $this->nextRef('journal'),
                'voucher_type'      => 'journal',
                'source'            => 'manual',
                'amount'            => $entry->amount ?? $reversal->totalDebit(),
                'master_profile_id' => $entry->master_profile_id,
                'party_label'       => $entry->party_label,
                'money_account_id'  => $entry->money_account_id,
                'created_by'        => $user?->id,
                'posted_by'         => $user?->id,
            ]);

            $this->undoOpenItems($entry);
            $this->event($entry, 'reversed', "Reversed by {$reversal->voucher_ref}" . ($reason ? " — {$reason}" : ''), $user);
            $this->event($reversal, 'created', "Reversal of {$entry->ref()}", $user);
            $this->event($reversal, 'posted', null, $user);

            return $reversal;
        });
    }

    /** Scheduler: post every scheduled voucher whose date has come. Returns [posted, skipped]. */
    public function runScheduled(): array
    {
        $posted = $skipped = 0;

        JournalEntry::where('status', JournalEntryStatus::SCHEDULED->value)
            ->whereDate('scheduled_for', '<=', today())
            ->get()
            ->each(function (JournalEntry $e) use (&$posted, &$skipped) {
                try {
                    $e->update(['status' => JournalEntryStatus::DRAFT]);
                    $this->post($e->fresh(), system: true);
                    $posted++;
                } catch (RuntimeException $ex) {
                    $this->event($e, 'note', 'Auto-post skipped: ' . $ex->getMessage());
                    $skipped++;
                }
            });

        return [$posted, $skipped];
    }

    /* ------------------------------------------------------------------
     |  State, checks, impact
     * ------------------------------------------------------------------ */

    /** ready · approval · issue · scheduled · rejected · posted · reversed · void */
    public function state(JournalEntry $entry): string
    {
        return match ($entry->status) {
            JournalEntryStatus::REJECTED  => 'rejected',
            JournalEntryStatus::SCHEDULED => 'scheduled',
            JournalEntryStatus::VOID      => 'void',
            JournalEntryStatus::POSTED    => $entry->relationLoaded('reversingEntry')
                ? ($entry->reversingEntry->isNotEmpty() ? 'reversed' : 'posted')
                : ($entry->reversingEntry()->exists() ? 'reversed' : 'posted'),
            default => collect($this->checks($entry))->contains('state', 'bad')
                ? 'issue'
                : ($this->needsApproval($entry) ? 'approval' : 'ready'),
        };
    }

    public function needsApproval(JournalEntry $entry): bool
    {
        $threshold = AccountsSettings::approvalThreshold();

        return $threshold !== null && (float) $entry->amount > $threshold && ! $entry->approved_at;
    }

    public function makerBlocked(JournalEntry $entry, ?User $user): bool
    {
        return AccountsSettings::ruleOn('maker') && $user && (int) $entry->created_by === (int) $user->id
            && $entry->source === 'manual' && ! AccountsAccess::isOwner($user);
    }

    public function locked($date): bool
    {
        $lock = AccountsSettings::lockDate();

        return AccountsSettings::ruleOn('lock') && $lock && CarbonImmutable::parse($date)->startOfDay()->lte($lock);
    }

    /**
     * The review panel's checks: state ok | warn | bad. "bad" blocks posting.
     *
     * @return list<array{state: string, text: string}>
     */
    public function checks(JournalEntry $entry, ?User $user = null): array
    {
        $entry->loadMissing(['lines.account', 'attachments']);
        $out = [];
        $ok = function ($t) use (&$out) { $out[] = ['state' => 'ok', 'text' => $t]; };
        $warn = function ($t) use (&$out) { $out[] = ['state' => 'warn', 'text' => $t]; };
        $bad = function ($t) use (&$out) { $out[] = ['state' => 'bad', 'text' => $t]; };

        $dr = round((float) $entry->lines->sum('debit'), 2);
        $cr = round((float) $entry->lines->sum('credit'), 2);
        abs($dr - $cr) < 0.01 && $entry->lines->count() >= 2 ? $ok('Debit = Credit') : $bad('Debit ' . Bdt::money($dr) . ' ≠ Credit ' . Bdt::money($cr));

        $date = $entry->scheduled_for ?? $entry->entry_date;
        $this->locked($date)
            ? $bad('Date ' . $date->format('d M Y') . ' is inside the lock period (books locked up to ' . AccountsSettings::lockDate()->format('d M Y') . ')')
            : $ok(($entry->status === JournalEntryStatus::SCHEDULED ? 'Scheduled date' : 'Date') . ' is in an open period');

        foreach ($entry->lines->where('credit', '>', 0) as $line) {
            $a = $line->account;
            if ($a && in_array($a->subtype, LedgerAccounts::CASH_SUBTYPES, true)) {
                $balance = $this->postedBalance($a->id);
                $balance + 0.01 >= (float) $line->credit
                    ? $ok("{$a->name} has enough balance")
                    : $warn("{$a->name} balance " . Bdt::money($balance) . ' — less than ' . Bdt::money($line->credit));
            }
        }

        $attachAbove = AccountsSettings::attachmentThreshold();
        if ($entry->attachments->isNotEmpty()) {
            $ok('Attachment added (' . $entry->attachments->count() . ')');
        } elseif ($attachAbove !== null && (float) $entry->amount > $attachAbove && $entry->source === 'manual') {
            $bad('No attachment (required above ' . Bdt::money($attachAbove) . ')');
        }

        if ($threshold = AccountsSettings::approvalThreshold()) {
            if ((float) $entry->amount > $threshold) {
                $entry->approved_at
                    ? $ok('Approved by ' . ($entry->approvedBy?->name ?? 'Owner'))
                    : $warn('Above ' . Bdt::money($threshold) . ' — Owner approval required');
            }
        }

        $dupe = JournalEntry::query()
            ->whereKeyNot($entry->id)
            ->whereIn('status', ['draft', 'posted', 'scheduled'])
            ->whereDate('entry_date', $entry->entry_date)
            ->where('amount', $entry->amount)
            ->where(fn ($q) => $entry->master_profile_id
                ? $q->where('master_profile_id', $entry->master_profile_id)
                : $q->where('party_label', $entry->party_label)->whereNotNull('party_label'))
            ->first();
        $dupe ? $warn("Possible duplicate of {$dupe->ref()}") : $ok('No duplicate found');

        if ($user && $this->makerBlocked($entry, $user)) {
            $warn('You created this — another user must post it (maker-checker)');
        }

        return $out;
    }

    /**
     * "Post করলে যা বদলাবে": each affected account (and party) with its
     * posted balance before → after.
     *
     * @return list<array{label: string, from: string, to: string}>
     */
    public function impact(JournalEntry $entry): array
    {
        $entry->loadMissing('lines.account');

        return $entry->lines
            ->groupBy(fn ($l) => $l->account_id . '|' . $l->subledger_type . '|' . $l->subledger_id)
            ->map(function ($lines) use ($entry) {
                $first = $lines->first();
                $account = $first->account;
                $delta = $account->normal_balance->signedDelta((float) $lines->sum('debit'), (float) $lines->sum('credit'));
                $from = $this->postedBalance($account->id, $first->subledger_type, $first->subledger_id);
                $label = $account->name;

                if ($first->subledger_type) {
                    $party = $this->partyName($first->subledger_type, $first->subledger_id);
                    $label = $party ? "{$party} · {$account->name}" : $label;
                } elseif ($account->type->value === 'expense') {
                    $from = $this->postedBalance($account->id, null, null, $entry->entry_date->copy()->startOfMonth()->toDateString());
                    $label = "{$account->name} · " . $entry->entry_date->format('F');
                }

                return ['label' => $label, 'from' => Bdt::money($from), 'to' => Bdt::money($from + $delta)];
            })
            ->values()->all();
    }

    /** Posted balance of an account in its normal direction (optionally one party, optionally since a date). */
    public function postedBalance(int $accountId, ?string $subledgerType = null, ?int $subledgerId = null, ?string $since = null): float
    {
        $account = Account::find($accountId);
        if (! $account) {
            return 0.0;
        }

        $row = DB::table('journal_entry_lines as l')
            ->join('journal_entries as je', 'je.id', '=', 'l.journal_entry_id')
            ->where('l.account_id', $accountId)
            ->where('je.status', JournalEntryStatus::POSTED->value)
            ->when($subledgerType, fn ($q) => $q->where('l.subledger_type', $subledgerType)->where('l.subledger_id', $subledgerId))
            ->when($since, fn ($q) => $q->where('je.entry_date', '>=', $since))
            ->selectRaw('COALESCE(SUM(l.debit), 0) as d, COALESCE(SUM(l.credit), 0) as c')
            ->first();

        $opening = $subledgerType || $since ? 0.0 : (float) $account->opening_balance;

        return round($opening + $account->normal_balance->signedDelta((float) $row->d, (float) $row->c), 2);
    }

    public function partyName(?string $type, ?int $id): ?string
    {
        return match ($type) {
            Customer::class      => Customer::whereKey($id)->value('full_name'),
            Supplier::class      => Supplier::whereKey($id)->value('company_name') ?: Supplier::whereKey($id)->value('name'),
            MasterProfile::class => MasterProfile::whereKey($id)->value('display_name'),
            default              => null,
        };
    }

    /**
     * Subledger for a line on $accountId that belongs to $profile: the
     * profile's Customer row on customer ledgers, Supplier row on supplier
     * ledgers, otherwise the profile itself.
     */
    public function partyFields(int $accountId, ?MasterProfile $profile): array
    {
        if (! $profile) {
            return [];
        }

        $code = Account::whereKey($accountId)->value('code');
        $customerLedgers = [LedgerAccounts::RECEIVABLE, LedgerAccounts::CUSTOMER_CREDIT, LedgerAccounts::CUSTOMER_ADVANCE];
        $supplierLedgers = [LedgerAccounts::PAYABLE, LedgerAccounts::SUPPLIER_ADVANCE];

        if (in_array($code, $customerLedgers, true)) {
            $customer = app(\App\Actions\Profiles\PartyRecords::class)->customerFor($profile);
            $profile->addRole('customer');

            return ['subledger_type' => Customer::class, 'subledger_id' => $customer->id];
        }

        if (in_array($code, $supplierLedgers, true)) {
            $supplier = app(\App\Actions\Profiles\PartyRecords::class)->supplierFor($profile);
            $profile->addRole('supplier');

            return ['subledger_type' => Supplier::class, 'subledger_id' => $supplier->id];
        }

        return ['subledger_type' => MasterProfile::class, 'subledger_id' => $profile->id];
    }

    /** Default ledger for a receipt / payment from this profile. */
    public function defaultLedger(string $type, ?MasterProfile $profile): ?int
    {
        $code = match (true) {
            $type === 'receipt' && $profile?->hasRole('customer') => LedgerAccounts::RECEIVABLE,
            $type === 'receipt' && $profile?->hasRole('owner')    => LedgerAccounts::CAPITAL,
            $type === 'receipt'                                    => '4100',
            $type === 'payment' && $profile?->hasRole('supplier') => LedgerAccounts::PAYABLE,
            $type === 'payment' && $profile?->hasRole('employee') => LedgerAccounts::SALARY_PAYABLE,
            $type === 'payment' && $profile?->hasRole('owner')    => LedgerAccounts::DRAWINGS,
            default                                                => null,
        };

        return $code ? ($this->ledgers->id($code) ?: null) : null;
    }

    /** Voucher type key for any entry — panel vouchers carry it, module entries are mapped. */
    public function typeKey(JournalEntry $e): string
    {
        if ($e->voucher_type) {
            return $e->voucher_type;
        }

        return match ($e->transaction_type) {
            TransactionType::PAYMENT_RECEIPT, TransactionType::MANUAL_INCOME, TransactionType::COD_SETTLEMENT,
            TransactionType::GATEWAY_SETTLEMENT, TransactionType::CUSTOMER_ADVANCE, TransactionType::OWNER_INVESTMENT,
            TransactionType::LOAN_RECEIVED => 'receipt',
            TransactionType::SUPPLIER_PAYMENT, TransactionType::SUPPLIER_ADVANCE, TransactionType::OWNER_WITHDRAWAL,
            TransactionType::REFUND => 'payment',
            TransactionType::MANUAL_EXPENSE, TransactionType::RECURRING_EXPENSE => 'expense',
            TransactionType::TRANSFER, TransactionType::CURRENCY_CONVERSION => 'contra',
            TransactionType::LOAN_REPAYMENT => 'emi',
            TransactionType::SALARY, TransactionType::SALARY_PAYMENT, TransactionType::SALARY_ADVANCE => 'salary',
            TransactionType::OPENING_BALANCE => 'opening',
            TransactionType::SET_OFF => 'setoff',
            default => 'journal',
        };
    }

    public function typeLabel(JournalEntry $e): string
    {
        return $e->voucher_type ? self::TYPE_LABELS[$e->voucher_type] ?? ucfirst($e->voucher_type) : ($e->transaction_type?->label() ?? 'Journal');
    }

    /** Who the voucher is with: profile, typed name, a line's party, or the description. */
    public function partyOf(JournalEntry $e): string
    {
        if ($e->master_profile_id) {
            return $e->relationLoaded('profile') ? ($e->profile?->display_name ?? '—') : (MasterProfile::whereKey($e->master_profile_id)->value('display_name') ?? '—');
        }
        if ($e->party_label) {
            return $e->party_label;
        }

        $line = $e->relationLoaded('lines') ? $e->lines->firstWhere('subledger_type', '!=', null) : $e->lines()->whereNotNull('subledger_type')->first();

        return ($line ? $this->partyName($line->subledger_type, $line->subledger_id) : null) ?? ($e->description ?: '—');
    }

    /** "3 hours", "2 days", "Posts 15 Oct" */
    public function waiting(JournalEntry $entry): array
    {
        if ($entry->status === JournalEntryStatus::SCHEDULED) {
            return ['Posts ' . ($entry->scheduled_for ?? $entry->entry_date)->format('d M'), false];
        }

        $hours = (int) $entry->created_at->diffInHours(now());
        $text = $hours < 1 ? 'Just now' : ($hours < 24 ? "{$hours} hour" . ($hours > 1 ? 's' : '') : intdiv($hours, 24) . ' day' . (intdiv($hours, 24) > 1 ? 's' : ''));

        return [$text, $hours >= 48];
    }

    /* ------------------------------------------------------------------
     |  Numbering, events, open items
     * ------------------------------------------------------------------ */

    /** Next RV-0001 style ref for the type; resets each FY when Settings says so. */
    public function nextRef(string $type): string
    {
        return DB::transaction(function () use ($type) {
            $numbering = AccountsSettings::numbering();
            $fy = AccountsPeriod::fyLabel();

            if (AccountsSettings::get('numbering_reset_fy', true) && AccountsSettings::get('numbering_fy') && AccountsSettings::get('numbering_fy') !== $fy) {
                $numbering = collect($numbering)->map(fn ($n) => ['prefix' => $n['prefix'], 'next' => 1])->all();
            }
            AccountsSettings::set('numbering_fy', $fy);

            $prefix = $numbering[$type]['prefix'];
            $next = $numbering[$type]['next'];

            do {
                $ref = $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
                $next++;
            } while (JournalEntry::where('voucher_ref', $ref)->exists());

            $numbering[$type]['next'] = $next;
            AccountsSettings::set('numbering', $numbering);

            return $ref;
        });
    }

    public function event(JournalEntry $entry, string $event, ?string $note = null, ?User $by = null, array $meta = []): void
    {
        VoucherEvent::create([
            'journal_entry_id' => $entry->id,
            'event'            => $event,
            'user_id'          => $by?->id ?? auth()->id(),
            'note'             => $note ? mb_substr($note, 0, 500) : null,
            'meta'             => $meta ?: null,
            'created_at'       => now(),
        ]);
    }

    /**
     * A posted receipt / payment settles open invoices / bills oldest-first
     * (what PostCustomerPayment / PostSupplierPayment do for direct posts),
     * and a supplier payment is mirrored into the Purchase module's ledger.
     */
    protected function applyOpenItems(JournalEntry $entry): void
    {
        $ar = $this->ledgers->id(LedgerAccounts::RECEIVABLE);
        $ap = $this->ledgers->id(LedgerAccounts::PAYABLE);

        foreach ($entry->lines as $line) {
            if ($line->account_id === $ar && (float) $line->credit > 0 && $line->subledger_type === Customer::class) {
                $this->allocate(AccountsCustomerInvoice::where('customer_id', $line->subledger_id), $entry, (float) $line->credit);
            }

            if ($line->account_id === $ap && (float) $line->debit > 0 && $line->subledger_type === Supplier::class) {
                $this->allocate(AccountsSupplierBill::where('supplier_id', $line->subledger_id), $entry, (float) $line->debit);

                if ($entry->voucher_type === 'payment' && ($supplier = Supplier::find($line->subledger_id))) {
                    SupplierInvoice::create([
                        'supplier_id'    => $supplier->id,
                        'serial_number'  => ($supplier->invoices()->max('serial_number') ?? 0) + 1,
                        'invoice_number' => $entry->voucher_ref,
                        'type'           => SupplierInvoiceType::PAYMENT,
                        'amount'         => (float) $line->debit,
                        'invoice_date'   => $entry->entry_date->toDateString(),
                        'notes'          => $entry->description,
                    ]);
                }
            }
        }
    }

    /**
     * What a posted draft settles, by its source: a loan kisti, a payroll
     * item, or a supplier bill to open (with its due date).
     */
    protected function afterPost(JournalEntry $entry): void
    {
        $meta = (array) ($entry->meta ?? []);

        // A loan from the Loans & EMI builder turns active once its money is received.
        if ($entry->source_type === \App\Models\Loan::class && $entry->purpose === 'loan_received') {
            \App\Models\Loan::whereKey($entry->source_id)->where('status', 'pending')->update(['status' => 'active']);
        }

        if ($entry->source_type === \App\Models\LoanRepaymentSchedule::class && ($s = \App\Models\LoanRepaymentSchedule::with('loan')->find($entry->source_id))) {
            $paid = round((float) $s->paid_amount + (float) $entry->amount, 2);
            \App\Models\LoanRepayment::create([
                'loan_id' => $s->loan_id, 'schedule_id' => $s->id,
                'principal_amount' => (float) ($meta['principal'] ?? $entry->amount), 'interest_amount' => (float) ($meta['interest'] ?? 0),
                'journal_entry_id' => $entry->id, 'paid_at' => $entry->entry_date->toDateString(),
            ]);
            $s->update(['paid_amount' => $paid, 'status' => $paid + 0.005 >= $s->payable() ? 'paid' : $s->status]);
            if ($s->loan && $s->loan->outstandingPrincipal() <= 0.01) {
                $s->loan->update(['status' => 'closed']);
            }
        }

        if ($entry->source_type === \App\Models\PayrollItem::class && ($item = \App\Models\PayrollItem::with('run')->find($entry->source_id))) {
            $paid = round((float) $item->paid_amount + (float) $entry->amount, 2);
            $item->update(['paid_amount' => $paid, 'status' => $paid + 0.005 >= (float) $item->net ? 'paid' : 'partial']);
            $runPaid = round((float) $item->run->items()->sum('paid_amount'), 2);
            $item->run->update(['paid' => $runPaid, 'status' => $runPaid + 0.005 >= (float) $item->run->net ? 'paid' : 'partial']);
        }

        // A receipt / payment from a party on a kisti plan settles that plan's kisti.
        foreach ($entry->lines as $line) {
            $role = match (true) {
                $line->subledger_type === Customer::class && (float) $line->credit > 0 && $line->account_id === $this->ledgers->id(LedgerAccounts::RECEIVABLE) => 'customer',
                $line->subledger_type === Supplier::class && (float) $line->debit > 0 && $line->account_id === $this->ledgers->id(LedgerAccounts::PAYABLE) => 'supplier',
                default => null,
            };
            $pid = $role === 'customer' ? Customer::whereKey($line->subledger_id)->value('master_profile_id')
                : ($role === 'supplier' ? Supplier::whereKey($line->subledger_id)->value('master_profile_id') : null);

            if ($pid && in_array($entry->voucher_type, ['receipt', 'payment'], true) && ($profile = MasterProfile::find($pid))) {
                app(\App\Actions\Profiles\InstallmentPlans::class)->applyPayment($profile, $role, (float) ($line->credit ?: $line->debit), $entry->id);
            }
        }

        if (! empty($meta['supplier_bill'])) {
            $line = $entry->lines->first(fn ($l) => $l->subledger_type === Supplier::class && (float) $l->credit > 0);
            if ($line && ! AccountsSupplierBill::where('journal_entry_id', $entry->id)->exists()) {
                AccountsSupplierBill::create([
                    'supplier_id' => $line->subledger_id, 'amount' => (float) $line->credit, 'amount_allocated' => 0,
                    'status' => 'open', 'journal_entry_id' => $entry->id, 'due_date' => $meta['due_date'] ?? null,
                ]);
            }
        }
    }

    protected function allocate($openItems, JournalEntry $entry, float $amount): void
    {
        $remaining = $amount;

        foreach ($openItems->whereIn('status', ['open', 'partial'])->oldest()->get() as $item) {
            if ($remaining <= 0.004) {
                break;
            }

            $apply = round(min($remaining, $item->amountDue()), 2);
            if ($apply <= 0) {
                continue;
            }

            AccountsPaymentAllocation::create([
                'payment_journal_entry_id' => $entry->id,
                'allocatable_type'         => $item::class,
                'allocatable_id'           => $item->id,
                'amount'                   => $apply,
            ]);

            $item->increment('amount_allocated', $apply);
            $item->refresh();
            $item->update(['status' => $item->amountDue() <= 0.01 ? 'paid' : 'partial']);
            $remaining -= $apply;
        }
    }

    protected function undoOpenItems(JournalEntry $entry): void
    {
        AccountsPaymentAllocation::where('payment_journal_entry_id', $entry->id)->get()
            ->each(function (AccountsPaymentAllocation $a) {
                $item = $a->allocatable_type::find($a->allocatable_id);
                if ($item) {
                    $item->decrement('amount_allocated', (float) $a->amount);
                    $item->refresh();
                    $item->update(['status' => (float) $item->amount_allocated <= 0.01 ? 'open' : 'partial']);
                }
                $a->delete();
            });
    }

    protected function assertEditable(JournalEntry $entry): void
    {
        if (! $entry->status->isPending()) {
            throw new RuntimeException('Posted vouchers can\'t be edited — reverse it instead.');
        }
        if ($this->locked($entry->entry_date)) {
            throw new RuntimeException('This voucher is dated inside the lock period.');
        }
    }

    protected function assertBalanced(array $lines): void
    {
        $dr = round(collect($lines)->sum(fn ($l) => (float) ($l['debit'] ?? 0)), 2);
        $cr = round(collect($lines)->sum(fn ($l) => (float) ($l['credit'] ?? 0)), 2);

        if (count($lines) < 2 || abs($dr - $cr) > 0.01) {
            throw new RuntimeException('Debit ' . Bdt::money($dr) . ' must equal credit ' . Bdt::money($cr) . '.');
        }
    }
}
