<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CustomerGroupSeeder extends Seeder
{
    public function run(): void
    {
        $customerGroups = [
            [
                'name' => 'Regular',
                'description' => 'Default customer group with no special pricing or discounts.',
                'discount_type' => null,
                'discount_value' => 0.00,
                'minimum_order_amount' => 0.00,
                'minimum_order_qty' => 0,
                'allow_credit' => false,
                'reward_points_enabled' => true,
                'is_default' => true,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Wholesale',
                'description' => 'Bulk buyers who qualify for percentage-based discounts.',
                'discount_type' => 'Percentage',
                'discount_value' => 10.00,
                'minimum_order_amount' => 0.00,
                'minimum_order_qty' => 10.00,
                'allow_credit' => true,
                'reward_points_enabled' => false,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'VIP',
                'description' => 'High-value customers with fixed discounts and credit privileges.',
                'discount_type' => 'Fixed',
                'discount_value' => 50.00,
                'minimum_order_amount' => 0.00,
                'minimum_order_qty' => 0.00,
                'allow_credit' => true,
                'reward_points_enabled' => true,
                'is_default' => false,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        foreach ($customerGroups as $customerGroup) {
            DB::table('customer_groups')->updateOrInsert(
                ['name' => $customerGroup['name']],
                array_merge($customerGroup, ['updated_at' => now()])
            );
        }
    }
}
