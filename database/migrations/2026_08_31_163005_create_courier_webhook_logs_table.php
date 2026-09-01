<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Raw record of every inbound courier webhook call, kept regardless of
     * whether it could be matched to a shipment — needed to debug a
     * courier's webhook payload shape or replay a missed update.
     */
    public function up(): void
    {
        Schema::create('courier_webhook_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_id')->nullable()->constrained('couriers')->nullOnDelete();
            $table->foreignId('courier_shipment_id')->nullable()->constrained('courier_shipments')->nullOnDelete();
            $table->string('event_type')->nullable();
            $table->text('headers')->nullable();
            $table->text('payload')->nullable();
            $table->string('signature_status')->nullable();
            $table->string('status')->default('received');
            $table->text('error_message')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->index(['courier_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_webhook_logs');
    }
};
