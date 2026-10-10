<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Last FraudShield result per phone (01XXXXXXXXX). Re-used for the
     * badge on the Orders list and the card on the Order page, so a
     * phone is only sent to the API again when the admin asks for a
     * fresh check or the stored one is older than the configured window.
     * The permission is created here too, so live sites get it.
     */
    public function up(): void
    {
        Schema::create('fraud_checks', function (Blueprint $table) {
            $table->id();
            $table->string('phone', 20)->unique();
            $table->unsignedSmallInteger('score')->nullable();
            $table->string('level', 30)->nullable();
            $table->string('label')->nullable();
            $table->unsignedInteger('total_parcel')->default(0);
            $table->unsignedInteger('success_parcel')->default(0);
            $table->unsignedInteger('cancelled_parcel')->default(0);
            $table->decimal('success_ratio', 5, 2)->default(0);
            $table->unsignedInteger('review_count')->default(0);
            $table->json('payload')->nullable();
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();
        });

        // Same id as PermissionSeeder, which upserts by id — a different id
        // here would make the seeder insert a duplicate name.
        if (! DB::table('permissions')->where('name', 'fraud_checker.manage')->exists()) {
            $row = ['name' => 'fraud_checker.manage', 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()];

            DB::table('permissions')->where('id', 201)->exists()
                ? DB::table('permissions')->insert($row)
                : DB::table('permissions')->insert(['id' => 201] + $row);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::dropIfExists('fraud_checks');
        DB::table('permissions')->where('name', 'fraud_checker.manage')->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
