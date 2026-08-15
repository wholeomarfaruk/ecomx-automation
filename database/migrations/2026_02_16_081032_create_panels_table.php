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
        // 1. Define the Panels
        Schema::create('panels', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // e.g., "Administration"
            $table->string('slug')->unique(); // e.g., "admin"
            $table->string('description')->nullable();
            $table->string('route_name')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // 2. Link Panels to Spatie Roles
        Schema::create('panel_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('panel_id')->constrained('panels')->onDelete('cascade');
            // Link to Spatie's roles table id
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('panel_user'); 
        Schema::dropIfExists('panels');
    }
};
