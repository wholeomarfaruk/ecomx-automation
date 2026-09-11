<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * source_type/source_id/purpose together identify what business event a
     * journal entry belongs to (e.g. an Order's "sale" vs its "cogs" entry)
     * and back the (source_type, source_id, purpose) unique index that
     * prevents the same event from ever being posted twice — MySQL treats
     * NULLs as distinct, so pure manual entries (no source) are unaffected.
     */
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();

            $table->string('entry_number')->unique();
            $table->date('entry_date');
            $table->string('description')->nullable();

            $table->enum('status', ['draft', 'posted', 'void'])->default('draft');
            $table->string('transaction_type', 50);

            $table->string('source_type')->nullable();
            $table->unsignedBigInteger('source_id')->nullable();
            $table->string('purpose')->nullable();

            $table->foreignId('reversed_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();

            $table->foreignId('fiscal_period_id')->nullable()->constrained('fiscal_periods')->nullOnDelete();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('posted_at')->nullable();
            $table->foreignId('voided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('voided_at')->nullable();

            $table->timestamps();

            $table->index(['source_type', 'source_id']);
            $table->index('entry_date');
            $table->index('status');
            $table->index('transaction_type');
            $table->unique(['source_type', 'source_id', 'purpose'], 'journal_entries_source_purpose_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
