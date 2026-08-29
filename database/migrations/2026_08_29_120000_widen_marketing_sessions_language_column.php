<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Was string(20) — too short for real Accept-Language header values
        // (e.g. "en-GB,en-US;q=0.9,en;q=0.8,bn;q=0.7", 37+ chars), which
        // caused "Data too long for column 'language'" on session creation.
        // Widened to match devices.language (plain string, 255 chars).
        DB::statement('ALTER TABLE `marketing_sessions` MODIFY `language` VARCHAR(255) NULL');

        // Was string(64) — same class of bug, just not yet tripped in
        // practice. IANA zone names top out well under 64 chars, but this
        // column can also receive a raw Intl.DateTimeFormat().resolvedOptions().timeZone
        // string from the client, so drop the length limit rather than
        // guess at a safer cap.
        DB::statement('ALTER TABLE `marketing_sessions` MODIFY `timezone` VARCHAR(255) NULL');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE `marketing_sessions` MODIFY `language` VARCHAR(20) NULL');
        DB::statement('ALTER TABLE `marketing_sessions` MODIFY `timezone` VARCHAR(64) NULL');
    }
};
