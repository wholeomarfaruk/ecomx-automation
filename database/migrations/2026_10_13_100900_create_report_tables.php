<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Reports: daily close (snapshot + archived copy, versioned on reopen),
 * saved reports with a schedule / recipients / channels, and the archive
 * of generated copies.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('day_closings', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->unsignedSmallInteger('version')->default(1);
            $table->string('status', 10)->default('closed');      // closed | auto | open (reopened)
            $table->json('snapshot')->nullable();
            $table->string('pdf_path')->nullable();
            $table->text('note')->nullable();
            $table->decimal('short_amount', 20, 2)->default(0);
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('reopened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reopened_at')->nullable();
            $table->timestamps();
            $table->index(['date', 'version']);
        });

        Schema::create('saved_reports', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('report_key', 30);
            $table->json('filters')->nullable();
            $table->string('schedule', 20)->default('none');     // daily | weekly | monthly | month_end | none
            $table->string('recipients', 20)->default('me');     // owners | accountant | both | me
            $table->json('channels')->nullable();                // archive, email, whatsapp, sms
            $table->timestamp('last_run_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('report_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('saved_report_id')->nullable()->constrained()->nullOnDelete();
            $table->string('report_key', 30);
            $table->string('kind', 20)->default('other');        // daily | monthly | other
            $table->string('title');
            $table->string('period')->nullable();
            $table->string('path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('report_archives');
        Schema::dropIfExists('saved_reports');
        Schema::dropIfExists('day_closings');
    }
};
