<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_intake_logs', function (Blueprint $table) {
            $table->id();

            // No FK constraints — a log row must never block deleting a user.
            $table->unsignedBigInteger('user_id')->nullable();

            // sha256 of the normalized text + file hashes — finds a repeat
            // paste (cache / "already placed" warning).
            $table->string('input_hash', 64)->index();
            // text | image | pdf | mixed
            $table->string('source_type', 20);
            $table->text('input_text')->nullable();
            $table->unsignedTinyInteger('file_count')->default(0);

            // parser = resolved without AI; ai = AI fallback ran and succeeded;
            // ai_failed = AI was needed but failed (parser drafts kept);
            // ai_skipped = AI was needed but is off / over its limit.
            $table->string('resolution', 20);
            $table->unsignedSmallInteger('orders_found')->default(0);
            $table->unsignedSmallInteger('orders_ready')->default(0);

            // The AI call, when there was one.
            $table->string('model')->nullable();
            $table->unsignedInteger('prompt_tokens')->nullable();
            $table->unsignedInteger('completion_tokens')->nullable();
            $table->decimal('cost', 12, 6)->nullable();
            $table->unsignedInteger('latency_ms')->nullable();
            $table->string('error', 1000)->nullable();

            // What the AI returned (validated), reused when the same input
            // comes in again so a retry/repeat doesn't pay twice.
            $table->json('ai_result')->nullable();

            // Orders placed from this extraction (filled in by the bulk sheet).
            $table->json('order_ids')->nullable();

            $table->timestamps();
            $table->index(['created_at', 'resolution']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_intake_logs');
    }
};
