<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blocks', function (Blueprint $table) {
            $table->id();

            // Either a polymorphic target (Device, Customer, User) or a bare
            // IP address — never both. IP blocks have no model to attach to,
            // so they're checked directly against the request's IP.
            $table->nullableMorphs('blockable');
            $table->string('ip_address', 45)->nullable();

            $table->string('scope', 30);
            // full_site | orders | checkout | account_panel

            $table->text('reason')->nullable();
            $table->foreignId('blocked_by')->nullable()->constrained('users')->nullOnDelete();

            $table->timestamp('expires_at')->nullable();
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['blockable_type', 'blockable_id', 'scope', 'is_active'], 'blocks_target_scope_active_idx');
            $table->index(['ip_address', 'scope', 'is_active'], 'blocks_ip_scope_active_idx');
            $table->index('expires_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blocks');
    }
};
