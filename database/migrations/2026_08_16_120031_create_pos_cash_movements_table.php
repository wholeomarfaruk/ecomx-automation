<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pos_cash_movements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('session_id')->constrained('pos_sessions')->cascadeOnDelete();

            $table->string('type', 50);
            // cash_in, cash_out, opening, closing, refund

            $table->decimal('amount', 20, 2);

            $table->string('reason', 255)->nullable();
            $table->text('notes')->nullable();

            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();

            $table->timestamps();

            $table->index('session_id');
            $table->index('type');
            $table->index('created_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pos_cash_movements');
    }
};
