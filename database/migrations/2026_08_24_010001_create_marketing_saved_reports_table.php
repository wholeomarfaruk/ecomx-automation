<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketing_saved_reports', function (Blueprint $table) {
            $table->id();

            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            // Filter state as saved from the Reports builder UI: range, event
            // names, platform/source/campaign, device type, customer type.
            $table->json('filters');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketing_saved_reports');
    }
};
