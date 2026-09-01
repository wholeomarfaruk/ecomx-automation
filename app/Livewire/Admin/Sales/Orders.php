<?php

namespace App\Livewire\Admin\Sales;

use App\Enums\Sales\CourierStatus;
use App\Enums\Sales\OrderSource;
use App\Enums\Sales\OrderStatus;
use App\Enums\Sales\PaymentStatus;
use App\Exceptions\Inventory\InsufficientStockException;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Setting;
use App\Services\StockService;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

class Orders extends Component
{
    use WithPagination;

    protected string $paginationTheme = 'tailwind';

    public bool $viewModal = false;
    public ?int $viewOrderId = null;

    /** orders | products | autosaved */
    #[Url]
    public string $view = 'orders';

    #[Url]
    public string $search              = '';
    public string $filterStatus        = '';
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
        $previousStatus = $order->status->value;
        $stockService = app(StockService::class);

        try {
            DB::transaction(function () use ($order, $status, $previousStatus, $stockService) {
                $order->update(['status' => $status]);

                $deductOnConfirm = (bool) Setting::get('deduct_on_order_confirm', true, 'inventory');
                $restockOnRelease = (bool) Setting::get('restock_on_cancel_or_return', true, 'inventory');

                if ($deductOnConfirm && $status === 'confirmed' && $previousStatus !== 'confirmed') {
                    $stockService->commitOrder($order);
                } elseif ($restockOnRelease && in_array($status, ['cancelled', 'returned'], true) && $previousStatus === 'confirmed') {
                    $stockService->releaseOrder($order);
                }
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

    public function render(): mixed
    {
        $orders = Order::query()
            ->with('customer')
            ->withCount('items')
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
            ->when($this->dateTo, fn ($q) => $q->whereDate('created_at', '<=', $this->dateTo))
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

        return view('livewire.admin.sales.orders', [
            'orders'          => $orders,
            'orderedProducts' => $orderedProducts,
            'statuses'        => OrderStatus::cases(),
            'paymentStatuses' => PaymentStatus::cases(),
            'courierStatuses' => CourierStatus::cases(),
            'sources'         => OrderSource::cases(),
            'totalCount'      => Order::count(),
            'totalRevenue'    => Order::where('payment_status', PaymentStatus::PAID)->sum('total_amount'),
            'pendingCount'    => Order::where('status', OrderStatus::PENDING)->count(),
            'dueTotal'        => Order::sum('due_amount'),
            'viewingOrder'    => $viewingOrder,
        ])->layout('layouts.admin.admin');
    }
}
