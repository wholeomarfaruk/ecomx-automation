<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promotions', function (Blueprint $table) {
            $table->id();

            $table->string('type', 20);
            // coupon, offer

            $table->string('name');
            $table->text('description')->nullable();

            $table->string('status', 20)->default('draft');
            // draft, active, inactive, expired

            $table->integer('priority')->default(0);

            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();

            $table->boolean('stackable')->default(false);

            $table->timestamps();

            $table->index('type');
            $table->index('status');
            $table->index(['starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promotions');
    }
};
