<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    /**
     * Issues — anything that went wrong and needs following up (a parcel
     * the courier lost, a damaged delivery, a supplier short-ship, a
     * customer complaint). Polymorphic on both ends: `subject` is what it's
     * about (an Order, Product, Customer, ...), `party` is who's answerable
     * for it (a Courier, Supplier, ...) — so any module can raise one.
     *
     * Also order_items.lost_quantity: units the courier lost — part of
     * returned_quantity (the customer doesn't pay for them) but never
     * restocked; lost_posted_quantity is how much of it is written off in
     * the books (Stock Loss), the same delta idiom as return_posted_quantity.
     */
    public function up(): void
    {
        Schema::create('issues', function (Blueprint $table) {
            $table->id();
            $table->string('type', 40)->index();
            $table->string('status', 20)->default('open')->index();
            $table->string('priority', 20)->default('normal');
            $table->string('title');
            $table->text('description')->nullable();
            $table->nullableMorphs('subject');
            $table->nullableMorphs('party');
            $table->decimal('claim_amount', 20, 2)->nullable();
            $table->decimal('recovered_amount', 20, 2)->default(0);
            $table->foreignId('recovery_journal_entry_id')->nullable()->constrained('journal_entries')->nullOnDelete();
            $table->text('resolution')->nullable();
            $table->json('meta')->nullable();
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('lost_quantity', 20, 3)->default(0)->after('returned_quantity');
            $table->decimal('lost_posted_quantity', 20, 3)->default(0)->after('lost_quantity');
        });

        // Same ids as PermissionSeeder, which upserts by id — a different id
        // here would make the seeder insert a duplicate name.
        foreach ([202 => 'issue.view', 203 => 'issue.manage'] as $id => $name) {
            if (DB::table('permissions')->where('name', $name)->exists()) {
                continue;
            }

            $row = ['name' => $name, 'guard_name' => 'web', 'created_at' => now(), 'updated_at' => now()];

            DB::table('permissions')->where('id', $id)->exists()
                ? DB::table('permissions')->insert($row)
                : DB::table('permissions')->insert(['id' => $id] + $row);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn(['lost_quantity', 'lost_posted_quantity']);
        });

        Schema::dropIfExists('issues');
        DB::table('permissions')->whereIn('name', ['issue.view', 'issue.manage'])->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
