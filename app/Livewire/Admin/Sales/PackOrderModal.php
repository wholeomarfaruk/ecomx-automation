<?php

namespace App\Livewire\Admin\Sales;

use App\Actions\Sales\PackOrderItem;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Models\InventoryBatch;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Throwable;

/**
 * Pack a whole order from one place — every product line with the batch(es)
 * its units come from (only that item's own batches), pre-filled FEFO
 * (earliest expiry, then oldest) when nothing is packed yet. Saving packs
 * every line in one go through PackOrderItem, which deducts the batch stock
 * and records the cost the sale's COGS is posted at.
 *
 * Only with the Inventory module on. Opened with the `open-pack-order`
 * event from the orders list, the Packed tab and Bulk Courier Entry.
 */
class PackOrderModal extends Component
{
    public bool $show = false;

    public ?int $orderId = null;

    /** item id => list<array{batch_id: string, quantity: string}> */
    public array $alloc = [];

    /** Barcode scan box — a batch label's batch_no. */
    public string $scan = '';

    /** item id => units scanned since opening; an item's first scan replaces its pre-filled pick. */
    public array $scanned = [];

    #[On('open-pack-order')]
    public function open(int $orderId): void
    {
        if (app(StockService::class)->usesOwnStock()) {
            $this->dispatch('toast', ['type' => 'error', 'message' => 'Packing needs the Inventory module.']);

            return;
        }

        $this->resetErrorBag();
        $this->orderId = $orderId;
        unset($this->order, $this->batches);
        $this->alloc = [];
        $this->scanned = [];
        $this->scan = '';

        foreach ($this->items as $item) {
            $current = $item->batchAllocations->map(fn ($a) => ['batch_id' => (string) $a->inventory_batch_id, 'quantity' => (string) (float) $a->quantity])->all();
            $this->alloc[$item->id] = $current ?: $this->fefo($item);
        }
        $this->show = true;
    }

    #[Computed]
    public function order(): ?Order
    {
        return $this->orderId ? Order::with(['customer:id,full_name,phone', 'items.batchAllocations'])->find($this->orderId) : null;
    }

    /** Lines that need a batch: not a gift, has a product (combo lines are stocked from components). */
    #[Computed]
    public function items(): Collection
    {
        return $this->order?->items->filter(fn (OrderItem $i) => ! $i->is_gift && $i->product_id)->values() ?? collect();
    }

    /**
     * item id => batches of THAT item at the warehouse with stock, plus any
     * batch it's packed from now. 'available' counts what this item already
     * holds, since re-packing puts it back first.
     */
    #[Computed]
    public function batches(): array
    {
        $warehouse = Warehouse::default();
        $out = [];
        foreach ($this->items as $item) {
            $held = $item->batchAllocations->groupBy('inventory_batch_id')->map->sum('quantity');
            $out[$item->id] = InventoryBatch::where('warehouse_id', $warehouse->id)
                ->where('product_id', $item->product_id)->where('variant_id', $item->variant_id)
                ->where(fn ($q) => $q->where(fn ($w) => $w->where('status', 'active')->where('quantity', '>', 0))->orWhereIn('id', $held->keys()->all() ?: [0]))
                ->orderByRaw('expiry_date IS NULL')->orderBy('expiry_date')->orderBy('id')
                ->get(['id', 'batch_no', 'quantity', 'expiry_date', 'purchase_price'])
                ->map(fn ($b) => ['id' => $b->id, 'batch_no' => $b->batch_no, 'expiry' => $b->expiry_date?->format('d M y'),
                    'available' => round((float) $b->quantity + (float) ($held[$b->id] ?? 0), 3), 'cost' => (float) $b->purchase_price])
                ->all();
        }

        return $out;
    }

    /** First-expiry-first-out suggestion for one item. */
    protected function fefo(OrderItem $item): array
    {
        $need = (float) $item->quantity;
        $rows = [];
        foreach ($this->batches[$item->id] ?? [] as $b) {
            if ($need <= 0.0005) {
                break;
            }
            $take = min($need, $b['available']);
            if ($take > 0) {
                $rows[] = ['batch_id' => (string) $b['id'], 'quantity' => (string) round($take, 3)];
                $need -= $take;
            }
        }

        return $rows ?: [['batch_id' => '', 'quantity' => '']];
    }

    public function autoPick(): void
    {
        foreach ($this->items as $item) {
            $this->alloc[$item->id] = $this->fefo($item);
        }
    }

