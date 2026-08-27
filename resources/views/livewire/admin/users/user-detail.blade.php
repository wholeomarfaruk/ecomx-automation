<div x-data x-init="$store.pageName = { name: 'User Details', slug: 'user-details' }">

    {{-- Header --}}
    <div class="flex flex-wrap items-center justify-between gap-4 mb-6">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.users') }}"
                class="w-9 h-9 inline-flex items-center justify-center rounded-lg text-gray-500 bg-white border border-gray-200 hover:bg-gray-50 transition">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <div class="w-11 h-11 rounded-full bg-indigo-100 flex items-center justify-center shrink-0">
                <span class="text-indigo-600 font-semibold text-sm">{{ strtoupper(mb_substr($user->name ?? $customer->full_name ?? '?', 0, 1)) }}</span>
            </div>
            <div>
                <h1 class="text-lg font-semibold text-gray-900 flex items-center gap-2">
                    {{ $user->name ?? $customer->full_name ?? 'Unknown' }}
                    @if($entityIsActive)
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-emerald-600" title="Active now">
                            <span class="w-2 h-2 rounded-full bg-emerald-500 animate-pulse"></span>
                            Active
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1 text-xs font-medium text-gray-400" title="{{ $entityLastSeen?->format('d M Y, h:i A') ?? 'Never seen' }}">
                            <span class="w-2 h-2 rounded-full bg-gray-300"></span>
                            {{ $entityLastSeen ? 'Last seen ' . $entityLastSeen->diffForHumans() : 'Never seen' }}
                        </span>
                    @endif
                </h1>
                <p class="text-xs text-gray-400 font-mono">{{ $customer->customer_code ?? ($user->email ?? '') }}</p>
            </div>
        </div>
        <div class="flex items-center gap-2">
            @if($customer)
                @php
                    $statusStyles = [
                        'active'   => 'bg-emerald-50 text-emerald-600',
                        'inactive' => 'bg-gray-100 text-gray-500',
                        'blocked'  => 'bg-red-50 text-red-500',
                    ];
                @endphp
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium capitalize {{ $statusStyles[$customer->status] ?? 'bg-gray-100 text-gray-500' }}">
                    {{ $customer->status }}
                </span>
                <button wire:click="openEditModal" type="button"
                    class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                    </svg>
                    Edit
                </button>
            @else
                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-amber-50 text-amber-600">No customer linked</span>
            @endif
        </div>
    </div>

    <div class="flex flex-col lg:flex-row gap-6">
        {{-- Left nav --}}
        <div class="lg:w-56 shrink-0">
            <nav class="bg-white rounded-2xl shadow-sm border border-gray-200 p-2 space-y-0.5 lg:sticky lg:top-4">
                @php
                    $navItems = [
                        'user'      => ['User Details', 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z'],
                        'info'      => ['Customer Details', 'M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'],
                        'devices'   => ['Devices', 'M10.5 1.5H8.25A2.25 2.25 0 0 0 6 3.75v16.5a2.25 2.25 0 0 0 2.25 2.25h7.5A2.25 2.25 0 0 0 18 20.25V3.75a2.25 2.25 0 0 0-2.25-2.25H13.5m-3 0V3h3V1.5m-3 0h3m-3 18.75h3'],
                        'ips'       => ['IP Addresses', 'M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m-15.432 0A8.959 8.959 0 0 1 3 12c0-.778.099-1.533.284-2.253'],
                        'visits'    => ['Visits', 'M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244'],
                        'orders'    => ['Orders', 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.684 2.674-7.14a1.06 1.06 0 0 0-.999-1.335H5.85m4.5 8.475H5.85m0 0-.383-1.437M12 14.25l3.75-3.75M12 14.25l-3.75-3.75M12 14.25V6'],
                        'products'  => ['Ordered Products', 'M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z'],
                        'carts'     => ['Carts', 'M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.684 2.674-7.14a1.06 1.06 0 0 0-.999-1.335H5.85m4.5 8.475H5.85m0 0-.383-1.437M12 14.25l3.75-3.75M12 14.25l-3.75-3.75M12 14.25V6M9 19.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm10.5 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z'],
                        'combos'    => ['Combos', 'M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z'],
                        'wishlist'  => ['Wishlist', 'M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z'],
                        'addresses' => ['Addresses', 'M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z'],
                        'pos'       => ['POS Sessions', 'M2.25 18.75a60.07 60.07 0 0 1 15.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 0 1 3 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.625c.621 0 1.125.504 1.125 1.125V6h-.75a.75.75 0 0 1-.75-.75V4.5M3.75 4.5h16.5'],
                        'tokens'    => ['Device Tokens', 'M9.75 3.104v5.714a2.25 2.25 0 0 1-.659 1.591L5 14.5M9.75 3.104c-.251.023-.501.05-.75.082m.75-.082a24.301 24.301 0 0 1 4.5 0m0 0v5.714c0 .597.237 1.17.659 1.591L19.8 15.3M14.25 3.104c.251.023.501.05.75.082M19.8 15.3l-1.57.393A9.065 9.065 0 0 1 12 15a9.065 9.065 0 0 0-6.23-.693L5 14.5m14.8.8 1.402 1.402c1.232 1.232.65 3.318-1.067 3.611A48.309 48.309 0 0 1 12 21c-2.773 0-5.491-.235-8.135-.687-1.718-.293-2.3-2.379-1.067-3.61L5 14.5'],
                        'activity'  => ['Activity Log', 'M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z'],
                        'marketing' => ['Marketing', 'M2.25 18 9 11.25l4.306 4.306a11.95 11.95 0 0 1 5.814-5.518l2.74-1.22m0 0-5.94-2.281m5.94 2.28-2.28 5.941'],
                        'blocks'    => ['Blocks', 'M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                    ];
                @endphp
                @foreach($navItems as $key => [$label, $icon])
                    @php
                        $deviceLinkedTabs   = ['devices', 'ips', 'visits', 'activity', 'blocks', 'marketing'];
                        $userOnlyTabs       = ['pos', 'tokens'];
                        $customerOnlyTabs   = ['info', 'orders', 'products', 'carts', 'combos', 'wishlist', 'addresses'];
                        $disabled = ($key !== 'user') && match (true) {
                            in_array($key, $deviceLinkedTabs, true) => ! $customer && ! $user,
                            in_array($key, $userOnlyTabs, true)     => ! $user,
                            in_array($key, $customerOnlyTabs, true) => ! $customer,
                            default => false,
                        };
                    @endphp
                    <button type="button"
                        @if(! $disabled) wire:click="$set('tab', '{{ $key }}')" @endif
                        @disabled($disabled)
                        class="w-full flex items-center gap-2.5 px-3 py-2.5 rounded-xl text-sm font-medium transition text-left
                            {{ $tab === $key ? 'bg-indigo-50 text-indigo-600' : ($disabled ? 'text-gray-300 cursor-not-allowed' : 'text-gray-600 hover:bg-gray-50') }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="{{ $icon }}"/>
                        </svg>
                        {{ $label }}
                    </button>
                @endforeach
            </nav>
        </div>

        {{-- Right content --}}
        <div class="flex-1 min-w-0">

            @if($tab === 'user')
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                    @if($user)
                        <dl class="grid grid-cols-2 gap-x-8 gap-y-5">
                            <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Name</dt><dd class="mt-1 text-sm text-gray-800">{{ $user->name }}</dd></div>
                            <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Login Email</dt><dd class="mt-1 text-sm text-gray-800">{{ $user->email }}</dd></div>
                            <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Phone</dt><dd class="mt-1 text-sm text-gray-800">{{ $user->phone ?? '—' }}</dd></div>
                            <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Role</dt><dd class="mt-1 text-sm text-gray-800">{{ $user->roles->pluck('name')->join(', ') ?: '—' }}</dd></div>
                            <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Email Verified</dt><dd class="mt-1 text-sm text-gray-800">{{ $user->email_verified_at?->format('d M Y, h:i A') ?? 'Not verified' }}</dd></div>
                            <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Phone Verified</dt><dd class="mt-1 text-sm text-gray-800">{{ $user->phone_verified_at?->format('d M Y, h:i A') ?? 'Not verified' }}</dd></div>
                            <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Account Created</dt><dd class="mt-1 text-sm text-gray-800">{{ $user->created_at?->format('d M Y, h:i A') }}</dd></div>
                        </dl>
                    @else
                        <div class="flex flex-col items-center justify-center py-16 text-center">
                            <p class="text-sm font-semibold text-gray-700">No linked login account</p>
                            <p class="text-xs text-gray-400 mt-0.5">This customer isn't linked to a staff/login account.</p>
                        </div>
                    @endif
                </div>
            @endif

            @if($tab === 'info')
                @if($customer)
                    @php
                        $meta = $customer->metadata ?? [];
                        $socials = $meta['socials'] ?? [];
                        $physical = $meta['physical'] ?? [];
                        $personal = $meta['personal'] ?? [];
                        $socialIcons = [
                            'facebook'  => 'M9.101 23.691v-7.98H6.627v-3.667h2.474v-1.58c0-4.085 1.848-5.978 5.858-5.978.401 0 .955.042 1.468.103a8.68 8.68 0 0 1 1.141.195v3.325a8.623 8.623 0 0 0-.653-.036 26.805 26.805 0 0 0-.733-.009c-.707 0-1.259.096-1.675.309a1.686 1.686 0 0 0-.679.622c-.258.42-.374.995-.374 1.752v1.297h3.919l-.386 1.879-.287 1.788h-3.246v8.245',
                            'instagram' => 'M12 2c2.717 0 3.056.01 4.122.06 1.065.05 1.79.217 2.428.465.66.254 1.216.598 1.772 1.153a4.908 4.908 0 0 1 1.153 1.772c.247.637.415 1.363.465 2.428.047 1.066.06 1.405.06 4.122 0 2.717-.01 3.056-.06 4.122-.05 1.065-.218 1.79-.465 2.428a4.883 4.883 0 0 1-1.153 1.772 4.915 4.915 0 0 1-1.772 1.153c-.637.247-1.363.415-2.428.465-1.066.047-1.405.06-4.122.06-2.717 0-3.056-.01-4.122-.06-1.065-.05-1.79-.218-2.428-.465a4.89 4.89 0 0 1-1.772-1.153 4.904 4.904 0 0 1-1.153-1.772c-.248-.637-.415-1.363-.465-2.428C2.013 15.056 2 14.717 2 12c0-2.717.01-3.056.06-4.122.05-1.066.217-1.79.465-2.428a4.88 4.88 0 0 1 1.153-1.772A4.897 4.897 0 0 1 5.45 2.525c.638-.248 1.362-.415 2.428-.465C8.944 2.013 9.283 2 12 2Zm0 5a5 5 0 1 0 0 10 5 5 0 0 0 0-10Zm6.4-.2a1.2 1.2 0 1 0-2.4 0 1.2 1.2 0 0 0 2.4 0Z',
                            'twitter'   => 'M23 3a10.9 10.9 0 0 1-3.14 1.53 4.48 4.48 0 0 0-7.86 3v1A10.66 10.66 0 0 1 3 4s-4 9 5 13a11.64 11.64 0 0 1-7 2c9 5 20 0 20-11.5a4.5 4.5 0 0 0-.08-.83A7.72 7.72 0 0 0 23 3Z',
                            'linkedin'  => 'M6.94 5a2 2 0 1 1-4-.002 2 2 0 0 1 4 .002ZM7 8.48H3V21h4V8.48Zm6.32 0H9.34V21h3.94v-6.57c0-3.66 4.77-4 4.77 0V21H22v-7.93c0-6.17-7.06-5.94-8.68-2.91V8.48Z',
                            'whatsapp'  => 'M17.6 6.32A7.85 7.85 0 0 0 12.05 4a7.94 7.94 0 0 0-6.9 11.9L4 20l4.2-1.1a7.9 7.9 0 0 0 3.85 1h.01a7.94 7.94 0 0 0 7.94-7.94 7.9 7.9 0 0 0-2.4-5.64ZM12.06 18.4a6.5 6.5 0 0 1-3.33-.91l-.24-.14-2.49.65.67-2.43-.16-.25a6.55 6.55 0 1 1 12.16-3.4 6.55 6.55 0 0 1-6.61 6.48Z',
                            'telegram'  => 'M21.5 3.5 2.75 10.9c-1.28.51-1.28 1.24-.24 1.56l4.8 1.5 1.85 5.66c.22.6.37.84.75.84.3 0 .43-.14.6-.3l2.55-2.45 5.02 3.7c.92.5 1.58.24 1.82-.85l3.3-15.5c.34-1.34-.5-1.94-1.6-1.5Z',
                        ];
                    @endphp

                    <div class="space-y-6">
                        {{-- Core details --}}
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-sm font-semibold text-gray-900 mb-4">Core Details</h3>
                            <dl class="grid grid-cols-2 gap-x-8 gap-y-5">
                                <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Full Name</dt><dd class="mt-1 text-sm text-gray-800">{{ $customer->full_name }}</dd></div>
                                <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Customer Code</dt><dd class="mt-1 text-sm text-gray-800 font-mono">{{ $customer->customer_code }}</dd></div>
                                <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Email</dt><dd class="mt-1 text-sm text-gray-800">{{ $customer->email ?? '—' }}</dd></div>
                                <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Phone</dt><dd class="mt-1 text-sm text-gray-800">{{ $customer->phone }}</dd></div>
                                <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Alternative Phone</dt><dd class="mt-1 text-sm text-gray-800">{{ $customer->alternative_phone ?? '—' }}</dd></div>
                                <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Gender</dt><dd class="mt-1 text-sm text-gray-800">{{ $customer->gender ?? '—' }}</dd></div>
                                <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Date of Birth</dt><dd class="mt-1 text-sm text-gray-800">{{ $customer->date_of_birth?->format('d M Y') ?? '—' }}</dd></div>
                                <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Customer Group</dt><dd class="mt-1 text-sm text-gray-800">{{ $customer->customerGroup?->name ?? '—' }}</dd></div>
                                <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Reward Points</dt><dd class="mt-1 text-sm text-gray-800">{{ number_format($customer->reward_points) }}</dd></div>
                                <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Wallet Balance</dt><dd class="mt-1 text-sm text-gray-800">৳{{ number_format($customer->wallet_balance, 2) }}</dd></div>
                                <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Email Verified</dt><dd class="mt-1 text-sm text-gray-800">{{ $customer->email_verified_at?->format('d M Y, h:i A') ?? 'Not verified' }}</dd></div>
                                <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Phone Verified</dt><dd class="mt-1 text-sm text-gray-800">{{ $customer->phone_verified_at?->format('d M Y, h:i A') ?? 'Not verified' }}</dd></div>
                                <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Joined</dt><dd class="mt-1 text-sm text-gray-800">{{ $customer->created_at?->format('d M Y, h:i A') }}</dd></div>
                            </dl>
                        </div>

                        {{-- Social profiles --}}
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-sm font-semibold text-gray-900 mb-4">Social Profiles</h3>
                            @if (empty(array_filter($socials)))
                                <p class="text-sm text-gray-400">No social profiles added yet.</p>
                            @else
                                <div class="grid grid-cols-2 gap-x-8 gap-y-4">
                                    @foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'twitter' => 'Twitter / X', 'linkedin' => 'LinkedIn', 'whatsapp' => 'WhatsApp', 'telegram' => 'Telegram'] as $key => $label)
                                        @if (!empty($socials[$key]))
                                            <div class="flex items-center gap-2.5">
                                                <span class="w-7 h-7 rounded-lg bg-indigo-50 text-indigo-600 flex items-center justify-center shrink-0">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="{{ $socialIcons[$key] }}"/></svg>
                                                </span>
                                                <div class="min-w-0">
                                                    <dt class="text-xs font-medium text-gray-400">{{ $label }}</dt>
                                                    <dd class="text-sm text-gray-800 truncate">{{ $socials[$key] }}</dd>
                                                </div>
                                            </div>
                                        @endif
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Physical & personal details --}}
                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                                <h3 class="text-sm font-semibold text-gray-900 mb-4">Physical Details</h3>
                                <dl class="grid grid-cols-2 gap-x-6 gap-y-4">
                                    <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Height</dt><dd class="mt-1 text-sm text-gray-800">{{ isset($physical['height_cm']) ? $physical['height_cm'] . ' cm' : '—' }}</dd></div>
                                    <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Weight</dt><dd class="mt-1 text-sm text-gray-800">{{ isset($physical['weight_kg']) ? $physical['weight_kg'] . ' kg' : '—' }}</dd></div>
                                    <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Blood Group</dt><dd class="mt-1 text-sm text-gray-800">{{ $physical['blood_group'] ?? '—' }}</dd></div>
                                </dl>
                            </div>
                            <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                                <h3 class="text-sm font-semibold text-gray-900 mb-4">Personal Details</h3>
                                <dl class="grid grid-cols-2 gap-x-6 gap-y-4">
                                    <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Occupation</dt><dd class="mt-1 text-sm text-gray-800">{{ $personal['occupation'] ?? '—' }}</dd></div>
                                    <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Marital Status</dt><dd class="mt-1 text-sm text-gray-800">{{ $personal['marital_status'] ?? '—' }}</dd></div>
                                    <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Nationality</dt><dd class="mt-1 text-sm text-gray-800">{{ $personal['nationality'] ?? '—' }}</dd></div>
                                    <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">NID Number</dt><dd class="mt-1 text-sm text-gray-800 font-mono">{{ $personal['nid_number'] ?? '—' }}</dd></div>
                                    <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">Anniversary</dt><dd class="mt-1 text-sm text-gray-800">{{ !empty($personal['anniversary_date']) ? \Illuminate\Support\Carbon::parse($personal['anniversary_date'])->format('d M Y') : '—' }}</dd></div>
                                </dl>
                            </div>
                        </div>

                        {{-- Custom fields (admin-added, no fixed schema) — managed inline, independent of the Edit Customer modal --}}
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <div class="flex items-center justify-between mb-4">
                                <h3 class="text-sm font-semibold text-gray-900">Custom Fields</h3>
                                @if (!$customFieldsEditing)
                                    <button wire:click="openCustomFieldsEditor" type="button" class="inline-flex items-center gap-1.5 text-xs font-medium text-indigo-600 hover:text-indigo-700">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                                        </svg>
                                        Manage
                                    </button>
                                @endif
                            </div>

                            @php $custom = $meta['custom'] ?? []; @endphp

                            @if (!$customFieldsEditing)
                                @if (empty($custom))
                                    <p class="text-sm text-gray-400">No custom fields added yet. Click "Manage" to add any information not covered above.</p>
                                @else
                                    <dl class="grid grid-cols-2 gap-x-8 gap-y-4">
                                        @foreach ($custom as $key => $value)
                                            <div><dt class="text-xs font-medium text-gray-400 uppercase tracking-wide">{{ $key }}</dt><dd class="mt-1 text-sm text-gray-800">{{ $value }}</dd></div>
                                        @endforeach
                                    </dl>
                                @endif
                            @else
                                <div wire:key="custom-fields-editor">
                                    @error('editCustomFields') <p class="text-xs text-red-500 mb-2">{{ $message }}</p> @enderror
                                    <div class="space-y-2">
                                        @forelse ($editCustomFields as $i => $field)
                                            <div class="flex items-start gap-2" wire:key="custom-field-{{ $i }}">
                                                <div class="flex-1">
                                                    <input wire:model="editCustomFields.{{ $i }}.key" type="text" placeholder="Field name" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                                    @error('editCustomFields.' . $i . '.key') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                                </div>
                                                <div class="flex-1">
                                                    <input wire:model="editCustomFields.{{ $i }}.value" type="text" placeholder="Value" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                                    @error('editCustomFields.' . $i . '.value') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                                                </div>
                                                <button wire:click="removeCustomField({{ $i }})" type="button" class="w-9 h-9 shrink-0 flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        @empty
                                            <p class="text-sm text-gray-400">No custom fields yet. Click "Add Field" below to add anything not covered above.</p>
                                        @endforelse
                                    </div>

                                    <div class="flex items-center justify-between mt-4 pt-4 border-t border-gray-100">
                                        <button wire:click="addCustomField" type="button" class="inline-flex items-center gap-1 text-xs font-medium text-indigo-600 hover:text-indigo-700">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                            </svg>
                                            Add Field
                                        </button>
                                        <div class="flex items-center gap-2">
                                            <button wire:click="cancelCustomFieldsEditor" type="button" class="px-3 py-1.5 text-xs font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                                            <button wire:click="saveCustomFields" type="button" class="px-4 py-1.5 text-xs font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">Save</button>
                                        </div>
                                    </div>
                                </div>
                            @endif
                        </div>

                        {{-- Notes --}}
                        <div class="bg-white rounded-2xl shadow-sm border border-gray-200 p-6">
                            <h3 class="text-sm font-semibold text-gray-900 mb-4">Notes</h3>
                            <p class="text-sm text-gray-600 whitespace-pre-line">{{ $customer->notes ?? 'No notes added yet.' }}</p>
                        </div>
                    </div>
                @else
                    @include('livewire.admin.users.partials.no-customer')
                @endif
            @endif

            @if($tab === 'devices')
                @if($customer || $user)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/40">
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Device</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Platform / Browser</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Activity</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($devices as $device)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-5 py-3">
                                            <span class="block text-sm font-medium text-gray-800">{{ $device->device_brand ?? ucfirst($device->device_type) }} {{ $device->device_model }}</span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-gray-100 text-gray-600 mt-1 capitalize">{{ $device->device_type }}</span>
                                        </td>
                                        <td class="px-5 py-3">
                                            <span class="block text-sm text-gray-600">{{ $device->operating_system }} {{ $device->os_version }}</span>
                                            <span class="block text-xs text-gray-400">{{ $device->browser }} {{ $device->browser_version }}</span>
                                        </td>
                                        <td class="px-5 py-3">
                                            @if($device->is_active_now)
                                                <span class="inline-flex items-center gap-1.5 text-sm font-medium text-emerald-600">
                                                    <span class="w-1.5 h-1.5 rounded-full bg-emerald-500 animate-pulse"></span>
                                                    {{ $device->activity_label }}
                                                </span>
                                            @else
                                                <span class="text-sm text-gray-500">{{ $device->activity_label }}</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3 text-center">
                                            @if($device->is_trusted)<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-emerald-50 text-emerald-600">Trusted</span>@endif
                                            @if($device->is_blocked)<span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-500">Blocked</span>@endif
                                        </td>
                                        <td class="px-5 py-3 text-right">
                                            <a href="{{ route('admin.users.devices.show', $device->id) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">View →</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-5 py-16 text-center text-sm text-gray-500">No devices linked to this customer yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($devices->hasPages())<div class="px-5 py-3 border-t border-gray-100">{{ $devices->links() }}</div>@endif
                </div>
                @else
                    @include('livewire.admin.users.partials.no-customer')
                @endif
            @endif

            @if($tab === 'ips')
                @if($customer || $user)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/40">
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">IP Address</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Device</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Hits</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">First Seen</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Last Seen</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($ipAddresses as $ip)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-5 py-3 text-sm text-gray-800 font-mono">{{ $ip->ip_address }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-500">{{ $ip->device?->device_brand ?? ucfirst($ip->device?->device_type ?? '—') }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-600 text-center">{{ $ip->hits }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-500">{{ $ip->first_seen_at?->format('d M Y, h:i A') }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-500">{{ $ip->last_seen_at?->format('d M Y, h:i A') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-5 py-16 text-center text-sm text-gray-500">No IP address history recorded yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($ipAddresses->hasPages())<div class="px-5 py-3 border-t border-gray-100">{{ $ipAddresses->links() }}</div>@endif
                </div>
                @else
                    @include('livewire.admin.users.partials.no-customer')
                @endif
            @endif

            @if($tab === 'visits')
                @if($customer || $user)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/40">
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">URL</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Content</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Visited At</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($visits as $visit)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-5 py-3 text-sm text-gray-800 break-all max-w-xs">{{ $visit->url }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-500">{{ $visit->content_type ? ucfirst($visit->content_type) . ': ' . $visit->content_title : '—' }}</td>
                                        <td class="px-5 py-3 text-sm text-center">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono {{ $visit->status_code && $visit->status_code < 400 ? 'bg-emerald-50 text-emerald-600' : 'bg-red-50 text-red-500' }}">{{ $visit->status_code ?? '—' }}</span>
                                        </td>
                                        <td class="px-5 py-3 text-sm text-gray-500">{{ $visit->created_at?->format('d M Y, h:i A') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-5 py-16 text-center text-sm text-gray-500">No visits recorded yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($visits->hasPages())<div class="px-5 py-3 border-t border-gray-100">{{ $visits->links() }}</div>@endif
                </div>
                @else
                    @include('livewire.admin.users.partials.no-customer')
                @endif
            @endif

            @if($tab === 'orders')
                @if($customer)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
                        <select wire:model.live="orderStatus" class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="">All Statuses</option>
                            @foreach(\App\Enums\Sales\OrderStatus::cases() as $status)
                                <option value="{{ $status->value }}">{{ $status->label() }}</option>
                            @endforeach
                        </select>
                        <input wire:model.live="orderFrom" type="date" class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <span class="text-xs text-gray-400">to</span>
                        <input wire:model.live="orderTo" type="date" class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/40">
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Order</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Placed</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($orders as $order)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-5 py-3 text-sm font-medium text-gray-800">#{{ $order->id }}</td>
                                        <td class="px-5 py-3 text-center">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $order->status->badgeClass() }}">{{ $order->status->label() }}</span>
                                        </td>
                                        <td class="px-5 py-3 text-sm text-gray-700 text-right">৳{{ number_format($order->total_amount, 2) }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-500">{{ $order->created_at?->format('d M Y, h:i A') }}</td>
                                        <td class="px-5 py-3 text-right">
                                            <a href="{{ route('admin.sales.orders.show', $order->id) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">View →</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-5 py-16 text-center text-sm text-gray-500">No orders found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($orders->hasPages())<div class="px-5 py-3 border-t border-gray-100">{{ $orders->links() }}</div>@endif
                </div>
                @else
                    @include('livewire.admin.users.partials.no-customer')
                @endif
            @endif

            @if($tab === 'products')
                @if($customer)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="flex flex-wrap items-center gap-3 px-5 py-4 border-b border-gray-100">
                        <input wire:model.live.debounce.300ms="productSearch" type="text" placeholder="Search by product name or SKU…"
                            class="flex-1 min-w-[200px] text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <input wire:model.live="productFrom" type="date" class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <span class="text-xs text-gray-400">to</span>
                        <input wire:model.live="productTo" type="date" class="text-sm rounded-lg border border-gray-300 px-3 py-2 focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/40">
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Product</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Order</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Qty</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Unit Price</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Total</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($orderedProducts as $item)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-5 py-3">
                                            <span class="block text-sm font-medium text-gray-800">{{ $item->product_name }}</span>
                                            @if($item->variant_name)<span class="block text-xs text-gray-400">{{ $item->variant_name }}</span>@endif
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-gray-100 text-gray-600 mt-1">{{ $item->sku }}</span>
                                        </td>
                                        <td class="px-5 py-3">
                                            <a href="{{ route('admin.sales.orders.show', $item->order_id) }}" class="text-sm text-indigo-600 hover:text-indigo-700">#{{ $item->order_id }}</a>
                                        </td>
                                        <td class="px-5 py-3 text-sm text-gray-600 text-center">{{ (float) $item->quantity }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-700 text-right">৳{{ number_format($item->unit_price, 2) }}</td>
                                        <td class="px-5 py-3 text-sm font-medium text-gray-800 text-right">৳{{ number_format($item->total_amount, 2) }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-500">{{ $item->order?->created_at?->format('d M Y') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-5 py-16 text-center text-sm text-gray-500">No ordered products found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($orderedProducts->hasPages())<div class="px-5 py-3 border-t border-gray-100">{{ $orderedProducts->links() }}</div>@endif
                </div>
                @else
                    @include('livewire.admin.users.partials.no-customer')
                @endif
            @endif

            @if($tab === 'carts')
                @if($customer)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/40">
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Cart</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Items</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Subtotal</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Created</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($carts as $cart)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-5 py-3 text-sm font-medium text-gray-800">#{{ $cart->id }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-600 text-center">{{ $cart->items_count }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-700 text-right">৳{{ number_format($cart->subtotal, 2) }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-500">{{ $cart->created_at?->format('d M Y, h:i A') }}</td>
                                        <td class="px-5 py-3 text-right">
                                            <a href="{{ route('admin.customers.carts.show', $cart->id) }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-700">View →</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-5 py-16 text-center text-sm text-gray-500">No carts found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($carts->hasPages())<div class="px-5 py-3 border-t border-gray-100">{{ $carts->links() }}</div>@endif
                </div>
                @else
                    @include('livewire.admin.users.partials.no-customer')
                @endif
            @endif

            @if($tab === 'combos')
                @if($customer)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/40">
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Combo</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Items</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Qty</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Price</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Created</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($combos as $combo)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-5 py-3 text-sm font-medium text-gray-800">{{ $combo->name }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-600 text-center">{{ $combo->items_count }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-600 text-center">{{ (float) $combo->quantity }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-700 text-right">৳{{ number_format($combo->price, 2) }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-500">{{ $combo->created_at?->format('d M Y, h:i A') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="px-5 py-16 text-center text-sm text-gray-500">No combos found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($combos->hasPages())<div class="px-5 py-3 border-t border-gray-100">{{ $combos->links() }}</div>@endif
                </div>
                @else
                    @include('livewire.admin.users.partials.no-customer')
                @endif
            @endif

            @if($tab === 'wishlist')
                @if($customer)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/40">
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Product</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Price</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Added</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($wishlistItems as $wi)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-5 py-3 text-sm font-medium text-gray-800">{{ $wi->product?->name ?? '—' }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-700 text-right">৳{{ number_format($wi->product?->price ?? 0, 2) }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-500">{{ $wi->created_at?->format('d M Y, h:i A') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="px-5 py-16 text-center text-sm text-gray-500">No wishlist items found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($wishlistItems->hasPages())<div class="px-5 py-3 border-t border-gray-100">{{ $wishlistItems->links() }}</div>@endif
                </div>
                @else
                    @include('livewire.admin.users.partials.no-customer')
                @endif
            @endif

            @if($tab === 'addresses')
                @if($customer)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/40">
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Type</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Name / Phone</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Address</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Default</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($addresses as $address)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-5 py-3 text-sm text-gray-600 capitalize">{{ $address->address_type }}</td>
                                        <td class="px-5 py-3">
                                            <span class="block text-sm text-gray-800">{{ $address->name }}</span>
                                            <span class="block text-xs text-gray-400">{{ $address->phone }}</span>
                                        </td>
                                        <td class="px-5 py-3 text-sm text-gray-600 max-w-sm">{{ $address->full_address }}</td>
                                        <td class="px-5 py-3 text-center">
                                            @if($address->is_default_billing)<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-indigo-50 text-indigo-600 mr-1">Billing</span>@endif
                                            @if($address->is_default_shipping)<span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-emerald-50 text-emerald-600">Shipping</span>@endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-5 py-16 text-center text-sm text-gray-500">No addresses found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($addresses->hasPages())<div class="px-5 py-3 border-t border-gray-100">{{ $addresses->links() }}</div>@endif
                </div>
                @else
                    @include('livewire.admin.users.partials.no-customer')
                @endif
            @endif

            @if($tab === 'pos')
                @if($user)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/40">
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Register</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Opening Cash</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Closing Cash</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Opened</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Closed</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($posSessions as $session)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-5 py-3 text-sm font-medium text-gray-800">{{ $session->register?->name ?? '—' }}</td>
                                        <td class="px-5 py-3 text-center">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium capitalize {{ $session->status->value === 'open' ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-100 text-gray-500' }}">{{ $session->status->value }}</span>
                                        </td>
                                        <td class="px-5 py-3 text-sm text-gray-700 text-right">৳{{ number_format($session->opening_cash, 2) }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-700 text-right">{{ $session->closing_cash !== null ? '৳' . number_format($session->closing_cash, 2) : '—' }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-500">{{ $session->opened_at?->format('d M Y, h:i A') }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-500">{{ $session->closed_at?->format('d M Y, h:i A') ?? '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="px-5 py-16 text-center text-sm text-gray-500">No POS sessions found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($posSessions->hasPages())<div class="px-5 py-3 border-t border-gray-100">{{ $posSessions->links() }}</div>@endif
                </div>
                @else
                    @include('livewire.admin.users.partials.no-user')
                @endif
            @endif

            @if($tab === 'tokens')
                @if($user)
                <div class="space-y-6">
                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100"><h3 class="text-sm font-semibold text-gray-700">Device Tokens (Push Notifications)</h3></div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="border-b border-gray-100 bg-gray-50/40">
                                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Platform</th>
                                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Token</th>
                                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Last Used</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($deviceTokens as $token)
                                        <tr class="hover:bg-gray-50/50 transition">
                                            <td class="px-5 py-3 text-sm text-gray-600 capitalize">{{ $token->platform }}</td>
                                            <td class="px-5 py-3 text-sm text-gray-500 font-mono break-all max-w-md">{{ Str::limit($token->token, 40) }}</td>
                                            <td class="px-5 py-3 text-sm text-gray-500">{{ $token->last_used_at?->format('d M Y, h:i A') ?? '—' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="3" class="px-5 py-10 text-center text-sm text-gray-500">No device tokens found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($deviceTokens->hasPages())<div class="px-5 py-3 border-t border-gray-100">{{ $deviceTokens->links() }}</div>@endif
                    </div>

                    <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                        <div class="px-5 py-3 border-b border-gray-100"><h3 class="text-sm font-semibold text-gray-700">Push Subscriptions (Web)</h3></div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full">
                                <thead>
                                    <tr class="border-b border-gray-100 bg-gray-50/40">
                                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Endpoint</th>
                                        <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Created</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-100">
                                    @forelse($pushSubscriptions as $sub)
                                        <tr class="hover:bg-gray-50/50 transition">
                                            <td class="px-5 py-3 text-sm text-gray-500 font-mono break-all max-w-md">{{ Str::limit($sub->endpoint, 60) }}</td>
                                            <td class="px-5 py-3 text-sm text-gray-500">{{ $sub->created_at?->format('d M Y, h:i A') }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="2" class="px-5 py-10 text-center text-sm text-gray-500">No push subscriptions found.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if($pushSubscriptions->hasPages())<div class="px-5 py-3 border-t border-gray-100">{{ $pushSubscriptions->links() }}</div>@endif
                    </div>
                </div>
                @else
                    @include('livewire.admin.users.partials.no-user')
                @endif
            @endif

            @if($tab === 'activity')
                @if($customer || $user)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/40">
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Event</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Description</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Causer</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Date</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($activities as $activity)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-5 py-3 text-sm text-gray-600 capitalize">{{ $activity->event }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-800">{{ $activity->description }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-500">{{ $activity->causer?->name ?? 'System' }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-500">{{ $activity->created_at?->format('d M Y, h:i A') }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="px-5 py-16 text-center text-sm text-gray-500">No activity recorded yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if($activities->hasPages())<div class="px-5 py-3 border-t border-gray-100">{{ $activities->links() }}</div>@endif
                </div>
                @else
                    @include('livewire.admin.users.partials.no-customer')
                @endif
            @endif

            @if($tab === 'marketing')
                @if($customer || $user)
                    @livewire('admin.users.user-marketing', [
                        'customerId' => $customer?->id,
                        'userId' => $user?->id,
                        'deviceIds' => $deviceIds->all(),
                    ], key('user-marketing-' . ($customer?->id ?? 'none') . '-' . ($user?->id ?? 'none')))
                @else
                    @include('livewire.admin.users.partials.no-customer')
                @endif
            @endif

            @if($tab === 'blocks')
                @if($customer || $user)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-200 overflow-hidden">
                    <div class="flex items-center justify-between px-5 py-4 border-b border-gray-100">
                        <h3 class="text-sm font-semibold text-gray-700">Blocks on this account, its devices, and their IPs</h3>
                        <button wire:click="openBlockModal" type="button"
                            class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                            </svg>
                            Add Block
                        </button>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="min-w-full">
                            <thead>
                                <tr class="border-b border-gray-100 bg-gray-50/40">
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Target</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Scope</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Reason</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Blocked By</th>
                                    <th class="px-5 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wide">Expires</th>
                                    <th class="px-5 py-3 text-center text-xs font-semibold text-gray-500 uppercase tracking-wide">Status</th>
                                    <th class="px-5 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wide">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @forelse($blocks as $block)
                                    <tr class="hover:bg-gray-50/50 transition">
                                        <td class="px-5 py-3 text-sm text-gray-800">
                                            @if($block->ip_address)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-gray-100 text-gray-600">IP</span>
                                                <span class="font-mono">{{ $block->ip_address }}</span>
                                            @elseif($block->blockable_type === \App\Models\Device::class)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-gray-100 text-gray-600">Device</span>
                                                {{ $block->blockable?->device_brand ?? ucfirst($block->blockable?->device_type ?? '—') }}
                                            @elseif($block->blockable_type === \App\Models\Customer::class)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-gray-100 text-gray-600">Customer</span>
                                                {{ $block->blockable?->full_name ?? '—' }}
                                            @elseif($block->blockable_type === \App\Models\User::class)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-mono bg-gray-100 text-gray-600">User</span>
                                                {{ $block->blockable?->name ?? '—' }}
                                            @endif
                                        </td>
                                        <td class="px-5 py-3">
                                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium {{ $block->scope === 'full_site' ? 'bg-red-50 text-red-600' : 'bg-amber-50 text-amber-600' }}">{{ $block->scopeLabel() }}</span>
                                        </td>
                                        <td class="px-5 py-3 text-sm text-gray-600 max-w-xs">{{ $block->reason ?? '—' }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-500">{{ $block->blockedBy?->name ?? '—' }}</td>
                                        <td class="px-5 py-3 text-sm text-gray-500">{{ $block->expires_at?->format('d M Y, h:i A') ?? 'Never' }}</td>
                                        <td class="px-5 py-3 text-center">
                                            @if(! $block->is_active)
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Disabled</span>
                                            @elseif($block->isExpired())
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-gray-100 text-gray-500">Expired</span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-medium bg-red-50 text-red-600">Active</span>
                                            @endif
                                        </td>
                                        <td class="px-5 py-3">
                                            <div class="flex items-center justify-end gap-1">
                                                <button wire:click="toggleBlock({{ $block->id }})" type="button"
                                                    title="{{ $block->is_active ? 'Disable' : 'Re-enable' }}"
                                                    class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-amber-50 hover:text-amber-600 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 9V5.25A3.75 3.75 0 0 1 12 1.5v0a3.75 3.75 0 0 1 3.75 3.75V9m-9 3.75h10.5a1.5 1.5 0 0 1 1.5 1.5v6a1.5 1.5 0 0 1-1.5 1.5H6.75a1.5 1.5 0 0 1-1.5-1.5v-6a1.5 1.5 0 0 1 1.5-1.5Z"/>
                                                    </svg>
                                                </button>
                                                <button type="button" x-data
                                                    @click="Swal.fire({
                                                        title: 'Remove block?',
                                                        text: 'This block record will be deleted permanently.',
                                                        icon: 'warning',
                                                        showCancelButton: true,
                                                        confirmButtonColor: '#ef4444',
                                                        confirmButtonText: 'Delete'
                                                    }).then(r => { if (r.isConfirmed) $wire.deleteBlock({{ $block->id }}) })"
                                                    class="w-8 h-8 inline-flex items-center justify-center rounded-lg text-gray-400 hover:bg-red-50 hover:text-red-500 transition">
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0"/>
                                                    </svg>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="px-5 py-16 text-center text-sm text-gray-500">No blocks on this account, its devices, or their IPs.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @else
                    @include('livewire.admin.users.partials.no-customer')
                @endif
            @endif

        </div>
    </div>

    {{-- Add Block Modal --}}
    @if($customer || $user)
    <div x-cloak x-data="{ open: @entangle('blockModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-lg bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col" @click.outside="open = false">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 shrink-0">
                <div class="w-8 h-8 rounded-lg bg-red-100 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/>
                    </svg>
                </div>
                <div class="flex-1"><h2 class="text-base font-semibold text-gray-900">Add Block</h2></div>
                <button @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit.prevent="createBlock" class="overflow-y-auto px-6 py-5 space-y-4">
                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Block Target <span class="text-red-500">*</span></label>
                    <select wire:model.live="blockTargetType" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @if(($blockableDevices ?? collect())->isNotEmpty())<option value="device">This device</option>@endif
                        @if($customer)<option value="customer">This customer (blocks every device they've used)</option>@endif
                        @if($user)<option value="user">This user account (blocks every device they've used)</option>@endif
                        <option value="ip">A specific IP address</option>
                    </select>
                </div>

                @if($blockTargetType === 'device')
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Which Device <span class="text-red-500">*</span></label>
                        <select wire:model="blockDeviceId" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="">— Select —</option>
                            @foreach(($blockableDevices ?? []) as $d)
                                <option value="{{ $d->id }}">{{ $d->device_brand ?? ucfirst($d->device_type) }} · {{ $d->browser }} (#{{ $d->id }})</option>
                            @endforeach
                        </select>
                        @error('blockDeviceId') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                @endif

                @if($blockTargetType === 'ip')
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">IP Address <span class="text-red-500">*</span></label>
                        <input wire:model="blockIp" type="text" placeholder="203.0.113.4" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('blockIp') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                @endif

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Scope <span class="text-red-500">*</span></label>
                    <select wire:model="blockScope" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        <option value="full_site">Full Site — hides the entire storefront (404)</option>
                        <option value="orders">Orders — can't view order tracking</option>
                        <option value="checkout">Checkout — can browse/cart but not place orders</option>
                        <option value="account_panel">Account Panel — can't access the admin panel</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Reason</label>
                    <textarea wire:model="blockReason" rows="2" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                    @error('blockReason') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div>
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Expires At <span class="text-gray-400">(leave empty for permanent)</span></label>
                    <input wire:model="blockExpiresAt" type="datetime-local" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    @error('blockExpiresAt') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 transition">Create Block</button>
                </div>
            </form>
        </div>
    </div>
    @endif

    {{-- Edit Modal --}}
    @if($customer)
    <div x-cloak x-data="{ open: @entangle('editModal') }" x-show="open" x-transition
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/60 p-4" role="dialog">
        <div class="w-full max-w-3xl bg-white rounded-2xl shadow-2xl overflow-hidden max-h-[90vh] flex flex-col" @click.outside="open = false">
            <div class="flex items-center gap-3 px-6 py-4 border-b border-gray-100 shrink-0">
                <div class="w-8 h-8 rounded-lg bg-indigo-100 flex items-center justify-center shrink-0">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-indigo-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125"/>
                    </svg>
                </div>
                <div class="flex-1"><h2 class="text-base font-semibold text-gray-900">Edit Customer</h2></div>
                <button @click="open = false" type="button" class="w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:bg-gray-100 transition">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form wire:submit.prevent="updateCustomer" class="overflow-y-auto px-6 py-5 space-y-4">
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Customer Code <span class="text-red-500">*</span></label>
                        <input wire:model="editCode" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('editCode') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Customer Group</label>
                        <select wire:model="editCustomerGroupId" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="">— None —</option>
                            @foreach($customerGroups as $group)
                                <option value="{{ $group->id }}">{{ $group->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">First Name <span class="text-red-500">*</span></label>
                        <input wire:model="editFirstName" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('editFirstName') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Last Name</label>
                        <input wire:model="editLastName" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Phone <span class="text-red-500">*</span></label>
                        <input wire:model="editPhone" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('editPhone') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Email</label>
                        <input wire:model="editEmail" type="email" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                        @error('editEmail') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Gender</label>
                        <select wire:model="editGender" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="">— Not specified —</option>
                            <option value="Male">Male</option>
                            <option value="Female">Female</option>
                            <option value="Other">Other</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Date of Birth</label>
                        <input wire:model="editDateOfBirth" type="date" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-1.5">Status</label>
                        <select wire:model="editStatus" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            <option value="active">Active</option>
                            <option value="inactive">Inactive</option>
                            <option value="blocked">Blocked</option>
                        </select>
                    </div>
                </div>

                {{-- Social profiles --}}
                <div class="pt-2 border-t border-gray-100">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Social Profiles</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Facebook</label>
                            <input wire:model="editFacebook" type="text" placeholder="facebook.com/username" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('editFacebook') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Instagram</label>
                            <input wire:model="editInstagram" type="text" placeholder="@username" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('editInstagram') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Twitter / X</label>
                            <input wire:model="editTwitter" type="text" placeholder="@username" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('editTwitter') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">LinkedIn</label>
                            <input wire:model="editLinkedin" type="text" placeholder="linkedin.com/in/username" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('editLinkedin') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">WhatsApp</label>
                            <input wire:model="editWhatsapp" type="text" placeholder="+8801XXXXXXXXX" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('editWhatsapp') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Telegram</label>
                            <input wire:model="editTelegram" type="text" placeholder="@username" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('editTelegram') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Physical details --}}
                <div class="pt-2 border-t border-gray-100">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Physical Details</h3>
                    <div class="grid grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Height (cm)</label>
                            <input wire:model="editHeightCm" type="number" step="0.1" min="0" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('editHeightCm') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Weight (kg)</label>
                            <input wire:model="editWeightKg" type="number" step="0.1" min="0" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('editWeightKg') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Blood Group</label>
                            <select wire:model="editBloodGroup" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <option value="">— Unknown —</option>
                                @foreach (['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'] as $bg)
                                    <option value="{{ $bg }}">{{ $bg }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                {{-- Personal details --}}
                <div class="pt-2 border-t border-gray-100">
                    <h3 class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-3">Personal Details</h3>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Occupation</label>
                            <input wire:model="editOccupation" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('editOccupation') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Marital Status</label>
                            <select wire:model="editMaritalStatus" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                                <option value="">— Not specified —</option>
                                <option value="Single">Single</option>
                                <option value="Married">Married</option>
                                <option value="Divorced">Divorced</option>
                                <option value="Widowed">Widowed</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Nationality</label>
                            <input wire:model="editNationality" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('editNationality') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">NID Number</label>
                            <input wire:model="editNidNumber" type="text" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm font-mono focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('editNidNumber') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-medium text-gray-600 mb-1.5">Anniversary Date</label>
                            <input wire:model="editAnniversaryDate" type="date" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500">
                            @error('editAnniversaryDate') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>

                {{-- Notes --}}
                <div class="pt-2 border-t border-gray-100">
                    <label class="block text-xs font-medium text-gray-600 mb-1.5">Notes</label>
                    <textarea wire:model="editNotes" rows="3" class="w-full rounded-lg border border-gray-300 px-3 py-2.5 text-sm focus:border-indigo-500 focus:ring-1 focus:ring-indigo-500"></textarea>
                    @error('editNotes') <p class="text-xs text-red-500 mt-1">{{ $message }}</p> @enderror
                </div>

                <div class="flex items-center justify-end gap-2 pt-2 border-t border-gray-100">
                    <button @click="open = false" type="button" class="px-4 py-2 text-sm font-medium text-gray-600 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition">Cancel</button>
                    <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-indigo-600 rounded-lg hover:bg-indigo-700 transition">Save Changes</button>
                </div>
            </form>
        </div>
    </div>
    @endif

</div>
