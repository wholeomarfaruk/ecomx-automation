<?php

namespace Tests\Feature\Accounts;

use App\Actions\Accounts\ApplyCustomerCredit;
use App\Actions\Accounts\PostCustomerCreditIssue;
use App\Actions\Accounts\PostCustomerPayment;
use App\Actions\Accounts\PostPurchaseReturn;
use App\Actions\Accounts\PostRefund;
use App\Actions\Accounts\PostRtoFee;
use App\Actions\Accounts\PostSalesReturn;
use App\Actions\Accounts\PostSaleWithCogs;
use App\Actions\Accounts\PostSupplierAdvance;
use App\Actions\Accounts\PostSupplierBill;
use App\Actions\Accounts\PostSupplierPayment;
use App\Actions\Accounts\WriteOffBadDebt;
use App\Enums\Accounts\TransactionType;
use App\Models\Account;
use App\Models\AccountsCustomerInvoice;
use App\Models\AccountsSupplierBill;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\AccountSeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Verifies M2 (Receivables & Payables) against docs/ecomX-accounts-cases.md
 * Cases 3.1-3.10 and 4.1-4.5.
 */
class ReceivablesPayablesTest extends TestCase
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

    protected function makeCustomer(): Customer
    {
        return Customer::create([
            'customer_code' => 'CUST-1',
            'first_name'    => 'Rahim',
            'last_name'     => 'Uddin',
            'full_name'     => 'Rahim Uddin',
            'phone'         => '01700000000',
            'status'        => 'active',
        ]);
    }

    protected function makeSupplier(): Supplier
    {
        return Supplier::create([
            'code'   => 'SUP-1',
            'name'   => 'ABC Traders',
            'status' => 'active',
        ]);
    }

    protected function makeOrder(Customer $customer, float $unitPrice, float $purchasePrice, float $qty = 1): Order
    {
        $order = Order::create(['customer_id' => $customer->id]);

        OrderItem::create([
            'order_id'        => $order->id,
            'product_name'    => 'Test Product',
            'quantity'        => $qty,
            'unit_price'      => $unitPrice,
            'purchase_price'  => $purchasePrice,
            'total_amount'    => $unitPrice * $qty,
        ]);

        return $order;
    }

    public function test_case_3_1_sale_with_cogs_creates_receivable_and_reduces_stock(): void
    {
        $customer = $this->makeCustomer();
        $order = $this->makeOrder($customer, 5000, 3000);

        $result = app(PostSaleWithCogs::class)->handle(
            $order,
            $this->account('1100')->id,
            $this->account('4000')->id,
            $this->account('1200')->id,
            $this->account('5000')->id,
        );

        $this->assertSame(5000.0, $this->account('1100')->balance());
        $this->assertSame(-3000.0, $this->account('1200')->balance());
        $this->assertSame(3000.0, $this->account('5000')->balance());
        $this->assertNotNull($result['invoice']);
        $this->assertSame(5000.0, (float) $result['invoice']->amount);
    }

    public function test_case_3_2_full_payment_closes_invoice(): void
    {
        $customer = $this->makeCustomer();
        $order = $this->makeOrder($customer, 5000, 3000);
        $sale = app(PostSaleWithCogs::class)->handle(
            $order, $this->account('1100')->id, $this->account('4000')->id,
            $this->account('1200')->id, $this->account('5000')->id,
        );

        app(PostCustomerPayment::class)->handle(
            $customer, $this->account('1030')->id, $this->account('1100')->id,
            5000, '2026-09-10',
        );

        $this->assertSame(0.0, $this->account('1100')->balance());
        $this->assertSame('paid', $sale['invoice']->fresh()->status);
    }

    public function test_case_3_3_partial_payment_leaves_balance_due(): void
    {
        $customer = $this->makeCustomer();
        $order = $this->makeOrder($customer, 5000, 3000);
        $sale = app(PostSaleWithCogs::class)->handle(
            $order, $this->account('1100')->id, $this->account('4000')->id,
            $this->account('1200')->id, $this->account('5000')->id,
        );

        app(PostCustomerPayment::class)->handle(
            $customer, $this->account('1030')->id, $this->account('1100')->id,
            2000, '2026-09-10',
        );

        $this->assertSame(3000.0, $this->account('1100')->balance());
        $this->assertSame('partial', $sale['invoice']->fresh()->status);
    }

    public function test_case_3_4_one_payment_allocated_across_two_invoices(): void
    {
        $customer = $this->makeCustomer();

        $order1 = $this->makeOrder($customer, 4000, 2000);
        $sale1 = app(PostSaleWithCogs::class)->handle(
            $order1, $this->account('1100')->id, $this->account('4000')->id,
            $this->account('1200')->id, $this->account('5000')->id,
        );

        $order2 = $this->makeOrder($customer, 6000, 3000);
        $sale2 = app(PostSaleWithCogs::class)->handle(
            $order2, $this->account('1100')->id, $this->account('4000')->id,
            $this->account('1200')->id, $this->account('5000')->id,
        );

        app(PostCustomerPayment::class)->handle(
            $customer, $this->account('1030')->id, $this->account('1100')->id,
            10000, '2026-09-10',
        );

        $this->assertSame(0.0, $this->account('1100')->balance());
        $this->assertSame('paid', $sale1['invoice']->fresh()->status);
        $this->assertSame('paid', $sale2['invoice']->fresh()->status);
    }

    public function test_case_3_6_sales_return_reverses_sale_and_restocks(): void
    {
        $customer = $this->makeCustomer();
        $order = $this->makeOrder($customer, 5000, 3000);
        app(PostSaleWithCogs::class)->handle(
            $order, $this->account('1100')->id, $this->account('4000')->id,
            $this->account('1200')->id, $this->account('5000')->id,
        );

        app(PostSalesReturn::class)->handle(
            $order, $this->account('4900')->id, $this->account('1100')->id,
            $this->account('1200')->id, $this->account('5000')->id,
            5000, 3000, '2026-09-11',
        );

        $this->assertSame(0.0, $this->account('1100')->balance());
        $this->assertSame(0.0, $this->account('1200')->balance());
        $this->assertSame(0.0, $this->account('5000')->balance());
    }

    public function test_case_3_6_rto_only_posts_courier_fee(): void
    {
        $customer = $this->makeCustomer();
        $order = $this->makeOrder($customer, 1000, 600);

        app(PostRtoFee::class)->handle(
            $order, $this->account('5120')->id, $this->account('1030')->id, 80, '2026-09-11',
        );

        $this->assertSame(80.0, $this->account('5120')->balance());
        $this->assertSame(-80.0, $this->account('1030')->balance());
    }

    public function test_case_3_7_refund_to_customer(): void
    {
        app(PostRefund::class)->handle(
            $this->account('4900')->id, $this->account('1030')->id, 5000, '2026-09-11',
        );

        $this->assertSame(5000.0, $this->account('4900')->balance());
        $this->assertSame(-5000.0, $this->account('1030')->balance());
    }

    public function test_case_3_8_overpayment_issues_credit_then_applied_to_new_sale(): void
    {
        $customer = $this->makeCustomer();

        app(PostCustomerCreditIssue::class)->handle(
            $customer, $this->account('1030')->id, $this->account('1100')->id,
            $this->account('2150')->id, 7000, 5000, '2026-09-10',
        );

        $this->assertSame(2000.0, $this->account('2150')->balance());

        app(ApplyCustomerCredit::class)->handle(
            $customer, $this->account('2150')->id, $this->account('4000')->id, 2000, '2026-09-11',
        );

        $this->assertSame(0.0, $this->account('2150')->balance());
        $this->assertSame(2000.0, $this->account('4000')->balance());
    }

    public function test_case_3_9_customer_credit_refunded(): void
    {
        $customer = $this->makeCustomer();

        app(PostCustomerCreditIssue::class)->handle(
            $customer, $this->account('1030')->id, $this->account('1100')->id,
            $this->account('2150')->id, 2000, 0, '2026-09-10',
        );

        app(PostRefund::class)->handle(
            $this->account('2150')->id, $this->account('1030')->id, 2000, '2026-09-11',
            TransactionType::REFUND, null, $customer->id,
        );

        $this->assertSame(0.0, $this->account('2150')->balance());
    }

    public function test_case_3_10_bad_debt_write_off(): void
    {
        $customer = $this->makeCustomer();
        $order = $this->makeOrder($customer, 3000, 1800);
        $sale = app(PostSaleWithCogs::class)->handle(
            $order, $this->account('1100')->id, $this->account('4000')->id,
            $this->account('1200')->id, $this->account('5000')->id,
        );

        app(WriteOffBadDebt::class)->handle(
            $customer, $this->account('5600')->id, $this->account('1100')->id,
            3000, '2026-09-15', $sale['invoice'],
        );

        $this->assertSame(0.0, $this->account('1100')->balance());
        $this->assertSame(3000.0, $this->account('5600')->balance());
        $this->assertSame('written_off', $sale['invoice']->fresh()->status);
    }

    public function test_case_4_1_supplier_bill_increases_stock_and_payable(): void
    {
        $supplier = $this->makeSupplier();

        $bill = app(PostSupplierBill::class)->handle(
            $supplier, $this->account('1200')->id, $this->account('2100')->id,
            100000, '2026-09-10',
        );

        $this->assertSame(100000.0, $this->account('1200')->balance());
        $this->assertSame(100000.0, $this->account('2100')->balance());
        $this->assertInstanceOf(AccountsSupplierBill::class, $bill);
    }

    public function test_case_4_2_supplier_payment_reduces_payable(): void
    {
        $supplier = $this->makeSupplier();
        $bill = app(PostSupplierBill::class)->handle(
            $supplier, $this->account('1200')->id, $this->account('2100')->id, 100000, '2026-09-10',
        );

        app(PostSupplierPayment::class)->handle(
            $supplier, $this->account('2100')->id, $this->account('1020')->id, 70000, '2026-09-11',
        );

        $this->assertSame(30000.0, $this->account('2100')->balance());
        $this->assertSame('partial', $bill->fresh()->status);
    }

    public function test_case_4_3_one_payment_allocated_across_two_bills(): void
    {
        $supplier = $this->makeSupplier();
        $bill1 = app(PostSupplierBill::class)->handle($supplier, $this->account('1200')->id, $this->account('2100')->id, 40000, '2026-09-10');
        $bill2 = app(PostSupplierBill::class)->handle($supplier, $this->account('1200')->id, $this->account('2100')->id, 60000, '2026-09-10');

        app(PostSupplierPayment::class)->handle(
            $supplier, $this->account('2100')->id, $this->account('1020')->id, 100000, '2026-09-11',
        );

        $this->assertSame(0.0, $this->account('2100')->balance());
        $this->assertSame('paid', $bill1->fresh()->status);
        $this->assertSame('paid', $bill2->fresh()->status);
    }

    public function test_case_4_4_purchase_return_reduces_payable_and_stock(): void
    {
        $supplier = $this->makeSupplier();
        app(PostSupplierBill::class)->handle($supplier, $this->account('1200')->id, $this->account('2100')->id, 100000, '2026-09-10');

        app(PostPurchaseReturn::class)->handle(
            $supplier, $this->account('2100')->id, $this->account('1200')->id, 20000, '2026-09-11',
        );

        $this->assertSame(80000.0, $this->account('2100')->balance());
        $this->assertSame(80000.0, $this->account('1200')->balance());
    }

    public function test_case_4_5_supplier_advance_is_an_asset(): void
    {
        $supplier = $this->makeSupplier();

        app(PostSupplierAdvance::class)->handle(
            $supplier, $this->account('1150')->id, $this->account('1020')->id, 50000, '2026-09-10',
        );

        $this->assertSame(50000.0, $this->account('1150')->balance());
        $this->assertSame(-50000.0, $this->account('1020')->balance());
    }
}
