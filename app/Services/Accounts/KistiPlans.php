<?php

namespace App\Services\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\Installment;
use App\Models\InstallmentPlan;
use App\Models\JournalEntry;
use App\Models\Loan;
use App\Models\MasterProfile;
use App\Services\Profiles\LedgerAccounts;
use App\Support\Bdt;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Creates plans from the Loans & EMI builder and runs the daily late rules.
 *
 *   loan      Loan (+ its own payable ledger) and EMI schedule; a draft receipt
 *             Dr bank / Cr loan goes to Posting Pending. The loan turns active
 *             when that receipt posts (Vouchers::afterPost).
 *   sale      customer kisti plan over money already receivable; any profit
 *             is a draft Dr receivable / Cr other income.
 *   supplier  supplier kisti plan over money already payable; any interest is
 *             a draft Dr interest expense / Cr payable.
 */
class KistiPlans
{
    public const TYPES = ['loan' => 'lender', 'sale' => 'customer', 'supplier' => 'supplier'];

    public const SUPPORT = ['next_kisti', 'principal', 'advance'];

    public const LATE = ['reminder' => 'Reminder only (SMS 1 day before + on due date)', 'fee' => 'Reminder + late fee ৳200', 'block' => 'Reminder + block new credit'];

    public const LATE_FEE = 200;

    public function __construct(protected KistiBuilder $builder, protected Vouchers $vouchers, protected LedgerAccounts $ledgers) {}

    /** @return array{0: Loan|InstallmentPlan, 1: ?JournalEntry} */
    public function create(array $d): array
    {
        $profile = MasterProfile::findOrFail($d['profile_id']);
        $s = $this->builder->build($d['frequency'], (int) ($d['count'] ?? 1), $d['first_due'], (float) $d['amount'], $d['rate_type'], (float) ($d['rate'] ?? 0), (string) ($d['offsets'] ?? ''));

        if (! $s['rows']) {
            throw new InvalidArgumentException('Plan needs an amount and at least one kisti.');
        }

        return DB::transaction(fn () => $d['type'] === 'loan' ? $this->loan($profile, $d, $s) : $this->plan($profile, $d, $s));
    }

    protected function loan(MasterProfile $lender, array $d, array $s): array
    {
        $parent = Account::findOrFail($this->ledgers->require(LedgerAccounts::LOAN_PAYABLE));
        $name = $lender->display_name;
        $principal = round((float) $d['amount'], 2);

        $account = Account::create([
            'code' => (string) ((int) Account::max('code') + 1), 'name' => "Loan Payable — {$name}",
            'type' => $parent->type, 'subtype' => $parent->subtype, 'normal_balance' => $parent->normal_balance,
            'parent_id' => $parent->id, 'is_control_account' => true, 'is_system' => false, 'is_active' => true,
        ]);

        $loan = Loan::create([
            'name' => $name, 'lender' => $name, 'master_profile_id' => $lender->id,
            'lender_type' => $lender->roleSettings('lender')['lender_type'] ?? null,
            'principal' => $principal, 'total_payable' => $s['total'], 'interest_rate' => $d['rate_type'] === 'none' ? null : (float) $d['rate'],
            'rate_type' => $d['rate_type'], 'frequency' => $d['frequency'], 'installments' => $s['count'],
            'custom_offsets' => $d['frequency'] === 'custom' ? KistiBuilder::offsets((string) $d['offsets']) : null,
            'term_months' => $d['frequency'] === 'monthly' ? $s['count'] : null,
            'start_date' => $d['first_due'], 'support_rule' => $d['support_rule'], 'late_rule' => $d['late_rule'],
            'loan_payable_account_id' => $account->id, 'status' => 'pending',
        ]);

        foreach ($s['rows'] as $r) {
            $loan->schedules()->create([
                'installment_no' => $r['no'], 'due_date' => $r['due']->toDateString(),
                'principal_due' => $r['principal'], 'interest_due' => $r['interest'], 'status' => 'pending',
            ]);
        }

        $cash = (int) $d['account_id'];
        $entry = $this->vouchers->draft([
            'type' => 'receipt', 'transaction_type' => TransactionType::LOAN_RECEIVED->value, 'date' => today()->toDateString(),
            'description' => "Loan received — {$name}", 'profile_id' => $lender->id, 'money_account_id' => $cash,
            'source_type' => Loan::class, 'source_id' => $loan->id, 'purpose' => 'loan_received',
            'lines' => [['account_id' => $cash, 'debit' => $principal], ['account_id' => $account->id, 'credit' => $principal]],
        ]);

        $lender->addRole('lender');
        $lender->logActivity('loan', "Loan plan created · {$s['count']} kisti × " . Bdt::money($s['kisti']) . " · receipt {$entry->ref()} waiting");

        return [$loan, $entry];
    }

