<?php

namespace Tests\Feature\Accounts;

use App\Actions\Accounts\PostManualTransaction;
use App\Enums\Accounts\TransactionType;
use App\Livewire\Admin\Accounts\Dashboard;
use App\Models\Account;
use App\Models\User;
use Database\Seeders\AccountSeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
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

    public function test_dashboard_reflects_todays_posted_transactions(): void
    {
        app(PostManualTransaction::class)->handle(
            $this->account('1010')->id, $this->account('4100')->id, 10000,
            now()->toDateString(), TransactionType::MANUAL_INCOME,
        );

        app(PostManualTransaction::class)->handle(
            $this->account('5110')->id, $this->account('1010')->id, 3000,
            now()->toDateString(), TransactionType::MANUAL_EXPENSE,
        );

        Livewire::test(Dashboard::class)
            ->assertViewHas('todayIn', 10000.0)
            ->assertViewHas('todayOut', 3000.0)
            ->assertViewHas('cashOnHand', 7000.0)
            ->assertViewHas('currentProfit', 7000.0);
    }
}
