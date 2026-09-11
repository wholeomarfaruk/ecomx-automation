<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $roles = [
            'superadmin',
            'admin',
            'user',
        ];
        foreach ($roles as $role) {
            // Role::create(['name' => $role]);
            Role::updateOrCreate(['name' => $role]);
        }

        //permissions
        $permissions = [
            //user
            ['id' => 1, 'name' => 'user.show'],
            ['id' => 2, 'name' => 'user.view'],
            ['id' => 3, 'name' => 'user.create'],
            ['id' => 4, 'name' => 'user.edit'],
            ['id' => 5, 'name' => 'user.delete'],
            ['id' => 6, 'name' => 'user.role_assign'],
            ['id' => 7, 'name' => 'user.role_remove'],

            //permissions
            ['id' => 8, 'name' => 'permission.show'],
            ['id' => 9, 'name' => 'permission.view'],
            ['id' => 10, 'name' => 'permission.create'],
            ['id' => 11, 'name' => 'permission.edit'],
            ['id' => 12, 'name' => 'permission.delete'],

            //roles
            ['id' => 13, 'name' => 'role.view'],
            ['id' => 14, 'name' => 'role.create'],
            ['id' => 15, 'name' => 'role.edit'],
            ['id' => 16, 'name' => 'role.delete'],

            //panel
            ['id' => 17, 'name' => 'panel.show'],
            ['id' => 18, 'name' => 'panel.view'],
            ['id' => 23, 'name' => 'panel.create'],
            ['id' => 24, 'name' => 'panel.edit'],
            ['id' => 25, 'name' => 'panel.delete'],

            //dashboard
            ['id' => 19, 'name' => 'dashboard.readonly'],
            ['id' => 20, 'name' => 'dashboard.view'],

            //UI components
            ['id' => 21, 'name' => 'ui.show'],
            ['id' => 22, 'name' => 'ui_components.view'],

            //advance
            ['id' => 26, 'name' => 'developer_tools.view'],
            ['id' => 27, 'name' => 'system_health.view'],
            ['id' => 28, 'name' => 'license_configuration.view'],
            ['id' => 29, 'name' => 'license_configuration.manage'],
            ['id' => 30, 'name' => 'sms_configuration.view'],
            ['id' => 31, 'name' => 'sms_configuration.manage'],
            ['id' => 32, 'name' => 'email_configuration.view'],
            ['id' => 33, 'name' => 'email_configuration.manage'],
            ['id' => 34, 'name' => 'notification_configuration.view'],
            ['id' => 35, 'name' => 'notification_configuration.manage'],

            //catalog - categories
            ['id' => 36, 'name' => 'category.view'],
            ['id' => 37, 'name' => 'category.create'],
            ['id' => 38, 'name' => 'category.edit'],
            ['id' => 39, 'name' => 'category.delete'],

            //catalog - brands
            ['id' => 40, 'name' => 'brand.view'],
            ['id' => 41, 'name' => 'brand.create'],
            ['id' => 42, 'name' => 'brand.edit'],
            ['id' => 43, 'name' => 'brand.delete'],

            //catalog - products
            ['id' => 44, 'name' => 'product.view'],
            ['id' => 45, 'name' => 'product.create'],
            ['id' => 46, 'name' => 'product.edit'],
            ['id' => 47, 'name' => 'product.delete'],

            //catalog - attributes
            ['id' => 48, 'name' => 'attribute.view'],
            ['id' => 49, 'name' => 'attribute.create'],
            ['id' => 50, 'name' => 'attribute.edit'],
            ['id' => 51, 'name' => 'attribute.delete'],

            //purchase - suppliers
            ['id' => 52, 'name' => 'supplier.view'],
            ['id' => 53, 'name' => 'supplier.create'],
            ['id' => 54, 'name' => 'supplier.edit'],
            ['id' => 55, 'name' => 'supplier.delete'],

            //purchase - orders
            ['id' => 56, 'name' => 'purchase_order.view'],
            ['id' => 57, 'name' => 'purchase_order.create'],
            ['id' => 58, 'name' => 'purchase_order.edit'],
            ['id' => 59, 'name' => 'purchase_order.delete'],

            //courier configuration
            ['id' => 60, 'name' => 'courier_configuration.view'],
            ['id' => 61, 'name' => 'courier_configuration.manage'],

            //accounts - dashboard & transactions
            ['id' => 62, 'name' => 'accounts_dashboard.view'],
            ['id' => 63, 'name' => 'accounts_transaction.view'],
            ['id' => 64, 'name' => 'accounts_transaction.create'],
            ['id' => 65, 'name' => 'accounts_transaction.edit'],
            ['id' => 66, 'name' => 'accounts_transaction.void'],

            //accounts - cash & bank
            ['id' => 67, 'name' => 'accounts_cash_account.view'],
            ['id' => 68, 'name' => 'accounts_cash_account.manage'],

            //accounts - receivables
            ['id' => 69, 'name' => 'accounts_receivable.view'],
            ['id' => 70, 'name' => 'accounts_receivable.manage'],

            //accounts - payables
            ['id' => 71, 'name' => 'accounts_payable.view'],
            ['id' => 72, 'name' => 'accounts_payable.manage'],

            //accounts - loans
            ['id' => 73, 'name' => 'accounts_loan.view'],
            ['id' => 74, 'name' => 'accounts_loan.manage'],

            //accounts - expenses
            ['id' => 75, 'name' => 'accounts_expense.view'],
            ['id' => 76, 'name' => 'accounts_expense.manage'],

            //accounts - fixed assets
            ['id' => 77, 'name' => 'accounts_fixed_asset.view'],
            ['id' => 78, 'name' => 'accounts_fixed_asset.manage'],

            //accounts - owner equity
            ['id' => 79, 'name' => 'accounts_owner_equity.manage'],

            //accounts - reports
            ['id' => 80, 'name' => 'accounts_report.view'],

            //accounts - journal entries / settings (advanced)
            ['id' => 81, 'name' => 'journal_entry.view'],
            ['id' => 82, 'name' => 'journal_entry.create'],
            ['id' => 83, 'name' => 'journal_entry.void'],
            ['id' => 84, 'name' => 'accounts_settings.manage'],
            ['id' => 85, 'name' => 'fiscal_period.manage'],

        ];
        foreach ($permissions as $permission) {
            // Permission::create(['name' => $permission]);
            Permission::updateOrCreate(
                ['id' => $permission['id'] ?? null],
                ['name' => $permission['name'] ?? null]
            );
        }
      
    }
}
