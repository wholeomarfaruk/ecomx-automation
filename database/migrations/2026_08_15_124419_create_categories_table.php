<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            $table->string('name');
            $table->string('slug')->unique();

            $table->foreignId('parent_id')->nullable()->constrained('categories')->nullOnDelete();

            $table->text('description')->nullable();

            $table->string('status', 50)->default('active');

            $table->foreignId('featured_image_id')->nullable()->constrained('files')->nullOnDelete();
            $table->foreignId('cover_image_id')->nullable()->constrained('files')->nullOnDelete();

            // seo
            $table->foreignId('meta_image_id')->nullable()->constrained('files')->nullOnDelete();
            $table->string('meta_title')->nullable();
            $table->text('meta_description')->nullable();

            $table->integer('sort_order')->default(0);

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('sort_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
