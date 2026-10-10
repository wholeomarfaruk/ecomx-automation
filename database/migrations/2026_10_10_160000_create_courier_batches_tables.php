<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Sales → Bulk Courier: a sheet of orders to book with couriers in one
     * go. Each admin has one draft batch at a time; its items are the
     * editable courier rows (pre-filled from the order, saved as they are
     * edited). Once every row is booked the batch is completed and kept as
     * history.
     */
    public function up(): void
    {
        Schema::create('courier_batches', function (Blueprint $table) {
            $table->id();
            $table->string('status', 20)->default('draft')->index(); // draft | completed
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('courier_batch_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_batch_id')->constrained()->cascadeOnDelete();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('courier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('recipient_name')->default('');
            $table->string('recipient_phone', 30)->default('');
            $table->text('recipient_address')->nullable();
            $table->decimal('cod_amount', 12, 2)->default(0);
            $table->decimal('weight', 8, 3)->default(0.5);
            $table->unsignedInteger('quantity')->default(1);
            $table->string('description', 500)->nullable();
            $table->string('instruction', 500)->nullable();
            $table->string('status', 20)->default('draft'); // draft | booked | failed
            $table->string('tracking_number')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('booked_at')->nullable();
            $table->timestamps();

            $table->unique(['courier_batch_id', 'order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_batch_items');
        Schema::dropIfExists('courier_batches');
    }
};
