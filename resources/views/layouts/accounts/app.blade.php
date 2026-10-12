<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('layouts.admin.partials.head-meta')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700&family=Hind+Siliguri:wght@400;500;600;700&display=swap" rel="stylesheet">
    @vite(['resources/sass/admin.scss', 'resources/css/admin.css', 'resources/js/admin.js'])
    @livewireStyles
    @stack('styles')
</head>

{{-- Accounts Panel shell (/accounts) — dark slate sidebar with the Accounts
     menu, white top bar, page content on gray-50. --}}
<body x-data="{ navOpen: false }" class="min-h-screen bg-gray-50 font-['Plus_Jakarta_Sans','Hind_Siliguri',sans-serif] antialiased">

    <form method="POST" action="{{ route('logout') }}" x-ref="logoutForm" class="hidden">
        @csrf
    </form>

    <div class="flex min-h-screen">

        {{-- Sidebar — shared with the profile pages (design: Main.dc.html / Sidebar.dc.html) --}}
        <aside class="fixed inset-y-0 left-0 z-30 flex w-[248px] -translate-x-full flex-col overflow-y-auto bg-[#0f172a] transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
               :class="{ 'translate-x-0!': navOpen }">
            <x-accounts.sidebar active="dashboard" />
        </aside>

        {{-- Mobile sidebar backdrop --}}
        <div x-cloak x-show="navOpen" x-transition.opacity @click="navOpen = false"
             class="fixed inset-0 z-20 bg-gray-900/50 lg:hidden"></div>

        <div class="flex min-w-0 flex-1 flex-col">

            {{-- Top bar --}}
            <header class="sticky top-0 z-10 flex h-16 items-center justify-between gap-3 border-b border-gray-200 bg-white px-4 sm:px-6">
                <button type="button" @click="navOpen = !navOpen" aria-label="Toggle menu"
                        class="text-gray-500 hover:text-gray-700 lg:hidden">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 12h16m-7 6h7"/></svg>
                </button>
                <div class="hidden text-[13px] text-gray-500 lg:block">{{ now()->format('l, d M Y') }}</div>

                <div class="flex items-center gap-2">
                @livewire('admin.topbar.notifications')

                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = !open" @click.outside="open = false"
                            class="flex items-center gap-2 rounded-full p-1 pr-2 hover:bg-gray-100">
                        <img src="{{ 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=111827&color=ffffff&bold=true' }}"
                             alt="Profile" class="h-8 w-8 rounded-full border border-gray-200 object-cover">
                        <span class="hidden max-w-[10rem] truncate text-sm font-medium text-gray-700 sm:block">{{ auth()->user()->name }}</span>
                        <svg class="hidden h-4 w-4 text-gray-400 transition-transform sm:block" :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div x-cloak x-show="open" x-transition
                         class="absolute right-0 z-50 mt-2 w-56 overflow-hidden rounded-lg border border-gray-200 bg-white shadow-lg">
                        <div class="border-b border-gray-100 px-4 py-3">
                            <p class="truncate text-sm font-semibold text-gray-800">{{ auth()->user()->name }}</p>
                            <p class="truncate text-xs text-gray-400">{{ auth()->user()->email }}</p>
                        </div>
                        <button type="button" @click="$refs.logoutForm.submit()"
                                class="w-full px-4 py-2.5 text-left text-sm text-red-500 hover:bg-gray-50">Logout</button>
                    </div>
                </div>
                </div>
            </header>

            @if (app()->isDownForMaintenance())
                <div class="bg-red-600 px-4 py-2 text-center text-sm font-medium text-white">
                    Maintenance Mode is ON — the public site is inaccessible to visitors.
                    @if (auth()->user()->hasPanel('admin'))
                        <a href="{{ route('admin.site-settings') }}" class="ml-1 underline hover:no-underline">Turn it off</a>
                    @endif
                </div>
            @endif

            <main class="min-w-0 flex-1 p-4 sm:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            Livewire.on('toast', (data) => {
                Toast.fire({ icon: data[0].type, title: data[0].message });
            });
        });
    </script>
    @livewireScripts
    @stack('scripts')
</body>

</html>
