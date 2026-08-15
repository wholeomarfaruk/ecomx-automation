<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();

            $table->string('name');
            $table->string('company_name')->nullable();

            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('alternative_phone')->nullable();

            $table->text('address')->nullable();

            $table->decimal('balance', 20, 2)->default(0);

            $table->string('status', 50)->default('active');
            // active, inactive

            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('name');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
