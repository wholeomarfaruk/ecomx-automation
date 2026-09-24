<?php

use App\Models\Account;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * 4910 Sales Discount (contra income) — order-level discounts are posted
     * here at completion (PostSaleWithCogs), so an order's customer invoice
     * equals its total. Also in AccountSeeder for fresh installs.
     */
    public function up(): void
    {
        Account::firstOrCreate(['code' => '4910'], [
            'name'               => 'Sales Discount',
            'type'               => 'income',
            'subtype'            => 'contra_income',
            'normal_balance'     => 'debit',
            'is_control_account' => false,
            'is_system'          => true,
            'is_active'          => true,
        ]);
    }

    public function down(): void
    {
        // Left in place — journal lines may already reference it.
    }
};
