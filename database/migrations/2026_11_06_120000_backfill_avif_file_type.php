<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * AVIF wasn't recognised as an image extension, so uploaded .avif files were
 * saved with type = NULL and the media picker's "image" filter hid them.
 */
return new class extends Migration
{
    public function up(): void
    {
        DB::table('files')
            ->whereNull('type')
            ->whereRaw('LOWER(extension) = ?', ['avif'])
            ->update(['type' => 'image']);
    }

    public function down(): void
    {
        // Not reversible — NULL type was the bug.
    }
};
