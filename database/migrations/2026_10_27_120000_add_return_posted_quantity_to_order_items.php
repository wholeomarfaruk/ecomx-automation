<?php

use App\Models\Order;
use App\Models\OrderItem;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * PostOrderReturn used to read "already reversed" off the stock ledger's
     * return movements — so a return restocked before the sale was posted
     * looked reversed and never was. It now tracks it on the line.
     *
     * Backfill: lines of orders that already have a sales-return entry are
     * taken as reversed up to what was restocked (inventory on), or fully
     * (nothing restocked — inventory off). Everything else starts at 0, so a
     * return that was never reversed shows up in Accounts → Posting.
     */
    public function up(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->decimal('return_posted_quantity', 20, 3)->default(0)->after('returned_quantity');
        });

        $reversedOrderIds = DB::table('journal_entries')
            ->where('source_type', Order::class)
            ->where('purpose', 'like', 'sales_return%')
            ->distinct()
            ->pluck('source_id');

        DB::table('order_items')
            ->whereIn('order_id', $reversedOrderIds->all() ?: [0])
            ->where('returned_quantity', '>', 0)
            ->orderBy('id')
            ->get(['id', 'returned_quantity'])
            ->each(function ($item) {
                $restocked = (float) DB::table('inventory_stock_movements')
                    ->where('reference_type', OrderItem::class)
                    ->where('reference_id', $item->id)
                    ->where('type', 'return')
                    ->sum('quantity');

                $posted = $restocked > 0 ? min($restocked, (float) $item->returned_quantity) : (float) $item->returned_quantity;

                DB::table('order_items')->where('id', $item->id)->update(['return_posted_quantity' => $posted]);
            });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('return_posted_quantity');
        });
    }
};
