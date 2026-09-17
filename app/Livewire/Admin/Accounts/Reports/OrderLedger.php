<?php

namespace App\Livewire\Admin\Accounts\Reports;

use App\Models\Order;
use Livewire\Component;

/**
 * Every journal entry tied to one order (source_type=Order::class,
 * source_id=$order->id) — booking/sale/cogs/shipping/advance-conversion/
 * return/refund, whatever combination of PostOrderCompletion, PostOrderReturn,
 * ReverseOrder, RefundOrder etc. posted for it — oldest first, each with its
 * full set of debit/credit lines. Unlike CustomerLedger's single running
 * balance, an order's entries commonly span several different accounts at
 * once (Receivable, Sales, COGS, Inventory, ...), so this shows each entry
 * as its own balanced block rather than one flat running total.
 */
class OrderLedger extends Component
{
    public int $orderId;

    public function mount(int $orderId): void
    {
        $this->orderId = $orderId;
    }

    public function render(): mixed
    {
        $order = Order::with('customer')->findOrFail($this->orderId);

        $entries = $order->journalEntries()
            ->with(['lines.account'])
            ->where('status', '!=', 'void')
            ->orderBy('entry_date')
            ->orderBy('id')
            ->get();

        $totalDebit = $entries->sum(fn ($entry) => $entry->totalDebit());
        $totalCredit = $entries->sum(fn ($entry) => $entry->totalCredit());

        return view('livewire.admin.accounts.reports.order-ledger', [
            'order'       => $order,
            'entries'     => $entries,
            'totalDebit'  => $totalDebit,
            'totalCredit' => $totalCredit,
        ])->layout('layouts.admin.admin');
    }
}
