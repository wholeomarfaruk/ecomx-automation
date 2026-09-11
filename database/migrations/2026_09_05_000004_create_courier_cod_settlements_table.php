<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Case 10.2 — a courier batch-settles COD collections, keeping a fee
     * and remitting the rest. One row per settlement batch; individual
     * shipments it covers aren't tracked line-by-line here (the spec's
     * example works off a lump sum), so this stays a header-only record
     * backed by its journal entry for the actual money movement.
     */
    public function up(): void
    {
        Schema::create('courier_cod_settlements', function (Blueprint $table) {
            $table->id();

            $table->foreignId('courier_id')->constrained('couriers')->restrictOnDelete();
            $table->date('settled_at');
            $table->decimal('gross_amount', 20, 2);
            $table->decimal('fee_amount', 20, 2)->default(0);
            $table->decimal('net_amount', 20, 2);

            $table->foreignId('journal_entry_id')->constrained('journal_entries')->restrictOnDelete();

            $table->timestamps();

            $table->index(['courier_id', 'settled_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_cod_settlements');
    }
};
