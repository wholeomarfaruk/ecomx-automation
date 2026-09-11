<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * category_account_id points at a non-system child of the system
     * "Fixed Assets" (1500) account (see plan: fixed-asset categories reuse
     * the accounts tree rather than a separate category model).
     * accumulated_depreciation is a denormalized running total mirrored
     * from fixed_asset_depreciation_entries, purely so list/detail screens
     * don't need to sum entries on every render — RunDepreciation/
     * DisposeFixedAsset keep it in sync, same pattern as Supplier.balance.
     */
    public function up(): void
    {
        Schema::create('fixed_assets', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->foreignId('category_account_id')->constrained('accounts')->restrictOnDelete();

            $table->decimal('cost', 20, 2);
            $table->date('purchase_date');
            $table->unsignedSmallInteger('useful_life_months');
            $table->decimal('salvage_value', 20, 2)->default(0);
            $table->string('method')->default('straight_line');

            $table->decimal('accumulated_depreciation', 20, 2)->default(0);

            $table->enum('status', ['active', 'disposed'])->default('active');

            $table->foreignId('journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_assets');
    }
};
