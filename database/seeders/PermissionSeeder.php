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

            //users - master profile
            ['id' => 86, 'name' => 'master_profile.view'],
            ['id' => 87, 'name' => 'master_profile.create'],
            ['id' => 88, 'name' => 'master_profile.edit'],
            ['id' => 89, 'name' => 'master_profile.delete'],

            //users - devices, blocks, active
            ['id' => 90, 'name' => 'user_device.view'],
            ['id' => 91, 'name' => 'user_block.view'],
            ['id' => 92, 'name' => 'user_block.manage'],
            ['id' => 93, 'name' => 'active_user.view'],

            //sales - orders
            ['id' => 94, 'name' => 'order.view'],
            ['id' => 95, 'name' => 'order.create'],
            ['id' => 96, 'name' => 'order.edit'],
            ['id' => 97, 'name' => 'order.delete'],
            ['id' => 98, 'name' => 'order.status_change'],
            ['id' => 99, 'name' => 'order.print'],

            //sales - coupons
            ['id' => 100, 'name' => 'coupon.view'],
            ['id' => 101, 'name' => 'coupon.create'],
            ['id' => 102, 'name' => 'coupon.edit'],
            ['id' => 103, 'name' => 'coupon.delete'],
            ['id' => 104, 'name' => 'coupon_usage.view'],

            //sales - offers
            ['id' => 105, 'name' => 'offer.view'],
            ['id' => 106, 'name' => 'offer.create'],
            ['id' => 107, 'name' => 'offer.edit'],
            ['id' => 108, 'name' => 'offer.delete'],

            //sales - pos
            ['id' => 109, 'name' => 'pos.access'],
            ['id' => 110, 'name' => 'pos_register.view'],
            ['id' => 111, 'name' => 'pos_register.manage'],
            ['id' => 112, 'name' => 'pos_session.view'],
            ['id' => 113, 'name' => 'pos_session.open'],
            ['id' => 114, 'name' => 'pos_session.close'],

            //customers
            ['id' => 115, 'name' => 'customer.view'],
            ['id' => 116, 'name' => 'customer.create'],
            ['id' => 117, 'name' => 'customer.edit'],
            ['id' => 118, 'name' => 'customer.delete'],
            ['id' => 119, 'name' => 'customer_group.view'],
            ['id' => 120, 'name' => 'customer_group.create'],
            ['id' => 121, 'name' => 'customer_group.edit'],
            ['id' => 122, 'name' => 'customer_group.delete'],
            ['id' => 123, 'name' => 'cart.view'],
            ['id' => 124, 'name' => 'combo.view'],
            ['id' => 125, 'name' => 'loved_product.view'],

            //customers - reviews
            ['id' => 126, 'name' => 'review.view'],
            ['id' => 127, 'name' => 'review.create'],
            ['id' => 128, 'name' => 'review.edit'],
            ['id' => 129, 'name' => 'review.delete'],
            ['id' => 130, 'name' => 'review.approve'],
            ['id' => 131, 'name' => 'review_settings.manage'],

            //purchase - supplier invoices & ledger
            ['id' => 132, 'name' => 'supplier_invoice.view'],
            ['id' => 133, 'name' => 'supplier_invoice.create'],
            ['id' => 134, 'name' => 'supplier_invoice.edit'],
            ['id' => 135, 'name' => 'supplier_invoice.delete'],
            ['id' => 136, 'name' => 'supplier_ledger.view'],

            //inventory
            ['id' => 137, 'name' => 'stock.view'],
            ['id' => 138, 'name' => 'stock.adjust'],
            ['id' => 139, 'name' => 'stock_in.create'],
            ['id' => 140, 'name' => 'batch.view'],
            ['id' => 141, 'name' => 'stock_movement.view'],
            ['id' => 142, 'name' => 'warehouse.view'],
            ['id' => 143, 'name' => 'warehouse.create'],
            ['id' => 144, 'name' => 'warehouse.edit'],
            ['id' => 145, 'name' => 'warehouse.delete'],
            ['id' => 146, 'name' => 'inventory_settings.manage'],

            //accounts - chart of accounts & opening balance
            ['id' => 147, 'name' => 'chart_of_account.view'],
            ['id' => 148, 'name' => 'chart_of_account.manage'],
            ['id' => 149, 'name' => 'opening_balance.manage'],

            //marketing
            ['id' => 150, 'name' => 'marketing_dashboard.view'],
            ['id' => 151, 'name' => 'marketing_journey.view'],
            ['id' => 152, 'name' => 'marketing_campaign.view'],
            ['id' => 153, 'name' => 'marketing_campaign.manage'],
            ['id' => 154, 'name' => 'marketing_analytics.view'],
            ['id' => 155, 'name' => 'marketing_report.view'],
            ['id' => 156, 'name' => 'marketing_settings.manage'],

            //frontend / storefront
            ['id' => 157, 'name' => 'frontend.view'],
            ['id' => 158, 'name' => 'frontend.manage'],
            ['id' => 159, 'name' => 'appearance.manage'],
            ['id' => 160, 'name' => 'theme.manage'],
            ['id' => 161, 'name' => 'menu.manage'],

            //landing pages
            ['id' => 162, 'name' => 'landing_page.view'],
            ['id' => 163, 'name' => 'landing_page.create'],
            ['id' => 164, 'name' => 'landing_page.edit'],
            ['id' => 165, 'name' => 'landing_page.delete'],
            ['id' => 166, 'name' => 'landing_page_settings.manage'],

            //settings
            ['id' => 167, 'name' => 'settings.view'],
            ['id' => 168, 'name' => 'settings.manage'],
            ['id' => 169, 'name' => 'shipping.view'],
            ['id' => 170, 'name' => 'shipping.manage'],
            ['id' => 171, 'name' => 'site_settings.view'],
            ['id' => 172, 'name' => 'site_settings.manage'],
            ['id' => 173, 'name' => 'location.view'],
            ['id' => 174, 'name' => 'location.manage'],
            ['id' => 175, 'name' => 'gender.manage'],
            ['id' => 176, 'name' => 'currency.view'],
            ['id' => 177, 'name' => 'currency.manage'],
            ['id' => 178, 'name' => 'branch.view'],
            ['id' => 179, 'name' => 'branch.create'],
            ['id' => 180, 'name' => 'branch.edit'],
            ['id' => 181, 'name' => 'branch.delete'],
            ['id' => 182, 'name' => 'language.view'],
            ['id' => 183, 'name' => 'language.create'],
            ['id' => 184, 'name' => 'language.edit'],
            ['id' => 185, 'name' => 'language.delete'],

            //logs & media
            ['id' => 186, 'name' => 'activity_log.view'],
            ['id' => 187, 'name' => 'visit.view'],
            ['id' => 188, 'name' => 'media.view'],
            ['id' => 189, 'name' => 'media.upload'],
            ['id' => 190, 'name' => 'media.delete'],

            //sidebar menus not covered above
            ['id' => 191, 'name' => 'frontend_component.manage'],
            ['id' => 192, 'name' => 'landing_page_template.view'],
            ['id' => 193, 'name' => 'icon.view'],
            ['id' => 194, 'name' => 'marketing_source.view'],
            ['id' => 195, 'name' => 'marketing_product.view'],
            ['id' => 196, 'name' => 'marketing_customer.view'],
            ['id' => 197, 'name' => 'marketing_audience.view'],
            ['id' => 198, 'name' => 'marketing_event.view'],
            ['id' => 199, 'name' => 'marketing_attribution.view'],
            ['id' => 200, 'name' => 'marketing_integration.manage'],

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
