<?php

namespace App\Services\DataReset;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

/**
 * Advance → Clean Data: wipes business data so a site can start fresh,
 * while keeping its setup (settings, theme, pages, menus, chart of accounts,
 * warehouses, branches, couriers, templates, locations, roles).
 *
 * Whole groups are TRUNCATEd (ids restart at 1). Users and master profiles
 * are partially deleted, so superadmins and the acting user always survive.
 * Groups that reference each other pull their dependencies in (see
 * `requires`) so no kept row is left pointing at a wiped one.
 */
class DataResetService
{
    public const CONFIRM_PHRASE = 'RESET';

    /**
     * @return array<string, array{label: string, description: string, tables: string[], requires: string[]}>
     */
    public static function groups(): array
    {
        return [
            'orders' => [
                'label' => 'Orders & sales',
                'description' => 'Orders, items, payments, charges, carts, courier shipments/batches/COD, POS sales & sessions, fraud checks, stock bookings.',
                'tables' => [
                    'orders', 'order_items', 'order_item_batches', 'order_charges', 'order_offers', 'order_payments',
                    'order_intake_logs', 'coupon_usages', 'carts', 'cart_items',
                    'courier_shipments', 'courier_tracking_events', 'courier_batches', 'courier_batch_items',
                    'courier_cod_settlements', 'courier_api_logs', 'courier_webhook_logs', 'fraud_checks',
                    'pos_sales', 'pos_sessions', 'pos_cash_movements', 'inventory_stock_booking_movements',
                ],
                'requires' => ['accounting'],
            ],
            'inventory' => [
                'label' => 'Inventory & batches',
                'description' => 'Stock levels, batches, stock movements and receivings. Product/variant stock is set to 0.',
                'tables' => [
                    'inventory_stocks', 'inventory_batches', 'inventory_stock_movements', 'inventory_stock_booking_movements',
                    'stock_receivings', 'stock_receiving_items', 'order_item_batches',
                ],
                'requires' => ['purchases'],
            ],
            'purchases' => [
                'label' => 'Purchases',
                'description' => 'Purchase orders and supplier invoices.',
                'tables' => ['purchase_orders', 'purchase_order_items', 'supplier_invoices', 'supplier_invoice_items'],
                'requires' => ['inventory', 'accounting'],
            ],
            'accounting' => [
                'label' => 'Accounting transactions',
                'description' => 'Journals, vouchers, invoices/bills, advances, reconciliations, day closings, cash counts, assets, loans, payroll, commissions, installments. Chart of accounts is kept; opening balances reset to 0.',
                'tables' => [
                    'journal_entries', 'journal_entry_lines', 'voucher_attachments', 'voucher_events',
                    'accounts_customer_invoices', 'accounts_customer_advances', 'accounts_supplier_bills',
                    'accounts_supplier_advances', 'accounts_payment_allocations',
                    'bank_reconciliations', 'bank_reconciliation_lines', 'cash_counts', 'day_closings',
                    'fixed_assets', 'fixed_asset_depreciation_entries', 'fixed_asset_disposals',
                    'loans', 'loan_repayments', 'loan_repayment_schedules',
                    'payroll_runs', 'payroll_items', 'employee_advances',
                    'commission_accruals', 'commission_settlements', 'gateway_settlements',
                    'installment_plans', 'installments', 'promises_to_pay', 'report_archives',
                ],
                'requires' => [],
            ],
            'customers' => [
                'label' => 'Customers',
                'description' => 'Customers, addresses, wishlists, customer login accounts and customer profiles.',
                'tables' => ['customers', 'delivery_addresses', 'wishlists', 'wishlist_items', 'coupon_customers', 'product_review_helpful_votes'],
                'requires' => ['orders'],
            ],
            'suppliers' => [
                'label' => 'Suppliers',
                'description' => 'Suppliers and supplier profiles.',
                'tables' => ['suppliers'],
                'requires' => ['purchases'],
            ],
            'products' => [
                'label' => 'Products',
                'description' => 'Products, variants, combos, gifts, reviews. Media library files are kept.',
                'tables' => [
                    'products', 'product_variants', 'product_variant_values', 'product_variant_links', 'product_variant_media',
                    'product_attributes', 'product_attribute_values', 'product_attribute_order', 'product_category_pivot',
                    'product_combo_items', 'product_gifts', 'combos', 'combo_items',
                    'product_reviews', 'product_review_media', 'product_review_replies', 'product_review_helpful_votes',
                    'product_review_statistics', 'promotion_items', 'cart_items', 'wishlist_items', 'marketing_event_items',
                ],
                'requires' => ['orders', 'inventory'],
            ],
            'catalog_setup' => [
                'label' => 'Categories, brands & attributes',
                'description' => 'Categories, brands, attributes and attribute values.',
                'tables' => ['categories', 'brands', 'attributes', 'attribute_values'],
                'requires' => ['products'],
            ],
            'promotions' => [
                'label' => 'Offers, promotions & coupons',
                'description' => 'Offers, promotions with their rules, and coupons.',
                'tables' => [
                    'offers', 'promotions', 'promotion_conditions', 'promotion_discount_rules', 'promotion_items',
                    'coupons', 'coupon_customers', 'coupon_usages',
                ],
                'requires' => [],
            ],
            'staff' => [
                'label' => 'Staff users',
                'description' => 'Admin/staff logins. Superadmins and your own account are always kept; staff with POS records are kept unless Orders is wiped too.',
                'tables' => [],
                'requires' => [],
            ],
            'logs' => [
                'label' => 'Logs & tracking',
                'description' => 'Activity log, SMS/email/notification logs, notifications, request logs, visits, devices, marketing events & sessions, failed jobs.',
                'tables' => [
                    'activity_log', 'sms_logs', 'email_logs', 'notification_logs', 'notifications', 'request_logs',
                    'device_visits', 'device_ip_addresses', 'devices',
                    'marketing_sessions', 'marketing_events', 'marketing_event_items', 'marketing_event_destinations',
                    'marketing_attributions', 'marketing_report_exports', 'failed_jobs',
                ],
                'requires' => [],
            ],
        ];
    }

