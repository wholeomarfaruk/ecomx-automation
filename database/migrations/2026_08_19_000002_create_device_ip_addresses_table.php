<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_ip_addresses', function (Blueprint $table) {
            $table->id();

            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->string('ip_address', 45);

            $table->unsignedInteger('hits')->default(1);
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');

            $table->timestamps();

            $table->unique(['device_id', 'ip_address']);
            $table->index('ip_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_ip_addresses');
    }
};
