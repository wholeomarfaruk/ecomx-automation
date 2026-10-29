<?php

namespace App\Livewire\Admin\Sales;

use App\Actions\Sales\Exchanges\CloseExchange;
use App\Actions\Sales\Exchanges\CreateExchange;
use App\Actions\Sales\Exchanges\ShipExchange;
use App\Courier\Enums\CourierCapability;
use App\Enums\Sales\OrderStatus;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Models\Courier;
use App\Models\CourierAccount;
use App\Models\Order;
use App\Models\OrderExchange;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use DomainException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The order page's Exchanges card: open an exchange inside the order (what
 * comes back, what goes out, the exchange charge), put it on a courier
 * parcel (book through the API or attach one booked on the courier's panel)
 * and close it (old item received / customer refused / cancel). Every
 * change goes through App\Actions\Sales\Exchanges\*; the page refreshes
 * through `order-updated`.
 */
class OrderExchanges extends Component
{
    #[Locked]
    public int $orderId;

    // New exchange
    public bool $createModal = false;
    /** @var array<int, string> order item id => quantity coming back */
    public array $back = [];
    /** @var list<array{product_id: string, variant_id: string, quantity: string, unit_price: string}> */
    public array $newLines = [];
    public string $productPickerId = '';
    public string $charge = '';
    public string $reason = '';

    // Parcel
    public bool $shipModal = false;
    public ?int $shipExchangeId = null;
    public string $shipMode = 'book';
    public string $shipAccountId = '';
    public string $shipCourierId = '';
    public string $shipTracking = '';
    public string $shipCod = '';
    public string $shipWeight = '0.5';
    public string $shipQuantity = '1';
    public string $shipDescription = '';
    public string $shipInstruction = '';

    // Customer refused
    public bool $failModal = false;
    public ?int $failExchangeId = null;
    public bool $failKeepCharge = false;

    public function mount(int $orderId): void
    {
        $this->orderId = $orderId;
    }

    protected function order(): Order
    {
        return Order::with('items')->findOrFail($this->orderId);
    }

    protected function exchange(int $id): OrderExchange
    {
        return OrderExchange::where('order_id', $this->orderId)->findOrFail($id);
    }

    protected function canEdit(): bool
    {
        $user = auth()->user();

        return (bool) ($user?->hasRole('superadmin') || $user?->can('order.edit'));
    }

    protected function canCourier(): bool
    {
        return (bool) auth()->user()?->can('courier_configuration.manage');
    }

    protected function done(string $message): void
    {
        // The order page re-reads totals, items and payment status (not back to this card — it re-renders anyway).
        $this->dispatch('order-updated')->to(OrderDetail::class);
        $this->dispatch('toast', ['type' => 'success', 'message' => $message]);
    }

    protected function toastError(string $message): void
    {
        $this->dispatch('toast', ['type' => 'error', 'message' => $message]);
    }

    // ── New exchange ─────────────────────────────────────────────────────

    public function openCreate(): void
    {
        abort_unless($this->canEdit(), 403);

        $this->resetValidation();
        $this->reset(['back', 'newLines', 'productPickerId', 'reason']);
        $this->charge = (string) (float) $this->order()->shipping_amount;
        $this->createModal = true;
    }

    public function updatedProductPickerId(string $value): void
    {
        $product = $value !== '' ? Product::active()->find((int) $value) : null;

        if ($product) {
            $this->newLines[] = [
                'product_id' => (string) $product->id,
                'variant_id' => '',
                'quantity'   => '1',
                'unit_price' => (string) $product->sellingPrice(),
            ];
        }

        $this->productPickerId = '';
    }

    public function updatedNewLines(mixed $value, string $key): void
    {
        // Picking a variant re-prices the line at that variant's price.
        [$index, $field] = array_pad(explode('.', $key), 2, null);

        if ($field !== 'variant_id' || ! isset($this->newLines[$index])) {
            return;
        }

        $line = $this->newLines[$index];
        $product = Product::find((int) $line['product_id']);
        $variant = $line['variant_id'] !== '' ? ProductVariant::where('product_id', $line['product_id'])->find((int) $line['variant_id']) : null;

        if ($product) {
            $this->newLines[$index]['unit_price'] = (string) $product->sellingPrice($variant);
        }
    }

    public function removeNewLine(int $index): void
    {
        unset($this->newLines[$index]);
        $this->newLines = array_values($this->newLines);
    }

    public function create(CreateExchange $createExchange): void
    {
        abort_unless($this->canEdit(), 403);

        $this->validate([
            'back.*'                => 'nullable|numeric|min:0',
            'newLines'              => 'required|array|min:1',
            'newLines.*.product_id' => 'required|exists:products,id',
            'newLines.*.variant_id' => 'nullable',
            'newLines.*.quantity'   => 'required|numeric|min:0.001',
            'newLines.*.unit_price' => 'required|numeric|min:0',
            'charge'                => 'nullable|numeric|min:0',
            'reason'                => 'nullable|string|max:255',
        ], [], [
            'newLines' => 'new items', 'newLines.*.quantity' => 'quantity', 'newLines.*.unit_price' => 'price',
        ]);

        // A product with variants needs one picked.
        foreach ($this->newLines as $i => $line) {
            if ($line['variant_id'] === '' && ProductVariant::active()->where('product_id', $line['product_id'])->exists()) {
                $this->addError("newLines.{$i}.variant_id", 'Pick a variant.');

                return;
            }
        }

        try {
            $exchange = $createExchange->handle($this->order(), $this->back, $this->newLines, (float) $this->charge, $this->reason);
        } catch (DomainException|InsufficientStockException $e) {
            $this->toastError($e->getMessage());

            return;
        }

        $this->createModal = false;
        $this->done("Exchange #{$exchange->number} opened");
    }

    // ── Parcel ───────────────────────────────────────────────────────────

    public function openShip(int $exchangeId): void
    {
        abort_unless($this->canEdit() && $this->canCourier(), 403);

        $exchange = $this->exchange($exchangeId)->load('returnItems.orderItem', 'newItems');
        $order = $this->order();
        $fmt = fn ($n) => rtrim(rtrim(number_format((float) $n, 3, '.', ''), '0'), '.');

        $this->resetValidation();
        $this->shipExchangeId = $exchange->id;
        $this->shipMode = 'book';
        $this->shipAccountId = '';
        $this->shipCourierId = (string) ($order->courierShipments()->where('type', '!=', 'exchange')->value('courier_id') ?? '');
        $this->shipTracking = '';
        // What's left to collect on the order — the exchange's balance after the credit.
        $this->shipCod = (string) (float) $order->due_amount;
        $this->shipQuantity = (string) max(1, (int) ceil((float) $exchange->newItems->sum('quantity')));
        $this->shipWeight = (string) max(0.5, round((float) $exchange->newItems->sum('quantity') * 0.5, 3));
        $this->shipDescription = mb_substr('EXCHANGE — deliver: ' . $exchange->newItems->map(fn ($i) => $fmt($i->quantity) . ' × ' . $i->product_name)->implode(', '), 0, 500);
        $this->shipInstruction = mb_substr('EXCHANGE parcel. Collect from customer: '
            . $exchange->returnItems->map(fn ($l) => $fmt($l->quantity) . ' × ' . $l->orderItem?->product_name)->implode(', ')
            . " (Order #{$order->id}).", 0, 500);
        $this->shipModal = true;
    }

    public function ship(ShipExchange $shipExchange): void
    {
        abort_unless($this->canEdit() && $this->canCourier(), 403);

        $exchange = $this->exchange((int) $this->shipExchangeId);

        try {
            if ($this->shipMode === 'book') {
                $this->validate([
                    'shipAccountId'   => 'required|exists:courier_accounts,id',
                    'shipCod'         => 'required|numeric|min:0',
                    'shipWeight'      => 'required|numeric|min:0.01',
                    'shipQuantity'    => 'required|integer|min:1',
                    'shipDescription' => 'nullable|string|max:500',
                    'shipInstruction' => 'nullable|string|max:500',
                ], [], ['shipAccountId' => 'courier']);

                $courier = CourierAccount::with('courier')->findOrFail((int) $this->shipAccountId)->courier;
                $tracking = $shipExchange->book($exchange, $courier, (float) $this->shipCod, (float) $this->shipWeight, (int) $this->shipQuantity, $this->shipDescription, $this->shipInstruction);
                $message = "Exchange parcel booked — tracking #{$tracking}";
            } else {
                $this->validate([
                    'shipCourierId' => 'required|exists:couriers,id',
                    'shipTracking'  => 'required|string|max:100',
                    'shipCod'       => 'required|numeric|min:0',
                ], [], ['shipCourierId' => 'courier', 'shipTracking' => 'consignment / tracking ID']);

                $shipExchange->attach($exchange, Courier::findOrFail((int) $this->shipCourierId), $this->shipTracking, (float) $this->shipCod);
                $message = 'Exchange parcel attached';
            }
        } catch (DomainException $e) {
            $this->toastError($e->getMessage());

            return;
        }

        $this->shipModal = false;
        $this->done($message);
    }

    /** Takes a not-yet-picked-up parcel off the exchange (it isn't cancelled at the courier). */
    public function detach(int $exchangeId, ShipExchange $shipExchange): void
    {
        abort_unless($this->canEdit(), 403);

        try {
            $shipExchange->detach($this->exchange($exchangeId));
        } catch (DomainException $e) {
            $this->toastError($e->getMessage());

            return;
        }

        $this->done('Parcel removed from the exchange');
    }

    // ── Close ────────────────────────────────────────────────────────────

    public function complete(int $exchangeId, CloseExchange $closeExchange): void
    {
        abort_unless($this->canEdit(), 403);

        try {
            $closeExchange->complete($this->exchange($exchangeId));
        } catch (DomainException|InsufficientStockException $e) {
            $this->toastError($e->getMessage());

            return;
        }

        $this->done('Exchange completed — old item restocked');
    }

    public function openFail(int $exchangeId): void
    {
        abort_unless($this->canEdit(), 403);

        $this->failExchangeId = $exchangeId;
        $this->failKeepCharge = false;
        $this->failModal = true;
    }

    public function markFailed(CloseExchange $closeExchange): void
    {
        abort_unless($this->canEdit(), 403);

        try {
            $closeExchange->fail($this->exchange((int) $this->failExchangeId), $this->failKeepCharge);
        } catch (DomainException|InsufficientStockException $e) {
            $this->toastError($e->getMessage());

            return;
        }

        $this->failModal = false;
        $this->done('Exchange marked failed — new item restocked');
    }

    public function cancel(int $exchangeId, CloseExchange $closeExchange): void
    {
        abort_unless($this->canEdit(), 403);

        try {
            $closeExchange->cancel($this->exchange($exchangeId));
        } catch (DomainException|InsufficientStockException $e) {
            $this->toastError($e->getMessage());

            return;
        }

        $this->done('Exchange cancelled');
    }

    #[On('order-updated')]
    public function refreshExchanges(): void
    {
    }

    public function render(): mixed
    {
        $order = $this->order();
        $exchanges = $order->exchanges()->with(['returnItems.orderItem', 'newItems', 'shipment.courier'])->orderBy('number')->get();

        $exchangeable = collect();
        $productOptions = collect();
        $variantOptions = collect();
        $preview = null;

        if ($this->createModal) {
            $quantities = CreateExchange::exchangeableQuantities($order);
            $exchangeable = $order->items->filter(fn (OrderItem $item) => isset($quantities[$item->id]))
                ->map(fn (OrderItem $item) => ['item' => $item, 'max' => $quantities[$item->id]]);

            $productOptions = Product::active()->where('product_type', '!=', 'combo')->orderBy('name')->get(['id', 'name', 'code'])
                ->mapWithKeys(fn ($p) => [$p->id => $p->code ? "{$p->name} ({$p->code})" : $p->name]);
            $variantOptions = ProductVariant::active()->whereIn('product_id', collect($this->newLines)->pluck('product_id')->filter()->unique())
                ->orderBy('sort_order')->get()->groupBy('product_id');

            // Same maths as the order: the old units' price after their share of the discount.
            $factor = $order->returnDiscountFactor();
            $credit = $order->items->sum(function (OrderItem $item) use ($factor) {
                $qty = min(max(0.0, (float) ($this->back[$item->id] ?? 0)), (float) $item->quantity);

                return $item->is_gift || (float) $item->quantity <= 0 ? 0 : (float) $item->total_amount / (float) $item->quantity * $qty * ($item->exchange_id ? 1.0 : $factor);
            });
            $new = collect($this->newLines)->sum(fn ($l) => (float) $l['quantity'] * (float) $l['unit_price']);
            $preview = ['credit' => round($credit, 2), 'new' => round($new, 2), 'charge' => round((float) $this->charge, 2)];
            $preview['balance'] = round($preview['new'] + $preview['charge'] - $preview['credit'], 2);
        }

        return view('livewire.admin.sales.order-exchanges', [
            'order'          => $order,
            'exchanges'      => $exchanges,
            'canEdit'        => $this->canEdit(),
            'canCourier'     => $this->canCourier(),
            'canCreate'      => $this->canEdit() && $order->status === OrderStatus::DELIVERED,
            'exchangeable'   => $exchangeable,
            'productOptions' => $productOptions,
            'variantOptions' => $variantOptions,
            'preview'        => $preview,
            'bookableAccounts' => $this->shipModal
                ? CourierAccount::query()->where('is_active', true)->whereHas('courier', fn ($q) => $q->where('is_active', true))->with('courier')->get()
                    ->filter(fn (CourierAccount $a) => $a->courier->hasCapability(CourierCapability::SHIPMENT_CREATE->value))->values()
                : collect(),
            'couriers'       => $this->shipModal ? Courier::where('is_active', true)->orderBy('sort_order')->get(['id', 'name']) : collect(),
        ]);
    }
}
