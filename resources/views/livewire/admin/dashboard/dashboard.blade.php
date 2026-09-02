<div class="space-y-4" wire:key="admin-dashboard">

    {{-- Header + range filter --}}
    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h1 class="text-2xl font-bold text-gray-900">Dashboard</h1>
            <p class="text-sm text-gray-500 mt-1">Business overview — sales, orders, and store health.</p>
        </div>

        @include('livewire.admin.marketing.partials.date-range-picker')
    </div>

    {{-- KPI cards --}}
    @php
        $pctChange = function (?float $current, ?float $previous) {
            if ($previous === null || $previous == 0.0) {
                return null;
            }
            return (($current - $previous) / $previous) * 100;
        };
        $salesChange = $pctChange($kpis['total_sales'], $previousKpis['total_sales']);
        $ordersChange = $pctChange((float) $kpis['total_orders'], $previousKpis['total_orders'] !== null ? (float) $previousKpis['total_orders'] : null);

        $cards = [
            ['label' => 'Total Sales', 'value' => '৳'.number_format($kpis['total_sales'], 2), 'accent' => 'text-indigo-600 bg-indigo-50', 'change' => $salesChange, 'icon' => 'M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 0 0-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 0 1-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 0 0 3 15h-.75M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'],
            ['label' => 'Total Orders', 'value' => number_format($kpis['total_orders']), 'accent' => 'text-sky-600 bg-sky-50', 'change' => $ordersChange, 'icon' => 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.684 2.674-7.14a1.06 1.06 0 0 0-.999-1.335H5.85m4.5 8.475H5.85m0 0-.383-1.437'],
            ['label' => 'Pending Orders', 'value' => number_format($kpis['pending_orders']), 'accent' => 'text-amber-600 bg-amber-50', 'icon' => 'M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
            ['label' => 'Delivered Orders', 'value' => number_format($kpis['delivered_orders']), 'accent' => 'text-emerald-600 bg-emerald-50', 'icon' => 'm4.5 12.75 6 6 9-13.5'],
            ['label' => 'Cancelled Orders', 'value' => number_format($kpis['cancelled_orders']), 'accent' => 'text-gray-500 bg-gray-100', 'icon' => 'M6 18 18 6M6 6l12 12'],
            ['label' => 'Returned Orders', 'value' => number_format($kpis['returned_orders']), 'accent' => 'text-red-500 bg-red-50', 'icon' => 'M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3'],
            ['label' => 'Net Revenue', 'value' => '৳'.number_format($kpis['net_revenue'], 2), 'accent' => 'text-violet-600 bg-violet-50', 'icon' => 'M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a2.25 2.25 0 0 0 2.25-2.25V6.75A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25v10.5A2.25 2.25 0 0 0 4.5 19.5Z'],
            ['label' => 'Avg. Order Value', 'value' => '৳'.number_format($kpis['aov'], 2), 'accent' => 'text-teal-600 bg-teal-50', 'icon' => 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75'],
        ];
    @endphp

    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
        @foreach ($cards as $card)
            <div class="dash-card-in bg-white rounded-lg shadow-sm border border-gray-200 p-3 transition-shadow hover:shadow-md" style="animation-delay: {{ $loop->index * 40 }}ms">
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">{{ $card['label'] }}</span>
                    <span class="w-8 h-8 rounded-lg flex items-center justify-center {{ $card['accent'] }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $card['icon'] }}" />
                        </svg>
                    </span>
                </div>
                <div class="text-2xl font-bold text-gray-900">{{ $card['value'] }}</div>
                @if (array_key_exists('change', $card) && $card['change'] !== null)
                    <div class="mt-1 text-xs font-medium {{ $card['change'] >= 0 ? 'text-emerald-600' : 'text-red-500' }}">
                        {{ $card['change'] >= 0 ? '↑' : '↓' }} {{ number_format(abs($card['change']), 1) }}% vs previous period
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    {{-- Action Required — always-current, independent of the date range above --}}
    @php
        $alerts = [
            ['count' => $actionRequired['needs_confirmation'], 'label' => 'orders need confirmation', 'href' => route('admin.sales.orders', ['filterStatus' => \App\Enums\Sales\OrderStatus::PENDING->value])],
            ['count' => $actionRequired['needs_courier_entry'], 'label' => 'confirmed orders need courier entry', 'href' => route('admin.sales.orders', ['filterStatus' => \App\Enums\Sales\OrderStatus::CONFIRMED->value])],
            ['count' => $actionRequired['failed_deliveries'], 'label' => 'deliveries failed', 'href' => route('admin.settings.advance.courier.shipments')],
            ['count' => $actionRequired['out_of_stock'], 'label' => 'products are out of stock', 'href' => route('admin.catalog.products', ['filterStockStatus' => 'out_of_stock'])],
            ['count' => $actionRequired['low_stock'], 'label' => 'products are low on stock', 'href' => route('admin.catalog.products', ['filterStockStatus' => 'low_stock'])],
            ['count' => $actionRequired['refunds_pending'], 'label' => 'refunds pending', 'href' => route('admin.sales.orders', ['filterPaymentStatus' => \App\Enums\Sales\PaymentStatus::REFUNDED->value])],
        ];
        $activeAlerts = collect($alerts)->filter(fn ($a) => $a['count'] > 0);
    @endphp
    @if ($activeAlerts->isNotEmpty())
        <div class="bg-amber-50 border border-amber-200 rounded-lg p-2">
            <h2 class="text-xs font-semibold text-amber-900 mb-1.5 flex items-center gap-1.5">
                <svg xmlns="http://www.w3.org/2000/svg" class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                </svg>
                Action Required
            </h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-1.5">
                @foreach ($activeAlerts as $alert)
                    <a href="{{ $alert['href'] }}" class="flex items-center gap-1.5 bg-white/70 hover:bg-white rounded-lg px-2.5 py-1.5 text-xs text-amber-900 transition">
                        <span class="font-bold">{{ number_format($alert['count']) }}</span>
                        <span>{{ $alert['label'] }}</span>
                    </a>
                @endforeach
            </div>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-2">

        {{-- Revenue trend chart --}}
        <div class="dash-card-in lg:col-span-2 bg-white rounded-lg shadow-sm border border-gray-200 p-3">
            <h2 class="text-sm font-semibold text-gray-900 mb-2">Sales & Orders Trend</h2>
            <div class="h-64" wire:ignore
                x-data="salesTrendChart(@js($trend))"
                x-init="init()">
                <canvas x-ref="canvas"></canvas>
            </div>
        </div>

        {{-- Order status breakdown --}}
        <div class="dash-card-in bg-white rounded-lg shadow-sm border border-gray-200 p-3" style="animation-delay: 80ms">
            <h2 class="text-sm font-semibold text-gray-900 mb-2">Order Status</h2>
            <div class="space-y-2">
                @php $maxStatusCount = max(1, $orderStatusBreakdown->max('count')); @endphp
                @foreach ($orderStatusBreakdown as $row)
                    @continue($row['count'] === 0)
                    <a href="{{ route('admin.sales.orders', ['filterStatus' => $row['status']->value]) }}" class="block group">
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="text-gray-500 group-hover:text-indigo-600">{{ $row['status']->label() }}</span>
                            <span class="text-gray-900 font-semibold">{{ number_format($row['count']) }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                            <div class="dash-bar-in h-full rounded-full bg-linear-to-r from-indigo-500 to-violet-500"
                                style="width: {{ max(4, round($row['count'] / $maxStatusCount * 100)) }}%"></div>
                        </div>
                    </a>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Recent Orders --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Recent Orders</h2>
            <a href="{{ route('admin.sales.orders') }}" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">View all →</a>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-400 uppercase tracking-wide border-b border-gray-100">
                        <th class="px-4 py-2 font-semibold">Order</th>
                        <th class="px-3 py-2 font-semibold">Customer</th>
                        <th class="px-3 py-2 font-semibold text-right">Amount</th>
                        <th class="px-3 py-2 font-semibold">Payment</th>
                        <th class="px-4 py-2 font-semibold">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($recentOrders as $order)
                        <tr class="hover:bg-gray-50 cursor-pointer" onclick="window.location='{{ route('admin.sales.orders.show', $order->id) }}'">
                            <td class="px-4 py-2">
                                <div class="text-gray-900 font-medium">#{{ $order->id }}</div>
                                <div class="text-xs text-gray-400">{{ $order->placed_at?->format('M j, Y g:i A') }}</div>
                            </td>
                            <td class="px-3 py-3 text-gray-700">{{ $order->customer?->full_name ?? 'Guest' }}</td>
                            <td class="px-3 py-3 text-right text-gray-900 font-semibold">৳{{ number_format($order->total_amount, 2) }}</td>
                            <td class="px-3 py-3">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $order->payment_status->badgeClass() }}">
                                    {{ $order->payment_status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-2">
                                <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $order->status->badgeClass() }}">
                                    {{ $order->status->label() }}
                                </span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-400 text-sm">No orders in this range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Inventory snapshot --}}
    <div class="grid grid-cols-2 lg:grid-cols-5 gap-2">
        @php
            $inventoryCards = [
                ['label' => 'Total Products', 'value' => number_format($inventory['total_products']), 'accent' => 'text-indigo-600 bg-indigo-50'],
                ['label' => 'Active', 'value' => number_format($inventory['active_products']), 'accent' => 'text-emerald-600 bg-emerald-50'],
                ['label' => 'Draft', 'value' => number_format($inventory['draft_products']), 'accent' => 'text-gray-500 bg-gray-100'],
                ['label' => 'Out of Stock', 'value' => number_format($inventory['out_of_stock']), 'accent' => 'text-red-500 bg-red-50'],
                ['label' => 'Low Stock', 'value' => number_format($inventory['low_stock']), 'accent' => 'text-amber-600 bg-amber-50'],
            ];
        @endphp
        @foreach ($inventoryCards as $card)
            <div class="dash-card-in bg-white rounded-lg shadow-sm border border-gray-200 p-3 transition-shadow hover:shadow-md" style="animation-delay: {{ $loop->index * 40 }}ms">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">{{ $card['label'] }}</span>
                <div class="text-xl font-bold text-gray-900 mt-2">{{ $card['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-2">

        {{-- Top products --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">Top Products</h2>
                <a href="{{ route('admin.catalog.products') }}" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">View all →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-400 uppercase tracking-wide border-b border-gray-100">
                            <th class="px-4 py-2 font-semibold">Product</th>
                            <th class="px-3 py-2 font-semibold text-right">Sold</th>
                            <th class="px-4 py-2 font-semibold text-right">Revenue</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($topProducts as $product)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2">
                                    <a href="{{ route('admin.catalog.products.edit', $product->product_id) }}" class="text-gray-900 font-medium hover:text-indigo-600">
                                        {{ $product->product_name }}
                                    </a>
                                </td>
                                <td class="px-3 py-3 text-right text-gray-700">{{ number_format($product->qty_sold, 0) }}</td>
                                <td class="px-4 py-2 text-right text-gray-900 font-semibold">৳{{ number_format($product->revenue, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-gray-400 text-sm">No sales in this range.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Low stock --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">Low Stock</h2>
                <a href="{{ route('admin.inventory.stock') }}" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">View all →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-400 uppercase tracking-wide border-b border-gray-100">
                            <th class="px-4 py-2 font-semibold">Product</th>
                            <th class="px-3 py-2 font-semibold text-right">Stock</th>
                            <th class="px-4 py-2 font-semibold text-right">Alert</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($lowStockVariants as $variant)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2">
                                    <a href="{{ route('admin.catalog.products.edit', $variant->product_id) }}" class="text-gray-900 font-medium hover:text-indigo-600">
                                        {{ $variant->product?->name }}
                                    </a>
                                    <div class="text-xs text-gray-400">{{ $variant->sku }}</div>
                                </td>
                                <td class="px-3 py-3 text-right text-gray-700">{{ number_format($variant->stock_quantity, 0) }}</td>
                                <td class="px-4 py-2 text-right">
                                    <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $variant->stock_quantity <= 0 ? 'bg-red-50 text-red-500' : 'bg-amber-50 text-amber-600' }}">
                                        {{ $variant->stock_quantity <= 0 ? 'Critical' : 'Low' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-gray-400 text-sm">All stock levels healthy.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- Customer overview --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-2">
        @php
            $customerCards = [
                ['label' => 'Total Customers', 'value' => number_format($customers['total_customers']), 'accent' => 'text-indigo-600 bg-indigo-50'],
                ['label' => 'Active in Range', 'value' => number_format($customers['active_customers']), 'accent' => 'text-sky-600 bg-sky-50'],
                ['label' => 'New Customers', 'value' => number_format($customers['new_customers']), 'accent' => 'text-emerald-600 bg-emerald-50'],
                ['label' => 'Returning Customers', 'value' => number_format($customers['returning_customers']), 'accent' => 'text-violet-600 bg-violet-50'],
            ];
        @endphp
        @foreach ($customerCards as $card)
            <div class="dash-card-in bg-white rounded-lg shadow-sm border border-gray-200 p-3 transition-shadow hover:shadow-md" style="animation-delay: {{ $loop->index * 40 }}ms">
                <span class="text-xs font-semibold text-gray-400 uppercase tracking-wide">{{ $card['label'] }}</span>
                <div class="text-xl font-bold text-gray-900 mt-2">{{ $card['value'] }}</div>
            </div>
        @endforeach
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-2">

        {{-- Top customers --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
            <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
                <h2 class="text-sm font-semibold text-gray-900">Top Customers</h2>
                <a href="{{ route('admin.customers.index') }}" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">View all →</a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs text-gray-400 uppercase tracking-wide border-b border-gray-100">
                            <th class="px-4 py-2 font-semibold">Customer</th>
                            <th class="px-3 py-2 font-semibold text-right">Orders</th>
                            <th class="px-4 py-2 font-semibold text-right">Spent</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($topCustomers as $row)
                            <tr class="hover:bg-gray-50">
                                <td class="px-4 py-2 text-gray-900 font-medium">{{ $row->customer?->full_name ?? 'Guest' }}</td>
                                <td class="px-3 py-3 text-right text-gray-700">{{ number_format($row->orders_count) }}</td>
                                <td class="px-4 py-2 text-right text-gray-900 font-semibold">৳{{ number_format($row->total_spent, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="px-4 py-6 text-center text-gray-400 text-sm">No customers in this range.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Courier overview --}}
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
            <h2 class="text-sm font-semibold text-gray-900 mb-2">Courier Overview</h2>
            <div class="grid grid-cols-2 gap-2 mb-2">
                @php
                    $courierCards = [
                        ['label' => 'Total Parcels', 'value' => $courierOverview['total_parcels']],
                        ['label' => 'In Transit', 'value' => $courierOverview['in_transit']],
                        ['label' => 'Delivered', 'value' => $courierOverview['delivered']],
                        ['label' => 'Returned/Failed', 'value' => $courierOverview['returned'] + $courierOverview['failed']],
                    ];
                @endphp
                @foreach ($courierCards as $card)
                    <div class="dash-card-in bg-gray-50 rounded-lg px-3 py-2 transition-colors hover:bg-gray-100" style="animation-delay: {{ $loop->index * 40 }}ms">
                        <div class="text-xs text-gray-400">{{ $card['label'] }}</div>
                        <div class="text-lg font-bold text-gray-900">{{ number_format($card['value']) }}</div>
                    </div>
                @endforeach
            </div>
            <a href="{{ route('admin.settings.advance.courier.shipments') }}" class="text-xs text-indigo-600 hover:text-indigo-700 font-medium">View all shipments →</a>
        </div>
    </div>

    {{-- Courier performance --}}
    <div class="bg-white rounded-lg shadow-sm border border-gray-200 overflow-hidden">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <h2 class="text-sm font-semibold text-gray-900">Courier Performance</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs text-gray-400 uppercase tracking-wide border-b border-gray-100">
                        <th class="px-4 py-2 font-semibold">Courier</th>
                        <th class="px-3 py-2 font-semibold text-right">Parcels</th>
                        <th class="px-3 py-2 font-semibold text-right">Delivered</th>
                        <th class="px-3 py-2 font-semibold text-right">Returned</th>
                        <th class="px-4 py-2 font-semibold text-right">Success Rate</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($courierPerformance as $row)
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-2 text-gray-900 font-medium">{{ $row['name'] }}</td>
                            <td class="px-3 py-3 text-right text-gray-700">{{ number_format($row['parcels']) }}</td>
                            <td class="px-3 py-3 text-right text-gray-700">{{ number_format($row['delivered']) }}</td>
                            <td class="px-3 py-3 text-right text-gray-700">{{ number_format($row['returned']) }}</td>
                            <td class="px-4 py-2 text-right font-semibold {{ $row['success_rate'] !== null && $row['success_rate'] >= 80 ? 'text-emerald-600' : 'text-amber-600' }}">
                                {{ $row['success_rate'] !== null ? number_format($row['success_rate'], 1).'%' : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-6 text-center text-gray-400 text-sm">No courier shipments in this range.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Payment breakdown --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-2">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
            <h2 class="text-sm font-semibold text-gray-900 mb-2">Payment Methods</h2>
            <div class="space-y-2">
                @php $maxMethodAmount = max(1, $paymentByMethod->max('amount')); @endphp
                @forelse ($paymentByMethod as $row)
                    <div>
                        <div class="flex items-center justify-between text-xs mb-1">
                            <span class="text-gray-500">{{ $row->payment_method?->label() ?? 'Unknown' }}</span>
                            <span class="text-gray-900 font-semibold">৳{{ number_format($row->amount, 2) }}</span>
                        </div>
                        <div class="h-2 rounded-full bg-gray-100 overflow-hidden">
                            <div class="dash-bar-in h-full rounded-full bg-linear-to-r from-indigo-500 to-violet-500"
                                style="width: {{ max(4, round($row->amount / $maxMethodAmount * 100)) }}%"></div>
                        </div>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No paid payments in this range.</p>
                @endforelse
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200 p-3">
            <h2 class="text-sm font-semibold text-gray-900 mb-2">Payment Status</h2>
            <div class="space-y-2">
                @php $totalPaymentCount = max(1, $paymentByStatus->sum('total')); @endphp
                @forelse ($paymentByStatus as $row)
                    <div class="flex items-center justify-between text-sm">
                        <span class="inline-flex px-2 py-0.5 rounded-full text-xs font-medium {{ $row->status?->badgeClass() ?? 'bg-gray-100 text-gray-500' }}">
                            {{ $row->status?->label() ?? 'Unknown' }}
                        </span>
                        <span class="text-gray-900 font-semibold">{{ number_format($row->total) }} · ৳{{ number_format($row->amount, 2) }}</span>
                    </div>
                @empty
                    <p class="text-sm text-gray-400">No payments in this range.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes dash-card-in {
        from { opacity: 0; transform: translateY(6px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .dash-card-in {
        animation: dash-card-in .35s ease-out both;
    }
    @keyframes dash-bar-in {
        from { width: 0 !important; }
    }
    .dash-bar-in {
        animation: dash-bar-in .6s ease-out;
    }
    @media (prefers-reduced-motion: reduce) {
        .dash-card-in, .dash-bar-in { animation: none; }
    }
</style>

@script
<script>
    Alpine.data('salesTrendChart', (trend) => ({
        init() {
            new window.Chart(this.$refs.canvas, {
                type: 'line',
                data: {
                    labels: trend.labels,
                    datasets: [
                        {
                            label: 'Revenue',
                            data: trend.revenue,
                            borderColor: '#6366f1',
                            backgroundColor: 'rgba(99, 102, 241, 0.08)',
                            tension: 0.35,
                            fill: true,
                            pointRadius: 0,
                            borderWidth: 2,
                        },
                        {
                            label: 'Orders',
                            data: trend.orders,
                            borderColor: '#10b981',
                            backgroundColor: 'rgba(16, 185, 129, 0.08)',
                            tension: 0.35,
                            fill: true,
                            pointRadius: 0,
                            borderWidth: 2,
                            yAxisID: 'y1',
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: {
                        legend: {
                            labels: { color: '#6b7280', usePointStyle: true, boxWidth: 6 },
                        },
                    },
                    scales: {
                        x: {
                            grid: { display: false },
                            ticks: { color: '#9ca3af', maxTicksLimit: 8 },
                        },
                        y: {
                            grid: { color: 'rgba(0,0,0,0.04)' },
                            ticks: { color: '#9ca3af' },
                        },
                        y1: {
                            position: 'right',
                            grid: { display: false },
                            ticks: { color: '#9ca3af' },
                        },
                    },
                },
            });
        },
    }));
</script>
@endscript
