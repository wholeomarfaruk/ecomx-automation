<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Master registry of couriers the system knows how to drive — one row
     * per courier addon driver (Pathao, SteadFast, RedX, ...), independent
     * of any merchant's actual account credentials (see courier_accounts).
     */
    public function up(): void
    {
        Schema::create('couriers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('driver_key')->unique();
            $table->string('logo')->nullable();
            $table->text('description')->nullable();
            $table->string('type')->default('api');
            $table->json('capabilities')->nullable();
            $table->boolean('is_active')->default(false);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('couriers');
    }
};
