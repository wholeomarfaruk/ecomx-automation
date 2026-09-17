<?php

namespace Tests\Feature\Accounts;

use App\Concerns\CreatesMasterProfile;
use App\Livewire\Admin\Accounts\Receivables;
use App\Livewire\Admin\Accounts\Transactions;
use App\Models\Account;
use App\Models\AccountsCustomerInvoice;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\User;
use Database\Seeders\AccountSeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

/**
 * Exercises the actual Livewire component logic (not just page render) for
 * the two most-used M1/M2 screens, to catch wiring bugs the smoke test's
 * plain GET assertOk() would miss (e.g. a broken $this->validate() rule or
 * a wire:model typo that a page-load test can't see).
 */
class AccountsUiInteractionTest extends TestCase
{
    use RefreshDatabase, CreatesMasterProfile;

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

    public function test_transactions_screen_posts_manual_income(): void
    {
        Livewire::test(Transactions::class)
            ->call('openCreateModal', 'income')
            ->set('newDate', '2026-09-10')
            ->set('newDebitAccountId', $this->account('1010')->id)
            ->set('newCreditAccountId', $this->account('4100')->id)
            ->set('newAmount', '10000')
            ->call('save')
            ->assertSet('createModal', false)
            ->assertDispatched('toast');

        $this->assertSame(10000.0, $this->account('1010')->balance());
    }

    public function test_receivables_screen_receives_payment_and_closes_invoice(): void
    {
        $masterProfile = $this->createMasterProfileFor([
            'display_name' => 'Rahim Uddin',
            'first_name'   => 'Rahim',
            'phone'        => '01700000000',
        ]);

        $customer = Customer::create([
            'master_profile_id' => $masterProfile->id,
            'customer_code' => 'CUST-1',
            'first_name'    => 'Rahim',
            'full_name'     => 'Rahim Uddin',
            'phone'         => '01700000000',
            'status'        => 'active',
        ]);

        $order = Order::create(['customer_id' => $customer->id]);
        OrderItem::create([
            'order_id'       => $order->id,
            'product_name'   => 'Test Product',
            'quantity'       => 1,
            'unit_price'     => 5000,
            'purchase_price' => 3000,
            'total_amount'   => 5000,
        ]);

        app(\App\Actions\Accounts\PostSaleWithCogs::class)->handle(
            $order, $this->account('1100')->id, $this->account('4000')->id,
            $this->account('1200')->id, $this->account('5000')->id,
        );

        Livewire::test(Receivables::class)
            ->call('openPayModal', $customer->id)
            ->set('payAmount', '5000')
            ->set('payCashAccountId', $this->account('1030')->id)
            ->set('payDate', '2026-09-10')
            ->call('receivePayment')
            ->assertDispatched('toast');

        $this->assertSame(0.0, $this->account('1100')->balance());
        $this->assertSame('paid', AccountsCustomerInvoice::where('customer_id', $customer->id)->first()->status);
    }
}
