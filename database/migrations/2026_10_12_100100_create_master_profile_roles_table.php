<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * One row per role a profile plays (customer / supplier / lender / owner /
 * employee) with that role's settings. Existing rows are backfilled by
 * 2026_10_12_100700_link_existing_parties_to_profiles.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_profile_roles', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_profile_id')->constrained('master_profiles')->cascadeOnDelete();
            $table->string('role', 20);
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->unique(['master_profile_id', 'role']);
            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_profile_roles');
    }
};
