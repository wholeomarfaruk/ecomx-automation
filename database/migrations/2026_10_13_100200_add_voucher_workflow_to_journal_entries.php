<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Accounts Panel voucher workflow on top of journal_entries (no separate
 * vouchers table — every module already posts here):
 *   status      draft · posted · void · rejected · scheduled
 *   voucher_ref RV-0001 style number per voucher type (Settings › numbering)
 *   source      manual (made in the panel) | system (made by a module/job)
 *   approval, rejection, schedule fields; cached amount / party / money account
 * plus voucher_events (audit trail) and voucher_attachments.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->string('status', 20)->default('draft')->change();
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->string('voucher_ref', 30)->nullable()->unique()->after('entry_number');
            $table->string('voucher_type', 20)->nullable()->after('transaction_type');
            $table->string('source', 10)->default('system')->after('voucher_type');
            $table->decimal('amount', 20, 2)->nullable()->after('description');
            $table->foreignId('master_profile_id')->nullable()->after('amount')->constrained('master_profiles')->nullOnDelete();
            $table->string('party_label')->nullable()->after('master_profile_id');
            $table->foreignId('money_account_id')->nullable()->after('party_label')->constrained('accounts')->nullOnDelete();
            $table->date('scheduled_for')->nullable()->after('entry_date');
            $table->foreignId('approved_by')->nullable()->after('posted_at')->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable()->after('approved_by');
            $table->foreignId('rejected_by')->nullable()->after('approved_at')->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable()->after('rejected_by');
            $table->text('reject_reason')->nullable()->after('rejected_at');
            $table->index('voucher_type');
            $table->index('master_profile_id');
        });

        DB::table('journal_entries')->whereNull('amount')->update([
            'amount' => DB::raw('(SELECT COALESCE(SUM(l.debit), 0) FROM journal_entry_lines l WHERE l.journal_entry_id = journal_entries.id)'),
        ]);

        Schema::create('voucher_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->string('event', 30);
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('note', 500)->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['journal_entry_id', 'created_at']);
        });

        Schema::create('voucher_attachments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('journal_entry_id')->constrained('journal_entries')->cascadeOnDelete();
            $table->string('name');
            $table->string('path');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('voucher_attachments');
        Schema::dropIfExists('voucher_events');

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropIndex(['voucher_type']);
            $table->dropIndex(['master_profile_id']);
            $table->dropUnique(['voucher_ref']);
            $table->dropConstrainedForeignId('master_profile_id');
            $table->dropConstrainedForeignId('money_account_id');
            $table->dropConstrainedForeignId('approved_by');
            $table->dropConstrainedForeignId('rejected_by');
            $table->dropColumn(['voucher_ref', 'voucher_type', 'source', 'amount', 'party_label', 'scheduled_for', 'approved_at', 'rejected_at', 'reject_reason']);
        });
    }
};
