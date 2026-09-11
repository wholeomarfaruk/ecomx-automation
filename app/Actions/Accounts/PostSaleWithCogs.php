<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Models\AccountsCustomerInvoice;
use App\Models\Customer;
use App\Models\Order;

/**
 * Case 3.1 — recognizing a sale creates a receivable (or hits cash directly
 * for a paid-in-full order) and, in the same breath, moves the cost of what
 * was sold out of Inventory into COGS. Two separate journal entries with
 * distinct `purpose` values ("sale" and "cogs") so re-running this for the
 * same order is caught by PostJournalEntry's duplicate guard per entry, and
 * one entry can be reversed independently of the other if ever needed.
 *
 * COGS uses OrderItem.purchase_price × quantity (a flat per-unit cost
 * snapshot taken at sale time) rather than a FIFO/weighted-average costing
 * engine — see plan risk (f): the codebase has no such engine, and this is
 * the only cost figure available.
 */
class PostSaleWithCogs
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    public function handle(
        Order $order,
        int $receivableOrCashAccountId,
        int $salesAccountId,
        int $inventoryAccountId,
        int $cogsAccountId,
        ?string $entryDate = null,
        bool $isReceivable = true,
    ): array {
        $order->loadMissing('items', 'customer');

        $entryDate ??= now()->toDateString();
        $saleAmount = (float) $order->items->sum(fn ($item) => $item->is_gift ? 0 : (float) $item->total_amount);
        $cogsAmount = (float) $order->items->sum(fn ($item) => $item->is_gift ? 0 : (float) $item->quantity * (float) $item->purchase_price);

        $saleEntry = $this->postJournalEntry->handle([
            'entry_date'       => $entryDate,
            'description'      => "Sale — Order #{$order->id}",
            'transaction_type' => TransactionType::SALE->value,
            'source_type'      => Order::class,
            'source_id'        => $order->id,
            'purpose'          => 'sale',
        ], [
            [
                'account_id'     => $receivableOrCashAccountId,
                'debit'          => $saleAmount,
                'subledger_type' => $isReceivable ? \App\Models\Customer::class : null,
                'subledger_id'   => $isReceivable ? $order->customer_id : null,
            ],
            ['account_id' => $salesAccountId, 'credit' => $saleAmount],
        ]);

        $cogsEntry = null;
        if ($cogsAmount > 0) {
            $cogsEntry = $this->postJournalEntry->handle([
                'entry_date'       => $entryDate,
                'description'      => "COGS — Order #{$order->id}",
                'transaction_type' => TransactionType::COGS->value,
                'source_type'      => Order::class,
                'source_id'        => $order->id,
                'purpose'          => 'cogs',
            ], [
                ['account_id' => $cogsAccountId, 'debit' => $cogsAmount],
                ['account_id' => $inventoryAccountId, 'credit' => $cogsAmount],
            ]);
        }

        $invoice = null;
        if ($isReceivable) {
            $invoice = AccountsCustomerInvoice::create([
                'customer_id'      => $order->customer_id,
                'order_id'         => $order->id,
                'invoice_number'   => 'INV-' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
                'amount'           => $saleAmount,
                'amount_allocated' => 0,
                'status'           => 'open',
                'journal_entry_id' => $saleEntry->id,
            ]);
        }

        return ['sale' => $saleEntry, 'cogs' => $cogsEntry, 'invoice' => $invoice];
    }
}
