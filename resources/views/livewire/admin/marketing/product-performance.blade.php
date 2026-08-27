<div class="space-y-6">

    <div>
        <h1 class="text-2xl font-bold text-gray-900">Product Performance</h1>
        <p class="text-sm text-gray-500 mt-1">Views, add-to-cart, and purchase conversion per product.</p>
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-4">
        <input type="text" wire:model.live.debounce.400ms="search" placeholder="Search product name…"
            class="w-full max-w-sm rounded-lg border-gray-300 text-sm focus:border-indigo-500 focus:ring-indigo-500">
    </div>

    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="border-b border-gray-100 bg-gray-50/40">
                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Product</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Visitors</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Views</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Add to Cart</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">View→Cart</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Cart→Purchase</th>
                        <th class="px-3 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Purchases</th>
                        <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Revenue</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($products as $product)
                        <tr class="hover:bg-gray-50/50 transition">
                            <td class="px-5 py-3 text-sm font-medium text-gray-900">
                                <a href="{{ route('admin.marketing.products.journeys', ['selectedProduct' => $product['name']]) }}" class="hover:text-indigo-600 hover:underline">
                                    {{ $product['name'] }}
                                </a>
                            </td>
                            <td class="px-3 py-3 text-right text-sm text-gray-700">{{ number_format($product['visitors']) }}</td>
                            <td class="px-3 py-3 text-right text-sm text-gray-700">{{ number_format($product['views']) }}</td>
                            <td class="px-3 py-3 text-right text-sm text-gray-700">{{ number_format($product['add_to_cart']) }}</td>
                            <td class="px-3 py-3 text-right text-sm text-gray-500">{{ $product['view_to_cart'] }}%</td>
                            <td class="px-3 py-3 text-right text-sm text-gray-500">{{ $product['cart_to_purchase'] }}%</td>
                            <td class="px-3 py-3 text-right text-sm text-gray-700">{{ number_format($product['purchases']) }}</td>
                            <td class="px-5 py-3 text-right text-sm font-semibold text-gray-900">৳{{ number_format($product['revenue'], 2) }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-5 py-16 text-center text-sm text-gray-500">No product events recorded yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
