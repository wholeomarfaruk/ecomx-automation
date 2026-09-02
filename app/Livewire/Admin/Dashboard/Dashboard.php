<?php

namespace App\Livewire\Admin\Dashboard;

use App\Enums\Sales\CourierStatus;
use App\Enums\Sales\OrderStatus;
use App\Enums\Sales\PaymentStatus;
use App\Livewire\Admin\Marketing\Concerns\HasDateRange;
use App\Models\Courier;
use App\Models\CourierShipment;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderPayment;
use App\Models\Product;
use App\Models\ProductVariant;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.admin.admin')]
class Dashboard extends Component
{
    use HasDateRange;

    public function render()
    {
        $since = $this->since();
        $until = $this->until();

        $orders = Order::query()
            ->when($since, fn ($q) => $q->where('placed_at', '>=', $since))
            ->when($until, fn ($q) => $q->where('placed_at', '<=', $until));

        $refundedTotal = (clone $orders)->where('payment_status', PaymentStatus::REFUNDED)->sum('total_amount');
        $totalSales = (clone $orders)->sum('total_amount');
        $totalOrders = (clone $orders)->count();

        $statusCounts = (clone $orders)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $kpis = [
            'total_sales' => (float) $totalSales,
            'total_orders' => $totalOrders,
            'pending_orders' => (int) ($statusCounts[OrderStatus::PENDING->value] ?? 0),
            'delivered_orders' => (int) ($statusCounts[OrderStatus::DELIVERED->value] ?? 0),
            'cancelled_orders' => (int) ($statusCounts[OrderStatus::CANCELLED->value] ?? 0),
            'returned_orders' => (int) ($statusCounts[OrderStatus::RETURNED->value] ?? 0) + (int) ($statusCounts[OrderStatus::PARTIALLY_RETURNED->value] ?? 0),
            'net_revenue' => (float) $totalSales - (float) $refundedTotal,
            'aov' => $totalOrders > 0 ? (float) $totalSales / $totalOrders : 0.0,
        ];

        // Previous period of equal length, for the vs-previous comparison strip.
        $previousKpis = $this->previousPeriodKpis($since, $until);

        $orderStatusBreakdown = collect(OrderStatus::cases())->map(fn (OrderStatus $status) => [
            'status' => $status,
            'count' => (int) ($statusCounts[$status->value] ?? 0),
        ]);

        // Daily revenue/orders trend, zero-filled so the chart has no gaps.
        $trendStart = $since ?? Carbon::parse((clone $orders)->min('placed_at') ?? now())->startOfDay();
        $trendEnd = $until ?? now();

        $dailyRaw = (clone $orders)
            ->selectRaw('DATE(placed_at) as day, COUNT(*) as orders_count, SUM(total_amount) as revenue')
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        $period = collect();
        $cursor = $trendStart->copy()->startOfDay();
        while ($cursor->lte($trendEnd)) {
            $period->push($cursor->format('Y-m-d'));
            $cursor = $cursor->addDay();
        }

        $trend = [
            'labels' => $period->map(fn ($d) => Carbon::parse($d)->format('M j'))->all(),
            'revenue' => $period->map(fn ($d) => (float) ($dailyRaw->get($d)->revenue ?? 0))->all(),
            'orders' => $period->map(fn ($d) => (int) ($dailyRaw->get($d)->orders_count ?? 0))->all(),
        ];

        $recentOrders = Order::with('customer')
            ->latest('placed_at')
            ->limit(8)
            ->get();

        $actionRequired = $this->actionRequiredAlerts();

        $inventory = [
            'total_products' => Product::count(),
            'active_products' => Product::where('status', 'active')->count(),
            'draft_products' => Product::where('status', 'draft')->count(),
            'out_of_stock' => Product::where('stock_status', 'out_of_stock')->count(),
            'low_stock' => Product::where('stock_status', 'low_stock')->count(),
        ];

        // Best sellers within the selected range, by quantity sold.
        $topProducts = OrderItem::query()
            ->join('orders', 'orders.id', '=', 'order_items.order_id')
            ->when($since, fn ($q) => $q->where('orders.placed_at', '>=', $since))
            ->when($until, fn ($q) => $q->where('orders.placed_at', '<=', $until))
            ->where('order_items.is_gift', false)
            ->selectRaw('order_items.product_id, order_items.product_name, SUM(order_items.quantity) as qty_sold, SUM(order_items.total_amount) as revenue')
            ->groupBy('order_items.product_id', 'order_items.product_name')
            ->orderByDesc('qty_sold')
            ->limit(5)
            ->get();

        $lowStockVariants = ProductVariant::with('product')
            ->whereColumn('stock_quantity', '<=', 'reorder_level')
            ->where('status', 'active')
            ->orderBy('stock_quantity')
            ->limit(6)
            ->get();

        // Customers who placed their first-ever order inside the selected
        // range are "new"; customers with an order in range but an earlier
        // order too are "returning".
        $customerIdsInRange = (clone $orders)->whereNotNull('customer_id')->pluck('customer_id')->unique();

        $firstOrderDates = Order::whereIn('customer_id', $customerIdsInRange)
            ->selectRaw('customer_id, MIN(placed_at) as first_placed_at')
            ->groupBy('customer_id')
            ->pluck('first_placed_at', 'customer_id');

        $newCustomerCount = 0;
        $returningCustomerCount = 0;
        foreach ($customerIdsInRange as $customerId) {
            $firstPlacedAt = $firstOrderDates[$customerId] ?? null;
            $isNew = $firstPlacedAt && (! $since || Carbon::parse($firstPlacedAt)->gte($since));
            $isNew ? $newCustomerCount++ : $returningCustomerCount++;
        }

        $customers = [
            'total_customers' => Customer::count(),
            'active_customers' => $customerIdsInRange->count(),
            'new_customers' => $newCustomerCount,
            'returning_customers' => $returningCustomerCount,
        ];

        $topCustomers = Order::query()
            ->whereNotNull('customer_id')
            ->when($since, fn ($q) => $q->where('placed_at', '>=', $since))
            ->when($until, fn ($q) => $q->where('placed_at', '<=', $until))
            ->selectRaw('customer_id, COUNT(*) as orders_count, SUM(total_amount) as total_spent')
            ->groupBy('customer_id')
            ->orderByDesc('total_spent')
            ->with('customer')
            ->limit(5)
            ->get();

        // Courier delivery health for the selected range.
        $shipments = CourierShipment::query()
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->when($until, fn ($q) => $q->where('created_at', '<=', $until));

        $shipmentStatusCounts = (clone $shipments)
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $delivered = (int) ($shipmentStatusCounts[CourierStatus::DELIVERED->value] ?? 0);
        $returnedOrFailed = (int) ($shipmentStatusCounts[CourierStatus::FAILED->value] ?? 0) + (int) ($shipmentStatusCounts[CourierStatus::RETURNED->value] ?? 0);
        $finalized = $delivered + $returnedOrFailed;

        $courierOverview = [
            'total_parcels' => (clone $shipments)->count(),
            'in_transit' => (int) ($shipmentStatusCounts[CourierStatus::IN_TRANSIT->value] ?? 0) + (int) ($shipmentStatusCounts[CourierStatus::PICKED_UP->value] ?? 0) + (int) ($shipmentStatusCounts[CourierStatus::OUT_FOR_DELIVERY->value] ?? 0),
            'delivered' => $delivered,
            'returned' => (int) ($shipmentStatusCounts[CourierStatus::RETURNED->value] ?? 0),
            'failed' => (int) ($shipmentStatusCounts[CourierStatus::FAILED->value] ?? 0),
        ];

        $courierPerformance = Courier::query()
            ->withCount(['shipments' => fn ($q) => $q
                ->when($since, fn ($qq) => $qq->where('created_at', '>=', $since))
                ->when($until, fn ($qq) => $qq->where('created_at', '<=', $until))])
            ->withCount(['shipments as delivered_count' => fn ($q) => $q
                ->when($since, fn ($qq) => $qq->where('created_at', '>=', $since))
                ->when($until, fn ($qq) => $qq->where('created_at', '<=', $until))
                ->where('status', CourierStatus::DELIVERED->value)])
            ->withCount(['shipments as returned_count' => fn ($q) => $q
                ->when($since, fn ($qq) => $qq->where('created_at', '>=', $since))
                ->when($until, fn ($qq) => $qq->where('created_at', '<=', $until))
                ->whereIn('status', [CourierStatus::RETURNED->value, CourierStatus::FAILED->value])])
            ->having('shipments_count', '>', 0)
            ->orderByDesc('shipments_count')
            ->get()
            ->map(fn (Courier $courier) => [
                'name' => $courier->name,
                'parcels' => $courier->shipments_count,
                'delivered' => $courier->delivered_count,
                'returned' => $courier->returned_count,
                'success_rate' => $courier->shipments_count > 0 ? round($courier->delivered_count / $courier->shipments_count * 100, 1) : null,
            ]);

        // Payment method + status breakdown from recorded payments in range.
        $payments = OrderPayment::query()
            ->when($since, fn ($q) => $q->where('created_at', '>=', $since))
            ->when($until, fn ($q) => $q->where('created_at', '<=', $until));

        $paymentByMethod = (clone $payments)
            ->where('status', PaymentStatus::PAID)
            ->selectRaw('payment_method, COUNT(*) as total, SUM(amount) as amount')
            ->groupBy('payment_method')
            ->get();

        $paymentByStatus = (clone $payments)
            ->selectRaw('status, COUNT(*) as total, SUM(amount) as amount')
            ->groupBy('status')
            ->get();

        return view('livewire.admin.dashboard.dashboard', [
            'kpis' => $kpis,
            'previousKpis' => $previousKpis,
            'orderStatusBreakdown' => $orderStatusBreakdown,
            'trend' => $trend,
            'recentOrders' => $recentOrders,
            'actionRequired' => $actionRequired,
            'inventory' => $inventory,
            'topProducts' => $topProducts,
            'lowStockVariants' => $lowStockVariants,
            'customers' => $customers,
            'topCustomers' => $topCustomers,
            'courierOverview' => $courierOverview,
            'courierPerformance' => $courierPerformance,
            'paymentByMethod' => $paymentByMethod,
            'paymentByStatus' => $paymentByStatus,
        ]);
    }

