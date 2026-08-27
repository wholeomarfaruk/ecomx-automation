<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_review_helpful_votes', function (Blueprint $table) {
            $table->dropUnique('review_helpful_votes_review_customer_unique');
        });

        DB::statement('ALTER TABLE `product_review_helpful_votes` MODIFY `customer_id` BIGINT UNSIGNED NULL');

        Schema::table('product_review_helpful_votes', function (Blueprint $table) {
            $table->foreignId('device_id')->nullable()->after('customer_id')->constrained('devices')->cascadeOnDelete();

            $table->index('device_id');
            $table->unique(['product_review_id', 'device_id'], 'review_helpful_votes_review_device_unique');
        });
    }

    public function down(): void
    {
        Schema::table('product_review_helpful_votes', function (Blueprint $table) {
            $table->dropUnique('review_helpful_votes_review_device_unique');
            $table->dropConstrainedForeignId('device_id');
        });

        DB::statement('ALTER TABLE `product_review_helpful_votes` MODIFY `customer_id` BIGINT UNSIGNED NOT NULL');

        Schema::table('product_review_helpful_votes', function (Blueprint $table) {
            $table->unique(['product_review_id', 'customer_id'], 'review_helpful_votes_review_customer_unique');
        });
    }
};
