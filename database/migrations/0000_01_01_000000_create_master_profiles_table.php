<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('master_profiles', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('type');
            $table->string('display_name');
            $table->string('legal_name')->nullable();
            $table->string('first_name')->nullable();
            $table->string('last_name')->nullable();

            $table->string('country_code', 8)->nullable();
            $table->string('phone')->nullable()->index();
            $table->string('email')->nullable()->index();
            $table->string('website')->nullable();
            $table->string('avatar')->nullable();

            $table->date('date_of_birth')->nullable();
            $table->string('gender')->nullable();

            $table->string('national_id')->nullable();
            $table->string('tax_number')->nullable();
            $table->string('vat_number')->nullable();
            $table->string('registration_number')->nullable();

            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->json('meta')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('master_profiles');
    }
};
