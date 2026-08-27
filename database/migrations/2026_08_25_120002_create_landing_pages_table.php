<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_pages', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('title');
            $table->string('slug')->unique();
            $table->foreignId('landing_page_template_id')->constrained('landing_page_templates');
            $table->json('content')->nullable();
            $table->string('status')->default('draft');
            $table->json('seo')->nullable();
            $table->string('header_mode')->default('none');
            $table->string('footer_mode')->default('none');
            $table->timestamp('published_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_pages');
    }
};
