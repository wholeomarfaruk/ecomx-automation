<?php

namespace App\Actions\Accounts;

use App\Enums\Accounts\TransactionType;
use App\Enums\Sales\OrderPaymentType;
use App\Enums\Sales\PaymentMethod;
use App\Enums\Sales\PaymentStatus;
use App\Models\Account;
use App\Models\AccountsCustomerAdvance;
use App\Models\AccountsCustomerInvoice;
use App\Models\AccountsPaymentAllocation;
use App\Models\CourierShipment;
use App\Models\Customer;
use App\Models\Setting;
use Illuminate\Support\Facades\DB;

/**
 * A courier's delivery webhook reports it collected cash from the customer
 * (Pathao's collected_amount on "order.delivered") — but what actually
 * lands in the courier's own cash-in-hand account is net of their delivery
 * + COD fee (Order::courier_charge, computed at booking time), since the
 * courier keeps that before remitting the rest. The customer's receivable/
 * advance still clears for the FULL collected amount though — they really
 * did pay the whole thing, the fee is the business's own cost of using this
 * courier, not something the customer owes less of. So one journal entry
 * splits the debit side: Dr Courier Cash (net) + Dr Courier Expense (fee) =
 * Cr Receivable-or-Advance (full collected amount).
 *
 * Same completed/not-completed branch addPayment() uses: if the order has
 * been completed (a real AccountsCustomerInvoice exists), this clears
 * Accounts Receivable directly; otherwise it's a customer advance (no sale
 * recognized yet). Idempotent per shipment — a courier can resend the same
 * delivered webhook more than once, so this only posts the first time.
 */
class PostCourierCodCollected
{
    public function __construct(protected PostJournalEntry $postJournalEntry) {}

    /** @param  string  $when  for the journal description: "on delivery", "on partial delivery", "on paid return" */
    public function handle(CourierShipment $shipment, float $collectedAmount, string $entryDate, string $when = 'on delivery'): void
    {
        $order = $shipment->order;

        if (! $order || $collectedAmount <= 0) {
            return;
        }

        if ($order->payments()->where('type', OrderPaymentType::PAYMENT)->where('payment_method', PaymentMethod::COD)->exists()) {
            return;
        }

        $accountsOn = (bool) Setting::get('accounts_enabled', false, 'modules');
        $cashAccount = $shipment->courier?->cashAccount;

        // The journal needs the courier's cash account and a customer to
        // post against. With the Accounts module off there's no journal —
        // the order's payment section is the only record, so it's still kept.
        if ($accountsOn && (! $order->customer_id || ! $cashAccount)) {
            return;
        }

        $fee = min($collectedAmount, max(0.0, (float) $order->courier_charge));
        $netAmount = round($collectedAmount - $fee, 2);

        DB::transaction(function () use ($order, $shipment, $collectedAmount, $fee, $netAmount, $entryDate, $cashAccount, $when, $accountsOn) {
            $order->payments()->create([
                'type'            => OrderPaymentType::PAYMENT,
                'payment_method'  => PaymentMethod::COD,
                'cash_account_id' => $cashAccount?->id,
                'amount'          => $collectedAmount,
                'status'          => 'paid',
                'paid_at'         => now(),
            ]);

            $order->recalculateTotals();
            $order->update(['payment_status' => $order->due_amount > 0 ? PaymentStatus::PARTIAL : PaymentStatus::PAID]);

            if (! $accountsOn) {
                return;
            }

            $customer = $order->customer;
            $invoice = AccountsCustomerInvoice::where('order_id', $order->id)->first();
            $description = "COD collected {$when} — Order #{$order->id} ({$shipment->tracking_number})";

            $creditAccountId = $invoice ? $this->accountId('1100') : $this->accountId('2160');

            $lines = [];

            // Zero when the courier keeps everything it collected — a paid
            // return where the customer only paid the delivery charge.
            if ($netAmount > 0) {
                $lines[] = [
                    'account_id' => $cashAccount->id,
                    'debit'      => $netAmount,
                ];
            }

            if ($fee > 0) {
                $lines[] = [
                    'account_id' => $this->accountId('5120'),
                    'debit'      => $fee,
                ];
            }

            $lines[] = [
                'account_id'     => $creditAccountId,
                'credit'         => $collectedAmount,
                'subledger_type' => Customer::class,
                'subledger_id'   => $customer->id,
            ];

            $entry = $this->postJournalEntry->handle([
                'entry_date'       => $entryDate,
                'description'      => $description,
                'transaction_type' => TransactionType::COD_SETTLEMENT->value,
            ], $lines);

            if ($invoice) {
                $applied = min($collectedAmount, $invoice->amountDue());

                if ($applied > 0) {
                    AccountsPaymentAllocation::create([
                        'payment_journal_entry_id' => $entry->id,
                        'allocatable_type'         => AccountsCustomerInvoice::class,
                        'allocatable_id'           => $invoice->id,
                        'amount'                   => $applied,
                    ]);

                    $invoice->increment('amount_allocated', $applied);
                    $invoice->refresh();
                    $invoice->update(['status' => $invoice->amountDue() <= 0.01 ? 'paid' : 'partial']);
                }
            } else {
                AccountsCustomerAdvance::create([
                    'customer_id'      => $customer->id,
                    'order_id'         => $order->id,
                    'amount'           => $collectedAmount,
                    'amount_applied'   => 0,
                    'status'           => 'held',
                    'journal_entry_id' => $entry->id,
                ]);
            }
        });
    }

    protected function accountId(string $code): int
    {
        return Account::where('code', $code)->value('id')
            ?? throw new \RuntimeException("Chart of accounts is missing account code {$code}.");
    }
}
