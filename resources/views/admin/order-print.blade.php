@php
    $isInvoice = $type === 'invoice';
    $title = $isInvoice ? 'Invoice' : 'Packing Slip';
    $money = fn ($n) => $currency . number_format((float) $n, 2);
    $qty = fn ($n) => rtrim(rtrim(number_format((float) $n, 3), '0'), '.');
    $companyLine = collect([$company['address'], $company['city'], $company['state'], $company['postal_code']])->filter()->implode(', ');
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }} — Order #{{ $order->id }}</title>
    @if($company['favicon'])
        <link rel="icon" href="{{ file_path($company['favicon']) }}">
    @endif
    @vite(['resources/css/admin.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { padding: 0 !important; background: #fff !important; }
            .print-card { box-shadow: none !important; border: none !important; }
        }
        @page { margin: 1.2cm; }
    </style>
</head>

<body class="bg-gray-100 p-6 sm:p-10 antialiased text-gray-800">

    <div class="no-print max-w-3xl mx-auto mb-4 flex items-center justify-between gap-3">
        <div class="flex gap-2 text-sm">
            <a href="{{ route('admin.sales.orders.print', [$order->id, 'invoice']) }}"
                class="px-3 py-1.5 rounded-lg {{ $isInvoice ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 border border-gray-200' }}">Invoice</a>
            <a href="{{ route('admin.sales.orders.print', [$order->id, 'packing-slip']) }}"
                class="px-3 py-1.5 rounded-lg {{ ! $isInvoice ? 'bg-indigo-600 text-white' : 'bg-white text-gray-600 border border-gray-200' }}">Packing Slip</a>
        </div>
        <button onclick="window.print()"
            class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
            Print
        </button>
    </div>

    <div class="print-card max-w-3xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

        {{-- Header --}}
        <div class="px-8 py-6 border-b border-gray-100 flex items-start justify-between gap-6">
            <div class="flex items-center gap-4">
                @if($company['logo'])
                    <img src="{{ file_path($company['logo']) }}" alt="{{ $company['name'] }}" class="h-14 object-contain">
                @endif
                <div>
                    <p class="text-lg font-semibold text-gray-900">{{ $company['name'] }}</p>
                    @if($companyLine)<p class="text-xs text-gray-500">{{ $companyLine }}</p>@endif
                    <p class="text-xs text-gray-500">
                        {{ collect([$company['phone'] ?: $company['mobile'], $company['email'], $company['website']])->filter()->implode(' · ') }}
                    </p>
                    @if($isInvoice && $company['tax_number'])<p class="text-xs text-gray-500">VAT/Tax No: {{ $company['tax_number'] }}</p>@endif
                </div>
            </div>
            <div class="text-right shrink-0">
                <p class="text-2xl font-bold text-gray-900 uppercase tracking-wide">{{ $title }}</p>
                <p class="text-sm text-gray-600 mt-1">Order #{{ $order->id }}</p>
                @if($isInvoice)
                    <p class="text-xs text-gray-500">INV-{{ str_pad((string) $order->id, 6, '0', STR_PAD_LEFT) }}</p>
                @endif
                <p class="text-xs text-gray-500">{{ local_time($order->placed_at ?? $order->created_at)?->format('d M Y, h:i A') }}</p>
                <p class="text-xs text-gray-500 mt-1">{{ $order->status->label() }}@if($isInvoice) · {{ $order->payment_status->label() }}@endif</p>
            </div>
        </div>

        {{-- Addresses --}}
        <div class="px-8 py-5 grid grid-cols-2 gap-6 border-b border-gray-100">
            @foreach([['Bill To', $order->billingAddress], ['Ship To', $order->shippingAddress]] as [$label, $address])
                <div>
                    <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide mb-1">{{ $label }}</p>
                    @if($address)
                        <p class="text-sm font-medium text-gray-900">{{ $address->name }}</p>
                        @if($address->phone)<p class="text-sm text-gray-600">{{ $address->phone }}</p>@endif
                        <p class="text-sm text-gray-600 whitespace-pre-line">{{ $address->full_address }}</p>
                    @elseif($order->customer)
                        <p class="text-sm font-medium text-gray-900">{{ $order->customer->full_name }}</p>
                        @if($order->customer->phone)<p class="text-sm text-gray-600">{{ $order->customer->phone }}</p>@endif
                    @else
                        <p class="text-sm text-gray-500">Guest</p>
                    @endif
                </div>
            @endforeach
        </div>

        @if(! $isInvoice && ($order->courier_provider || $order->courier_tracking_number))
            <div class="px-8 py-3 border-b border-gray-100 text-sm text-gray-600">
                Courier: <span class="font-medium text-gray-900">{{ $order->courier_provider ?: '—' }}</span>
                @if($order->courier_tracking_number) · Tracking: <span class="font-mono font-medium text-gray-900">{{ $order->courier_tracking_number }}</span>@endif
            </div>
        @endif

        {{-- Items --}}
        <div class="px-8 py-5">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-[11px] uppercase tracking-wide text-gray-400 border-b border-gray-200">
                        <th class="py-2 pr-2 w-8">#</th>
                        <th class="py-2 pr-2">Item</th>
                        <th class="py-2 px-2 text-right">Qty</th>
                        @if($isInvoice)
                            <th class="py-2 px-2 text-right">Unit Price</th>
                            <th class="py-2 px-2 text-right">Discount</th>
                            <th class="py-2 pl-2 text-right">Total</th>
                        @else
                            <th class="py-2 pl-2 text-center w-16">Packed</th>
                        @endif
                    </tr>
                </thead>
                <tbody>
                    @foreach($order->items as $item)
                        <tr class="border-b border-gray-100 align-top">
                            <td class="py-2.5 pr-2 text-gray-400">{{ $loop->iteration }}</td>
                            <td class="py-2.5 pr-2">
                                <p class="text-gray-900">{{ $item->product_name }}@if($item->is_gift) <span class="text-[10px] uppercase text-emerald-600">(Gift)</span>@endif</p>
                                @php($options = $item->variant?->options_map ?? [])
                                @if($options || $item->sku)
                                    <p class="text-xs text-gray-500">{{ implode(' · ', $options) }}{{ $options && $item->sku ? ' · ' : '' }}{{ $item->sku ? 'SKU ' . $item->sku : '' }}</p>
                                @endif
                            </td>
                            <td class="py-2.5 px-2 text-right">{{ $qty($item->quantity) }}</td>
                            @if($isInvoice)
                                <td class="py-2.5 px-2 text-right">{{ $money($item->unit_price) }}</td>
                                <td class="py-2.5 px-2 text-right text-gray-500">{{ (float) $item->discount_amount > 0 ? '−' . $money($item->discount_amount) : '—' }}</td>
                                <td class="py-2.5 pl-2 text-right font-medium">{{ $money($item->total_amount) }}</td>
                            @else
                                <td class="py-2.5 pl-2 text-center"><span class="inline-block w-4 h-4 border border-gray-400 rounded-sm"></span></td>
                            @endif
                        </tr>
                    @endforeach
                </tbody>
            </table>

            @if($isInvoice)
                <div class="flex justify-end mt-4">
                    <dl class="w-72 text-sm space-y-1.5">
                        <div class="flex justify-between"><dt class="text-gray-500">Subtotal</dt><dd>{{ $money($order->subtotal) }}</dd></div>
                        @if((float) $order->discount_amount > 0)
                            <div class="flex justify-between"><dt class="text-gray-500">Discount</dt><dd>−{{ $money($order->discount_amount) }}</dd></div>
                        @endif
                        <div class="flex justify-between"><dt class="text-gray-500">Shipping</dt><dd>{{ $money($order->shipping_amount) }}</dd></div>
                        @if((float) $order->shipping_discount > 0)
                            <div class="flex justify-between"><dt class="text-gray-500">Shipping Discount{{ $order->coupon_code ? " ({$order->coupon_code})" : '' }}</dt><dd>−{{ $money($order->shipping_discount) }}</dd></div>
                        @endif
                        @if((float) $order->tax_amount > 0)
                            <div class="flex justify-between"><dt class="text-gray-500">Tax / VAT</dt><dd>{{ $money($order->tax_amount) }}</dd></div>
                        @endif
                        @foreach($order->charges as $charge)
                            <div class="flex justify-between"><dt class="text-gray-500">{{ $charge->label }}</dt><dd>{{ $money($charge->amount) }}</dd></div>
                        @endforeach
                        <div class="flex justify-between pt-2 mt-1 border-t border-gray-200 text-base font-semibold text-gray-900"><dt>Total</dt><dd>{{ $money($order->total_amount) }}</dd></div>
                        <div class="flex justify-between"><dt class="text-gray-500">Paid</dt><dd>{{ $money($order->paid_amount) }}</dd></div>
                        <div class="flex justify-between font-semibold"><dt>Due</dt><dd>{{ $money($order->due_amount) }}</dd></div>
                    </dl>
                </div>

                @if($order->offers->isNotEmpty())
                    <p class="text-xs text-gray-500 mt-3">Offers applied: {{ $order->offers->pluck('name')->implode(', ') }}</p>
                @endif
            @else
                <p class="text-sm text-gray-600 mt-4">Total items: <span class="font-medium text-gray-900">{{ $qty($order->items->sum('quantity')) }}</span></p>
            @endif
        </div>

        @if($order->customer_note)
            <div class="px-8 py-4 border-t border-gray-100">
                <p class="text-[11px] font-semibold text-gray-400 uppercase tracking-wide mb-1">Customer Note</p>
                <p class="text-sm text-gray-700 whitespace-pre-line">{{ $order->customer_note }}</p>
            </div>
        @endif

        <div class="px-8 py-4 border-t border-gray-100 text-center text-xs text-gray-400">
            {{ $isInvoice ? 'Thank you for your order!' : 'Please check all items before handing over.' }}
        </div>
    </div>
</body>

</html>
