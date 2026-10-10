<?php

namespace App\Livewire\Admin\Sales;

use App\Courier\CourierManager;
use App\Courier\DTO\ShipmentRequest;
use App\Courier\Exceptions\CourierException;
use App\Enums\Sales\CourierStatus;
use App\Enums\Sales\OrderStatus;
use App\Livewire\Concerns\BooksCourierShipments;
use App\Models\Courier;
use App\Models\CourierBatch;
use App\Models\CourierBatchItem;
use App\Models\FraudCheck;
use App\Models\Order;
use App\Services\FraudShield\FraudShield;
use App\Services\FraudShield\FraudShieldSettings;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Renderless;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;
use Throwable;

/**
 * Sales → Bulk Courier: one-click courier entry for many orders.
 *
 * Top: orders still waiting for a courier (Pending/Confirmed/Processing,
 * not booked) — (+) adds one to the sheet. Bottom: the admin's draft sheet
 * (CourierBatch), one editable row per order pre-filled exactly like the
 * single "Book Courier" modal; every edit is saved straight away, so the
 * sheet survives reloads and other devices. "Book all" books the rows one
 * at a time from the browser (bookItem per row) so each row's result shows
 * as it lands and one bad row never blocks the rest.
 */
class BulkCourierEntry extends Component
{
    use BooksCourierShipments;
    use WithPagination;

    /** Row fields an admin may edit on the sheet. */
    private const EDITABLE = [
        'courier_id', 'recipient_name', 'recipient_phone', 'recipient_address',
        'cod_amount', 'weight', 'quantity', 'description', 'instruction',
    ];

    #[Url(except: '')]
    public string $search = '';

    #[Url(except: '')]
    public string $filterStatus = '';

    /** "Set courier for all rows" picker. */
    public ?int $bulkCourierId = null;

    /** Editable sheet rows keyed "r{itemId}". */
    public array $rows = [];

    public ?int $historyBatchId = null;

    /** The draft sheet, resolved once per request (not a Livewire property). */
    private ?CourierBatch $draft = null;

    public function mount(): void
    {
        $this->guardCourierManage();
        $this->loadRows();
    }

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingFilterStatus(): void
    {
        $this->resetPage();
    }

    protected function sheet(): CourierBatch
    {
        return $this->draft ??= CourierBatch::draftFor(auth()->id());
    }

    protected function loadRows(): void
    {
        $this->rows = $this->sheet()->items()->ordered()->get()
            ->mapWithKeys(fn (CourierBatchItem $item) => ['r' . $item->id => [
                'courier_id' => $item->courier_id,
                'recipient_name' => $item->recipient_name,
                'recipient_phone' => $item->recipient_phone,
                'recipient_address' => (string) $item->recipient_address,
                'cod_amount' => $this->num($item->cod_amount),
                'weight' => $this->num($item->weight),
                'quantity' => (string) $item->quantity,
                'description' => (string) $item->description,
                'instruction' => (string) $item->instruction,
            ]])
            ->all();
    }

    private function num($value): string
    {
        return rtrim(rtrim(number_format((float) $value, 3, '.', ''), '0'), '.') ?: '0';
    }

    /** Orders that still need a courier: being worked on and not booked (or their booking was cancelled). */
    protected function eligibleOrders(): Builder
    {
        return Order::query()
            ->whereIn('status', [OrderStatus::PENDING, OrderStatus::CONFIRMED, OrderStatus::PROCESSING])
            ->where(fn ($q) => $q->whereNull('courier_status')->orWhere('courier_status', CourierStatus::CANCELLED));
    }

    /** @return Collection<int, Courier> active couriers whose driver can book, with an active account */
    protected function bookableCouriers(): Collection
    {
        return $this->bookableAccounts()->pluck('courier')->unique('id')->sortBy('name')->values();
    }

    /** Customer's preferred courier when bookable, else the default account's courier. */
    protected function defaultCourierIdFor(Order $order, Collection $couriers): ?int
    {
        $preferred = $order->customer?->default_courier_id;

        if ($preferred && $couriers->contains('id', $preferred)) {
            return $preferred;
        }

        return $this->bulkCourierId
            ?: $this->bookableAccounts()->sortByDesc('is_default')->first()?->courier_id;
    }

    /** Order ids sitting unbooked on another admin's draft sheet → that admin's name. */
    protected function lockedByOthers(array $orderIds): Collection
    {
        if (! $orderIds) {
            return collect();
        }

        return CourierBatchItem::query()
            ->whereIn('order_id', $orderIds)
            ->where('status', '!=', CourierBatchItem::BOOKED)
            ->whereHas('batch', fn ($q) => $q->where('status', CourierBatch::DRAFT)->where('created_by', '!=', auth()->id()))
            ->with('batch.creator')
            ->get()
            ->mapWithKeys(fn ($item) => [$item->order_id => $item->batch->creator?->name ?? 'another admin']);
    }

