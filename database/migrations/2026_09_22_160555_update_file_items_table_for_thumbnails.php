<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Plain string->unsignedBigInteger swap needs doctrine/dbal for
        // Schema::change(), which isn't installed here — raw SQL avoids
        // adding that dependency just for this one column.
        DB::statement('ALTER TABLE file_items MODIFY size BIGINT UNSIGNED NULL');

        Schema::table('file_items', function (Blueprint $table) {
            $table->index(['file_id', 'type'], 'file_items_file_id_type_index');
        });
    }

    public function down(): void
    {
        Schema::table('file_items', function (Blueprint $table) {
            $table->dropIndex('file_items_file_id_type_index');
        });

        DB::statement('ALTER TABLE file_items MODIFY size VARCHAR(255) NULL');
    }
};
