<?php

namespace App\Services;

use App\Models\InventoryBatch;
use App\Models\ProductVariant;
use App\Models\PurchaseOrderItem;
use App\Models\SupplierInvoiceItem;
use Illuminate\Support\Collection;

/**
 * Merges the three independent places this app records what was paid for a
 * variant — purchase order lines, received batches, and supplier invoice
 * lines — into one normalized, date-sorted timeline. These sources are
 * entered independently of each other (a batch doesn't require a PO, an
 * invoice isn't generated from PO items), so they can disagree; this service
 * doesn't reconcile them, just presents all of them with their source
 * labelled.
 */
class PurchasePriceHistoryService
{
    /**
     * @return Collection<int, array{date: \Illuminate\Support\Carbon, source: string, source_label: string, supplier: ?string, quantity: float, unit_price: float, reference: string}>
     */
    public function forVariant(int $variantId): Collection
    {
        $fromOrders = PurchaseOrderItem::with('purchaseOrder.supplier')
            ->where('product_variant_id', $variantId)
            ->whereNotNull('unit_price')
            ->get()
            ->map(fn (PurchaseOrderItem $item) => [
                'date' => $item->purchaseOrder->order_date ?? $item->created_at,
                'source' => 'purchase_order',
                'source_label' => 'Purchase Order',
                'supplier' => $item->purchaseOrder->supplier->name ?? null,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'reference' => $item->purchaseOrder->order_number ?? '—',
            ]);

        $fromBatches = InventoryBatch::with('supplier')
            ->where('variant_id', $variantId)
            ->whereNotNull('purchase_price')
            ->get()
            ->map(fn (InventoryBatch $batch) => [
                'date' => $batch->created_at,
                'source' => 'batch',
                'source_label' => 'Batch Received',
                'supplier' => $batch->supplier->name ?? null,
                'quantity' => (float) $batch->quantity,
                'unit_price' => (float) $batch->purchase_price,
                'reference' => $batch->batch_no,
            ]);

        $fromInvoices = SupplierInvoiceItem::with('invoice.supplier')
            ->where('product_variant_id', $variantId)
            ->whereNotNull('unit_price')
            ->get()
            ->map(fn (SupplierInvoiceItem $item) => [
                'date' => $item->invoice->invoice_date ?? $item->created_at,
                'source' => 'invoice',
                'source_label' => 'Supplier Invoice',
                'supplier' => $item->invoice->supplier->name ?? null,
                'quantity' => (float) $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'reference' => $item->invoice->serial_number ?? '—',
            ]);

        $timeline = $fromOrders->concat($fromBatches)->concat($fromInvoices)
            ->sortByDesc('date')
            ->values();

        $variant = ProductVariant::with('product')->find($variantId);

        if ($variant?->product?->purchase_price !== null) {
            $timeline->prepend([
                'date' => $variant->product->updated_at,
                'source' => 'current_product',
                'source_label' => 'Current (Product)',
                'supplier' => null,
                'quantity' => null,
                'unit_price' => (float) $variant->product->purchase_price,
                'reference' => $variant->product->sku ?? $variant->product->name,
            ]);
        }

        if ($variant && $variant->purchase_price !== null) {
            $timeline->prepend([
                'date' => $variant->updated_at,
                'source' => 'current_variant',
                'source_label' => 'Current (Variant)',
                'supplier' => null,
                'quantity' => null,
                'unit_price' => (float) $variant->purchase_price,
                'reference' => $variant->sku,
            ]);
        }

        return $timeline;
    }

    /**
     * The price to suggest when adding this variant to a new purchase order:
     * its most recent recorded price (PO/batch/invoice — whichever is
     * newest), falling back to the variant's own stored purchase_price,
     * falling back to the parent product's purchase_price. Returns null only
     * when none of the three has ever been set.
     */
    public function suggestedPrice(int $variantId): ?float
    {
        $variant = ProductVariant::with('product')->find($variantId);

        if (! $variant) {
            return null;
        }

        $mostRecent = $this->forVariant($variantId)->first(
            fn ($entry) => ! in_array($entry['source'], ['current_variant', 'current_product'], true)
        );
        if ($mostRecent) {
            return (float) $mostRecent['unit_price'];
        }

        if ($variant->purchase_price !== null) {
            return (float) $variant->purchase_price;
        }

        if ($variant->product?->purchase_price !== null) {
            return (float) $variant->product->purchase_price;
        }

        return null;
    }

    /**
     * @return array{lowest: ?float, highest: ?float, most_recent: ?float, count: int}
     */
    public function summarize(Collection $history): array
    {
        if ($history->isEmpty()) {
            return ['lowest' => null, 'highest' => null, 'most_recent' => null, 'count' => 0];
        }

        return [
            'lowest' => $history->min('unit_price'),
            'highest' => $history->max('unit_price'),
            'most_recent' => $history->first()['unit_price'],
            'count' => $history->count(),
        ];
    }
}
