<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $company['name'] ?: 'Company' }} — Company Information</title>
    @if($company['favicon'])
        <link rel="icon" href="{{ file_path($company['favicon']) }}">
    @endif
    @vite(['resources/css/admin.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { padding: 0 !important; }
            .print-card { box-shadow: none !important; border: none !important; }
        }
        @page { margin: 1.5cm; }
    </style>
</head>

<body class="bg-gray-100 p-6 sm:p-10 antialiased">

    <div class="no-print max-w-3xl mx-auto mb-4 flex justify-end">
        <button onclick="window.print()"
            class="inline-flex items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-indigo-600 rounded-xl hover:bg-indigo-700 transition shadow-sm">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0 1 10.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18m0 0 .229 2.523a1.125 1.125 0 0 1-1.12 1.227H7.231c-.662 0-1.18-.568-1.12-1.227L6.34 18m11.318 0h1.091A2.25 2.25 0 0 0 21 15.75V9.456c0-1.081-.768-2.015-1.837-2.175a48.055 48.055 0 0 0-1.913-.247M6.34 18H5.25A2.25 2.25 0 0 1 3 15.75V9.456c0-1.081.768-2.015 1.837-2.175a48.041 48.041 0 0 1 1.913-.247m10.5 0a48.536 48.536 0 0 0-10.5 0m10.5 0V3.375c0-.621-.504-1.125-1.125-1.125h-8.25c-.621 0-1.125.504-1.125 1.125v3.659M18 10.5h.008v.008H18V10.5Zm-3 0h.008v.008H15V10.5Z"/>
            </svg>
            Print
        </button>
    </div>

    <div class="print-card max-w-3xl mx-auto bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">

        {{-- Header --}}
        <div class="px-8 py-8 border-b border-gray-100 flex items-center gap-4">
            @if($company['logo'])
                <img src="{{ file_path($company['logo']) }}" alt="{{ $company['name'] }}" class="h-16 object-contain">
            @endif
            <div>
                <h1 class="text-xl font-semibold text-gray-900">{{ $company['name'] ?: 'Company Name Not Set' }}</h1>
                @if($company['legal_name'])
                    <p class="text-sm text-gray-500 mt-0.5">{{ $company['legal_name'] }}</p>
                @endif
            </div>
        </div>

        <div class="px-8 py-6 space-y-6">

            {{-- Contact --}}
            @if($company['email'] || $company['phone'] || $company['mobile'] || $company['website'])
                <div>
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Contact</p>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
                        @if($company['email'])
                            <div>
                                <dt class="text-xs text-gray-400">Email</dt>
                                <dd class="text-sm text-gray-800">{{ $company['email'] }}</dd>
                            </div>
                        @endif
                        @if($company['website'])
                            <div>
                                <dt class="text-xs text-gray-400">Website</dt>
                                <dd class="text-sm text-gray-800">{{ $company['website'] }}</dd>
                            </div>
                        @endif
                        @if($company['phone'])
                            <div>
                                <dt class="text-xs text-gray-400">Phone</dt>
                                <dd class="text-sm text-gray-800">{{ $company['phone'] }}</dd>
                            </div>
                        @endif
                        @if($company['mobile'])
                            <div>
                                <dt class="text-xs text-gray-400">Mobile</dt>
                                <dd class="text-sm text-gray-800">{{ $company['mobile'] }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif

            {{-- Address --}}
            @if($company['address'] || $company['city'] || $company['state'] || $country || $company['postal_code'])
                <div class="border-t border-gray-100 pt-6">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Address</p>
                    <p class="text-sm text-gray-800 leading-relaxed">
                        @if($company['address']){{ $company['address'] }}<br>@endif
                        @php
                            $line = collect([$company['city'], $company['state'], $company['postal_code']])->filter()->implode(', ');
                        @endphp
                        @if($line){{ $line }}<br>@endif
                        @if($country){{ $country->name }}@endif
                    </p>
                </div>
            @endif

            {{-- Legal / Tax --}}
            @if($company['tax_number'] || $company['trade_license'])
                <div class="border-t border-gray-100 pt-6">
                    <p class="text-xs font-semibold text-gray-400 uppercase tracking-wide mb-3">Legal / Tax</p>
                    <dl class="grid grid-cols-1 sm:grid-cols-2 gap-x-6 gap-y-3">
                        @if($company['tax_number'])
                            <div>
                                <dt class="text-xs text-gray-400">Tax / VAT Number</dt>
                                <dd class="text-sm text-gray-800 font-mono">{{ $company['tax_number'] }}</dd>
                            </div>
                        @endif
                        @if($company['trade_license'])
                            <div>
                                <dt class="text-xs text-gray-400">Trade License Number</dt>
                                <dd class="text-sm text-gray-800 font-mono">{{ $company['trade_license'] }}</dd>
                            </div>
                        @endif
                    </dl>
                </div>
            @endif

        </div>
    </div>

</body>

</html>
