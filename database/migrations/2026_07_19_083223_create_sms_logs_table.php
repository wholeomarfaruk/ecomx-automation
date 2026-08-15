<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('sms_logs', function (Blueprint $table) {
            $table->id();
            $table->string('driver_key')->index();
            $table->string('to');
            $table->text('message');
            $table->string('status')->index();
            $table->string('message_id')->nullable();
            $table->decimal('cost', 12, 4)->nullable();
            $table->string('error_code')->nullable();
            $table->string('error_message')->nullable();
            $table->json('provider_response')->nullable();
            $table->json('raw_response')->nullable();
            $table->string('context')->nullable()->index();
            $table->unsignedInteger('retry_count')->default(0);
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sms_logs');
    }
};
