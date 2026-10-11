<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PanelSeeder extends Seeder
{
    public function run(): void
    {
        $panels = [
            [
                'id'          => 1,
                'name'        => 'Admin Panel',
                'slug'        => 'admin',
                'description' => 'Full access to the admin dashboard and management tools.',
                'route_name'  => 'admin.dashboard',
                'is_active'   => true,
                'sort_order'  => 1,
            ],
            [
                'id'          => 2,
                'name'        => 'User Panel',
                'slug'        => 'user',
                'description' => 'Personal dashboard for registered users.',
                'route_name'  => 'dashboard',
                'is_active'   => true,
                'sort_order'  => 2,
            ],
            [
                'id'          => 3,
                'name'        => 'Website',
                'slug'        => 'website',
                'description' => 'Public website — no login required.',
                'route_name'  => 'home',
                'is_active'   => true,
                'sort_order'  => 3,
            ],
        ];

        foreach ($panels as $panel) {
            \App\Models\Panel::updateOrCreate(['id' => $panel['id']], $panel);
        }

        // Keyed by slug, not id — on existing sites the next free id may
        // already be taken, so it isn't fixed like the panels above.
        $accounts = \App\Models\Panel::updateOrCreate(['slug' => 'accounts'], [
            'name'        => 'Accounts Panel',
            'description' => 'Accounts home — cash, due, payable, loans and EMI.',
            'route_name'  => 'accounts.dashboard',
            'is_active'   => true,
            'sort_order'  => 4,
        ]);

        // Superadmins get the Accounts Panel so someone can reach it right
        // away; everyone else is assigned from Admin > Users. On a fresh
        // db:seed this runs before roles exist — the role() scope would throw.
        if (\Spatie\Permission\Models\Role::where('name', 'superadmin')->exists()) {
            \App\Models\User::role('superadmin')->each(
                fn ($user) => $user->panels()->syncWithoutDetaching([$accounts->id])
            );
        }
    }
}
