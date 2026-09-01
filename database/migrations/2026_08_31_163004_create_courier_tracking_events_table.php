<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Full status timeline for a shipment, one row per event — whether it
     * arrived via webhook or a polling sync job. `status` is always the
     * courier-agnostic normalized value (see CourierStatusNormalizer);
     * `raw_status`/`raw_data` keep the provider's own wording for audit.
     */
    public function up(): void
    {
        Schema::create('courier_tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_shipment_id')->constrained('courier_shipments')->cascadeOnDelete();
            $table->string('status');
            $table->string('raw_status')->nullable();
            $table->string('message')->nullable();
            $table->string('location')->nullable();
            $table->timestamp('event_at')->nullable();
            $table->text('raw_data')->nullable();
            $table->timestamps();

            $table->index(['courier_shipment_id', 'event_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_tracking_events');
    }
};