    /** Selected keys plus everything they require, in groups() order. */
    public function resolve(array $selected): array
    {
        $groups = self::groups();
        $resolved = [];
        $queue = array_values(array_intersect($selected, array_keys($groups)));

        while ($queue) {
            $key = array_shift($queue);
            if (isset($resolved[$key])) {
                continue;
            }
            $resolved[$key] = true;
            array_push($queue, ...$groups[$key]['requires']);
        }

        return array_values(array_filter(array_keys($groups), fn ($k) => isset($resolved[$k])));
    }

    /** @return array<string, int> row count per group (existing tables only). */
    public function counts(): array
    {
        $counts = [];
        foreach (self::groups() as $key => $group) {
            $counts[$key] = match ($key) {
                'staff' => $this->staffUserIds()->count(),
                default => collect($group['tables'])->unique()->filter(fn ($t) => Schema::hasTable($t))
                    ->sum(fn ($t) => DB::table($t)->count()),
            };
        }
        $counts['customers'] += $this->customerUserIds()->count();

        return $counts;
    }

    /**
     * @return array{groups: string[], tables: int, users: int, profiles: int}
     */
    public function run(array $selected, User $actor): array
    {
        @set_time_limit(0);

        $keys = $this->resolve($selected);
        $groups = self::groups();

        $tables = collect($keys)->flatMap(fn ($k) => $groups[$k]['tables'])
            ->unique()->filter(fn ($t) => Schema::hasTable($t))->values();

        Schema::disableForeignKeyConstraints();
        try {
            foreach ($tables as $table) {
                DB::table($table)->truncate();
            }
        } finally {
            Schema::enableForeignKeyConstraints();
        }

        $this->afterTruncate($keys);

        // Picked after truncating so POS rows that were just wiped no longer protect a user.
        $userIds = collect();
        if (in_array('customers', $keys)) {
            $userIds = $userIds->merge($this->customerUserIds($actor->id));
        }
        if (in_array('staff', $keys)) {
            $userIds = $userIds->merge($this->staffUserIds($actor->id));
        }
        $userIds = $userIds->unique()->diff($this->posUserIds())->values();

        $users = $this->deleteUsers($userIds->all());

        $profileRoles = array_keys(array_filter(['customer' => 'customers', 'supplier' => 'suppliers'], fn ($g) => in_array($g, $keys)));
        $profiles = $this->pruneProfiles($profileRoles);

        Artisan::call('cache:clear');
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return ['groups' => $keys, 'tables' => $tables->count(), 'users' => $users, 'profiles' => $profiles];
    }

    /** Fix up rows in tables that were kept but pointed at wiped data. */
    protected function afterTruncate(array $keys): void
    {
        $has = fn ($k) => in_array($k, $keys);

        if ($has('orders') && ! $has('inventory')) {
            $this->setColumn('inventory_stocks', 'booked_quantity', 0);
        }
        if ($has('inventory') && ! $has('products')) {
            $this->setColumn('products', 'stock_quantity', 0);
            $this->setColumn('product_variants', 'stock_quantity', 0);
        }
        if ($has('accounting')) {
            $this->setColumn('accounts', 'opening_balance', 0);
        }
        if ($has('accounting') && ! $has('inventory')) {
            // COGS posting marker — its journals are gone now.
            $this->setColumn('order_item_batches', 'posted_at', null);
        }
        if ($has('orders') && ! $has('products')) {
            $this->setColumn('product_reviews', 'order_id', null);
            $this->setColumn('product_reviews', 'order_item_id', null);
        }
        if ($has('customers')) {
            foreach (['combos', 'devices', 'marketing_sessions', 'product_reviews', 'product_review_replies'] as $table) {
                $this->setColumn($table, 'customer_id', null);
            }
        }
    }

    protected function setColumn(string $table, string $column, mixed $value): void
    {
        if (Schema::hasTable($table) && Schema::hasColumn($table, $column)) {
            DB::table($table)->whereNotNull($column)->update([$column => $value]);
        }
    }

