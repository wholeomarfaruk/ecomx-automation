<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fixed_asset_disposals', function (Blueprint $table) {
            $table->id();

            $table->foreignId('fixed_asset_id')->constrained('fixed_assets')->cascadeOnDelete();

            $table->date('disposed_at');
            $table->decimal('proceeds', 20, 2)->default(0);
            $table->decimal('book_value', 20, 2);
            $table->decimal('gain_loss_amount', 20, 2)->default(0);
            // positive = gain, negative = loss

            $table->foreignId('journal_entry_id')->constrained('journal_entries')->restrictOnDelete();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fixed_asset_disposals');
    }
};
