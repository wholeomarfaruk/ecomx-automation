<?php

namespace Tests\Feature\Accounts;

use App\Actions\Accounts\PostJournalEntry;
use App\Actions\Profiles\InstallmentPlans;
use App\Actions\Profiles\LoanKisti;
use App\Actions\Profiles\MergeProfiles;
use App\Actions\Profiles\PartyRecords;
use App\Actions\Profiles\Payroll;
use App\Actions\Profiles\ProfileVouchers;
use App\Enums\Accounts\JournalEntryStatus;
use App\Enums\Accounts\TransactionType;
use App\Livewire\Accounts\Profiles\Customers;
use App\Livewire\Accounts\Profiles\ProfileForm;
use App\Livewire\Accounts\Profiles\ProfileIndex;
use App\Models\Account;
use App\Models\Customer;
use App\Models\MasterProfile;
use App\Models\PayrollRun;
use App\Models\User;
use App\Services\MasterProfileUuidGenerator;
use App\Services\Profiles\InstallmentScheduler;
use App\Services\Profiles\ProfileBalances;
use App\Services\Profiles\ProfileLedger;
use App\Support\Bdt;
use App\Support\BdPhone;
use Carbon\Carbon;
use Database\Seeders\AccountSeeder;
use Database\Seeders\AssignPermissionSeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Accounts Panel › Master Profiles: pages render, and the business rules
 * of spec §7 hold (one profile per phone, posted-only balances, Dr/Cr
 * ledger, set-off, kisti support, owners, payroll).
 */
class MasterProfilesTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(AssignPermissionSeeder::class);
        $this->seed(CurrencySeeder::class);
        $this->seed(AccountSeeder::class);

        $this->admin = User::factory()->create();
        $this->admin->assignRole('superadmin');
        $this->actingAs($this->admin);
    }

    protected function cash(): int
    {
        return Account::where('code', '1010')->value('id');
    }

    protected function profile(string $name, array $roles = [], ?string $phone = null): MasterProfile
    {
        $p = MasterProfile::create([
            'uuid'         => app(MasterProfileUuidGenerator::class)->generate(),
            'type'         => 'individual',
            'display_name' => $name,
            'phone'        => $phone ?? '017' . random_int(10000000, 99999999),
            'status'       => 'active',
        ]);

        foreach ($roles as $role => $settings) {
            $p->addRole(is_int($role) ? $settings : $role, is_int($role) ? [] : $settings);
        }

        return $p->load('roles');
    }

    /** Dr receivable / Cr sales for a customer — what a credit sale posts. */
    protected function creditSale(MasterProfile $p, float $amount, string $status = 'posted'): void
    {
        $customer = app(PartyRecords::class)->customerFor($p);

        app(PostJournalEntry::class)->handle([
            'entry_date' => today()->toDateString(), 'description' => 'Credit sale', 'status' => $status,
            'transaction_type' => TransactionType::SALE->value,
        ], [
            ['account_id' => Account::where('code', '1100')->value('id'), 'debit' => $amount, 'subledger_type' => Customer::class, 'subledger_id' => $customer->id],
            ['account_id' => Account::where('code', '4000')->value('id'), 'credit' => $amount],
        ]);
    }

    protected function supplierBill(MasterProfile $p, float $amount): void
    {
        $supplier = app(PartyRecords::class)->supplierFor($p);

        app(PostJournalEntry::class)->handle([
            'entry_date' => today()->toDateString(), 'description' => 'Purchase', 'transaction_type' => TransactionType::SUPPLIER_BILL->value,
        ], [
            ['account_id' => Account::where('code', '1200')->value('id'), 'debit' => $amount],
            ['account_id' => Account::where('code', '2100')->value('id'), 'credit' => $amount, 'subledger_type' => \App\Models\Supplier::class, 'subledger_id' => $supplier->id],
        ]);
    }

    /* ---------------- Pages ---------------- */

    public function test_every_profile_page_renders(): void
    {
        $p = $this->profile('Karim Enterprise', ['customer', 'supplier', 'lender', 'owner' => ['share_percent' => 60], 'employee' => ['salary' => 20000]]);
        $this->creditSale($p, 5000);

        foreach (['index', 'customers', 'suppliers', 'lenders', 'owners', 'employees', 'create', 'merge', 'import', 'payroll'] as $page) {
            $this->get(route("accounts.profiles.{$page}"))->assertOk();
        }

        $this->get(route('accounts.profiles.show', $p))->assertOk()->assertSee('Karim Enterprise')->assertSee('Net position');
        $this->get(route('accounts.profiles.edit', $p))->assertOk();
        $this->get(route('accounts.profiles.statement', $p))->assertOk()->assertSee('৳5,000 Dr');
        $this->get(route('accounts.dashboard'))->assertOk();
    }

    public function test_tabs_and_sidebar_show_live_counts(): void
    {
        $this->profile('A', ['customer']);
        $this->profile('B', ['customer', 'supplier']);

        $this->get(route('accounts.profiles.customers'))->assertOk()->assertSeeInOrder(['Customers', '2', 'Suppliers', '1']);
    }

    /* ---------------- Rule 1: one person = one profile ---------------- */

    public function test_duplicate_phone_is_blocked_and_offers_add_role(): void
    {
        $existing = $this->profile('Karim Enterprise', ['customer'], '01712345678');

        Livewire::test(ProfileForm::class)
            ->set('name', 'Karim Textile')
            ->set('phone', '+880 1712-345678')
            ->assertSee('এই নম্বরে আগে থেকেই profile আছে')
            ->assertSee('ওখানে role যোগ করুন')
            ->call('save')
            ->assertHasErrors('phone');

        $this->assertSame(1, MasterProfile::where('phone', '01712345678')->count());

        Livewire::test(ProfileForm::class, ['profile' => $existing])
            ->set('presetRole', 'supplier')
            ->set('roles.supplier', true)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertTrue($existing->fresh()->load('roles')->hasRole('supplier'));
        $this->assertNotNull($existing->supplier()->first());
    }

    public function test_form_creates_profile_with_roles_party_rows_and_opening_balance(): void
    {
        Livewire::test(ProfileForm::class)
            ->set('type', 'business')
            ->set('name', 'Rahman Fabrics')
            ->set('phone', '01811223344')
            ->set('roles.customer', true)
            ->set('roles.supplier', true)
            ->set('customer.credit_limit', 30000)
            ->set('ob_amount', 6000)
            ->set('ob_direction', 'pay')
            ->set('ob_date', '2026-09-01')
            ->set('tags', 'Wholesale, Mirpur')
            ->call('save')
            ->assertHasNoErrors();

        $p = MasterProfile::where('phone', '01811223344')->with('roles')->firstOrFail();
        $this->assertMatchesRegularExpression('/^P-\d{4,}$/', $p->code);
        $this->assertTrue($p->hasRole('customer') && $p->hasRole('supplier'));
        $this->assertEquals(30000, $p->roleSettings('customer')['credit_limit']);
        $this->assertSame(['Wholesale', 'Mirpur'], $p->tags);
        $this->assertNotNull($p->customer()->first());

        $b = app(ProfileBalances::class)->forProfile($p->id);
        $this->assertEquals(6000.0, (float) $b->payable);
        $this->assertSame('দিবো', ProfileBalances::describe($b)['label']);
    }

    public function test_bd_phone_normalisation(): void
    {
        $this->assertSame('01712345678', BdPhone::normalize('+880 1712-345678'));
        $this->assertSame('01712345678', BdPhone::normalize('1712345678'));
        $this->assertTrue(BdPhone::isValid('01912345678'));
        $this->assertFalse(BdPhone::isValid('01212345678'));
    }

    /* ---------------- Rule 2 & 3: posted only, Dr/Cr ledger ---------------- */

    public function test_only_posted_entries_count_in_balances(): void
    {
        $p = $this->profile('Nasrin Akter', ['customer']);
        $this->creditSale($p, 4000);
        $this->creditSale($p, 9999, JournalEntryStatus::DRAFT->value);

        $this->assertEquals(4000.0, (float) app(ProfileBalances::class)->forProfile($p->id)->receivable);
    }

    public function test_ledger_runs_dr_and_cr_with_opening_and_closing(): void
    {
        $p = $this->profile('Karim Enterprise', ['customer', 'supplier']);
        $this->creditSale($p, 31500);
        $this->supplierBill($p, 40000);
        app(ProfileVouchers::class)->collect($p, 6500, $this->cash(), today()->toDateString());

        $st = app(ProfileLedger::class)->statement($p, today()->subDay()->toDateString(), today()->toDateString());

        $this->assertEquals(0.0, $st['opening']);
        $this->assertCount(3, $st['rows']);
        $this->assertEquals(31500.0, $st['debit']);
        $this->assertEquals(46500.0, $st['credit']);
        $this->assertEquals(-15000.0, $st['closing']);
        $this->assertSame('৳15,000 Cr', ProfileLedger::drCr($st['closing']));

        $customerOnly = app(ProfileLedger::class)->statement($p, null, null, 'customer');
        $this->assertEquals(25000.0, $customerOnly['closing']);
    }

    /* ---------------- Rule 4: net position + set-off ---------------- */

    public function test_set_off_moves_min_of_receivable_and_payable(): void
    {
        $p = $this->profile('Karim Enterprise', ['customer', 'supplier']);
        $this->creditSale($p, 18500);
        $this->supplierBill($p, 40000);

        $entry = app(ProfileVouchers::class)->setOff($p, today()->toDateString());

        $this->assertSame(TransactionType::SET_OFF, $entry->transaction_type);
        $b = app(ProfileBalances::class)->forProfile($p->id);
        $this->assertEquals(0.0, (float) $b->receivable);
        $this->assertEquals(21500.0, (float) $b->payable);
    }

    public function test_net_label_for_multi_role_profile(): void
    {
        $p = $this->profile('Karim Enterprise', ['customer', 'supplier']);
        $this->creditSale($p, 18500);
        $this->supplierBill($p, 40000);

        $d = ProfileBalances::describe(app(ProfileBalances::class)->forProfile($p->id));
        $this->assertSame(['amount' => 21500.0, 'label' => 'Net দিবো', 'kind' => 'pay'], $d);
    }

    /* ---------------- Rule 5: kisti + support ---------------- */

    public function test_schedule_frequencies(): void
    {
        $s = app(InstallmentScheduler::class);

        $weekly = $s->build('weekly', 8, '2026-09-22', 52000);
        $this->assertCount(8, $weekly);
        $this->assertSame('2026-09-29', $weekly[0]['due_date']->toDateString());
        $this->assertEquals(6500, $weekly[0]['amount']);

        $custom = $s->build('custom', 0, '2026-10-01', 90000, customOffsets: [45, 15, 90]);
        $this->assertSame(['2026-10-16', '2026-11-15', '2026-12-30'], array_map(fn ($r) => $r['due_date']->toDateString(), $custom));

        $flat = $s->build('monthly', 12, '2026-05-12', 240000, 'flat', totalPayable: 252000);
        $this->assertEquals(21000, $flat[0]['amount']);
        $this->assertEquals(252000, round(array_sum(array_column($flat, 'amount'))));

        $reducing = $s->build('monthly', 24, '2026-01-15', 690000, 'reducing', 12);
        $this->assertEquals(690000, round(array_sum(array_column($reducing, 'principal'))));
        $this->assertGreaterThan($reducing[23]['interest'], $reducing[0]['interest']);
    }

    public function test_loan_support_payment_reduces_next_kisti(): void
    {
        $lender = $this->profile('Rahim Traders', ['lender' => ['lender_type' => 'business']]);
        $loan = app(LoanKisti::class)->create($lender, [
            'name' => 'Rahim Traders loan', 'principal' => 240000, 'cash_account_id' => $this->cash(),
            'rate_type' => 'flat', 'total_payable' => 252000, 'frequency' => 'monthly', 'installments' => 12,
            'start_date' => today()->subMonth()->toDateString(), 'support_rule' => 'next_kisti',
        ]);

        $this->assertEquals(240000.0, (float) app(ProfileBalances::class)->forProfile($lender->id)->loan);

        $result = app(LoanKisti::class)->pay($loan, 22500, $this->cash(), today()->toDateString());

        $this->assertEquals(1500.0, $result['support']);
        $schedules = $loan->schedules()->orderBy('due_date')->get();
        $this->assertSame('paid', $schedules[0]->status);
        $this->assertEquals(1500.0, (float) $schedules[0]->support_amount);
        $this->assertEquals(19500.0, $schedules[1]->payable());
        // 22,500 paid = 1,000 profit + 21,500 principal.
        $this->assertEquals(218500.0, (float) app(ProfileBalances::class)->forProfile($lender->id)->loan);
    }

    public function test_loan_support_principal_rule_takes_from_the_last_kisti(): void
    {
        $lender = $this->profile('City Bank', ['lender']);
        $loan = app(LoanKisti::class)->create($lender, [
            'name' => 'SME', 'principal' => 12000, 'cash_account_id' => $this->cash(), 'rate_type' => 'none',
            'frequency' => 'monthly', 'installments' => 4, 'start_date' => today()->toDateString(), 'support_rule' => 'principal',
        ]);

        app(LoanKisti::class)->pay($loan, 4000, $this->cash(), today()->toDateString());

        $s = $loan->schedules()->orderBy('due_date')->get();
        $this->assertEquals(3000.0, $s[1]->payable());
        $this->assertEquals(2000.0, $s[3]->payable());
    }

    public function test_customer_kisti_plan_applies_collections_with_support(): void
    {
        $p = $this->profile('Karim Enterprise', ['customer']);
        $this->creditSale($p, 52000);
        $plan = app(InstallmentPlans::class)->create($p, 'customer', 52000, 'weekly', 8, today()->toDateString());

        app(ProfileVouchers::class)->collect($p, 6500, $this->cash(), today()->toDateString());
        app(ProfileVouchers::class)->collect($p, 9000, $this->cash(), today()->toDateString());

        $k = $plan->installments()->orderBy('no')->get();
        $this->assertSame(['paid', 'paid'], [$k[0]->status, $k[1]->status]);
        $this->assertEquals(2500.0, (float) $k[1]->support_amount);
        $this->assertEquals(4000.0, (float) $k[2]->adjusted_amount);
        $this->assertEquals(36500.0, (float) app(ProfileBalances::class)->forProfile($p->id)->receivable);
    }

    /* ---------------- Rule 6 & 7: credit limit, aging (Customers page) ---------------- */

    public function test_customers_page_flags_over_limit_and_filters_by_status(): void
    {
        $over = $this->profile('Shapla Boutique', ['customer' => ['credit_limit' => 20000]]);
        $this->creditSale($over, 24000);
        $clear = $this->profile('Ruma Akter', ['customer']);

        $c = Livewire::test(Customers::class);
        $this->assertSame(1, $c->instance()->summary['over_limit']);
        $this->assertEquals(24000.0, $c->instance()->summary['due']);

        $c->call('setSt', 'clear')->assertSee('Ruma Akter')->assertDontSee('Shapla Boutique');
        $c->call('setSt', 'due')->assertSee('Shapla Boutique')->assertSee('120%');
    }

    /* ---------------- Rule 9: owners ---------------- */

    public function test_drawings_are_equity_not_expense(): void
    {
        $owner = $this->profile('Ariful Rahman', ['owner' => ['share_percent' => 60]]);
        app(ProfileVouchers::class)->addCapital($owner, 700000, $this->cash(), today()->toDateString());
        app(ProfileVouchers::class)->drawing($owner, 20000, $this->cash(), today()->toDateString());

        $b = app(ProfileBalances::class)->forProfile($owner->id);
        $this->assertEquals(700000.0, (float) $b->capital);
        $this->assertEquals(20000.0, (float) $b->drawings);

        $expense = Account::where('type', 'expense')->pluck('id');
        $this->assertSame(0, \App\Models\JournalEntryLine::whereIn('account_id', $expense)->count());

        $this->get(route('accounts.profiles.owners'))->assertOk()->assertSee('Owner · 60%')->assertSee('৳7,00,000');
    }

    /* ---------------- Rule 10: payroll ---------------- */

    public function test_payroll_deducts_advances_and_payment_clears_payable(): void
    {
        $e = $this->profile('Rafiq Hasan', ['employee' => ['salary' => 25000, 'pay_via' => 'bkash']]);
        app(Payroll::class)->advance($e, 10000, 2500, $this->cash(), today()->toDateString());

        $run = app(Payroll::class)->prepare(now()->startOfMonth());

        $this->assertEquals(25000.0, (float) $run->gross);
        $this->assertEquals(2500.0, (float) $run->advance_deducted);
        $this->assertEquals(22500.0, (float) $run->net);
        $this->assertEquals(7500.0, (float) $e->advances()->sum('balance'));

        $b = app(ProfileBalances::class)->forProfile($e->id);
        $this->assertEquals(22500.0, (float) $b->salary_payable);
        $this->assertEquals(7500.0, (float) $b->salary_advance);

        app(Payroll::class)->pay($e, 22500, $this->cash(), today()->toDateString());

        $this->assertEquals(0.0, (float) app(ProfileBalances::class)->forProfile($e->id)->salary_payable);
        $this->assertSame('paid', PayrollRun::first()->status);

        $this->expectException(\RuntimeException::class);
        app(Payroll::class)->prepare(now()->startOfMonth());
    }

    /* ---------------- Merge, list, money ---------------- */

    public function test_merge_moves_roles_and_ledger_to_primary(): void
    {
        $a = $this->profile('Karim Enterprise', ['customer'], '01700000001');
        $b = $this->profile('Karim Ent.', ['supplier'], '01700000001');
        $this->supplierBill($b, 5000);

        app(MergeProfiles::class)->handle($a, [$b->id]);

        $a->load('roles');
        $this->assertTrue($a->hasRole('supplier'));
        $this->assertSoftDeleted($b);
        $this->assertEquals(5000.0, (float) app(ProfileBalances::class)->forProfile($a->id)->payable);
    }

    public function test_index_filters_by_role_and_balance(): void
    {
        $due = $this->profile('Due Person', ['customer']);
        $this->creditSale($due, 1000);
        $this->profile('Clear Person', ['customer']);
        $this->profile('Supplier Only', ['supplier']);

        Livewire::test(ProfileIndex::class)
            ->call('setRole', 'Customer')
            ->assertSee('Due Person')->assertSee('Clear Person')->assertDontSee('Supplier Only')
            ->call('setBal', 'পাবো')
            ->assertSee('Due Person')->assertDontSee('Clear Person');
    }

    public function test_money_uses_bangladeshi_grouping(): void
    {
        $this->assertSame('৳17,75,250', Bdt::money(1775250));
        $this->assertSame('− ৳22,500', Bdt::money(-22500));
        $this->assertSame('+ ৳500', Bdt::money(500, true));
        $this->assertSame('Today', Bdt::relative(Carbon::now()));
    }
}
