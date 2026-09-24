<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Setting;
use Illuminate\Contracts\View\View;

/**
 * Printable invoice / packing slip for an order (admin → Order → Invoice /
 * Packing slip). Same standalone print-page pattern as CompanyPrintController;
 * the packing slip is the invoice without prices, for the warehouse/courier.
 */
class OrderPrintController extends Controller
{
    public function __invoke(int $id, string $type = 'invoice'): View
    {
        abort_unless(in_array($type, ['invoice', 'packing-slip'], true), 404);

        $order = Order::with([
            'customer', 'billingAddress', 'shippingAddress',
            'items.variant', 'charges', 'offers', 'payments',
        ])->findOrFail($id);

        $company = collect([
            'name', 'logo', 'favicon', 'email', 'phone', 'mobile', 'website',
            'address', 'city', 'state', 'postal_code', 'tax_number',
        ])->mapWithKeys(fn ($field) => [$field => Setting::get("company_{$field}", null, 'company')]);

        $company['name'] = $company['name'] ?: Setting::get('site_name', config('app.name'));

        return view('admin.order-print', [
            'order'    => $order,
            'company'  => $company,
            'type'     => $type,
            'currency' => Setting::get('currency_symbol', '৳', 'localization'),
        ]);
    }
}
