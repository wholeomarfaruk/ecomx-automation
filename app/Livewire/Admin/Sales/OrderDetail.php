<?php

namespace App\Livewire\Admin\Sales;

use App\Actions\Accounts\PostCustomerAdvance;
use App\Actions\Accounts\PostCustomerPayment;
use App\Actions\Accounts\PostOrderCompletion;
use App\Actions\Accounts\PostOrderReturn;
use App\Actions\Accounts\PostRtoFee;
use App\Actions\Accounts\RefundCustomerAdvance;
use App\Actions\Accounts\RefundOrder;
use App\Actions\Accounts\ReverseOrder;
use App\Actions\Sales\PackOrderItem;
use App\Enums\Sales\CourierStatus;
use App\Enums\Sales\FulfillmentStatus;
use App\Enums\Sales\OrderPaymentType;
use App\Enums\Sales\OrderStatus;
use App\Enums\Sales\PaymentMethod;
use App\Enums\Sales\PaymentStatus;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Livewire\Concerns\BooksCourierShipments;
use App\Models\Account;
use App\Models\AccountsCustomerAdvance;
use App\Models\AccountsCustomerInvoice;
use App\Models\InventoryBatch;
use App\Models\JournalEntry;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class OrderDetail extends Component
{
    use BooksCourierShipments;

    public int $orderId;

    public string $status            = '';
    public string $paymentStatus     = '';
    public string $fulfillmentStatus = '';

    public string $courierProvider = '';
    public string $courierTrackingNumber = '';
    public string $courierCharge   = '';
    public string $courierStatus   = '';

    public bool   $paymentModal    = false;
    public string $paymentAccountId = '';
    public string $transactionId   = '';
    public string $paymentAmount   = '';
    public string $paymentStatusNew = 'paid';

    public bool   $refundModal        = false;
    public string $refundAmount       = '';
    public string $refundCashAccountId = '';
    public bool   $refundAsStoreCredit = false;
    public string $refundNote         = '';

    /** @var array<int, string> item_id => returned_quantity */
    public array $returnedQuantities = [];

    /** @var array<int, string> item_id => delivered_quantity */
    public array $deliveredQuantities = [];

    public string $returnCharge = '';
    public string $returnChargeCashAccountId = '';

    public bool $packModal = false;
    public ?int $packingItemId = null;

    /** @var array<int, array{batch_id: string, quantity: string}> */
    public array $packAllocations = [];

    public function mount(int $id): void
    {
        $order = Order::findOrFail($id);

        $this->orderId           = $order->id;
        $this->status            = $order->status->value;
        $this->paymentStatus     = $order->payment_status->value;
        $this->fulfillmentStatus = $order->fulfillment_status->value;

        $this->courierProvider        = $order->courier_provider ?? '';
        $this->courierTrackingNumber  = $order->courier_tracking_number ?? '';
        $this->courierCharge          = $order->courier_charge !== null ? (string) $order->courier_charge : '';
        $this->courierStatus          = $order->courier_status?->value ?? '';

        foreach ($order->items as $item) {
            $this->returnedQuantities[$item->id] = (string) $item->returned_quantity;
            $this->deliveredQuantities[$item->id] = (string) $item->delivered_quantity;
        }
    }

    /** This page always books for its own order — wraps the trait's generic method so the view can call openBookingModal() with no arguments, same as before. */
    public function openBookingModalForCurrentOrder(): void
    {
        $this->openBookingModal($this->orderId);
    }

    public bool $confirmReverseModal = false;

    /**
     * True once the admin has confirmed the "this order has a payment on it"
     * warning in confirmReverseModal — updateStatus() re-checks this instead
     * of re-asking, so the second call (from the modal's Confirm button)
     * goes straight through.
     */
    public bool $reverseAcknowledged = false;

    /**
     * The status dropdown drives everything: booking/releasing stock
     * (OrderStatus::isBookable()) and, for COMPLETED specifically, the full
     * Sale+COGS+shipping posting and physical stock deduction
     * (PostOrderCompletion, item-level partial-delivery aware). A cancel or
     * post-completion return that still has a paid amount on it shows a
     * confirm gate first — force-completing that gate converts whatever was
     * paid into Customer Credit automatically (ReverseOrder), same as
     * before.
     */
    public function updateStatus(): void
    {
        $order = Order::with('items')->findOrFail($this->orderId);
        $oldStatus = $order->status;
        $newStatus = OrderStatus::from($this->status);

        $isUnwinding = ! $newStatus->isBookable() && $newStatus !== OrderStatus::COMPLETED && $oldStatus !== $newStatus;

        if ($isUnwinding && (float) $order->paid_amount > 0 && ! $this->reverseAcknowledged) {
            $this->confirmReverseModal = true;
            return;
        }

        $stockService = app(StockService::class);

        try {
            DB::transaction(function () use ($order, $oldStatus, $newStatus, $stockService) {
                $order->update([
                    'status'             => $this->status,
                    'payment_status'     => $this->paymentStatus,
                    'fulfillment_status' => $this->fulfillmentStatus,
                ]);

                $bookOnConfirm = (bool) Setting::get('book_on_order_confirm', true, 'inventory');

                if ($bookOnConfirm && ! $oldStatus->isBookable() && $newStatus->isBookable()) {
                    $stockService->bookOrder($order);
                } elseif ($bookOnConfirm && $oldStatus->isBookable() && ! $newStatus->isBookable() && $newStatus !== OrderStatus::COMPLETED) {
                    $stockService->releaseBooking($order, $this->releaseTypeFor($newStatus));
                }
            });
        } catch (InsufficientStockException $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
            return;
        }

        if ($newStatus === OrderStatus::COMPLETED && $oldStatus !== OrderStatus::COMPLETED) {
            app(PostOrderCompletion::class)->handle($order->fresh(['items']), now()->toDateString());
        }

        if ($isUnwinding) {
            $hasBeenCompleted = JournalEntry::query()
                ->where('source_type', Order::class)
                ->where('source_id', $order->id)
                ->where('purpose', 'like', 'sale%')
                ->exists();

            if ($hasBeenCompleted) {
                // A full force-cancel/return of an already-completed order
                // via this status-dropdown path (as opposed to entering
                // per-item return quantities in the Items card first) — mark
                // every item fully returned so PostOrderReturn's delta-based
                // reversal captures the whole order, exactly as if the admin
                // had entered each item's full quantity as returned.
                foreach ($order->items as $item) {
                    if ((float) $item->returned_quantity < (float) $item->quantity) {
                        $item->update(['returned_quantity' => $item->quantity]);
                    }
                }

                app(PostOrderReturn::class)->handle($order->fresh(['items']), now()->toDateString());
                app(StockService::class)->releaseOrder($order->fresh(['items']));
            }

            // Converts any still-paid amount into Customer Credit — the only
            // part of ReverseOrder still relevant here, since the Sale/COGS
            // reversal above (when applicable) already went through
            // PostOrderReturn.
            app(ReverseOrder::class)->handle($order->fresh(), now()->toDateString());
        }

        $this->confirmReverseModal = false;
        $this->reverseAcknowledged = false;

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($order)
            ->event('updated')
            ->log("Order #{$order->id} status updated");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Order status updated']);
    }

    protected function releaseTypeFor(OrderStatus $status): string
    {
        return match ($status) {
            OrderStatus::CANCELLED => 'unbooked_cancelled',
            OrderStatus::RETURNING, OrderStatus::RETURNED, OrderStatus::PARTIALLY_RETURNED => 'unbooked_returned',
            default => 'unbooked_cancelled',
        };
    }

    public function confirmReverseAndUpdateStatus(): void
    {
        $this->reverseAcknowledged = true;
        $this->updateStatus();
    }

    public function cancelReverseModal(): void
    {
        $this->confirmReverseModal = false;
        $this->reverseAcknowledged = false;
    }

    protected function accountId(string $code): int
    {
        return Account::where('code', $code)->value('id')
            ?? throw new \RuntimeException("Chart of accounts is missing account code {$code}.");
    }

    public function updateCourier(): void
    {
        $order = Order::findOrFail($this->orderId);

        $order->update([
            'courier_provider'          => $this->courierProvider ?: null,
            'courier_tracking_number'   => $this->courierTrackingNumber ?: null,
            'courier_charge'            => $this->courierCharge !== '' ? $this->courierCharge : null,
            'courier_status'            => $this->courierStatus ?: null,
            'courier_status_updated_at' => now(),
        ]);

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($order)
            ->event('updated')
            ->log("Order #{$order->id} courier details updated");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Courier details updated']);
    }

    public function openPaymentModal(): void
    {
        $this->reset(['paymentAccountId', 'transactionId', 'paymentAmount']);
        $this->paymentStatusNew = 'paid';
        $this->resetValidation();
        $this->paymentModal = true;
    }

    public function addPayment(): void
    {
        $this->validate([
            'paymentAccountId' => 'required|exists:accounts,id',
            'transactionId'    => 'nullable|string|max:255',
            'paymentAmount'    => 'required|numeric|min:0.01',
            'paymentStatusNew' => 'required|in:pending,partial,paid,failed,refunded',
        ]);

        $order = Order::findOrFail($this->orderId);
        $cashAccount = Account::findOrFail((int) $this->paymentAccountId);

        $order->payments()->create([
            'type'            => OrderPaymentType::PAYMENT,
            'payment_method'  => $this->paymentMethodForAccount($cashAccount),
            'cash_account_id' => $cashAccount->id,
            'transaction_id'  => $this->transactionId ?: null,
            'amount'          => $this->paymentAmount,
            'status'          => $this->paymentStatusNew,
            'paid_at'         => $this->paymentStatusNew === 'paid' ? now() : null,
        ]);

        $order->recalculateTotals();

        if ($this->paymentStatusNew === 'paid' && $order->customer_id) {
            $hasBeenCompleted = AccountsCustomerInvoice::where('order_id', $order->id)->exists();

            if ($hasBeenCompleted) {
                app(PostCustomerPayment::class)->handle(
                    customer: $order->customer,
                    cashAccountId: $cashAccount->id,
                    receivableAccountId: $this->accountId('1100'),
                    amount: (float) $this->paymentAmount,
                    entryDate: now()->toDateString(),
                    description: "Payment for Order #{$order->id} ({$cashAccount->name})",
                );
            } else {
                app(PostCustomerAdvance::class)->handle(
                    customer: $order->customer,
                    order: $order,
                    customerAdvanceAccountId: $this->accountId('2160'),
                    cashAccountId: $cashAccount->id,
                    amount: (float) $this->paymentAmount,
                    entryDate: now()->toDateString(),
                    description: "Advance for Order #{$order->id} ({$cashAccount->name})",
                );
            }
        }

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($order)
            ->event('updated')
            ->log("Payment of {$this->paymentAmount} recorded for Order #{$order->id}");

        $this->paymentModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Payment recorded']);
    }

    public function openRefundModal(): void
    {
        $this->reset(['refundAmount', 'refundCashAccountId', 'refundAsStoreCredit', 'refundNote']);
        $this->resetValidation();
        $this->refundModal = true;
    }

    /**
     * Refunds part or all of what this customer has actually paid on the
     * order — capped at paid_amount (a refund larger than what was received
     * doesn't make sense here). Cash refunds move money out of a real
     * cash/bank account; a store-credit refund instead credits the
     * customer's Customer Credit balance with no cash movement, redeemable
     * on a future order via the Accounts > customer-credit flow.
     */
    public function refundOrder(): void
    {
        $order = Order::findOrFail($this->orderId);

        $rules = [
            'refundAmount' => ['required', 'numeric', 'min:0.01', 'max:' . max(0.01, (float) $order->paid_amount)],
            'refundNote'   => 'nullable|string|max:255',
        ];

        if (! $this->refundAsStoreCredit) {
            $rules['refundCashAccountId'] = 'required|exists:accounts,id';
        }

        $this->validate($rules);

        if (! $order->customer_id) {
            $this->addError('refundAmount', 'This order has no customer on record — a refund needs one to credit.');
            return;
        }

        $cashAccount = $this->refundAsStoreCredit ? null : Account::find((int) $this->refundCashAccountId);

        $order->payments()->create([
            'type'            => OrderPaymentType::REFUND,
            'payment_method'  => $this->refundAsStoreCredit ? PaymentMethod::STORE_CREDIT : $this->paymentMethodForAccount($cashAccount),
            'cash_account_id' => $cashAccount?->id,
            'amount'          => $this->refundAmount,
            'status'          => 'refunded',
            'paid_at'         => now(),
        ]);

        $order->recalculateTotals();

        $hasBeenCompleted = AccountsCustomerInvoice::where('order_id', $order->id)->exists();

        if ($hasBeenCompleted) {
            app(RefundOrder::class)->handle(
                order: $order,
                amount: (float) $this->refundAmount,
                entryDate: now()->toDateString(),
                asStoreCredit: $this->refundAsStoreCredit,
                cashAccountId: $cashAccount?->id,
                description: $this->refundNote ?: null,
            );
        } else {
            $advance = AccountsCustomerAdvance::where('order_id', $order->id)
                ->whereIn('status', ['held', 'partial'])
                ->oldest()
                ->first();

            if ($advance) {
                app(RefundCustomerAdvance::class)->handle(
                    advance: $advance,
                    amount: (float) $this->refundAmount,
                    entryDate: now()->toDateString(),
                    asStoreCredit: $this->refundAsStoreCredit,
                    cashAccountId: $cashAccount?->id,
                    customerAdvanceAccountId: $this->accountId('2160'),
                    customerCreditAccountId: $this->accountId('2150'),
                    description: $this->refundNote ?: null,
                );
            }
        }

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($order)
            ->event('updated')
            ->log("Refund of {$this->refundAmount} recorded for Order #{$order->id}" . ($this->refundAsStoreCredit ? ' (store credit)' : ''));

        $this->refundModal = false;
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Refund recorded']);
    }

    /**
     * Best-effort PaymentMethod for a refund's own record, derived from the
     * chosen cash/bank Account's subtype — a refund doesn't go through
     * checkout's payment_method picker, so there's no gateway/wallet value
     * supplied directly the way a customer payment has one.
     */
    protected function paymentMethodForAccount(?Account $account): PaymentMethod
    {
        return match ($account?->subtype) {
            'bank' => PaymentMethod::BANK,
            'mobile_banking' => PaymentMethod::BKASH,
            default => PaymentMethod::CASH,
        };
    }

    /**
     * Item-level returns for an order that has already been completed
     * (a real Sale/COGS exists). Restocks the newly-returned quantity,
     * reverses that slice's Sale/COGS via PostOrderReturn (partial-aware —
     * only the delta since the last save), releases any leftover booking
     * (normally already 0 by the time an order is completed, but harmless
     * either way), and posts a return/RTO charge if one was entered.
     */
    public function saveReturns(): void
    {
        $order = Order::with('items')->findOrFail($this->orderId);
        $stockService = app(StockService::class);
        $restockOnReturn = (bool) Setting::get('restock_on_cancel_or_return', true, 'inventory');

        if ($this->returnCharge !== '' && $this->returnChargeCashAccountId === '') {
            $this->addError('returnChargeCashAccountId', 'Select which account the return charge was paid from.');
            return;
        }

        $hasBeenCompleted = AccountsCustomerInvoice::where('order_id', $order->id)->exists();

        DB::transaction(function () use ($order, $stockService, $restockOnReturn, $hasBeenCompleted) {
            foreach ($order->items as $item) {
                $value = $this->returnedQuantities[$item->id] ?? '0';
                $qty   = min((float) $value, (float) $item->quantity);
                $qty   = max(0, $qty);

                $item->update(['returned_quantity' => $qty]);
            }

            // Compute the accounting reversal delta BEFORE restocking —
            // PostOrderReturn derives "newly returned" from the same
            // type='return' inventory ledger that restockReturnedItem() is
            // about to write, so it must run first or every delta looks
            // already-consumed.
            if ($hasBeenCompleted) {
                app(PostOrderReturn::class)->handle($order->fresh(['items']), now()->toDateString());
            }

            if ($restockOnReturn) {
                foreach ($order->items as $item) {
                    $qty = (float) $item->fresh()->returned_quantity;

                    if ($qty > 0) {
                        $stockService->restockReturnedItem($item, $qty);
                    }
                }
            }
        });

        $order->syncReturnStatus();
        $order->refresh();
        $this->status = $order->status->value;

        $stockService->releaseBooking($order->fresh(['items']), 'unbooked_returned');

        if ($this->returnCharge !== '' && (float) $this->returnCharge > 0) {
            app(PostRtoFee::class)->handle(
                order: $order,
                courierExpenseAccountId: $this->accountId('5120'),
                paidFromAccountId: (int) $this->returnChargeCashAccountId,
                fee: (float) $this->returnCharge,
                entryDate: now()->toDateString(),
                description: "Return charge — Order #{$order->id}",
            );
        }

        $this->returnCharge = '';
        $this->returnChargeCashAccountId = '';

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($order)
            ->event('updated')
            ->log("Returns recorded for Order #{$order->id}");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Returns saved']);
    }

    /**
     * Item-level delivered-quantity tracking — pure shipping-status
     * bookkeeping (syncDeliveryStatus() only flips DELIVERED/
     * PARTIALLY_DELIVERED), no stock or accounting effect here. Those only
     * happen when the order is separately marked COMPLETED. Items with a
     * batch allocation (packed via the Pack Items modal) are skipped here —
     * packing is now the source of truth for their delivered_quantity, and
     * this manual input stays only for items nobody has packed yet.
     */
    public function saveDeliveries(): void
    {
        $order = Order::with('items.batchAllocations')->findOrFail($this->orderId);

        foreach ($order->items as $item) {
            if ($item->batchAllocations->isNotEmpty()) {
                continue;
            }

            $value = $this->deliveredQuantities[$item->id] ?? '0';
            $qty   = min((float) $value, (float) $item->quantity);
            $qty   = max(0, $qty);

            $item->update(['delivered_quantity' => $qty]);
        }

        $order->syncDeliveryStatus();
        $this->status = $order->fresh()->status->value;

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($order)
            ->event('updated')
            ->log("Delivery progress recorded for Order #{$order->id}");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Delivery progress saved']);
    }

    /**
     * Opens the Pack Items modal for one order item — pre-fills the
     * allocation rows from whatever batches it's currently packed from (so
     * re-opening to adjust a partially-packed item shows current state),
     * or one empty row if it's never been packed.
     */
    public function openPackModal(int $itemId): void
    {
        $item = OrderItem::with('batchAllocations')->findOrFail($itemId);

        $this->packingItemId = $item->id;
        $this->resetValidation();

        if ($item->batchAllocations->isNotEmpty()) {
            $this->packAllocations = $item->batchAllocations
                ->map(fn ($allocation) => [
                    'batch_id' => (string) $allocation->inventory_batch_id,
                    'quantity' => (string) $allocation->quantity,
                ])
                ->values()
                ->all();
        } else {
            $this->packAllocations = [['batch_id' => '', 'quantity' => '']];
        }

        $this->packModal = true;
    }

    public function addPackRow(): void
    {
        $this->packAllocations[] = ['batch_id' => '', 'quantity' => ''];
    }

    public function removePackRow(int $index): void
    {
        unset($this->packAllocations[$index]);
        $this->packAllocations = array_values($this->packAllocations);
    }

    public function closePackModal(): void
    {
        $this->packModal = false;
        $this->packingItemId = null;
        $this->packAllocations = [];
    }

    /**
     * Applies the picked batch allocation to the item being packed —
     * PackOrderItem does the actual physical/batch deduction and
     * OrderItemBatch bookkeeping; this just validates the form and updates
     * the on-screen delivered-quantity display afterward.
     */
    public function savePacking(): void
    {
        $item = OrderItem::findOrFail($this->packingItemId);

        $allocations = [];
        $total = 0.0;

        foreach ($this->packAllocations as $row) {
            $batchId = $row['batch_id'] ?? '';
            $qty = (float) ($row['quantity'] ?? 0);

            if ($batchId === '' || $qty <= 0) {
                continue;
            }

            $allocations[(int) $batchId] = ($allocations[(int) $batchId] ?? 0) + $qty;
            $total += $qty;
        }

        if (empty($allocations)) {
            $this->addError('packAllocations', 'Pick at least one batch and quantity.');
            return;
        }

        if ($total > (float) $item->quantity + 0.001) {
            $this->addError('packAllocations', "Allocated quantity ({$total}) exceeds the ordered quantity ({$item->quantity}).");
            return;
        }

        try {
            app(PackOrderItem::class)->handle($item, $allocations);
        } catch (InsufficientStockException $e) {
            $this->addError('packAllocations', $e->getMessage());
            return;
        }

        $this->deliveredQuantities[$item->id] = (string) $item->fresh()->delivered_quantity;
        $this->status = $item->order->fresh()->status->value;

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($item->order)
            ->event('updated')
            ->log("Order item #{$item->id} packed from " . count($allocations) . ' batch(es)');

        $this->closePackModal();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Item packed']);
    }

    /**
     * Active batches (remaining quantity > 0) for whichever item the Pack
     * Items modal currently has open, offered as the batch picker's
     * options — scoped to that item's specific product+variant so the
     * admin only ever sees batches that actually match what's being packed.
     */
    protected function packableBatchesForCurrentItem()
    {
        if (! $this->packingItemId) {
            return collect();
        }

        $item = OrderItem::find($this->packingItemId);

        if (! $item || ! $item->product_id) {
            return collect();
        }

        return InventoryBatch::query()
            ->where('product_id', $item->product_id)
            ->where('variant_id', $item->variant_id)
            ->active()
            ->orderBy('expiry_date')
            ->get();
    }

    public function render(): mixed
    {
        $order = Order::with([
            'customer',
            'billingAddress',
            'shippingAddress',
            'items.product',
            'items.variant',
            'items.combo.items.product',
            'items.combo.items.variant',
            'items.batchAllocations.batch',
            'payments',
            'courierShipments.courier',
            'courierShipments.courierAccount',
            'courierShipments.trackingEvents',
        ])->findOrFail($this->orderId);

        $canManageCourier = auth()->user()->can('courier_configuration.manage');

        return view('livewire.admin.sales.order-detail', [
            'order'               => $order,
            'statuses'            => OrderStatus::cases(),
            'paymentStatuses'     => PaymentStatus::cases(),
            'fulfillmentStatuses' => FulfillmentStatus::cases(),
            'courierStatuses'     => CourierStatus::cases(),
            'bookableAccounts'    => $canManageCourier ? $this->bookableAccounts() : collect(),
            'canManageCourier'    => $canManageCourier,
            'cashAccounts'        => Account::active()->whereIn('subtype', ['cash', 'bank', 'mobile_banking'])->orderBy('code')->get(),
            'packableBatches'     => $this->packableBatchesForCurrentItem(),
        ])->layout('layouts.admin.admin');
    }
}
