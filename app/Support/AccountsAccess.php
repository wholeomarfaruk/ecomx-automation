<?php

namespace App\Support;

use App\Models\User;
use Throwable;

/**
 * Accounts Panel permissions (Settings › Users & permissions):
 * view · create · post · approve · reverse · reports · cash · settings.
 * Superadmin can do everything; so can anyone while the permission rows
 * don't exist yet (before the migration runs).
 */
class AccountsAccess
{
    public static function can(string $permission, ?User $user = null): bool
    {
        $user ??= auth()->user();

        if (! $user) {
            return false;
        }

        if ($user->hasRole('superadmin')) {
            return true;
        }

        try {
            return $user->hasPermissionTo("accounts.{$permission}");
        } catch (Throwable) {
            // PermissionDoesNotExist: not migrated yet.
            return true;
        }
    }

    public static function authorize(string $permission): void
    {
        abort_unless(self::can($permission), 403, 'You do not have permission to do this.');
    }

    /** Owners are exempt from maker-checker. */
    public static function isOwner(?User $user = null): bool
    {
        $user ??= auth()->user();

        return (bool) $user?->hasAnyRole(['superadmin', 'owner']);
    }
}
