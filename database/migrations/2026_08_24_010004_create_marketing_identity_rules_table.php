<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_identity_rules', function (Blueprint $table) {
            $table->id();

            // What field on the incoming event/context is used to match an
            // existing customer: 'email', 'phone', 'device_fingerprint'.
            $table->string('match_field', 30);

            $table->unsignedInteger('priority')->default(0);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->unique('match_field');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_identity_rules');
    }
};
