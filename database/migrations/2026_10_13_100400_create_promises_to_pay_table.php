<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Receivables › promise to pay / call-back dates per customer profile.
 * A promise whose date has passed without enough being paid is "broken".
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promises_to_pay', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_profile_id')->constrained('master_profiles')->cascadeOnDelete();
            $table->string('kind', 12)->default('promise');
            $table->date('due_date');
            $table->decimal('amount', 20, 2)->nullable();
            $table->string('note')->nullable();
            $table->string('status', 12)->default('open');
            $table->decimal('balance_at_promise', 20, 2)->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
            $table->index(['master_profile_id', 'status']);
            $table->index('due_date');
        });

        Schema::table('courier_cod_settlements', function (Blueprint $table) {
            $table->unsignedInteger('parcels')->nullable()->after('net_amount');
        });
    }

    public function down(): void
    {
        Schema::table('courier_cod_settlements', function (Blueprint $table) {
            $table->dropColumn('parcels');
        });
        Schema::dropIfExists('promises_to_pay');
    }
};
