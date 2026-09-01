<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Every outbound request this app makes to a courier's API (shipment
     * create, cancel, track, rate, balance, ...) — the courier-side mirror
     * of courier_webhook_logs, used for debugging and rate-limit auditing.
     */
    public function up(): void
    {
        Schema::create('courier_api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('courier_id')->nullable()->constrained('couriers')->nullOnDelete();
            $table->foreignId('courier_account_id')->nullable()->constrained('courier_accounts')->nullOnDelete();
            $table->foreignId('courier_shipment_id')->nullable()->constrained('courier_shipments')->nullOnDelete();
            $table->string('action');
            $table->string('method', 10)->nullable();
            $table->string('endpoint')->nullable();
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('request_payload')->nullable();
            $table->text('response_payload')->nullable();
            $table->boolean('success')->default(false);
            $table->string('error_code')->nullable();
            $table->text('error_message')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['courier_id', 'action']);
            $table->index('success');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('courier_api_logs');
    }
};