    protected function plan(MasterProfile $profile, array $d, array $s): array
    {
        $role = self::TYPES[$d['type']];

        $plan = InstallmentPlan::create([
            'master_profile_id' => $profile->id, 'role' => $role, 'reference' => $d['reference'] ?? null,
            'frequency' => $d['frequency'], 'count' => $s['count'], 'amount' => $s['kisti'], 'total' => $s['total'],
            'rate_type' => $d['rate_type'], 'rate' => $d['rate_type'] === 'none' ? null : (float) $d['rate'], 'interest' => $s['interest'],
            'start_date' => $d['first_due'], 'custom_offsets' => $d['frequency'] === 'custom' ? KistiBuilder::offsets((string) $d['offsets']) : null,
            'support_rule' => $d['support_rule'], 'late_rule' => $d['late_rule'], 'status' => 'active', 'created_by' => auth()->id(),
        ]);

        foreach ($s['rows'] as $r) {
            $plan->installments()->create([
                'no' => $r['no'], 'due_date' => $r['due']->toDateString(), 'amount' => $r['amount'], 'adjusted_amount' => $r['amount'], 'status' => 'upcoming',
            ]);
        }

        $entry = null;
        if ($s['interest'] > 0.004) {
            $party = $this->ledgers->require($role === 'customer' ? LedgerAccounts::RECEIVABLE : LedgerAccounts::PAYABLE);
            $other = $role === 'customer' ? $this->ledgers->require('4100') : $this->ledgers->require(LedgerAccounts::INTEREST_EXPENSE);
            $lines = $role === 'customer'
                ? [['account_id' => $party, 'debit' => $s['interest']] + $this->vouchers->partyFields($party, $profile), ['account_id' => $other, 'credit' => $s['interest']]]
                : [['account_id' => $other, 'debit' => $s['interest']], ['account_id' => $party, 'credit' => $s['interest']] + $this->vouchers->partyFields($party, $profile)];

            $entry = $this->vouchers->draft([
                'type' => 'journal', 'date' => today()->toDateString(), 'profile_id' => $profile->id,
                'description' => ($role === 'customer' ? 'Kisti profit — ' : 'Kisti interest — ') . $profile->display_name,
                'source_type' => InstallmentPlan::class, 'source_id' => $plan->id, 'purpose' => 'kisti_interest', 'lines' => $lines,
            ]);
        }

        $profile->logActivity('kisti', ucfirst($d['frequency']) . " kisti plan created · {$s['count']} × " . Bdt::money($s['kisti']));

        return [$plan, $entry];
    }

    /**
     * Daily (spec §7.6): SMS one day before and on the due date for customer
     * kisti; overdue kisti get a late fee draft (once) or block new credit,
     * per the plan's late rule.
     *
     * @return array{sms: int, fees: int, blocked: int}
     */
    public function runDaily(): array
    {
        $out = ['sms' => 0, 'fees' => 0, 'blocked' => 0];
        $sms = app(\App\Actions\Profiles\SendProfileSms::class);
        $ar = $this->ledgers->id(LedgerAccounts::RECEIVABLE);
        $fee = $this->ledgers->id('4150');

        $open = Installment::with('plan.profile.roles')->where('status', '!=', 'paid')
            ->whereHas('plan', fn ($q) => $q->where('role', 'customer')->where('status', 'active'))
            ->where('due_date', '<=', today()->addDay()->toDateString())->get();

        foreach ($open as $k) {
            $plan = $k->plan;
            $p = $plan->profile;
            if (! $p) {
                continue;
            }
            $left = round((float) $k->adjusted_amount - (float) $k->paid_amount, 2);

            if ($k->due_date->gte(today())) {
                $when = $k->due_date->isToday() ? 'আজ' : 'আগামীকাল';
                $out['sms'] += (int) rescue(fn () => $sms->send($p, "{$p->display_name}, আপনার কিস্তি {$k->no}/{$plan->count} " . Bdt::money($left) . " {$when} দেওয়ার কথা। — " . \App\Support\AccountsSettings::businessName(), 'Kisti reminder SMS'), false, false);

                continue;
            }

            if ($plan->late_rule === 'fee' && ! $k->late_fee_at && $ar && $fee) {
                $this->vouchers->draft([
                    'type' => 'journal', 'date' => today()->toDateString(), 'profile_id' => $p->id, 'source' => 'system',
                    'description' => "Late fee — kisti {$k->no}/{$plan->count} · {$p->display_name}",
                    'source_type' => Installment::class, 'source_id' => $k->id, 'purpose' => 'late_fee',
                    'lines' => [['account_id' => $ar, 'debit' => self::LATE_FEE] + $this->vouchers->partyFields($ar, $p), ['account_id' => $fee, 'credit' => self::LATE_FEE]],
                ]);
                $k->update(['late_fee_at' => now()]);
                $out['fees']++;
            }

            if ($plan->late_rule === 'block' && ! ($p->roleSettings('customer')['credit_blocked'] ?? false)) {
                $p->addRole('customer', ['credit_blocked' => true]);
                $p->logActivity('setting', "Credit stopped — kisti {$k->no} missed");
                $out['blocked']++;
            }
        }

        return $out;
    }
}
