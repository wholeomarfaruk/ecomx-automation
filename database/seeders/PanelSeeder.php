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
    }
}
