<header class="sticky top-0 z-20 -mx-4 sm:-mx-6 -mt-4 sm:-mt-6 mb-4 sm:mb-6 flex items-center justify-between gap-2 sm:gap-4 bg-white border-b border-gray-200 px-4 sm:px-6 py-3 shrink-0">

    <div class="flex items-center gap-3 min-w-0">
        {{-- Mobile Menu Toggle --}}
        <button @click="$store.sidebar.navOpen = !$store.sidebar.navOpen"
            class="sm:hidden shrink-0 text-gray-500 hover:text-gray-700 focus:outline-none">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" x-bind:class="$store.sidebar.navOpen ? 'hidden' : ''"
                fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7" />
            </svg>
            <svg x-cloak xmlns="http://www.w3.org/2000/svg" class="h-6 w-6"
                x-bind:class="$store.sidebar.navOpen ? '' : 'hidden'" fill="none" viewBox="0 0 24 24"
                stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
        </button>

        <div class="min-w-0">
            <h1 class="text-lg font-semibold text-gray-800 truncate" x-cloak x-text="$store.pageName?.name ?? ''"></h1>

            <nav x-cloak aria-label="Breadcrumb">
                <ol class="flex items-center gap-1.5 text-xs">
                    <li>
                        <a href="{{ route('admin.dashboard') }}" class="text-gray-400 hover:text-gray-600 transition">Dashboard</a>
                    </li>
                    <li x-show="$store.pageName?.slug && $store.pageName.slug !== 'dashboard'" class="text-gray-300">/</li>
                    <li x-show="$store.pageName?.slug && $store.pageName.slug !== 'dashboard'"
                        class="text-gray-500 font-medium truncate" x-text="$store.pageName?.name ?? ''"></li>
                </ol>
            </nav>
        </div>
    </div>

    <div class="flex items-center gap-2 shrink-0">
        @livewire('admin.topbar.notifications')

        {{-- Quick Profile --}}
        <div x-data="{ open: false }" class="relative">
            <button @click="open = !open" @click.outside="open = false"
                class="flex items-center gap-2 rounded-full p-1 pr-2 hover:bg-gray-100 transition focus:outline-none">
                <img src="{{ 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=111827&color=ffffff&bold=true' }}"
                    alt="Profile" class="w-8 h-8 rounded-full object-cover border border-gray-200">
                <span class="hidden sm:block text-sm font-medium text-gray-700 max-w-[10rem] truncate">
                    {{ auth()->user()->name }}
                </span>
                <svg xmlns="http://www.w3.org/2000/svg" class="hidden sm:block w-4 h-4 text-gray-400 transition-transform"
                    :class="{ 'rotate-180': open }" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
            </button>

            <div x-cloak x-show="open" x-transition
                class="absolute right-0 mt-2 w-56 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden z-50">
                <div class="px-4 py-3 border-b border-gray-100">
                    <p class="text-sm font-semibold text-gray-800 truncate">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</p>
                </div>
                <a href="{{ route('admin.profile') }}"
                    class="block px-4 py-2.5 text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                    My Profile
                </a>
                <a href="{{ route('admin.settings') }}"
                    class="block px-4 py-2.5 text-sm text-gray-600 hover:bg-gray-50 hover:text-gray-900">
                    Account Settings
                </a>
                <button type="button" @click="$refs.logoutForm.submit()"
                    class="w-full text-left px-4 py-2.5 text-sm text-red-500 hover:bg-gray-50">
                    Logout
                </button>
            </div>
        </div>
    </div>
</header>
