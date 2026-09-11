<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_periods', function (Blueprint $table) {
            $table->id();

            $table->foreignId('fiscal_year_id')->constrained('fiscal_years')->cascadeOnDelete();

            $table->date('start_date');
            $table->date('end_date');

            $table->boolean('is_locked')->default(false);
            $table->timestamp('locked_at')->nullable();
            $table->foreignId('locked_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamps();

            $table->index(['start_date', 'end_date']);
            $table->index('is_locked');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_periods');
    }
};
