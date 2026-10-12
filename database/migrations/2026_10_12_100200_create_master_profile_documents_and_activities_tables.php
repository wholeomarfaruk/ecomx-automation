<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_profile_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_profile_id')->constrained('master_profiles')->cascadeOnDelete();
            $table->string('title');
            $table->string('path');
            $table->string('mime')->nullable();
            $table->unsignedBigInteger('size')->default(0);
            $table->date('expires_on')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('master_profile_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('master_profile_id')->constrained('master_profiles')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('type', 30);
            $table->string('text', 500);
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->nullable();
            $table->index(['master_profile_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_profile_activities');
        Schema::dropIfExists('master_profile_documents');
    }
};
