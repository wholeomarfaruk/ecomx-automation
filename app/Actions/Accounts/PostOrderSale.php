<?php

namespace App\Actions\Accounts;

use App\Exceptions\Accounts\DuplicateJournalEntryException;
use App\Models\Account;
use App\Models\Order;

/**
 * Shared entry point for booking a confirmed order's sale + COGS, used by
 * every place an order can become "confirmed" (OrderDetail's status change,
 * OrderCreate's admin form) so none of them can drift out of sync on which
 * accounts get used. Skipped for guest orders — AccountsCustomerInvoice
 * requires a customer — and safely ignored if this order was already
 * posted, so callers don't need their own duplicate guard.
 */
class PostOrderSale
{
    public function __construct(protected PostSaleWithCogs $postSaleWithCogs) {}

    public function handle(Order $order): void
    {
        if (! $order->customer_id) {
            return;
        }

        try {
            $this->postSaleWithCogs->handle(
                order: $order,
                receivableOrCashAccountId: $this->accountId('1100'),
                salesAccountId: $this->accountId('4000'),
                inventoryAccountId: $this->accountId('1200'),
                cogsAccountId: $this->accountId('5000'),
            );
        } catch (DuplicateJournalEntryException) {
            // Already posted for this order — nothing to do.
        }
    }

    protected function accountId(string $code): int
    {
        return Account::where('code', $code)->value('id')
            ?? throw new \RuntimeException("Chart of accounts is missing account code {$code}.");
    }
}
