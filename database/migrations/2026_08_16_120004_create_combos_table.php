<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('combos', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')->nullable()->constrained('customers')->nullOnDelete();

            $table->string('name')->nullable();

            $table->decimal('quantity', 20, 3)->default(1);
            $table->decimal('price', 20, 2)->default(0);

            $table->timestamps();

            $table->index('customer_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combos');
    }
};