    /**
     * A scanned label packs one unit from that exact batch: finds the order
     * line whose own batches include this batch_no (a line still short of
     * its quantity first) and adds 1 from it. The first scan of a line
     * clears its pre-filled pick, so what's scanned is what gets packed.
     */
    public function scanCode(): void
    {
        $code = trim($this->scan);
        $this->scan = '';
        $this->resetErrorBag('scan');

        if ($code === '') {
            return;
        }

        $matches = $this->items->map(function (OrderItem $item) use ($code) {
            $batch = collect($this->batches[$item->id] ?? [])->first(fn ($b) => strcasecmp($b['batch_no'], $code) === 0);

            return $batch ? [$item, $batch] : null;
        })->filter();

        if ($matches->isEmpty()) {
            $found = InventoryBatch::with('product:id,name', 'variant:id,sku')->where('batch_no', $code)->get();
            $mine = $found->first(fn ($b) => $this->items->contains(fn ($i) => (int) $i->product_id === (int) $b->product_id && (int) $i->variant_id === (int) $b->variant_id));
            $other = $found->first();
            $this->addError('scan', match (true) {
                $mine !== null  => "Batch {$code} has no stock left.",
                $other !== null => "{$code} is {$other->product?->name}" . ($other->variant ? " ({$other->variant->sku})" : '') . ' — not in this order.',
                default         => "No batch {$code} found.",
            });

            return;
        }

        [$item, $batch] = $matches->first(fn ($m) => (int) ($this->scanned[$m[0]->id] ?? 0) < (float) $m[0]->quantity) ?? $matches->first();
        $done = (int) ($this->scanned[$item->id] ?? 0);

        if ($done + 1 > (float) $item->quantity + 0.0005) {
            $this->addError('scan', "{$item->product_name}: all " . (float) $item->quantity . ' already scanned.');

            return;
        }

        $rows = $done ? array_values(array_filter($this->alloc[$item->id] ?? [], fn ($r) => ($r['batch_id'] ?? '') !== '')) : [];
        $at = collect($rows)->search(fn ($r) => (int) $r['batch_id'] === (int) $batch['id']);
        $inBatch = $at === false ? 0.0 : (float) $rows[$at]['quantity'];

        if ($inBatch + 1 > $batch['available'] + 0.0005) {
            $this->addError('scan', "Batch {$batch['batch_no']} has only " . (float) $batch['available'] . ' left.');

            return;
        }

        if ($at === false) {
            $rows[] = ['batch_id' => (string) $batch['id'], 'quantity' => '1'];
        } else {
            $rows[$at]['quantity'] = (string) ($inBatch + 1);
        }

        $this->alloc[$item->id] = $rows;
        $this->scanned[$item->id] = (int) ($this->scanned[$item->id] ?? 0) + 1;
    }

    public function addRow(int $itemId): void
    {
        $this->alloc[$itemId][] = ['batch_id' => '', 'quantity' => ''];
    }

    public function removeRow(int $itemId, int $i): void
    {
        unset($this->alloc[$itemId][$i]);
        $this->alloc[$itemId] = array_values($this->alloc[$itemId]) ?: [['batch_id' => '', 'quantity' => '']];
    }

    public function save(): void
    {
        $this->resetErrorBag();
        $plans = [];

        foreach ($this->items as $item) {
            $map = [];
            foreach ($this->alloc[$item->id] ?? [] as $row) {
                $bid = (int) ($row['batch_id'] ?? 0);
                $qty = round((float) ($row['quantity'] ?? 0), 3);
                if ($bid && $qty > 0) {
                    $map[$bid] = ($map[$bid] ?? 0) + $qty;
                }
            }
            if (! $map) {
                continue;
            }

            $options = collect($this->batches[$item->id] ?? [])->keyBy('id');
            foreach ($map as $bid => $qty) {
                $b = $options->get($bid);
                if (! $b) {
                    $this->addError("alloc.{$item->id}", 'That batch is not of this item.');
                } elseif ($qty > $b['available'] + 0.0005) {
                    $this->addError("alloc.{$item->id}", "Batch {$b['batch_no']} has only {$b['available']} left.");
                }
            }
            if (array_sum($map) > (float) $item->quantity + 0.0005) {
                $this->addError("alloc.{$item->id}", 'More than the ordered ' . rtrim(rtrim(number_format((float) $item->quantity, 3), '0'), '.') . '.');
            }
            $plans[$item->id] = $map;
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            return;
        }
        if (! $plans) {
            $this->addError('alloc', 'Pick a batch and quantity for at least one item.');

            return;
        }

        try {
            DB::transaction(function () use ($plans) {
                foreach ($plans as $itemId => $map) {
                    app(PackOrderItem::class)->handle(OrderItem::findOrFail($itemId), $map);
                }
            });
        } catch (InsufficientStockException $e) {
            $this->addError('alloc', $e->getMessage());

            return;
        } catch (Throwable $e) {
            report($e);
            $this->addError('alloc', $e->getMessage());

            return;
        }

        activity('sales')->causedBy(auth()->user())->performedOn($this->order)->event('updated')
            ->log("Order #{$this->orderId} packed (" . count($plans) . ' items)');

        $packed = $this->order->fresh('items.batchAllocations');
        $full = $packed->items->filter(fn ($i) => ! $i->is_gift && $i->product_id)
            ->every(fn ($i) => (float) $i->batchAllocations->sum('quantity') + 0.0005 >= (float) $i->quantity);

        $this->show = false;
        $this->dispatch('order-packed', orderId: $this->orderId);
        $this->dispatch('toast', ['type' => 'success', 'message' => "Order #{$this->orderId} " . ($full ? 'fully packed.' : 'partly packed.')]);
    }

    public function render()
    {
        return view('livewire.admin.sales.pack-order-modal');
    }
}
