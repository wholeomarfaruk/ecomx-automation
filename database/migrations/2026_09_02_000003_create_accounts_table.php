<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique();
            $table->string('name');

            $table->enum('type', ['asset', 'liability', 'equity', 'income', 'expense']);
            $table->string('subtype', 50)->nullable();
            // cash, bank, mobile_banking, receivable, payable, fixed_asset,
            // contra_asset, contra_equity, cogs, fee, tax

            $table->enum('normal_balance', ['debit', 'credit']);

            $table->foreignId('parent_id')->nullable()->constrained('accounts')->nullOnDelete();

            $table->boolean('is_control_account')->default(false);
            $table->boolean('is_system')->default(false);
            $table->boolean('is_active')->default(true);

            $table->foreignId('currency_id')->nullable()->constrained('currencies')->nullOnDelete();

            $table->decimal('opening_balance', 20, 2)->default(0);
            $table->text('description')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('type');
            $table->index('subtype');
            $table->index('is_control_account');
            $table->index('is_active');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
