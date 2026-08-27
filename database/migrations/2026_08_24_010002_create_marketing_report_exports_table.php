<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_report_exports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('marketing_saved_report_id')->nullable()
                ->constrained('marketing_saved_reports')->nullOnDelete();

            $table->string('name');
            $table->json('filters');

            $table->string('format', 10)->default('csv');
            $table->string('status', 20)->default('completed'); // exports run synchronously today

            $table->string('file_path')->nullable();
            $table->unsignedInteger('row_count')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_report_exports');
    }
};