    /**
     * Storefront customers: only the 'user' role (or none), and no direct
     * permission or panel access that would mark them as staff.
     */
    protected function customerUserIds(?int $exceptId = null)
    {
        $directPermissions = DB::table(config('permission.table_names.model_has_permissions', 'model_has_permissions'))
            ->where('model_type', (new User)->getMorphClass())->pluck('model_id');

        return DB::table('users')
            ->whereNotIn('id', $this->roleUserIds(fn ($q) => $q->where('roles.name', '!=', 'user')))
            ->whereNotIn('id', $directPermissions)
            ->when(Schema::hasTable('panel_user'), fn ($q) => $q->whereNotIn('id', DB::table('panel_user')->select('user_id')))
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->pluck('id');
    }

    /** POS tables cascade-delete on user delete, so users still owning POS rows are kept. */
    protected function posUserIds()
    {
        return collect(['pos_sessions' => 'user_id', 'pos_sales' => 'created_by', 'pos_cash_movements' => 'created_by'])
            ->filter(fn ($column, $table) => Schema::hasTable($table))
            ->flatMap(fn ($column, $table) => DB::table($table)->distinct()->pluck($column))
            ->unique();
    }

    /** Users holding any non-customer role, minus superadmins. */
    protected function staffUserIds(?int $exceptId = null)
    {
        $superadmins = $this->roleUserIds(fn ($q) => $q->where('roles.name', 'superadmin'));

        return DB::table('users')
            ->whereIn('id', $this->roleUserIds(fn ($q) => $q->where('roles.name', '!=', 'user')))
            ->whereNotIn('id', $superadmins)
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))
            ->pluck('id');
    }

    protected function roleUserIds(callable $filter)
    {
        $table = config('permission.table_names.model_has_roles', 'model_has_roles');
        $roles = config('permission.table_names.roles', 'roles');

        return $filter(DB::table($table)
            ->join($roles, "$roles.id", '=', "$table.role_id")
            ->where("$table.model_type", (new User)->getMorphClass()))
            ->pluck("$table.model_id");
    }

    /** Hard-deletes users with FK checks on, so their references cascade or null out. */
    protected function deleteUsers(array $ids): int
    {
        if (! $ids) {
            return 0;
        }

        $morph = (new User)->getMorphClass();
        $deleted = 0;

        foreach (array_chunk($ids, 500) as $chunk) {
            DB::transaction(function () use ($chunk, $morph, &$deleted) {
                foreach ([
                    config('permission.table_names.model_has_roles', 'model_has_roles'),
                    config('permission.table_names.model_has_permissions', 'model_has_permissions'),
                ] as $table) {
                    DB::table($table)->where('model_type', $morph)->whereIn('model_id', $chunk)->delete();
                }
                foreach (['sessions', 'device_tokens', 'push_subscriptions', 'panel_user'] as $table) {
                    if (Schema::hasTable($table) && Schema::hasColumn($table, 'user_id')) {
                        DB::table($table)->whereIn('user_id', $chunk)->delete();
                    }
                }
                if (Schema::hasTable('personal_access_tokens')) {
                    DB::table('personal_access_tokens')->where('tokenable_type', $morph)->whereIn('tokenable_id', $chunk)->delete();
                }
                if (Schema::hasTable('notifications')) {
                    DB::table('notifications')->where('notifiable_type', $morph)->whereIn('notifiable_id', $chunk)->delete();
                }

                $deleted += DB::table('users')->whereIn('id', $chunk)->delete();
            });
        }

        return $deleted;
    }

    /**
     * Drops the given roles from master profiles, then deletes those profiles
     * if they have no role and no linked user left. A profile still referenced
     * elsewhere (e.g. kept journal entries) is skipped rather than failing.
     */
    protected function pruneProfiles(array $roles): int
    {
        if (! $roles || ! Schema::hasTable('master_profiles')) {
            return 0;
        }

        $affected = DB::table('master_profile_roles')->whereIn('role', $roles)->distinct()->pluck('master_profile_id');
        DB::table('master_profile_roles')->whereIn('role', $roles)->delete();

        $ids = DB::table('master_profiles')
            ->whereIn('id', $affected)
            ->whereNotExists(fn ($q) => $q->from('master_profile_roles')->whereColumn('master_profile_roles.master_profile_id', 'master_profiles.id'))
            ->whereNotIn('id', DB::table('users')->whereNotNull('master_profile_id')->select('master_profile_id'))
            ->pluck('id');

        $deleted = 0;
        foreach ($ids->chunk(500) as $chunk) {
            try {
                $deleted += DB::table('master_profiles')->whereIn('id', $chunk)->delete();
            } catch (QueryException) {
                foreach ($chunk as $id) {
                    try {
                        $deleted += DB::table('master_profiles')->where('id', $id)->delete();
                    } catch (QueryException) {
                        // still referenced — keep it
                    }
                }
            }
        }

        return $deleted;
    }
}
