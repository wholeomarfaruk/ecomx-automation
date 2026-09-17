<?php

namespace Database\Seeders;

use App\Actions\Accounts\ApplyCustomerCredit;
use App\Concerns\CreatesMasterProfile;
use App\Actions\Accounts\DisposeFixedAsset;
use App\Actions\Accounts\PostCodSettlement;
use App\Actions\Accounts\PostCurrencyConversion;
use App\Actions\Accounts\PostCustomerCreditIssue;
use App\Actions\Accounts\PostCustomerPayment;
use App\Actions\Accounts\PostFixedAssetPurchase;
use App\Actions\Accounts\PostGatewaySettlement;
use App\Actions\Accounts\PostLoanReceived;
use App\Actions\Accounts\PostLoanRepayment;
use App\Actions\Accounts\PostManualTransaction;
use App\Actions\Accounts\PostOpeningBalance;
use App\Actions\Accounts\PostOwnerInvestment;
use App\Actions\Accounts\PostOwnerWithdrawal;
use App\Actions\Accounts\PostPurchaseReturn;
use App\Actions\Accounts\PostRecurringExpenseRun;
use App\Actions\Accounts\PostSalesReturn;
use App\Actions\Accounts\PostSaleWithCogs;
use App\Actions\Accounts\PostSupplierAdvance;
use App\Actions\Accounts\PostSupplierBill;
use App\Actions\Accounts\PostSupplierPayment;
use App\Actions\Accounts\PostTransferWithFee;
use App\Actions\Accounts\PostVatSale;
use App\Actions\Accounts\RunDepreciation;
use App\Actions\Accounts\WriteOffBadDebt;
use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\Courier;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\RecurringExpense;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Populates the Accounts module with realistic demo data covering most of
 * docs/ecomX-accounts-cases.md so every screen (Dashboard, Transactions,
 * Receivables, Payables, Loans, Fixed Assets, Owner Equity, Reports) has
 * something real to show. Not part of the default DatabaseSeeder run —
 * call explicitly with `php artisan db:seed --class=AccountsDemoSeeder`
 * since it posts real journal entries and creates its own lightweight
 * customer/supplier/order fixtures.
 */
class AccountsDemoSeeder extends Seeder
{
    use CreatesMasterProfile;

