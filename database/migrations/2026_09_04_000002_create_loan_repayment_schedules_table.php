<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('loan_repayment_schedules', function (Blueprint $table) {
            $table->id();

            $table->foreignId('loan_id')->constrained('loans')->cascadeOnDelete();

            $table->date('due_date');
            $table->decimal('principal_due', 20, 2);
            $table->decimal('interest_due', 20, 2)->default(0);

            $table->enum('status', ['pending', 'paid', 'overdue'])->default('pending');

            $table->timestamps();

            $table->index(['loan_id', 'due_date']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_repayment_schedules');
    }
};
