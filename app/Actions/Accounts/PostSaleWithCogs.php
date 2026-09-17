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

    /**
     * @param  array<int, float>|null  $itemAmounts  order_item_id => sale amount to recognize now (partial completion); omitted/null means every non-gift item's full total_amount
     * @param  array<int, float>|null  $itemCogsAmounts  order_item_id => cost amount to recognize now; omitted/null means quantity * purchase_price for every item
     */
    public function handle(
        Order $order,
        int $receivableOrCashAccountId,
        int $salesAccountId,
        int $inventoryAccountId,
        int $cogsAccountId,
        ?string $entryDate = null,
        bool $isReceivable = true,
        ?float $shippingAmount = null,
        ?int $shippingIncomeAccountId = null,
        ?string $purposeSuffix = null,
        ?array $itemAmounts = null,
        ?array $itemCogsAmounts = null,
    ): array {
        $order->loadMissing('items', 'customer');

        $entryDate ??= now()->toDateString();
        $purposeSuffix ??= '';

        $saleAmount = $itemAmounts !== null
            ? array_sum($itemAmounts)
            : (float) $order->items->sum(fn ($item) => $item->is_gift ? 0 : (float) $item->total_amount);

        $cogsAmount = $itemCogsAmounts !== null
            ? array_sum($itemCogsAmounts)
            : (float) $order->items->sum(fn ($item) => $item->is_gift ? 0 : (float) $item->quantity * (float) $item->purchase_price);

        $shippingAmount = $shippingAmount !== null ? round($shippingAmount, 2) : 0.0;
        $includeShipping = $shippingAmount > 0 && $shippingIncomeAccountId !== null;

        $receivableDebit = $saleAmount + ($includeShipping ? $shippingAmount : 0.0);

        $saleLines = [
            [
                'account_id'     => $receivableOrCashAccountId,
                'debit'          => $receivableDebit,
                'subledger_type' => $isReceivable ? Customer::class : null,
                'subledger_id'   => $isReceivable ? $order->customer_id : null,
            ],
            ['account_id' => $salesAccountId, 'credit' => $saleAmount],
        ];

        if ($includeShipping) {
            $saleLines[] = ['account_id' => $shippingIncomeAccountId, 'credit' => $shippingAmount];
        }

        $saleEntry = $this->postJournalEntry->handle([
            'entry_date'       => $entryDate,
            'description'      => "Sale — Order #{$order->id}",
            'transaction_type' => TransactionType::SALE->value,
            'source_type'      => Order::class,
            'source_id'        => $order->id,
            'purpose'          => 'sale' . $purposeSuffix,
        ], $saleLines);

        $cogsEntry = null;
        if ($cogsAmount > 0) {
            $cogsEntry = $this->postJournalEntry->handle([
                'entry_date'       => $entryDate,
                'description'      => "COGS — Order #{$order->id}",
                'transaction_type' => TransactionType::COGS->value,
                'source_type'      => Order::class,
                'source_id'        => $order->id,
                'purpose'          => 'cogs' . $purposeSuffix,
            ], [
                ['account_id' => $cogsAccountId, 'debit' => $cogsAmount],
                ['account_id' => $inventoryAccountId, 'credit' => $cogsAmount],
            ]);
        }

        $invoice = null;
        if ($isReceivable) {
            $invoice = AccountsCustomerInvoice::firstWhere('order_id', $order->id);

            if ($invoice) {
                $invoice->increment('amount', $receivableDebit);
            } else {
                $invoice = AccountsCustomerInvoice::create([
                    'customer_id'      => $order->customer_id,
                    'order_id'         => $order->id,
                    'invoice_number'   => 'INV-' . str_pad((string) $order->id, 6, '0', STR_PAD_LEFT),
                    'amount'           => $receivableDebit,
                    'amount_allocated' => 0,
                    'status'           => 'open',
                    'journal_entry_id' => $saleEntry->id,
                ]);
            }
        }

        return ['sale' => $saleEntry, 'cogs' => $cogsEntry, 'invoice' => $invoice];
    }
}
