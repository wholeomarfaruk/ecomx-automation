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
    <style>
        /* Profile pages' base from the design files, scoped to .acc-profiles and kept in
           Tailwind's base layer so utility classes still win over it. */
        @layer base {
            :where(.acc-profiles) { font-size: 14px; color: #111827; }
            :where(.acc-profiles) a { color: #4338ca; text-decoration: none; }
            :where(.acc-profiles) a:hover { color: #312e81; }
            :where(.acc-profiles) table { border-collapse: collapse; width: 100%; }
            :where(.acc-profiles) button { cursor: pointer; }
        }
        @media print {
            aside, header, .acc-no-print { display: none !important; }
            body, main { background: #fff !important; padding: 0 !important; }
        }
    </style>
    @stack('styles')
</head>

{{-- Accounts Panel shell (/accounts) — dark slate sidebar with the Accounts
     menu, white top bar, page content on gray-50. --}}
<body x-data="{ navOpen: false }" class="min-h-screen bg-[#f1f3f7] text-[#111827] font-['Plus_Jakarta_Sans','Hind_Siliguri',sans-serif] antialiased">

    <form method="POST" action="{{ route('logout') }}" x-ref="logoutForm" class="hidden">
        @csrf
    </form>

    <div class="flex min-h-screen">

        {{-- Sidebar — shared with the profile pages (design: Main.dc.html / Sidebar.dc.html) --}}
        <aside class="fixed inset-y-0 left-0 z-30 flex w-[248px] -translate-x-full flex-col overflow-y-auto bg-[#0f172a] transition-transform lg:sticky lg:top-0 lg:h-screen lg:translate-x-0"
               :class="{ 'translate-x-0!': navOpen }">
            <x-accounts.sidebar :active="$active ?? 'dashboard'" />
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
                {{-- Page title + "Accounts / …" breadcrumb (pages pass heading/crumb via layoutData). --}}
                @if (! empty($heading))
                    <div class="mr-auto min-w-0">
                        <div class="truncate text-[16px] font-bold text-[#111827]">{{ $heading }}</div>
                        <div class="truncate text-[12px] text-[#6b7280]">{{ $crumb ?? 'Accounts' }}</div>
                    </div>
                @else
                    <div class="hidden text-[13px] text-gray-500 lg:block">{{ now()->format('l, d M Y') }}</div>
                @endif

                <div class="flex items-center gap-[14px]">
                @livewire('admin.topbar.notifications', ['variant' => 'accounts'])

                <div x-data="{ open: false }" class="relative">
                    <button type="button" @click="open = !open" @click.outside="open = false"
                            class="flex items-center gap-[8px] rounded-full pr-1">
                        <span class="flex h-[34px] w-[34px] items-center justify-center rounded-full bg-[#0f172a] text-[12px] font-bold text-white">{{ \App\Models\MasterProfile::initialsOf(auth()->user()->name) }}</span>
                        <span class="hidden max-w-[10rem] truncate text-[13px] font-semibold text-[#111827] sm:block">{{ auth()->user()->name }}</span>
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

            <main class="min-w-0 flex-1 px-4 pb-10 pt-4 sm:px-[28px] sm:pt-[24px]">
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
