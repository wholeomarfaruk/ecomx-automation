<?php

use App\Actions\Profiles\LinkPartiesToProfiles;
use Illuminate\Database\Migrations\Migration;

/**
 * Bring existing customers, suppliers and loans under Master Profiles
 * (profile + role per party). Safe to re-run; only unlinked rows change.
 */
return new class extends Migration
{
    public function up(): void
    {
        app(LinkPartiesToProfiles::class)->handle();
    }

    public function down(): void
    {
        // Links and roles are kept: rolling back the tables that hold them
        // (master_profile_roles, loans.master_profile_id) removes them anyway.
    }
};
