<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('customer_code');

            $table->string('first_name');
            $table->string('last_name')->nullable();
            $table->string('full_name');

            $table->string('email')->nullable();
            $table->string('phone', 20);
            $table->string('alternative_phone', 20)->nullable();

            $table->string('gender', 50)->nullable();
            $table->date('date_of_birth')->nullable();

            $table->unsignedBigInteger('customer_group_id')->nullable();

            $table->decimal('reward_points', 20, 2)->default(0);
            $table->decimal('wallet_balance', 20, 2)->default(0);

            $table->timestamp('email_verified_at')->nullable();
            $table->timestamp('phone_verified_at')->nullable();

            $table->string('status', 50)->default('active');

            $table->text('notes')->nullable();
            $table->json('metadata')->nullable();


            $table->timestamps();
            $table->softDeletes();

            $table->foreign('customer_group_id')->references('id')->on('customer_groups')->nullOnDelete();

            $table->index('user_id');
            $table->index('customer_group_id');
            $table->unique('customer_code');
            $table->index('email');
            $table->index('phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
