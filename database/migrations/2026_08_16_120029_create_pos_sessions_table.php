<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_sessions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('register_id')->constrained('pos_registers')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

            $table->string('status', 50)->default('open');
            // open, closed

            $table->decimal('opening_cash', 20, 2)->default(0);
            $table->decimal('closing_cash', 20, 2)->nullable();

            $table->timestamp('opened_at');
            $table->timestamp('closed_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('register_id');
            $table->index('user_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_sessions');
    }
};
