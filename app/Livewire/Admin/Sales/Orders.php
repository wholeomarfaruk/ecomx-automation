<?php

namespace App\Livewire\Admin\Sales;

use App\Actions\Sales\DeleteOrder;
use App\Enums\Sales\CourierStatus;
use App\Enums\Sales\OrderSource;
use App\Enums\Sales\OrderStatus;
use App\Enums\Sales\PaymentStatus;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Livewire\Concerns\BooksCourierShipments;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Services\StockService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use OpenSpout\Common\Entity\Row;
use OpenSpout\Common\Entity\Style\Style;
use OpenSpout\Writer\XLSX\Entity\SheetView;
use OpenSpout\Writer\XLSX\Options as XlsxOptions;
use OpenSpout\Writer\XLSX\Writer as XlsxWriter;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Livewire\Component;
use Livewire\WithPagination;

class Orders extends Component
{
    use WithPagination;
    use BooksCourierShipments;

    protected string $paginationTheme = 'tailwind';

    public bool $viewModal = false;
    public ?int $viewOrderId = null;

    /** orders | products | packed | autosaved */
    #[Url]
    public string $view = 'orders';

    /** Packed tab: '' (all packed) | full (every packable unit packed — ready to ship) | partial */
    #[Url]
    public string $packState = '';

    #[Url]
    public string $search              = '';
    #[Url]
    public string $filterStatus        = '';
    #[Url]
    public string $filterPaymentStatus = '';
    public string $filterSource        = '';
    public string $dateFrom            = '';
    public string $dateTo              = '';

    public string $productSearch       = '';

    public function updatingSearch(): void              { $this->resetPage(); }
    public function updatingFilterStatus(): void        { $this->resetPage(); $this->resetPage('productsPage'); }
    public function updatingFilterPaymentStatus(): void { $this->resetPage(); $this->resetPage('productsPage'); }
    public function updatingFilterSource(): void        { $this->resetPage(); $this->resetPage('productsPage'); }
    public function updatingDateFrom(): void            { $this->resetPage(); $this->resetPage('productsPage'); }
    public function updatingDateTo(): void              { $this->resetPage(); $this->resetPage('productsPage'); }
    public function updatingProductSearch(): void       { $this->resetPage('productsPage'); }
    public function updatingPackState(): void           { $this->resetPage('packedPage'); }

    public function resetFilters(): void
    {
        $this->reset(['search', 'productSearch', 'filterStatus', 'filterPaymentStatus', 'filterSource', 'dateFrom', 'dateTo']);
        $this->resetPage();
        $this->resetPage('productsPage');
    }

    public function viewOrder(int $id): void
    {
        $this->viewOrderId = $id;
        $this->viewModal = true;
    }

    public function closeViewModal(): void
    {
        $this->viewModal = false;
        $this->viewOrderId = null;
    }

    /**
     * Inline status edits straight from the table row — mirrors
     * OrderDetail::updateStatus()'s stock-commit/release side effects so a
     * status flip here behaves identically to doing it from the full order
     * page, just without the courier/payment/returns fields also on screen.
     */
    public function updateOrderStatus(int $orderId, string $status): void
    {
        $order = Order::with('items')->findOrFail($orderId);
        $oldStatus = $order->status;
        $newStatus = OrderStatus::from($status);

        $stockService = app(StockService::class);

        try {
            DB::transaction(function () use ($order, $status, $oldStatus, $newStatus, $stockService) {
                $order->update(['status' => $status]);

                $stockService->onStatusChange($order, $oldStatus, $newStatus);
            });
        } catch (InsufficientStockException $e) {
            $this->dispatch('toast', ['type' => 'error', 'message' => $e->getMessage()]);
            return;
        }

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($order)
            ->event('updated')
            ->log("Order #{$order->id} status updated");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Order status updated']);
    }

    /** Inline Source dropdown in the orders table — informational only, no stock/accounting impact. */
    public function updateOrderSource(int $orderId, string $source): void
    {
        $newSource = OrderSource::tryFrom($source);

        if (! $newSource) {
            return;
        }

        $order = Order::findOrFail($orderId);
        $oldSource = $order->source;
        $order->update(['source' => $newSource]);

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($order)
            ->event('updated')
            ->withProperties(['changes' => ['before' => ['source' => $oldSource->value], 'after' => ['source' => $newSource->value]]])
            ->log("Order #{$order->id} source changed to {$newSource->label()}");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Order source updated']);
    }

