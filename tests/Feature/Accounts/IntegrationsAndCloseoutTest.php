<?php

namespace Tests\Feature\Accounts;

use App\Actions\Accounts\CreateFiscalYear;
use App\Actions\Accounts\LockFiscalPeriod;
use App\Actions\Accounts\PostCodSettlement;
use App\Actions\Accounts\PostGatewaySettlement;
use App\Actions\Accounts\PostJournalEntry;
use App\Actions\Accounts\PostManualTransaction;
use App\Actions\Accounts\PostRecurringExpenseRun;
use App\Actions\Accounts\PostVatSale;
use App\Actions\Accounts\ReconcileBankAccount;
use App\Enums\Accounts\TransactionType;
use App\Exceptions\Accounts\LockedFiscalPeriodException;
use App\Models\Account;
use App\Models\Courier;
use App\Models\RecurringExpense;
use App\Models\User;
use Database\Seeders\AccountSeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies M4 (Integrations & Close-out) against docs/ecomX-accounts-cases.md
 * Cases 2.2, 6.2, 10.2, 11.1, 12.1, and fiscal period locking (Golden Rule #7).
 */
class IntegrationsAndCloseoutTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(CurrencySeeder::class);
        $this->seed(AccountSeeder::class);
        $this->actingAs(User::factory()->create());
    }

    protected function account(string $code): Account
    {
        return Account::where('code', $code)->firstOrFail();
    }

    public function test_case_6_2_recurring_expense_posts_and_advances_next_run_date(): void
    {
        $recurring = RecurringExpense::create([
            'name'            => 'Office Rent',
            'account_id'      => $this->account('5140')->id,
            'from_account_id' => $this->account('1020')->id,
            'amount'          => 30000,
            'cadence'         => 'monthly',
            'next_run_date'   => '2026-09-05',
            'is_active'       => true,
        ]);

        app(PostRecurringExpenseRun::class)->handle($recurring);

        $this->assertSame(30000.0, $this->account('5140')->balance());
        $this->assertSame('2026-10-05', $recurring->fresh()->next_run_date->toDateString());
    }

    public function test_case_6_2_running_twice_for_same_due_date_does_not_double_post(): void
    {
        $recurring = RecurringExpense::create([
            'name'            => 'Internet',
            'account_id'      => $this->account('5100')->id,
            'from_account_id' => $this->account('1020')->id,
            'amount'          => 2000,
            'cadence'         => 'monthly',
            'next_run_date'   => '2026-09-05',
            'is_active'       => true,
        ]);

        app(PostRecurringExpenseRun::class)->handle($recurring);

        // Force next_run_date back to simulate a duplicate scheduler fire for the same date.
        $recurring->update(['next_run_date' => '2026-09-05']);

        $this->expectException(\App\Exceptions\Accounts\DuplicateJournalEntryException::class);
        app(PostRecurringExpenseRun::class)->handle($recurring);
    }

    public function test_case_10_2_cod_settlement_nets_courier_fee(): void
    {
        $courier = Courier::create(['name' => 'Steadfast', 'slug' => 'steadfast', 'driver_key' => 'steadfast']);

        // Case 10.1 — COD order shipped, building up the receivable this settlement will clear.
        app(PostManualTransaction::class)->handle(
            $this->account('1050')->id, $this->account('4000')->id, 1000, '2026-09-01', TransactionType::SALE,
        );

        $settlement = app(PostCodSettlement::class)->handle(
            $courier, 1000, 80, '2026-09-10',
            $this->account('1020')->id,
            $this->account('1050')->id,
            $this->account('5120')->id,
        );

        $this->assertSame(920.0, (float) $settlement->net_amount);
        $this->assertSame(920.0, $this->account('1020')->balance());
        $this->assertSame(80.0, $this->account('5120')->balance());
        $this->assertSame(0.0, $this->account('1050')->balance());
    }

    public function test_case_11_1_gateway_settlement_nets_fee(): void
    {
        $gatewayAccount = Account::create([
            'code' => '1061', 'name' => 'bKash Gateway', 'type' => 'asset',
            'subtype' => 'receivable', 'normal_balance' => 'debit',
            'parent_id' => $this->account('1060')->id, 'is_system' => false, 'is_active' => true,
        ]);

        $settlement = app(PostGatewaySettlement::class)->handle(
            $gatewayAccount->id, 2000, 40, '2026-09-10',
            $this->account('1020')->id, $this->account('5310')->id,
        );

        $this->assertSame(1960.0, (float) $settlement->net_amount);
        $this->assertSame(1960.0, $this->account('1020')->balance());
        $this->assertSame(40.0, $this->account('5310')->balance());
    }

    public function test_case_12_1_vat_sale_separates_vat_from_sales(): void
    {
        app(PostVatSale::class)->handle(
            $this->account('1010')->id, $this->account('4000')->id, $this->account('2300')->id,
            1000, 75, '2026-09-10',
        );

        $this->assertSame(1075.0, $this->account('1010')->balance());
        $this->assertSame(1000.0, $this->account('4000')->balance());
        $this->assertSame(75.0, $this->account('2300')->balance());
    }

    /**
     * The spec's own worked numbers (book=48,000, statement=50,000, then
     * Dr Bank Charge/Cr Bank 2,000 "reconciles" them) don't actually
     * balance — crediting the bank account moves it further from, not
     * closer to, the statement figure. This test uses the corrected
     * version of the same scenario: book is overstated by an unrecorded
     * bank charge, so book (52,000) is above statement (50,000), and the
     * Dr Bank Charge/Cr Bank entry correctly brings book down to match.
     */
    public function test_case_2_2_reconciliation_flags_difference_then_resolves_with_adjustment(): void
    {
        app(PostManualTransaction::class)->handle(
            $this->account('1020')->id, $this->account('4100')->id, 52000, '2026-09-01', TransactionType::MANUAL_INCOME,
        );

        $reconciliation = app(ReconcileBankAccount::class)->start($this->account('1020'), 50000, '2026-09-30');

        $this->assertSame('in_progress', $reconciliation->status);
        $this->assertSame(-2000.0, $reconciliation->difference());

        app(ReconcileBankAccount::class)->postAdjustment(
            $reconciliation, $this->account('5300')->id, 2000, '2026-09-30',
        );

        $this->assertSame('completed', $reconciliation->fresh()->status);
        $this->assertSame(50000.0, $this->account('1020')->balance());
    }

    public function test_fiscal_period_locking_blocks_new_posts_in_that_period(): void
    {
        app(CreateFiscalYear::class)->handle('FY2026', '2026-01-01');
        $period = \App\Models\FiscalPeriod::containingDate('2026-09-15')->firstOrFail();

        app(LockFiscalPeriod::class)->lock($period);

        $this->expectException(LockedFiscalPeriodException::class);

        app(PostJournalEntry::class)->handle(
            ['entry_date' => '2026-09-15', 'transaction_type' => TransactionType::MANUAL_EXPENSE->value],
            [
                ['account_id' => $this->account('5100')->id, 'debit' => 100],
                ['account_id' => $this->account('1010')->id, 'credit' => 100],
            ]
        );
    }

    public function test_fiscal_period_reopen_allows_posting_again(): void
    {
        app(CreateFiscalYear::class)->handle('FY2026', '2026-01-01');
        $period = \App\Models\FiscalPeriod::containingDate('2026-09-15')->firstOrFail();

        app(LockFiscalPeriod::class)->lock($period);
        app(LockFiscalPeriod::class)->reopen($period);

        $entry = app(PostJournalEntry::class)->handle(
            ['entry_date' => '2026-09-15', 'transaction_type' => TransactionType::MANUAL_EXPENSE->value],
            [
                ['account_id' => $this->account('5100')->id, 'debit' => 100],
                ['account_id' => $this->account('1010')->id, 'credit' => 100],
            ]
        );

        $this->assertNotNull($entry->id);
    }
}
