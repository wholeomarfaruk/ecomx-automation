<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Accounts Panel › Settings › Users & permissions: the role × permission
 * matrix from the design. The four design roles get the design defaults;
 * every other existing staff role keeps full access so nobody who uses the
 * panel today is locked out (narrow it from Settings).
 */
return new class extends Migration
{
    public const PERMISSIONS = ['view', 'create', 'post', 'approve', 'reverse', 'reports', 'cash', 'settings'];

    /** role => permissions granted by default (design: Settings.dc.html). */
    public const DEFAULTS = [
        'owner'      => ['view', 'create', 'post', 'approve', 'reverse', 'reports', 'cash', 'settings'],
        'accountant' => ['view', 'create', 'post', 'reverse', 'reports', 'cash'],
        'sales'      => ['view', 'create'],
        'inventory'  => ['view'],
    ];

    public function up(): void
    {
        foreach (self::PERMISSIONS as $p) {
            Permission::findOrCreate("accounts.{$p}", 'web');
        }

        foreach (self::DEFAULTS as $role => $perms) {
            Role::findOrCreate($role, 'web')->givePermissionTo(array_map(fn ($p) => "accounts.{$p}", $perms));
        }

        $all = array_map(fn ($p) => "accounts.{$p}", self::PERMISSIONS);
        Role::query()->whereNotIn('name', array_merge(array_keys(self::DEFAULTS), ['user']))->get()
            ->each(fn (Role $r) => $r->givePermissionTo($all));

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Permission::whereIn('name', array_map(fn ($p) => "accounts.{$p}", self::PERMISSIONS))->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
