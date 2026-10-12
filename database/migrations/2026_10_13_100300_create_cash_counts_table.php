<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Cash & Bank › Cash count: denominations counted vs the book balance. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cash_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('account_id')->constrained('accounts')->restrictOnDelete();
            $table->date('count_date');
            $table->json('denominations');
            $table->decimal('counted', 20, 2);
            $table->decimal('book', 20, 2);
            $table->decimal('difference', 20, 2);
            $table->foreignId('adjustment_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['account_id', 'count_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cash_counts');
    }
};
