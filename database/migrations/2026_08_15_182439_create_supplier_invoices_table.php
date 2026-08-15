<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('supplier_invoices', function (Blueprint $table) {
            $table->id();

            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();

            $table->unsignedBigInteger('serial_number');
            $table->string('invoice_number')->nullable();

            $table->enum('type', ['purchase', 'advance', 'payment', 'return']);

            $table->decimal('amount', 20, 2);

            $table->boolean('is_adjusted')->default(false);

            $table->date('invoice_date')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index('type');
            $table->index('is_adjusted');
            $table->index('invoice_date');
            $table->unique(['supplier_id', 'serial_number']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('supplier_invoices');
    }
};
