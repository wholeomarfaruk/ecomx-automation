<?php

namespace Tests\Feature\Accounts;

use App\Models\Account;
use App\Models\User;
use Database\Seeders\AccountSeeder;
use Database\Seeders\AssignPermissionSeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountsAdminUiSmokeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
        $this->seed(AssignPermissionSeeder::class);
        $this->seed(CurrencySeeder::class);
        $this->seed(AccountSeeder::class);
    }

    protected function admin(): User
    {
        $user = User::factory()->create();
        $user->assignRole('superadmin');

        return $user;
    }

    public function test_dashboard_page_renders(): void
    {
        $this->actingAs($this->admin())->get(route('admin.accounts.dashboard'))->assertOk();
    }

    public function test_transactions_page_renders(): void
    {
        $this->actingAs($this->admin())->get(route('admin.accounts.transactions'))->assertOk();
    }

    public function test_cash_bank_accounts_page_renders(): void
    {
        $this->actingAs($this->admin())->get(route('admin.accounts.cash-accounts.index'))->assertOk();
    }

    public function test_cash_bank_ledger_page_renders(): void
    {
        $cash = Account::where('code', '1010')->firstOrFail();

        $this->actingAs($this->admin())->get(route('admin.accounts.cash-accounts.ledger', $cash->id))->assertOk();
    }

    public function test_receivables_page_renders(): void
    {
        $this->actingAs($this->admin())->get(route('admin.accounts.receivables.index'))->assertOk();
    }

    public function test_payables_page_renders(): void
    {
        $this->actingAs($this->admin())->get(route('admin.accounts.payables.index'))->assertOk();
    }

    public function test_loans_page_renders(): void
    {
        $this->actingAs($this->admin())->get(route('admin.accounts.loans.index'))->assertOk();
    }

    public function test_fixed_assets_page_renders(): void
    {
        $this->actingAs($this->admin())->get(route('admin.accounts.fixed-assets.index'))->assertOk();
    }

    public function test_owner_equity_page_renders(): void
    {
        $this->actingAs($this->admin())->get(route('admin.accounts.owner-equity.index'))->assertOk();
    }

    public function test_expenses_page_renders(): void
    {
        $this->actingAs($this->admin())->get(route('admin.accounts.expenses.index'))->assertOk();
    }

    public function test_recurring_expenses_page_renders(): void
    {
        $this->actingAs($this->admin())->get(route('admin.accounts.expenses.recurring'))->assertOk();
    }

    public function test_reports_index_renders(): void
    {
        $this->actingAs($this->admin())->get(route('admin.accounts.reports.index'))->assertOk();
    }

    public function test_profit_and_loss_report_renders(): void
    {
        $this->actingAs($this->admin())->get(route('admin.accounts.reports.pnl'))->assertOk();
    }

    public function test_balance_sheet_report_renders(): void
    {
        $this->actingAs($this->admin())->get(route('admin.accounts.reports.balance-sheet'))->assertOk();
    }

    public function test_cash_flow_report_renders(): void
    {
        $this->actingAs($this->admin())->get(route('admin.accounts.reports.cash-flow'))->assertOk();
    }

    public function test_fee_report_renders(): void
    {
        $this->actingAs($this->admin())->get(route('admin.accounts.reports.fees'))->assertOk();
    }

    public function test_opening_balance_settings_page_renders(): void
    {
        $this->actingAs($this->admin())->get(route('admin.accounts.settings.opening-balance'))->assertOk();
    }

    public function test_chart_of_accounts_settings_page_renders(): void
    {
        $this->actingAs($this->admin())->get(route('admin.accounts.settings.chart-of-accounts'))->assertOk();
    }

    public function test_journal_entries_settings_page_renders(): void
    {
        $this->actingAs($this->admin())->get(route('admin.accounts.settings.journal-entries'))->assertOk();
    }

    public function test_fiscal_periods_settings_page_renders(): void
    {
        $this->actingAs($this->admin())->get(route('admin.accounts.settings.fiscal-periods'))->assertOk();
    }
}
