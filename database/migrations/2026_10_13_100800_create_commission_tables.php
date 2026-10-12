<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Customer Commission & Rebate (spec §5.6, §7.17): schemes with tiers and
 * exclusion rules, manual / included-back exclusions (logged with a reason),
 * month-end accruals and settlements.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('commission_schemes', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type', 20)->default('rebate');            // rebate | commission | instant
            $table->string('basis', 20)->default('net_sales');        // net_sales | quantity | gross_profit
            $table->string('period', 20)->default('yearly');          // yearly | half | quarter | month | custom
            $table->date('starts_on')->nullable();
            $table->date('ends_on')->nullable();
            $table->string('who', 20)->default('all');                // all | group | tag | selected
            $table->json('who_value')->nullable();
            $table->string('tier_mode', 10)->default('whole');        // whole | slab
            $table->json('rules')->nullable();                        // exclusion rules {key: {on, value}}
            $table->boolean('paid_only')->default(true);
            $table->unsignedSmallInteger('hold_days')->nullable()->default(60);
            $table->boolean('returns_reverse')->default(true);
            $table->string('settlement_method', 20)->default('credit_note'); // credit_note | deduct_due | cash | choose
            $table->string('accounting', 10)->default('rebate');      // rebate | expense
            $table->string('status', 20)->default('draft');           // draft | active | ended
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('commission_tiers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_scheme_id')->constrained()->cascadeOnDelete();
            $table->decimal('from_amount', 20, 2)->default(0);
            $table->decimal('to_amount', 20, 2)->nullable();
            $table->decimal('rate', 8, 3)->default(0);
            $table->timestamps();
        });

        Schema::create('commission_exclusions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_scheme_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_item_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source', 10)->default('manual');          // manual | auto
            $table->string('rule', 20)->nullable();
            $table->string('reason');
            $table->boolean('included_back')->default(false);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['commission_scheme_id', 'order_id']);
        });

        Schema::create('commission_accruals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_scheme_id')->constrained()->cascadeOnDelete();
            $table->foreignId('master_profile_id')->constrained()->cascadeOnDelete();
            $table->date('month');
            $table->decimal('eligible', 20, 2)->default(0);
            $table->decimal('rate', 8, 3)->default(0);
            $table->decimal('amount', 20, 2)->default(0);
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
            $table->index(['commission_scheme_id', 'month']);
        });

        Schema::create('commission_settlements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commission_scheme_id')->constrained()->cascadeOnDelete();
            $table->foreignId('master_profile_id')->constrained()->cascadeOnDelete();
            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('rate', 8, 3)->default(0);
            $table->decimal('amount', 20, 2);
            $table->string('method', 20);
            $table->boolean('advance')->default(false);
            $table->foreignId('journal_entry_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('commission_settlements');
        Schema::dropIfExists('commission_accruals');
        Schema::dropIfExists('commission_exclusions');
        Schema::dropIfExists('commission_tiers');
        Schema::dropIfExists('commission_schemes');
    }
};
