<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('request_logs', function (Blueprint $table) {
            $table->id();

            // No FK constraints — a log row must never block deleting a device
            // or user, and the insert stays a plain single-table write.
            $table->unsignedBigInteger('device_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->string('method', 10);
            $table->string('url', 2048);
            $table->string('path', 512);
            $table->string('route_name')->nullable();

            // storefront | admin | api
            $table->string('area', 20);
            // page | livewire | ajax | other
            $table->string('type', 20);
            // Livewire update requests: "component@method" per component.
            $table->string('livewire_action', 500)->nullable();

            $table->unsignedSmallInteger('status_code');
            $table->unsignedInteger('duration_ms');
            $table->unsignedSmallInteger('query_count')->default(0);
            $table->unsignedSmallInteger('memory_mb')->default(0);

            $table->string('ip_address', 45)->nullable();
            $table->string('referer', 2048)->nullable();
            $table->string('user_agent', 512)->nullable();

            $table->string('exception_class')->nullable();
            $table->text('exception_message')->nullable();
            $table->string('exception_location', 512)->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index('created_at');
            $table->index(['status_code', 'created_at']);
            $table->index(['device_id', 'created_at']);
            $table->index(['area', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index(['duration_ms', 'created_at']);
            $table->index('ip_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_logs');
    }
};
