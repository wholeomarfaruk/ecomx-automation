<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_visits', function (Blueprint $table) {
            $table->id();

            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();

            $table->string('url', 2048);
            $table->string('route_name')->nullable();
            $table->string('method', 10);
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('referer', 2048)->nullable();

            // Global content tracking: works for any visitable entity (product, blog
            // post, category, static page, …) whether or not it's an Eloquent model.
            // visitable_type/visitable_id link real models (e.g. Product); content_type
            // is a free-form label ('product', 'blog', 'page', 'category', …) used for
            // filtering even when visitable_id is null (e.g. registry-driven static pages).
            $table->nullableMorphs('visitable');
            $table->string('content_type', 50)->nullable();
            $table->string('content_slug')->nullable();
            $table->string('content_title')->nullable();

            $table->timestamp('created_at')->nullable();

            $table->index(['device_id', 'created_at']);
            $table->index(['content_type', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_visits');
    }
};