    public function addOrder(int $orderId): void
    {
        $this->guardCourierManage();

        if ($message = $this->addToSheet($orderId, $this->bookableCouriers())) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $message]);
        }

        $this->loadRows();
    }

    /** Adds every eligible order on the current page of the list ("12,11,9"). */
    public function addPage(string $orderIds): void
    {
        $this->guardCourierManage();

        $couriers = $this->bookableCouriers();
        $added = 0;

        $ids = array_filter(array_map('intval', explode(',', $orderIds)));

        foreach (array_slice($ids, 0, 100) as $orderId) {
            if ($this->addToSheet($orderId, $couriers) === null) {
                $added++;
            }
        }

        $this->loadRows();
        $this->dispatch('toast', ['type' => 'success', 'message' => "{$added} order(s) added to the sheet."]);
    }

    /** @return string|null why the order wasn't added */
    protected function addToSheet(int $orderId, Collection $couriers): ?string
    {
        $order = $this->eligibleOrders()->with(['customer', 'shippingAddress', 'items.product'])->find($orderId);

        if (! $order) {
            return "Order #{$orderId} is already booked or not ready to ship.";
        }

        $sheet = $this->sheet();

        if ($sheet->items()->where('order_id', $orderId)->exists()) {
            return "Order #{$orderId} is already on the sheet.";
        }

        if ($owner = $this->lockedByOthers([$orderId])->first()) {
            return "Order #{$orderId} is on {$owner}'s courier sheet.";
        }

        $address = $order->shippingAddress;
        $phone = (string) ($address->phone ?? $order->customer?->phone ?? '');

        try {
            $sheet->items()->create([
                'order_id' => $order->id,
                'courier_id' => $this->defaultCourierIdFor($order, $couriers),
                'recipient_name' => (string) ($address->name ?? $order->customer?->full_name ?? ''),
                // Some orders store the 10-digit national number — couriers want 01XXXXXXXXX.
                'recipient_phone' => FraudShield::normalizePhone($phone) ?: $phone,
                'recipient_address' => (string) ($address->full_address ?? ''),
                'cod_amount' => (float) $order->due_amount,
                'weight' => $this->totalWeightFor($order),
                'quantity' => (int) max(1, $order->items->sum('quantity')),
                'description' => $this->itemDescriptionFor($order),
                'sort_order' => (int) CourierBatchItem::where('courier_batch_id', $sheet->id)->max('sort_order') + 1,
            ]);
        } catch (QueryException) {
            // Unique (sheet, order): a double click raced the first add.
            return "Order #{$orderId} is already on the sheet.";
        }

        return null;
    }

    public function removeItem(int $itemId): void
    {
        $this->guardCourierManage();

        $this->sheet()->items()->whereKey($itemId)->where('status', '!=', CourierBatchItem::BOOKED)->delete();
        $this->loadRows();
    }

    /** Re-fills a row from its order (undo manual edits). */
    public function resetItem(int $itemId): void
    {
        $this->guardCourierManage();

        $item = $this->sheet()->items()->whereKey($itemId)->where('status', '!=', CourierBatchItem::BOOKED)->first();

        if (! $item) {
            return;
        }

        $orderId = $item->order_id;
        $keep = ['courier_id' => $item->courier_id, 'sort_order' => $item->sort_order];
        $item->delete();

        if ($message = $this->addToSheet($orderId, $this->bookableCouriers())) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $message]);
        } else {
            $this->sheet()->items()->where('order_id', $orderId)->update($keep);
        }

        $this->loadRows();
    }

    /** Autosave: every sheet edit goes straight to its row. */
    public function updated(string $name, $value): void
    {
        if (! preg_match('/^rows\.r(\d+)\.(\w+)$/', $name, $m) || ! in_array($m[2], self::EDITABLE, true)) {
            return;
        }

        $this->guardCourierManage();

        $item = $this->sheet()->items()->whereKey((int) $m[1])->first();

        if (! $item || $item->isBooked()) {
            $this->loadRows();

            return;
        }

        $field = $m[2];
        $value = is_string($value) ? trim($value) : $value;

        $value = match ($field) {
            'courier_id' => $value ? (int) $value : null,
            'cod_amount' => max(0, (float) $value),
            'weight' => max(0, (float) $value),
            'quantity' => max(1, (int) $value),
            'description', 'instruction' => mb_substr((string) $value, 0, 500),
            'recipient_name' => mb_substr((string) $value, 0, 255),
            'recipient_phone' => mb_substr((string) $value, 0, 30),
            default => (string) $value,
        };

        $item->update([$field => $value]);
    }

    public function applyCourierToAll(): void
    {
        $this->guardCourierManage();

        if (! $this->bulkCourierId) {
            return;
        }

        $this->sheet()->items()->where('status', '!=', CourierBatchItem::BOOKED)->update(['courier_id' => $this->bulkCourierId]);
        $this->loadRows();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Courier set on every unbooked row.']);
    }

    /** Ids the browser should book, in sheet order. */
    #[Renderless]
    public function bookableIds(): array
    {
        $this->guardCourierManage();

        return $this->sheet()->items()->ordered()->where('status', '!=', CourierBatchItem::BOOKED)->pluck('id')->all();
    }

    /**
     * Books one row. Called per row by "Book all" (and the row's own Book
     * button), so progress shows live and a failure only marks that row.
     *
     * @return array{ok: bool, message: string}
     */
    public function bookItem(int $itemId): array
    {
        $this->guardCourierManage();

        $item = $this->sheet()->items()->whereKey($itemId)->with('courier')->first();

        if (! $item || $item->isBooked()) {
            return ['ok' => true, 'message' => 'Already booked.'];
        }

        if ($error = $this->rowError($item)) {
            return $this->fail($item, $error);
        }

        // Two admins / two clicks must never book the same order twice.
        $lock = Cache::lock("courier-book:order:{$item->order_id}", 120);

        if (! $lock->get()) {
            return $this->fail($item, 'This order is being booked right now — try again in a moment.');
        }

        try {
            $order = Order::find($item->order_id);

            if (! $order) {
                return $this->fail($item, 'Order no longer exists.');
            }

            if ($order->courier_status && $order->courier_status !== CourierStatus::CANCELLED) {
                return $this->fail($item, "Already booked elsewhere (tracking {$order->courier_tracking_number}).");
            }

            $request = new ShipmentRequest(
                orderId: (string) $order->id,
                invoiceNumber: (string) $order->id,
                recipientName: trim($item->recipient_name),
                recipientPhone: FraudShield::normalizePhone($item->recipient_phone) ?: $item->recipient_phone,
                recipientAddress: (string) $item->recipient_address,
                codAmount: (float) $item->cod_amount,
                itemWeight: (float) $item->weight,
                itemQuantity: (int) $item->quantity,
                itemDescription: $item->description ?: null,
                specialInstruction: $item->instruction ?: null,
            );

            try {
                $response = app(CourierManager::class)->createShipment($order, $item->courier->driver_key, $request);
            } catch (CourierException $e) {
                return $this->fail($item, $e->getMessage());
            } catch (Throwable $e) {
                Log::error('Bulk courier booking failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);

                return $this->fail($item, 'Booking error: ' . $e->getMessage());
            }

            if (! $response->success) {
                return $this->fail($item, $response->errorMessage ?? 'Courier rejected the booking.');
            }

            // The parcel exists at the courier now — record that first, so
            // nothing below can leave a booked order looking unbooked here.
            $item->update([
                'status' => CourierBatchItem::BOOKED,
                'tracking_number' => $response->trackingNumber,
                'error_message' => null,
                'booked_at' => now(),
            ]);

            try {
                activity('sales')
                    ->causedBy(auth()->user())
                    ->performedOn($order)
                    ->event('updated')
                    ->log("Courier shipment booked with {$item->courier->name} for Order #{$order->id} via Bulk Courier (tracking: {$response->trackingNumber})");

                $note = $this->advanceToProcessingAfterBooking($order);
            } catch (Throwable $e) {
                Log::error('Bulk courier: post-booking step failed', ['order_id' => $order->id, 'error' => $e->getMessage()]);
                $note = ' — booked, but the order status was not updated: ' . $e->getMessage();
            }

            if (str_contains($note, 'not')) {
                $item->update(['error_message' => mb_substr(ltrim($note, ' —'), 0, 1000)]);
            }

            return ['ok' => true, 'message' => "#{$order->id} booked — {$response->trackingNumber}"];
        } finally {
            $lock->release();
        }
    }

    private function fail(CourierBatchItem $item, string $message): array
    {
        $item->update(['status' => CourierBatchItem::FAILED, 'error_message' => mb_substr($message, 0, 1000)]);

        return ['ok' => false, 'message' => "#{$item->order_id}: {$message}"];
    }

    /** Checks done before spending a courier API call. */
    protected function rowError(CourierBatchItem $item): ?string
    {
        $digits = preg_replace('/\D+/', '', $item->recipient_phone);

        return match (true) {
            ! $item->courier => 'Pick a courier.',
            ! $this->bookableCouriers()->contains('id', $item->courier_id) => 'This courier has no active account that can book.',
            trim($item->recipient_name) === '' => 'Recipient name is required.',
            ! preg_match('/^(?:880|0)?1[3-9]\d{8}$/', $digits) => 'Phone must be a valid BD mobile (01XXXXXXXXX).',
            mb_strlen(trim((string) $item->recipient_address)) < 10 => 'Address is too short.',
            (float) $item->weight <= 0 => 'Weight must be more than 0.',
            default => null,
        };
    }

    /**
     * Ends this sheet: booked rows stay in it as history, anything not
     * booked moves to a fresh draft.
     */
    public function completeSheet(): void
    {
        $this->guardCourierManage();

        $sheet = $this->sheet();

        if (! $sheet->items()->where('status', CourierBatchItem::BOOKED)->exists()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Nothing booked on this sheet yet.']);

            return;
        }

        $unbooked = $sheet->items()->where('status', '!=', CourierBatchItem::BOOKED)->pluck('id');

        $sheet->update(['status' => CourierBatch::COMPLETED, 'completed_at' => now()]);
        $this->draft = null;

        if ($unbooked->isNotEmpty()) {
            CourierBatchItem::whereIn('id', $unbooked)->update(['courier_batch_id' => $this->sheet()->id]);
        }

        $this->loadRows();
        $this->dispatch('toast', ['type' => 'success', 'message' => 'Sheet completed and saved to history.']);
    }

    public function clearSheet(): void
    {
        $this->guardCourierManage();

        $this->sheet()->items()->where('status', '!=', CourierBatchItem::BOOKED)->delete();
        $this->loadRows();
    }

    public function showHistory(?int $batchId): void
    {
        $this->historyBatchId = $batchId;
    }

    public function render()
    {
        $couriers = $this->bookableCouriers();
        $items = $this->sheet()->items()->ordered()->with(['order.customer', 'courier'])->get();
        $sheetOrderIds = $items->pluck('order_id')->all();

        $orders = $this->eligibleOrders()
            ->with(['customer', 'shippingAddress'])
            ->withCount('items')
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->search !== '', function ($q) {
                $term = trim($this->search);
                $q->where(fn ($w) => $w
                    ->where('id', ltrim($term, '#'))
                    ->orWhereHas('customer', fn ($c) => $c->where('phone', 'like', "%{$term}%")
                        ->orWhere('full_name', 'like', "%{$term}%"))
                    ->orWhereHas('shippingAddress', fn ($a) => $a->where('phone', 'like', "%{$term}%")->orWhere('name', 'like', "%{$term}%")));
            })
            ->orderByDesc('id')
            ->paginate(20);

        // Stored fraud results only — never an API call while rendering.
        $fraudEnabled = app(FraudShieldSettings::class)->ready();
        $phones = $orders->getCollection()->map(fn ($o) => FraudShield::orderPhone($o))
            ->merge($items->map(fn ($i) => FraudShield::normalizePhone($i->recipient_phone)))
            ->filter()->unique()->values();
        $fraudChecks = $fraudEnabled ? FraudCheck::whereIn('phone', $phones)->get()->keyBy('phone') : collect();

        $open = $items->where('status', '!=', CourierBatchItem::BOOKED);
        $risky = $open->filter(fn ($i) => $fraudChecks->get(FraudShield::normalizePhone($i->recipient_phone))?->tone() === 'danger');

        return view('livewire.admin.sales.bulk-courier-entry', [
            'orders' => $orders,
            'items' => $items,
            'sheetOrderIds' => $sheetOrderIds,
            'lockedByOthers' => $this->lockedByOthers($orders->getCollection()->pluck('id')->all()),
            'couriers' => $couriers,
            'statuses' => [OrderStatus::PENDING, OrderStatus::CONFIRMED, OrderStatus::PROCESSING],
            'fraudEnabled' => $fraudEnabled,
            'fraudChecks' => $fraudChecks,
            'stats' => [
                'rows' => $items->count(),
                'open' => $open->count(),
                'booked' => $items->where('status', CourierBatchItem::BOOKED)->count(),
                'failed' => $items->where('status', CourierBatchItem::FAILED)->count(),
                'cod' => (float) $open->sum('cod_amount'),
                'risky' => $risky->count(),
                'byCourier' => $open->groupBy(fn ($i) => $i->courier?->name ?? 'No courier')->map->count(),
            ],
            'history' => CourierBatch::where('status', CourierBatch::COMPLETED)
                ->withCount(['items as booked_count' => fn ($q) => $q->where('status', CourierBatchItem::BOOKED)])
                ->withSum(['items as booked_cod' => fn ($q) => $q->where('status', CourierBatchItem::BOOKED)], 'cod_amount')
                ->with('creator')
                ->latest('completed_at')
                ->limit(10)
                ->get(),
            'historyItems' => $this->historyBatchId
                ? CourierBatchItem::where('courier_batch_id', $this->historyBatchId)->ordered()->with('courier')->get()
                : collect(),
        ])->layout('layouts.admin.admin');
    }
}