    public function run(): void
    {
        $user = User::where('email', 'superadmin@gmail.com')->first() ?? User::first();
        auth()->login($user);

        $accounts = Account::query()->get()->keyBy('code');
        $acc = fn (string $code) => $accounts[$code]->id;

        // --- Opening balance (Case 9.1) ---
        app(PostOpeningBalance::class)->handle([
            $acc('1010') => 50000,
            $acc('1020') => 200000,
            $acc('1200') => 500000,
        ], $acc('3950'), now()->subMonths(2)->toDateString(), 'Opening balance');

        // --- Owner equity (Case 8.1, 8.3) ---
        app(PostOwnerInvestment::class)->handle(
            $acc('1020'), $acc('3000'), 300000, now()->subMonths(2)->toDateString(), 'Initial capital'
        );
        app(PostOwnerWithdrawal::class)->handle(
            $acc('3100'), $acc('1010'), 15000, now()->subDays(20)->toDateString(), 'Personal withdrawal'
        );

        // --- Manual income/expense/transfer/conversion (Case 1.1-1.5) ---
        app(PostManualTransaction::class)->handle(
            $acc('1010'), $acc('4100'), 8000, now()->subDays(10)->toDateString(), TransactionType::MANUAL_INCOME, 'Old customer settled cash'
        );
        app(PostManualTransaction::class)->handle(
            $acc('5110'), $acc('1030'), 6000, now()->subDays(3)->toDateString(), TransactionType::MANUAL_EXPENSE, 'Facebook ad campaign'
        );
        app(PostTransferWithFee::class)->handle(
            $acc('1030'), $acc('1020'), 20000, now()->subDays(5)->toDateString(), $acc('5300'), 20, 'bKash to bank transfer'
        );
        app(PostCurrencyConversion::class)->handle(
            $acc('1030'), $acc('1040'), 12200, 12000, now()->subDays(15)->toDateString(), $acc('5320'), 200, 'Bought $100 for Dollar Card'
        );

        // --- Customers + sales with COGS + payments (Case 3.1-3.4) ---
        $rahimProfile = $this->createMasterProfileFor([
            'display_name' => 'Rahim Uddin', 'first_name' => 'Rahim', 'last_name' => 'Uddin', 'phone' => '01711000001',
        ]);
        $rahim = Customer::create([
            'master_profile_id' => $rahimProfile->id,
            'customer_code' => 'CUST-DEMO-1', 'first_name' => 'Rahim', 'last_name' => 'Uddin',
            'full_name' => 'Rahim Uddin', 'phone' => '01711000001', 'status' => 'active',
        ]);
        $karimProfile = $this->createMasterProfileFor([
            'display_name' => 'Karim Molla', 'first_name' => 'Karim', 'last_name' => 'Molla', 'phone' => '01711000002',
        ]);
        $karim = Customer::create([
            'master_profile_id' => $karimProfile->id,
            'customer_code' => 'CUST-DEMO-2', 'first_name' => 'Karim', 'last_name' => 'Molla',
            'full_name' => 'Karim Molla', 'phone' => '01711000002', 'status' => 'active',
        ]);

        $order1 = $this->makeOrder($rahim, 'Cotton Panjabi', 1, 4000, 2400);
        $sale1 = app(PostSaleWithCogs::class)->handle(
            $order1, $acc('1100'), $acc('4000'), $acc('1200'), $acc('5000'), now()->subDays(8)->toDateString()
        );
        app(PostCustomerPayment::class)->handle(
            $rahim, $acc('1030'), $acc('1100'), 4000, now()->subDays(6)->toDateString()
        );

        $order2 = $this->makeOrder($karim, 'Leather Wallet', 1, 6000, 3500);
        $sale2 = app(PostSaleWithCogs::class)->handle(
            $order2, $acc('1100'), $acc('4000'), $acc('1200'), $acc('5000'), now()->subDays(4)->toDateString()
        );
        app(PostCustomerPayment::class)->handle(
            $karim, $acc('1010'), $acc('1100'), 2000, now()->subDays(2)->toDateString()
        );

        // A third sale left fully unpaid and old enough to show as an
        // overdue receivable on the dashboard.
        $sabbirProfile = $this->createMasterProfileFor([
            'display_name' => 'Sabbir Hasan', 'first_name' => 'Sabbir', 'last_name' => 'Hasan', 'phone' => '01711000003',
        ]);
        $sabbir = Customer::create([
            'master_profile_id' => $sabbirProfile->id,
            'customer_code' => 'CUST-DEMO-3', 'first_name' => 'Sabbir', 'last_name' => 'Hasan',
            'full_name' => 'Sabbir Hasan', 'phone' => '01711000003', 'status' => 'active',
        ]);
        $order3 = $this->makeOrder($sabbir, 'Sneakers', 1, 3000, 1800);
        $sale3 = app(PostSaleWithCogs::class)->handle(
            $order3, $acc('1100'), $acc('4000'), $acc('1200'), $acc('5000'), now()->subDays(12)->toDateString()
        );
        // Dashboard's "overdue" heuristic reads AccountsCustomerInvoice.created_at
        // (see Dashboard::render()), which is always "now" at insert time
        // regardless of the backdated entry_date above — backdate it
        // explicitly so this invoice actually shows up as overdue.
        $sale3['invoice']->forceFill(['created_at' => now()->subDays(12)])->save();

        // --- Sales return (Case 3.6) ---
        app(PostSalesReturn::class)->handle(
            $order1, $acc('4900'), $acc('1100'), $acc('1200'), $acc('5000'), 4000, 2400,
            now()->subDays(1)->toDateString(), 'Customer returned panjabi (wrong size)'
        );

        // --- Customer credit issue + redemption (Case 3.8) ---
        $anikaProfile = $this->createMasterProfileFor([
            'display_name' => 'Anika Rahman', 'first_name' => 'Anika', 'last_name' => 'Rahman', 'phone' => '01711000004',
        ]);
        $anika = Customer::create([
            'master_profile_id' => $anikaProfile->id,
            'customer_code' => 'CUST-DEMO-4', 'first_name' => 'Anika', 'last_name' => 'Rahman',
            'full_name' => 'Anika Rahman', 'phone' => '01711000004', 'status' => 'active',
        ]);
        app(PostCustomerCreditIssue::class)->handle(
            $anika, $acc('1030'), $acc('1100'), $acc('2150'), 2000, 0, now()->subDays(7)->toDateString(), 'Overpayment credited'
        );
        app(ApplyCustomerCredit::class)->handle(
            $anika, $acc('2150'), $acc('4000'), 1000, now()->subDays(1)->toDateString(), 'Credit applied to new order'
        );

        // --- Bad debt write-off (Case 3.10) ---
        $lostProfile = $this->createMasterProfileFor([
            'display_name' => 'Unreachable Customer', 'first_name' => 'Unknown', 'last_name' => '', 'phone' => '01711000005',
        ]);
        $lost = Customer::create([
            'master_profile_id' => $lostProfile->id,
            'customer_code' => 'CUST-DEMO-5', 'first_name' => 'Unknown', 'last_name' => '',
            'full_name' => 'Unreachable Customer', 'phone' => '01711000005', 'status' => 'inactive',
        ]);
        $orderLost = $this->makeOrder($lost, 'Sunglasses', 1, 1500, 900);
        $saleLost = app(PostSaleWithCogs::class)->handle(
            $orderLost, $acc('1100'), $acc('4000'), $acc('1200'), $acc('5000'), now()->subDays(40)->toDateString()
        );
        app(WriteOffBadDebt::class)->handle(
            $lost, $acc('5600'), $acc('1100'), 1500, now()->subDays(5)->toDateString(), $saleLost['invoice']
        );

        // --- VAT sale (Case 12.1) ---
        app(PostVatSale::class)->handle(
            $acc('1010'), $acc('4000'), $acc('2300'), 1000, 75, now()->subDays(2)->toDateString(), null, 'VAT-inclusive counter sale'
        );

        // --- Suppliers + bills + payments + return + advance (Case 4.1-4.5) ---
        $abcTradersProfile = $this->createMasterProfileFor(['type' => 'organization', 'display_name' => 'ABC Traders']);
        $abcTraders = Supplier::create(['master_profile_id' => $abcTradersProfile->id, 'code' => 'SUP-DEMO-1', 'name' => 'ABC Traders', 'status' => 'active']);
        $xyzImportsProfile = $this->createMasterProfileFor(['type' => 'organization', 'display_name' => 'XYZ Imports']);
        $xyzImports = Supplier::create(['master_profile_id' => $xyzImportsProfile->id, 'code' => 'SUP-DEMO-2', 'name' => 'XYZ Imports', 'status' => 'active']);

        $bill1 = app(PostSupplierBill::class)->handle(
            $abcTraders, $acc('1200'), $acc('2100'), 100000, now()->subDays(25)->toDateString(), null, 'Fabric purchase'
        );
        app(PostSupplierPayment::class)->handle(
            $abcTraders, $acc('2100'), $acc('1020'), 70000, now()->subDays(15)->toDateString()
        );

        $bill2 = app(PostSupplierBill::class)->handle(
            $xyzImports, $acc('1200'), $acc('2100'), 40000, now()->subDays(18)->toDateString(), null, 'Accessories purchase'
        );
        app(PostPurchaseReturn::class)->handle(
            $xyzImports, $acc('2100'), $acc('1200'), 5000, now()->subDays(16)->toDateString(), 'Returned defective accessories'
        );

        app(PostSupplierAdvance::class)->handle(
            $abcTraders, $acc('1150'), $acc('1020'), 20000, now()->subDays(10)->toDateString(), 'Advance for next order'
        );

        // --- Loan received + repayment (Case 5.1-5.2) ---
        $loan = app(PostLoanReceived::class)->handle(
            'Working Capital Loan', 'City Bank', 500000, now()->subMonths(2)->toDateString(),
            $acc('1020'), $acc('2200'), 12.0, 24
        );
        app(PostLoanRepayment::class)->handle(
            $loan, $acc('1020'), $acc('5400'), 20000, 5000, now()->subMonths(1)->toDateString()
        );

        // --- Fixed asset + depreciation (Case 7.1-7.2) ---
        $laptop = app(PostFixedAssetPurchase::class)->handle(
            'Office Laptop', $acc('1500'), 80000, now()->subMonths(2)->toDateString(), $acc('1020'), 48
        );
        app(RunDepreciation::class)->handle(
            $laptop, now()->subMonths(1)->startOfMonth()->toDateString(), $acc('5500'), $acc('1510')
        );
        app(RunDepreciation::class)->handle(
            $laptop->fresh(), now()->startOfMonth()->toDateString(), $acc('5500'), $acc('1510')
        );

        // Second asset, disposed for a small loss — populates the "disposed" state too.
        $oldPrinter = app(PostFixedAssetPurchase::class)->handle(
            'Old Printer', $acc('1500'), 15000, now()->subMonths(6)->toDateString(), $acc('1020'), 36
        );
        $oldPrinter->update(['accumulated_depreciation' => 10000]);
        app(DisposeFixedAsset::class)->handle(
            $oldPrinter, 3000, now()->subDays(3)->toDateString(),
            $acc('1010'), $acc('1510'), $acc('5700'), $acc('4200')
        );

        // --- Recurring expense (Case 6.2), one run posted, one still due ---
        $rent = RecurringExpense::create([
            'name' => 'Office Rent', 'account_id' => $acc('5140'), 'from_account_id' => $acc('1020'),
            'amount' => 25000, 'cadence' => 'monthly', 'next_run_date' => now()->subDays(35)->toDateString(), 'is_active' => true,
        ]);
        app(PostRecurringExpenseRun::class)->handle($rent);
        // next_run_date is now ~5 days from "last month" — push it into the past again so it shows as due on the dashboard.
        $rent->update(['next_run_date' => now()->subDays(2)->toDateString()]);

        RecurringExpense::create([
            'name' => 'Internet Bill', 'account_id' => $acc('5100'), 'from_account_id' => $acc('1030'),
            'amount' => 2000, 'cadence' => 'monthly', 'next_run_date' => now()->addDays(10)->toDateString(), 'is_active' => true,
        ]);

        // --- Courier COD + gateway settlement (Case 10.1-10.2, 11.1) ---
        $courier = Courier::first();
        if ($courier) {
            app(PostManualTransaction::class)->handle(
                $acc('1050'), $acc('4000'), 5000, now()->subDays(6)->toDateString(), TransactionType::SALE, 'COD order shipped'
            );
            app(PostCodSettlement::class)->handle(
                $courier, 5000, 100, now()->subDays(3)->toDateString(), $acc('1020'), $acc('1050'), $acc('5120')
            );
        }

        $bkashGateway = Account::firstOrCreate(
            ['code' => '1061'],
            [
                'name' => 'bKash Gateway', 'type' => 'asset', 'subtype' => 'receivable',
                'normal_balance' => 'debit', 'parent_id' => $acc('1060'), 'is_system' => false, 'is_active' => true,
            ]
        );
        app(PostManualTransaction::class)->handle(
            $bkashGateway->id, $acc('4000'), 3000, now()->subDays(4)->toDateString(), TransactionType::SALE, 'Online payment via bKash'
        );
        app(PostGatewaySettlement::class)->handle(
            $bkashGateway->id, 3000, 60, now()->subDays(2)->toDateString(), $acc('1020'), $acc('5310')
        );
    }

    protected function makeOrder(Customer $customer, string $productName, float $qty, float $unitPrice, float $purchasePrice): Order
    {
        $order = Order::create(['customer_id' => $customer->id]);

        OrderItem::create([
            'order_id'       => $order->id,
            'product_name'   => $productName,
            'quantity'       => $qty,
            'unit_price'     => $unitPrice,
            'purchase_price' => $purchasePrice,
            'total_amount'   => $unitPrice * $qty,
        ]);

        return $order;
    }
}
