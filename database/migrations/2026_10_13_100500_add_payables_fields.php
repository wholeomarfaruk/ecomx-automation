<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Payables: a due date on supplier bills (terms or rescheduled), recurring
 * bills with payee / day of month / "amount varies", and a small meta bag
 * on vouchers for what a draft settles when it posts.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('accounts_supplier_bills', function (Blueprint $table) {
            $table->date('due_date')->nullable()->after('status');
        });

        Schema::table('recurring_expenses', function (Blueprint $table) {
            $table->string('payee')->nullable()->after('name');
            $table->unsignedTinyInteger('day_of_month')->nullable()->after('cadence');
            $table->boolean('varies')->default(false)->after('amount');
        });

        Schema::table('journal_entries', function (Blueprint $table) {
            $table->json('meta')->nullable()->after('reject_reason');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', fn (Blueprint $t) => $t->dropColumn('meta'));
        Schema::table('recurring_expenses', fn (Blueprint $t) => $t->dropColumn(['payee', 'day_of_month', 'varies']));
        Schema::table('accounts_supplier_bills', fn (Blueprint $t) => $t->dropColumn('due_date'));
    }
};
