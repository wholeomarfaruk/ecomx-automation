<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Fixed Assets page: register details (code, category, location, assignee,
 * vendor, warranty), "in repair" state and date reminders (service due,
 * tax token, fitness…) as [{label, date}].
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->after('id');
            $table->string('category', 40)->nullable()->after('name');
            $table->string('location')->nullable()->after('method');
            $table->foreignId('assigned_profile_id')->nullable()->after('location')->constrained('master_profiles')->nullOnDelete();
            $table->string('vendor')->nullable()->after('assigned_profile_id');
            $table->date('warranty_until')->nullable()->after('vendor');
            $table->date('in_repair_since')->nullable()->after('status');
            $table->json('reminders')->nullable()->after('in_repair_since');
        });
    }

    public function down(): void
    {
        Schema::table('fixed_assets', function (Blueprint $table) {
            $table->dropConstrainedForeignId('assigned_profile_id');
            $table->dropColumn(['code', 'category', 'location', 'vendor', 'warranty_until', 'in_repair_since', 'reminders']);
        });
    }
};
