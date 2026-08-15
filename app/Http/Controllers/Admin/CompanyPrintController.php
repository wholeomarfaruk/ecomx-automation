<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Country;
use App\Models\Setting;
use Illuminate\Contracts\View\View;

class CompanyPrintController extends Controller
{
    public function __invoke(): View
    {
        $company = collect([
            'name', 'legal_name', 'logo', 'favicon', 'email', 'phone', 'mobile',
            'website', 'address', 'city', 'state', 'country_id', 'postal_code',
            'tax_number', 'trade_license', 'map_location',
        ])->mapWithKeys(fn ($field) => [$field => Setting::get("company_{$field}", null, 'company')]);

        $country = $company['country_id'] ? Country::find($company['country_id']) : null;

        return view('admin.company-print', [
            'company' => $company,
            'country' => $country,
        ]);
    }
}
