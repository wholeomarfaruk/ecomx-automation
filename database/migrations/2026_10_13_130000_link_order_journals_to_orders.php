<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Order-related journals (COD collected, refunds, customer advances and
 * advances applied) used to be saved without their order, so the order
 * ledger, reversals and lookups couldn't find them. Link the existing ones
 * from their "Order #123" description, only when that order exists and the
 * entry has no source yet. Data-only: nothing to undo.
 */
return new class extends Migration
{
    public function up(): void
    {
        $orderClass = 'App\\Models\\Order';
        $types = ['cod_settlement', 'refund', 'credit_note', 'customer_advance'];

        DB::table('journal_entries')->whereNull('source_type')->whereIn('transaction_type', $types)
            ->where('description', 'like', '%Order #%')->orderBy('id')
            ->chunkById(500, function ($rows) use ($orderClass) {
                foreach ($rows as $r) {
                    if (! preg_match('/Order #(\d+)/', (string) $r->description, $m)) {
                        continue;
                    }
                    $orderId = (int) $m[1];
                    if (DB::table('orders')->where('id', $orderId)->exists()) {
                        DB::table('journal_entries')->where('id', $r->id)->update(['source_type' => $orderClass, 'source_id' => $orderId]);
                    }
                }
            });
    }

    public function down(): void
    {
        //
    }
};