    public function updatePaymentStatus(int $orderId, string $paymentStatus): void
    {
        $order = Order::findOrFail($orderId);
        $order->update(['payment_status' => $paymentStatus]);

        activity('sales')
            ->causedBy(auth()->user())
            ->performedOn($order)
            ->event('updated')
            ->log("Order #{$order->id} payment status updated");

        $this->dispatch('toast', ['type' => 'success', 'message' => 'Payment status updated']);
    }

    /** The Orders tab's search + filters — shared by the table and the Excel export so both always match. */
    private function filteredOrders(): Builder
    {
        return Order::query()
            ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                ->where('id', 'like', "%{$this->search}%")
                ->orWhereHas('customer', fn ($c) => $c
                    ->where('full_name', 'like', "%{$this->search}%")
                    ->orWhere('phone', 'like', "%{$this->search}%"))
                ->orWhereHas('items', fn ($i) => $i
                    ->where('product_name', 'like', "%{$this->search}%")
                    ->orWhere('sku', 'like', "%{$this->search}%"))
            ))
            ->when($this->filterStatus !== '', fn ($q) => $q->where('status', $this->filterStatus))
            ->when($this->filterPaymentStatus !== '', fn ($q) => $q->where('payment_status', $this->filterPaymentStatus))
            ->when($this->filterSource !== '', fn ($q) => $q->where('source', $this->filterSource))
            ->when($this->dateFrom, fn ($q) => $q->whereDate('created_at', '>=', $this->dateFrom))
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo));
    }

    /**
     * Excel export of the Orders tab with its current search/filters applied
     * (one row per order). Uses OpenSpout; falls back to a CSV Excel can open
     * if the package isn't installed on this server.
     */
    public function exportExcel(): BinaryFileResponse|StreamedResponse
    {
        $headers = [
            'Order #', 'Date', 'Status', 'Payment Status', 'Fulfillment', 'Source',
            'Customer', 'Customer Phone', 'Ship To', 'Ship To Phone', 'Address', 'City',
            'Items', 'Qty', 'Subtotal', 'Discount', 'Shipping', 'Charges', 'Total', 'Paid', 'Due',
            'Coupon', 'Courier', 'Tracking #', 'Courier Status', 'Customer Note', 'Admin Note',
        ];

        $rows = function () {
            $query = $this->filteredOrders()
                ->with('customer', 'items', 'shippingAddress.city')
                ->orderByDesc('id');

            foreach ($query->lazy(500) as $order) {
                $address = $order->shippingAddress;

                yield [
                    $order->id,
                    local_time($order->placed_at ?? $order->created_at)?->format('Y-m-d H:i'),
                    $order->status?->label(),
                    $order->payment_status?->label(),
                    $order->fulfillment_status?->label(),
                    $order->source?->label(),
                    $order->customer?->full_name,
                    $order->customer?->phone,
                    $address?->name,
                    $address?->phone,
                    $address?->full_address,
                    $address?->city?->name,
                    $order->items->map(fn ($i) => trim($i->product_name . ($i->variant_name ? " ({$i->variant_name})" : ''))
                        . ' x' . rtrim(rtrim(number_format((float) $i->quantity, 3, '.', ''), '0'), '.')
                        . ($i->is_gift ? ' [gift]' : ''))->join('; '),
                    (float) $order->items->sum('quantity'),
                    (float) $order->subtotal,
                    (float) $order->discount_amount,
                    (float) $order->shipping_amount,
                    (float) $order->charges_amount,
                    (float) $order->total_amount,
                    (float) $order->paid_amount,
                    (float) $order->due_amount,
                    $order->coupon_code,
                    $order->courier_provider,
                    $order->courier_tracking_number,
                    $order->courier_status?->label(),
                    $order->customer_note,
                    $order->admin_note,
                ];
            }
        };

        $filename = 'orders-' . local_time(now())->format('Y-m-d-His');

        if (! class_exists(XlsxWriter::class)) {
            return response()->streamDownload(function () use ($headers, $rows) {
                $handle = fopen('php://output', 'w');
                fwrite($handle, "\xEF\xBB\xBF"); // UTF-8 BOM so Excel reads Bangla correctly
                fputcsv($handle, $headers);
                foreach ($rows() as $row) {
                    fputcsv($handle, $row);
                }
                fclose($handle);
            }, "{$filename}.csv", ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        $options = new XlsxOptions();
        $options->setColumnWidth(9, 1);
        $options->setColumnWidth(17, 2);
        $options->setColumnWidthForRange(14, 3, 6);
        $options->setColumnWidthForRange(18, 7, 10);
        $options->setColumnWidth(40, 11);
        $options->setColumnWidth(14, 12);
        $options->setColumnWidth(50, 13);
        $options->setColumnWidthForRange(12, 14, 22);
        $options->setColumnWidthForRange(16, 23, 26);
        $options->setColumnWidth(30, 27);

        $path = tempnam(sys_get_temp_dir(), 'orders') . '.xlsx';
        $writer = new XlsxWriter($options);
        $writer->openToFile($path);
        $writer->getCurrentSheet()->setName('Orders');
        $writer->getCurrentSheet()->setSheetView((new SheetView())->withFreezeRow(2));

        $writer->addRow(Row::fromValuesWithStyle($headers, new Style(fontBold: true, backgroundColor: 'E5E7EB')));
        foreach ($rows() as $row) {
            $writer->addRow(Row::fromValues($row));
        }
        $writer->close();

        activity('sales')
            ->causedBy(auth()->user())
            ->withProperties(['rows' => $writer->getWrittenRowCount() - 1])
            ->event('exported')
            ->log('Orders exported to Excel');

        return response()->download($path, "{$filename}.xlsx")->deleteFileAfterSend();
    }

    /** Permanently delete an order (e.g. a test order) — stock and accounts are reversed first, see DeleteOrder. */
    public function deleteOrder(int $orderId, DeleteOrder $deleteOrder): void
    {
        $user = auth()->user();
        abort_unless($user?->hasRole('superadmin') || $user?->can('order.delete'), 403);

        $order = Order::findOrFail($orderId);

        try {
            $deleteOrder->handle($order);
        } catch (\Throwable $e) {
            report($e);
            $this->dispatch('toast', ['type' => 'error', 'message' => "Order #{$orderId} could not be deleted: {$e->getMessage()}"]);
            return;
        }

        activity('sales')
            ->causedBy($user)
            ->withProperties(['order_id' => $orderId, 'total_amount' => $order->total_amount, 'status' => $order->status?->value])
            ->event('deleted')
            ->log("Order #{$orderId} was deleted");

        if ($this->viewOrderId === $orderId) {
            $this->viewModal = false;
            $this->viewOrderId = null;
        }

        $this->dispatch('toast', ['type' => 'success', 'message' => "Order #{$orderId} deleted"]);
    }

    /** The courier modal (its own component) booked a shipment — show the order's new courier/status. */
    #[\Livewire\Attributes\On('courier-booked')]
    public function refreshAfterCourierBooking(): void
    {
        // Re-render is all that's needed.
    }

    public function render(): mixed
    {
        $orders = $this->filteredOrders()
            ->with('customer')
            ->withCount('items')
            ->orderByDesc('id')
            ->paginate(20);

        // Which products, and how many units, have actually been ordered —
        // grouped by product_id/variant_id where available, falling back to
        // the snapshot name/SKU stored on the line item (products can be
        // deleted or renamed after the order was placed).
        $orderedProducts = $this->view === 'products'
            ? OrderItem::query()
                ->selectRaw('
                    COALESCE(product_id, 0) as product_id,
                    COALESCE(variant_id, 0) as variant_id,
                    product_name, variant_name, sku,
                    SUM(quantity) as total_quantity,
                    COUNT(DISTINCT order_id) as order_count,
                    SUM(total_amount) as total_revenue
                ')
                ->when($this->productSearch, fn ($q) => $q->where(fn ($w) => $w
                    ->where('product_name', 'like', "%{$this->productSearch}%")
                    ->orWhere('sku', 'like', "%{$this->productSearch}%")
                ))
                ->when(
                    $this->filterStatus !== '' || $this->filterPaymentStatus !== '' || $this->filterSource !== '' || $this->dateFrom || $this->dateTo,
                    fn ($q) => $q->whereHas('order', fn ($o) => $o
                        ->when($this->filterStatus !== '', fn ($oo) => $oo->where('status', $this->filterStatus))
                        ->when($this->filterPaymentStatus !== '', fn ($oo) => $oo->where('payment_status', $this->filterPaymentStatus))
                        ->when($this->filterSource !== '', fn ($oo) => $oo->where('source', $this->filterSource))
                        ->when($this->dateFrom, fn ($oo) => $oo->whereDate('created_at', '>=', $this->dateFrom))
                        ->when($this->dateTo, fn ($oo) => $oo->whereDate('created_at', '<=', $this->dateTo))
                    )
                )
                ->groupBy('product_id', 'variant_id', 'product_name', 'variant_name', 'sku')
                ->orderByDesc('total_quantity')
                ->paginate(20, pageName: 'productsPage')
            : null;

        // Packed tab: orders with packing started (units packed from a batch)
        // that are still in the fulfilment pipeline. "Packable" units follow
        // PostOrderCompletion::assertFullyPacked(): non-gift lines with a product.
        $packedOrders = null;

        if ($this->view === 'packed') {
            $packedSql = '(SELECT COALESCE(SUM(oib.quantity), 0) FROM order_item_batches oib'
                . ' JOIN order_items oi ON oi.id = oib.order_item_id'
                . ' WHERE oi.order_id = orders.id AND oi.product_id IS NOT NULL AND oi.is_gift = 0)';
            $packableSql = '(SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi'
                . ' WHERE oi.order_id = orders.id AND oi.product_id IS NOT NULL AND oi.is_gift = 0)';

            $packedOrders = Order::query()
                ->select('orders.*')
                ->selectRaw("{$packedSql} as packed_qty")
                ->selectRaw("{$packableSql} as packable_qty")
                ->with('customer')
                ->whereIn('status', [
                    OrderStatus::PENDING, OrderStatus::CONFIRMED, OrderStatus::PROCESSING,
                    OrderStatus::SHIPPED, OrderStatus::DELIVERED,
                ])
                ->whereRaw("{$packedSql} > 0")
                ->when($this->packState === 'full', fn ($q) => $q->whereRaw("{$packedSql} + 0.001 >= {$packableSql}"))
                ->when($this->packState === 'partial', fn ($q) => $q->whereRaw("{$packedSql} + 0.001 < {$packableSql}"))
                ->when($this->search, fn ($q) => $q->where(fn ($w) => $w
                    ->where('orders.id', 'like', "%{$this->search}%")
                    ->orWhereHas('customer', fn ($c) => $c
                        ->where('full_name', 'like', "%{$this->search}%")
                        ->orWhere('phone', 'like', "%{$this->search}%"))
                ))
                ->when($this->filterSource !== '', fn ($q) => $q->where('source', $this->filterSource))
                ->orderByDesc('orders.id')
                ->paginate(20, pageName: 'packedPage');
        }

        $viewingOrder = $this->viewOrderId
            ? Order::with([
                'customer',
                'billingAddress',
                'shippingAddress',
                'items.product',
                'items.variant',
                'payments',
                'courierShipments.courier',
            ])->find($this->viewOrderId)
            : null;

        $canManageCourier = auth()->user()->can('courier_configuration.manage');

        return view('livewire.admin.sales.orders', [
            'orders'          => $orders,
            'orderedProducts' => $orderedProducts,
            'packedOrders'    => $packedOrders,
            'statuses'        => OrderStatus::cases(),
            'paymentStatuses' => PaymentStatus::cases(),
            'courierStatuses' => CourierStatus::cases(),
            'sources'         => OrderSource::cases(),
            'totalCount'      => Order::count(),
            'totalRevenue'    => Order::where('payment_status', PaymentStatus::PAID)->sum('total_amount'),
            'pendingCount'    => Order::where('status', OrderStatus::PENDING)->count(),
            'dueTotal'        => Order::sum('due_amount'),
            'viewingOrder'    => $viewingOrder,
            'canManageCourier' => $canManageCourier,
        ])->layout('layouts.admin.admin');
    }
}
