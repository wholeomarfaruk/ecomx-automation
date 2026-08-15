<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Laravel Starter Kit</title>
    @vite(['resources/sass/admin.scss', 'resources/css/admin.css', 'resources/js/admin.js'])
    @livewireStyles
    @stack('styles')
</head>

<body x-data class=" mx-auto antialiased flex justify-between">

    {{-- Shared logout form, referenced by sidebar and topbar quick-profile menus --}}
    <form method="POST" action="{{ route('logout') }}" x-ref="logoutForm" class="hidden">
        @csrf
    </form>

    {{-- Sidebar --}}
    @include('layouts.admin.partials.sidebar')

    {{-- Mobile sidebar backdrop --}}
    <div x-cloak x-show="$store.sidebar.navOpen" x-transition.opacity
        @click="$store.sidebar.navOpen = false"
        class="sm:hidden fixed inset-0 z-20 bg-gray-900/50"></div>

    {{-- Page Content --}}


    <div class="min-h-screen flex-1 w-full min-w-0 p-4 sm:p-6 bg-gray-100" comment="Page Content">

        @if (app()->isDownForMaintenance())
            <div class="-mx-4 sm:-mx-6 -mt-4 sm:-mt-6 mb-4 sm:mb-6 bg-red-600 text-white text-sm font-medium text-center py-2 px-4">
                Maintenance Mode is ON — the public site is inaccessible to visitors.
                <a href="{{ route('admin.site-settings') }}" class="underline hover:no-underline ml-1">Turn it off</a>
            </div>
        @endif

        @include('layouts.admin.partials.topbar')

        {{ $slot }}
    </div>


    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('toast', (data) => {
                Toast.fire({ icon: data[0].type, title: data[0].message });
            });
        });
    </script>

    <script>
        document.addEventListener('alpine:init', () => {
            // Stores variable globally
            Alpine.store('sidebar', {
                full: localStorage.getItem('sidebar_full') === 'true',
                active: 'dashboard',
                navOpen: false,
            });
            Alpine.store('pageName', {
                slug: '',
                name: '',

            });
            // Creating component Dropdown
            Alpine.data('dropdown', () => ({
                open: false,
                init() {
                    // Close children when sidebar collapses
                    this.$watch('$store.sidebar.full', val => {
                        if (!val) this.open = false;
                    });
                },
                toggle(tab) {
                    this.open = !this.open;
                    Alpine.store('sidebar').active = tab;
                },
                activeClass: 'bg-gray-800 text-gray-200',
                expandedClass: 'border-l border-gray-400 ml-4 pl-4',
                shrinkedClass: 'sm:absolute top-0 left-20 sm:shadow-md sm:z-10 sm:bg-gray-900 sm:rounded-md sm:p-4 border-l sm:border-none border-gray-400 ml-4 pl-4 sm:ml-0 w-28'
            }));
            // Creating component Sub Dropdown
            Alpine.data('sub_dropdown', () => ({
                sub_open: false,
                sub_toggle() {
                    this.sub_open = !this.sub_open;
                },
                sub_expandedClass: 'border-l border-gray-400 ml-4 pl-4',
                sub_shrinkedClass: 'sm:absolute top-0 left-28 sm:shadow-md sm:z-10 sm:bg-gray-900 sm:rounded-md sm:p-4 border-l sm:border-none border-gray-400 ml-4 pl-4 sm:ml-0 w-28'
            }));
            // Creating tooltip
            Alpine.data('tooltip', () => ({
                show: false,
                visibleClass: 'block sm:absolute -top-7 sm:border border-gray-800 left-5 sm:text-sm sm:bg-gray-900 sm:px-2 sm:py-1 sm:rounded-md'
            }))

        })
    </script>
    @livewire('admin.file.media-picker')
    @livewireScripts

    @php
        $vapidPublicKey = \App\Models\PushGatewayConfig::forDriver('web_push')->credentials['vapid_public_key'] ?? null;
    @endphp
    @if ($vapidPublicKey)
        <script>
            (function () {
                if (!('serviceWorker' in navigator) || !('PushManager' in window)) {
                    return;
                }

                function urlBase64ToUint8Array(base64String) {
                    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
                    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
                    const rawData = window.atob(base64);
                    return Uint8Array.from([...rawData].map((char) => char.charCodeAt(0)));
                }

                navigator.serviceWorker.register('/service-worker.js').then(function (registration) {
                    return registration.pushManager.getSubscription().then(function (existing) {
                        if (existing) {
                            return existing;
                        }

                        return Notification.requestPermission().then(function (permission) {
                            if (permission !== 'granted') {
                                return null;
                            }

                            return registration.pushManager.subscribe({
                                userVisibleOnly: true,
                                applicationServerKey: urlBase64ToUint8Array(@json($vapidPublicKey)),
                            });
                        });
                    });
                }).then(function (subscription) {
                    if (!subscription) {
                        return;
                    }

                    fetch(@json(route('admin.push.subscribe')), {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                        },
                        body: JSON.stringify(subscription),
                    });
                }).catch(function () {
                    // Best-effort — push is a progressive enhancement, never block the page on failure.
                });
            })();
        </script>
    @endif

    @stack('scripts')
</body>

</html>
