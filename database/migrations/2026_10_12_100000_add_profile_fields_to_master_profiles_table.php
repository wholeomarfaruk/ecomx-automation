<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Accounts Panel › Master Profiles: master_profiles becomes the "one person
 * = one profile" record. Adds the fields the profile form needs and a
 * human code (P-0001) derived from the id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_profiles', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->unique()->after('uuid');
            $table->string('contact_person')->nullable()->after('last_name');
            $table->string('alt_phone')->nullable()->after('phone');
            $table->unsignedBigInteger('state_id')->nullable()->after('website');
            $table->unsignedBigInteger('city_id')->nullable()->after('state_id');
            $table->unsignedBigInteger('ps_id')->nullable()->after('city_id');
            $table->string('address')->nullable()->after('ps_id');
            $table->string('trade_license')->nullable()->after('registration_number');
            $table->json('tags')->nullable()->after('notes');
            $table->index('city_id');
        });

        DB::table('master_profiles')->whereNull('code')->orderBy('id')->chunkById(500, function ($rows) {
            foreach ($rows as $row) {
                DB::table('master_profiles')->where('id', $row->id)
                    ->update(['code' => 'P-' . str_pad((string) $row->id, 4, '0', STR_PAD_LEFT)]);
            }
        });
    }

    public function down(): void
    {
        Schema::table('master_profiles', function (Blueprint $table) {
            $table->dropIndex(['city_id']);
            $table->dropUnique(['code']);
            $table->dropColumn(['code', 'contact_person', 'alt_phone', 'state_id', 'city_id', 'ps_id', 'address', 'trade_license', 'tags']);
        });
    }
};
