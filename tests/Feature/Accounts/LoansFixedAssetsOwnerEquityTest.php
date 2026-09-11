<?php

namespace Tests\Feature\Accounts;

use App\Actions\Accounts\DisposeFixedAsset;
use App\Actions\Accounts\PostFixedAssetPurchase;
use App\Actions\Accounts\PostLoanReceived;
use App\Actions\Accounts\PostLoanRepayment;
use App\Actions\Accounts\PostOwnerInvestment;
use App\Actions\Accounts\PostOwnerWithdrawal;
use App\Actions\Accounts\RunDepreciation;
use App\Models\Account;
use App\Models\User;
use Database\Seeders\AccountSeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies M3 (Loans, Fixed Assets, Owner Equity) against
 * docs/ecomX-accounts-cases.md Cases 5.1-5.2, 7.1-7.3, 8.1-8.3.
 */
class LoansFixedAssetsOwnerEquityTest extends TestCase
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

    public function test_case_5_1_loan_received_increases_cash_and_liability(): void
    {
        $loan = app(PostLoanReceived::class)->handle(
            'Bank Loan', 'City Bank', 500000, '2026-09-01',
            $this->account('1020')->id, $this->account('2200')->id,
        );

        $this->assertSame(500000.0, $this->account('1020')->balance());
        $this->assertSame(500000.0, $loan->payableAccount->balance());
        $this->assertSame('active', $loan->status);
    }

    public function test_case_5_2_repayment_splits_principal_and_interest(): void
    {
        $loan = app(PostLoanReceived::class)->handle(
            'Bank Loan', 'City Bank', 500000, '2026-09-01',
            $this->account('1020')->id, $this->account('2200')->id,
        );

        app(PostLoanRepayment::class)->handle(
            $loan, $this->account('1020')->id, $this->account('5400')->id,
            20000, 5000, '2026-10-01',
        );

        $this->assertSame(480000.0, $loan->payableAccount->fresh()->balance());
        $this->assertSame(5000.0, $this->account('5400')->balance());
        $this->assertSame(480000.0, $loan->fresh()->outstandingPrincipal());
    }

    public function test_case_5_2_loan_closes_when_fully_repaid(): void
    {
        $loan = app(PostLoanReceived::class)->handle(
            'Small Loan', null, 20000, '2026-09-01',
            $this->account('1020')->id, $this->account('2200')->id,
        );

        app(PostLoanRepayment::class)->handle(
            $loan, $this->account('1020')->id, $this->account('5400')->id,
            20000, 0, '2026-10-01',
        );

        $this->assertSame('closed', $loan->fresh()->status);
    }

    public function test_case_7_1_fixed_asset_purchase_is_not_an_expense(): void
    {
        $asset = app(PostFixedAssetPurchase::class)->handle(
            'Laptop', $this->account('1500')->id, 80000, '2026-01-01',
            $this->account('1020')->id, 48,
        );

        $this->assertSame(80000.0, $this->account('1500')->balance());
        $this->assertSame(0.0, $this->account('5100')->balance());
        $this->assertSame(80000.0, $asset->bookValue());
    }

    public function test_case_7_2_straight_line_depreciation(): void
    {
        $asset = app(PostFixedAssetPurchase::class)->handle(
            'Laptop', $this->account('1500')->id, 80000, '2026-01-01',
            $this->account('1020')->id, 48,
        );

        app(RunDepreciation::class)->handle(
            $asset, '2026-02-01', $this->account('5500')->id, $this->account('1510')->id,
        );

        $expectedMonthly = round(80000 / 48, 2);
        $this->assertSame($expectedMonthly, $this->account('5500')->balance());
        $this->assertSame($expectedMonthly, (float) $asset->fresh()->accumulated_depreciation);
    }

    public function test_case_7_2_depreciation_is_idempotent_per_period(): void
    {
        $asset = app(PostFixedAssetPurchase::class)->handle(
            'Laptop', $this->account('1500')->id, 80000, '2026-01-01',
            $this->account('1020')->id, 48,
        );

        $first = app(RunDepreciation::class)->handle($asset, '2026-02-01', $this->account('5500')->id, $this->account('1510')->id);
        $second = app(RunDepreciation::class)->handle($asset->fresh(), '2026-02-15', $this->account('5500')->id, $this->account('1510')->id);

        $this->assertNotNull($first);
        $this->assertNull($second);
        $this->assertSame(round(80000 / 48, 2), (float) $asset->fresh()->accumulated_depreciation);
    }

    public function test_case_7_3_disposal_with_loss(): void
    {
        $asset = app(PostFixedAssetPurchase::class)->handle(
            'Laptop', $this->account('1500')->id, 80000, '2026-01-01',
            $this->account('1020')->id, 48,
        );
        $asset->update(['accumulated_depreciation' => 50000]);

        $disposal = app(DisposeFixedAsset::class)->handle(
            $asset, 25000, '2026-09-01', $this->account('1020')->id,
            $this->account('1510')->id, $this->account('5700')->id, $this->account('4200')->id,
        );

        $this->assertSame(-5000.0, (float) $disposal->gain_loss_amount);
        $this->assertSame(5000.0, $this->account('5700')->balance());
        $this->assertSame(0.0, $this->account('1500')->balance());
        $this->assertSame('disposed', $asset->fresh()->status);
    }

    public function test_case_7_3_disposal_with_gain(): void
    {
        $asset = app(PostFixedAssetPurchase::class)->handle(
            'Laptop', $this->account('1500')->id, 80000, '2026-01-01',
            $this->account('1020')->id, 48,
        );
        $asset->update(['accumulated_depreciation' => 50000]);

        $disposal = app(DisposeFixedAsset::class)->handle(
            $asset, 35000, '2026-09-01', $this->account('1020')->id,
            $this->account('1510')->id, $this->account('5700')->id, $this->account('4200')->id,
        );

        $this->assertSame(5000.0, (float) $disposal->gain_loss_amount);
        $this->assertSame(5000.0, $this->account('4200')->balance());
    }

    public function test_case_8_1_owner_investment_is_equity_not_income(): void
    {
        app(PostOwnerInvestment::class)->handle(
            $this->account('1020')->id, $this->account('3000')->id, 100000, '2026-09-01',
        );

        $this->assertSame(100000.0, $this->account('1020')->balance());
        $this->assertSame(100000.0, $this->account('3000')->balance());
        $this->assertSame(0.0, $this->account('4100')->balance());
    }

    public function test_case_8_3_owner_withdrawal_is_equity_not_expense(): void
    {
        app(PostOwnerWithdrawal::class)->handle(
            $this->account('3100')->id, $this->account('1020')->id, 20000, '2026-09-01',
        );

        $this->assertSame(20000.0, $this->account('3100')->balance());
        $this->assertSame(-20000.0, $this->account('1020')->balance());
        $this->assertSame(0.0, $this->account('5100')->balance());
    }
}
