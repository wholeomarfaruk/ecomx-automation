<?php

namespace App\Actions\Sales;

use App\Actions\Accounts\VoidJournalEntry;
use App\Enums\Accounts\JournalEntryStatus;
use App\Models\AccountsCustomerAdvance;
use App\Models\AccountsCustomerInvoice;
use App\Models\AccountsPaymentAllocation;
use App\Models\JournalEntry;
use App\Models\Order;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;

/**
 * Permanently deletes an order (e.g. a test order) and undoes what it left
 * behind, so stock and the books read as if it never existed:
 *
 *   stock     — each line's remaining booking is unbooked and any stock
 *               still deducted for it is added back (StockService::
 *               reverseItemForDeletion, from the line's own ledger rows);
 *   accounts  — every posted journal entry sourced from the order (or from
 *               its customer invoice / advances) is voided — never deleted,
 *               journal entries are immutable — then the invoice, advances
 *               and their payment allocations are removed;
 *   the order — deleted; items, payments, charges, offers, coupon usages,
 *               courier shipments and any POS sale cascade with it.
 *
 * Shared entries that cover several orders at once (a courier COD
 * settlement batch) are left alone. Courier consignments already booked
 * with the courier are not cancelled on the courier's side.
 */
class DeleteOrder
{
    public function __construct(
        protected StockService $stockService,
        protected VoidJournalEntry $voidJournalEntry,
    ) {}

    public function handle(Order $order): void
    {
        DB::transaction(function () use ($order) {
            $order->loadMissing('items.product', 'items.variant');

            foreach ($order->items as $item) {
                $this->stockService->reverseItemForDeletion($item);
            }

            $invoiceIds = AccountsCustomerInvoice::where('order_id', $order->id)->pluck('id');
            $advanceIds = AccountsCustomerAdvance::where('order_id', $order->id)->pluck('id');

            $linkedEntryIds = AccountsCustomerInvoice::whereIn('id', $invoiceIds)->pluck('journal_entry_id')
                ->merge(AccountsCustomerAdvance::whereIn('id', $advanceIds)->pluck('journal_entry_id'))
                ->filter();

            $entries = JournalEntry::query()
                ->where('status', JournalEntryStatus::POSTED)
                ->where(fn ($q) => $q
                    ->where(fn ($s) => $s->where('source_type', Order::class)->where('source_id', $order->id))
                    ->orWhereIn('id', $linkedEntryIds))
                ->get();

            foreach ($entries as $entry) {
                $this->voidJournalEntry->handle($entry, "Order #{$order->id} deleted");
            }

            AccountsPaymentAllocation::query()
                ->where(fn ($q) => $q
                    ->where(fn ($s) => $s->where('allocatable_type', AccountsCustomerInvoice::class)->whereIn('allocatable_id', $invoiceIds))
                    ->orWhere(fn ($s) => $s->where('allocatable_type', AccountsCustomerAdvance::class)->whereIn('allocatable_id', $advanceIds)))
                ->delete();

            AccountsCustomerInvoice::whereIn('id', $invoiceIds)->delete();
            AccountsCustomerAdvance::whereIn('id', $advanceIds)->delete();

            $order->delete();
        });
    }
}
