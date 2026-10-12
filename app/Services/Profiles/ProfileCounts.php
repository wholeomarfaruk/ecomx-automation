<?php

namespace App\Services\Profiles;

use App\Models\MasterProfile;
use Illuminate\Support\Facades\DB;

/**
 * Live counts for the sidebar and the profile tabs: all profiles and
 * profiles per role. Two small grouped queries, memoised per request.
 */
class ProfileCounts
{
    protected const KEY = 'accounts.profile-counts';

    public static function all(): array
    {
        if (app()->bound(self::KEY)) {
            return app(self::KEY);
        }

        $byRole = DB::table('master_profile_roles as r')
            ->join('master_profiles as p', 'p.id', '=', 'r.master_profile_id')
            ->whereNull('p.deleted_at')
            ->groupBy('r.role')
            ->pluck(DB::raw('COUNT(*)'), 'r.role');

        $counts = [
            'profiles'  => MasterProfile::count(),
            'customers' => (int) ($byRole['customer'] ?? 0),
            'suppliers' => (int) ($byRole['supplier'] ?? 0),
            'lenders'   => (int) ($byRole['lender'] ?? 0),
            'owners'    => (int) ($byRole['owner'] ?? 0),
            'employees' => (int) ($byRole['employee'] ?? 0),
        ];

        app()->instance(self::KEY, $counts);

        return $counts;
    }

    public static function flush(): void
    {
        app()->forgetInstance(self::KEY);
    }

    /** Tab / sidebar definitions in design order. */
    public static function tabs(): array
    {
        $c = static::all();

        return [
            ['key' => 'profiles',  'label' => 'Master Profiles', 'route' => 'accounts.profiles.index',     'count' => $c['profiles']],
            ['key' => 'customers', 'label' => 'Customers',       'route' => 'accounts.profiles.customers', 'count' => $c['customers']],
            ['key' => 'suppliers', 'label' => 'Suppliers',       'route' => 'accounts.profiles.suppliers', 'count' => $c['suppliers']],
            ['key' => 'lenders',   'label' => 'Lenders',         'route' => 'accounts.profiles.lenders',   'count' => $c['lenders']],
            ['key' => 'owners',    'label' => 'Owners',          'route' => 'accounts.profiles.owners',    'count' => $c['owners']],
            ['key' => 'employees', 'label' => 'Employees',       'route' => 'accounts.profiles.employees', 'count' => $c['employees']],
        ];
    }
}