    /**
     * Operational alerts scoped to "right now" rather than the selected date
     * range — these are always-current counts an admin needs to act on today,
     * independent of whichever historical window the KPI cards are showing.
     */
    protected function actionRequiredAlerts(): array
    {
        return [
            'needs_confirmation' => Order::where('status', OrderStatus::PENDING)->count(),
            'needs_courier_entry' => Order::where('status', OrderStatus::CONFIRMED)
                ->whereDoesntHave('courierShipments')
                ->count(),
            'failed_deliveries' => CourierShipment::where('status', CourierStatus::FAILED->value)->count(),
            'out_of_stock' => Product::where('stock_status', 'out_of_stock')->count(),
            'low_stock' => Product::where('stock_status', 'low_stock')->count(),
            'refunds_pending' => Order::where(fn ($q) => $q
                ->where('payment_status', PaymentStatus::REFUNDED)
                ->orWhere('status', OrderStatus::RETURNED))
                ->count(),
        ];
    }

    /**
     * Sales + order count for the period immediately preceding the current
     * range, matched to the same length, so KPI cards can show a % change.
     * Returns nulls when the range is "all time" (no meaningful previous period).
     */
    protected function previousPeriodKpis(?CarbonInterface $since, ?CarbonInterface $until): array
    {
        if (! $since) {
            return ['total_sales' => null, 'total_orders' => null];
        }

        $end = $until ?? now();
        $lengthInSeconds = $end->diffInSeconds($since);

        $previousUntil = $since->copy()->subSecond();
        $previousSince = $previousUntil->copy()->subSeconds($lengthInSeconds);

        $previousOrders = Order::query()
            ->where('placed_at', '>=', $previousSince)
            ->where('placed_at', '<=', $previousUntil);

        return [
            'total_sales' => (float) (clone $previousOrders)->sum('total_amount'),
            'total_orders' => (clone $previousOrders)->count(),
        ];
    }
}
