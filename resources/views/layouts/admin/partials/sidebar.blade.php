    <div class="h-screen z-30 bg-gray-900 transition-all duration-300 space-y-2 fixed sm:sticky flex justify-around flex-col"
        x-bind:class="{
            'w-64': $store.sidebar.full,
            'w-64 sm:w-20': !$store.sidebar.full,
            'top-0 left-0': $store.sidebar.navOpen,
            'top-0 -left-64 sm:left-0': !$store.sidebar.navOpen
        }">

        @php
            $siteName      = \App\Models\Setting::get('site_name', config('app.name'), 'general');
            $logoWhite     = \App\Models\Setting::get('site_logo_white', null, 'general');
            $logoSymbol    = \App\Models\Setting::get('site_logo_symbol', null, 'general');
            $logoWhiteUrl  = $logoWhite  ? file_path($logoWhite)  : null;
            $logoSymbolUrl = $logoSymbol ? file_path($logoSymbol) : null;
            $initial       = strtoupper(mb_substr($siteName, 0, 1));
        @endphp

        <div class="relative flex items-center border-b border-white/5 h-20 shrink-0">

            <div x-show="$store.sidebar.full" x-cloak
                 x-transition:enter="transition-opacity ease-out duration-150"
                 x-transition:enter-start="opacity-0"
                 x-transition:enter-end="opacity-100"
                 class="flex items-center gap-3 px-4 w-full overflow-hidden">
                @if($logoWhiteUrl)
                    <img src="{{ $logoWhiteUrl }}" alt="{{ $siteName }}"
                         class="h-14 w-full object-contain object-left">
                @else
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center shrink-0 shadow-sm">
                        <span class="text-white font-black text-base leading-none select-none">{{ $initial }}</span>
                    </div>
                    <span class="text-white font-bold text-base tracking-tight truncate leading-none">{{ $siteName }}</span>
                @endif
            </div>

            <div x-show="!$store.sidebar.full" x-cloak
                 class="flex items-center justify-center w-full">
                @if($logoSymbolUrl)
                    <img src="{{ $logoSymbolUrl }}" alt="{{ $siteName }}"
                         class="w-10 h-10 object-contain">
                @else
                    <div class="w-10 h-10 rounded-xl bg-indigo-600 flex items-center justify-center shadow-sm">
                        <span class="text-white font-black text-base leading-none select-none">{{ $initial }}</span>
                    </div>
                @endif
            </div>

            <button @click="$store.sidebar.full = !$store.sidebar.full; localStorage.setItem('sidebar_full', $store.sidebar.full)"
                class="hidden sm:flex items-center justify-center absolute -right-3 top-1/2 -translate-y-1/2 w-6 h-6 bg-gray-900 border border-gray-700 rounded-full shadow-md focus:outline-none cursor-pointer hover:bg-gray-800 transition">
                <svg class="h-3 w-3 text-gray-400 transition-transform duration-300"
                     :class="$store.sidebar.full ? '' : 'rotate-180'"
                     viewBox="0 0 20 20" fill="currentColor">
                    <path fill-rule="evenodd"
                        d="M12.707 5.293a1 1 0 010 1.414L9.414 10l3.293 3.293a1 1 0 01-1.414 1.414l-4-4a1 1 0 010-1.414l4-4a1 1 0 011.414 0z"
                        clip-rule="evenodd"/>
                </svg>
            </button>

            {{-- Mobile close button --}}
            <button @click="$store.sidebar.navOpen = false"
                class="sm:hidden absolute right-3 top-1/2 -translate-y-1/2 w-8 h-8 flex items-center justify-center rounded-full text-gray-400 hover:text-white hover:bg-white/10 transition focus:outline-none">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="px-4 space-y-2">
            <div class="h-[64vh] scrollbar scrollbar-thumb-gray-900 scrollbar-thin scrollbar-track-transparent"
                :class="$store.sidebar.full ? 'overflow-y-scroll' : ''">

                <div class="mt-4 mb-1">
                    <h2 class="text-gray-500 text-md font-semibold" :class="{ 'hidden': !$store.sidebar.full }"
                        x-transition>General</h2>
                </div>

                <a href="{{ route('admin.dashboard') }}" x-data="tooltip" x-on:mouseover="show = true"
                    x-on:mouseleave="show = false"
                    class="relative flex items-center hover:text-gray-200 hover:bg-gray-800 space-x-2 rounded-md p-2 cursor-pointer justify-start text-gray-400
                    {{ Route::currentRouteName() == 'admin.dashboard' ? 'text-gray-200 bg-gray-800' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                            d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6" />
                    </svg>
                    <h1 x-cloak x-bind:class="!$store.sidebar.full && show ? visibleClass : '' || !$store.sidebar.full ? 'sm:hidden' : ''">
                        Dashboard</h1>
                </a>

                <a href="{{ route('admin.uploads') }}" x-data="tooltip" x-on:mouseover="show = true"
                    x-on:mouseleave="show = false"
                    class="relative flex items-center hover:text-gray-200 hover:bg-gray-800 space-x-2 rounded-md p-2 cursor-pointer justify-start text-gray-400
                    {{ Route::currentRouteName() == 'admin.uploads' ? 'text-gray-200 bg-gray-800' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                        stroke="currentColor" class="h-6 w-6">
                        <path stroke-linecap="round" stroke-linejoin="round"
                            d="M12 16.5V9.75m0 0 3 3m-3-3-3 3M6.75 19.5a4.5 4.5 0 0 1-1.41-8.775 5.25 5.25 0 0 1 10.233-2.33 3 3 0 0 1 3.758 3.848A3.752 3.752 0 0 1 18 19.5H6.75Z" />
                    </svg>
                    <h1 x-cloak x-bind:class="!$store.sidebar.full && show ? visibleClass : '' || !$store.sidebar.full ? 'sm:hidden' : ''">
                        Uploads</h1>
                </a>

                @php
                    $usersActive = in_array(Route::currentRouteName(), [
                        'admin.users', 'admin.users.devices', 'admin.users.blocks', 'admin.users.active',
                    ]) || str_starts_with(Route::currentRouteName(), 'admin.customers.');
                @endphp
                <div x-data="dropdown" x-init="open = {{ $usersActive ? 'true' : 'false' }} && $store.sidebar.full" class="relative">
                    <div @click="toggle('users')" x-data="tooltip" @mouseover="show = true"
                        @mouseleave="show = false"
                        class="flex justify-between text-gray-400 hover:text-gray-200 hover:bg-gray-800 items-center space-x-2 rounded-md p-2 cursor-pointer
                        {{ $usersActive ? 'text-gray-200 bg-gray-800' : '' }}"
                        :class="{
                            'justify-start': $store.sidebar.full,
                            'sm:justify-center': !$store.sidebar.full
                        }">
                        <div class="relative flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2"
                                stroke="currentColor" class="h-6 w-6">
                                <path stroke-linecap="round" stroke-linejoin="round"
                                    d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <h1 x-cloak :class="!$store.sidebar.full ? (show ? visibleClass : 'sm:hidden') : ''">
                                Users
                            </h1>
                        </div>
                        <svg x-cloak :class="$store.sidebar.full ? '' : 'sm:hidden'" xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div x-cloak x-show="open" @click.outside="open=false"
                        :class="$store.sidebar.full ? expandedClass : shrinkedClass" class="text-gray-400 space-y-3">
                        <a href="{{ route('admin.users') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.users' ? 'text-gray-200' : '' }}">
                            Users
                        </a>
                        <a href="{{ route('admin.users.active') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.users.active' ? 'text-gray-200' : '' }}">
                            Active
                        </a>
                        <a href="{{ route('admin.customers.index') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ str_starts_with(Route::currentRouteName(), 'admin.customers.') ? 'text-gray-200' : '' }}">
                            Customers
                        </a>
                        <a href="{{ route('admin.users.devices') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.users.devices' ? 'text-gray-200' : '' }}">
                            Devices
                        </a>
                        <a href="{{ route('admin.users.blocks') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.users.blocks' ? 'text-gray-200' : '' }}">
                            Blocks
                        </a>
                    </div>
                </div>

                @php
                    $frontendActive = str_starts_with(Route::currentRouteName(), 'admin.frontend.');
                @endphp
                <div x-data="dropdown" x-init="open = {{ $frontendActive ? 'true' : 'false' }} && $store.sidebar.full" class="relative">
                    <div @click="toggle('frontend')" x-data="tooltip" @mouseover="show = true"
                        @mouseleave="show = false"
                        class="flex justify-between text-gray-400 hover:text-gray-200 hover:bg-gray-800 items-center space-x-2 rounded-md p-2 cursor-pointer
                        {{ $frontendActive ? 'text-gray-200 bg-gray-800' : '' }}"
                        :class="{
                            'justify-start': $store.sidebar.full,
                            'sm:justify-center': !$store.sidebar.full
                        }">
                        <div class="relative flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h12A2.25 2.25 0 0 1 20.25 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h5.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM16.5 13.5a2.25 2.25 0 0 0-2.25 2.25V18a2.25 2.25 0 0 0 2.25 2.25h1.5A2.25 2.25 0 0 0 20.25 18v-2.25a2.25 2.25 0 0 0-2.25-2.25h-1.5Z" />
                            </svg>
                            <h1 x-cloak :class="!$store.sidebar.full ? (show ? visibleClass : 'sm:hidden') : ''">
                                Frontend Engine
                            </h1>
                        </div>
                        <svg x-cloak :class="$store.sidebar.full ? '' : 'sm:hidden'" xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div x-cloak x-show="open" @click.outside="open=false"
                        :class="$store.sidebar.full ? expandedClass : shrinkedClass" class="text-gray-400 space-y-3">
                        <a href="{{ route('admin.frontend.menu') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.frontend.menu' || Route::currentRouteName() === 'admin.frontend.menu.show' ? 'text-gray-200' : '' }}">
                            Pages
                        </a>
                        <a href="{{ route('admin.frontend.themes') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.frontend.themes' ? 'text-gray-200' : '' }}">
                            Themes
                        </a>
                        <a href="{{ route('admin.frontend.appearance') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.frontend.appearance' ? 'text-gray-200' : '' }}">
                            Appearance
                        </a>
                        <a href="{{ route('admin.frontend.menus') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.frontend.menus' ? 'text-gray-200' : '' }}">
                            Menus
                        </a>
                        <a href="{{ route('admin.frontend.components') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.frontend.components' ? 'text-gray-200' : '' }}">
                            Components
                        </a>
                    </div>
                </div>

                @php
                    $landingPageActive = str_starts_with(Route::currentRouteName(), 'admin.landingpages.');
                @endphp
                <div x-data="dropdown" x-init="open = {{ $landingPageActive ? 'true' : 'false' }} && $store.sidebar.full" class="relative">
                    <div @click="toggle('landing-page')" x-data="tooltip" @mouseover="show = true"
                        @mouseleave="show = false"
                        class="flex justify-between text-gray-400 hover:text-gray-200 hover:bg-gray-800 items-center space-x-2 rounded-md p-2 cursor-pointer
                        {{ $landingPageActive ? 'text-gray-200 bg-gray-800' : '' }}"
                        :class="{
                            'justify-start': $store.sidebar.full,
                            'sm:justify-center': !$store.sidebar.full
                        }">
                        <div class="relative flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 5.25h16.5m-16.5 4.5h16.5m-16.5 4.5h9.75m-9.75 4.5h9.75M18 15.75l2.25 2.25L18 20.25" />
                            </svg>
                            <h1 x-cloak :class="!$store.sidebar.full ? (show ? visibleClass : 'sm:hidden') : ''">
                                Landing Page
                            </h1>
                        </div>
                        <svg x-cloak :class="$store.sidebar.full ? '' : 'sm:hidden'" xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div x-cloak x-show="open" @click.outside="open=false"
                        :class="$store.sidebar.full ? expandedClass : shrinkedClass" class="text-gray-400 space-y-3">
                        <a href="{{ route('admin.landingpages.pages') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.landingpages.pages' || str_starts_with(Route::currentRouteName(), 'admin.landingpages.pages.') ? 'text-gray-200' : '' }}">
                            All Pages
                        </a>
                        <a href="{{ route('admin.landingpages.templates') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.landingpages.templates' ? 'text-gray-200' : '' }}">
                            Templates
                        </a>
                        <a href="{{ route('admin.landingpages.settings') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.landingpages.settings' ? 'text-gray-200' : '' }}">
                            Settings
                        </a>
                    </div>
                </div>

                <div class="mt-4 mb-1">
                    <h2 class="text-gray-500 text-md font-semibold" :class="{ 'hidden': !$store.sidebar.full }"
                        x-transition>Ecommerce</h2>
                </div>

                @php
                    $catalogActive = str_starts_with(Route::currentRouteName(), 'admin.catalog.');
                @endphp
                <div x-data="dropdown" x-init="open = {{ $catalogActive ? 'true' : 'false' }} && $store.sidebar.full" class="relative">
                    <div @click="toggle('catalog')" x-data="tooltip" @mouseover="show = true"
                        @mouseleave="show = false"
                        class="flex justify-between text-gray-400 hover:text-gray-200 hover:bg-gray-800 items-center space-x-2 rounded-md p-2 cursor-pointer
                        {{ $catalogActive ? 'text-gray-200 bg-gray-800' : '' }}"
                        :class="{
                            'justify-start': $store.sidebar.full,
                            'sm:justify-center': !$store.sidebar.full
                        }">
                        <div class="relative flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                            </svg>
                            <h1 x-cloak :class="!$store.sidebar.full ? (show ? visibleClass : 'sm:hidden') : ''">
                                Catalog
                            </h1>
                        </div>
                        <svg x-cloak :class="$store.sidebar.full ? '' : 'sm:hidden'" xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div x-cloak x-show="open" @click.outside="open=false"
                        :class="$store.sidebar.full ? expandedClass : shrinkedClass" class="text-gray-400 space-y-3">
                        <a href="{{ route('admin.catalog.categories') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.catalog.categories' ? 'text-gray-200' : '' }}">
                            Categories
                        </a>
                        <a href="{{ route('admin.catalog.brands') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.catalog.brands' ? 'text-gray-200' : '' }}">
                            Brands
                        </a>
                        <a href="{{ route('admin.catalog.attributes') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.catalog.attributes' ? 'text-gray-200' : '' }}">
                            Attributes
                        </a>
                        <a href="{{ route('admin.catalog.products') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ str_starts_with(Route::currentRouteName(), 'admin.catalog.products') ? 'text-gray-200' : '' }}">
                            Products
                        </a>
                    </div>
                </div>

                @php
                    $customersActive = str_starts_with(Route::currentRouteName(), 'admin.customers.');
                @endphp
                <div x-data="dropdown" x-init="open = {{ $customersActive ? 'true' : 'false' }} && $store.sidebar.full" class="relative">
                    <div @click="toggle('customers')" x-data="tooltip" @mouseover="show = true"
                        @mouseleave="show = false"
                        class="flex justify-between text-gray-400 hover:text-gray-200 hover:bg-gray-800 items-center space-x-2 rounded-md p-2 cursor-pointer
                        {{ $customersActive ? 'text-gray-200 bg-gray-800' : '' }}"
                        :class="{
                            'justify-start': $store.sidebar.full,
                            'sm:justify-center': !$store.sidebar.full
                        }">
                        <div class="relative flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            </svg>
                            <h1 x-cloak :class="!$store.sidebar.full ? (show ? visibleClass : 'sm:hidden') : ''">
                                Customers
                            </h1>
                        </div>
                        <svg x-cloak :class="$store.sidebar.full ? '' : 'sm:hidden'" xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div x-cloak x-show="open" @click.outside="open=false"
                        :class="$store.sidebar.full ? expandedClass : shrinkedClass" class="text-gray-400 space-y-3">
                        <a href="{{ route('admin.customers.index') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.customers.index' ? 'text-gray-200' : '' }}">
                            Customers
                        </a>
                        <a href="{{ route('admin.customers.carts') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ str_starts_with(Route::currentRouteName(), 'admin.customers.carts') ? 'text-gray-200' : '' }}">
                            Carts
                        </a>
                        <a href="{{ route('admin.customers.combo') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.customers.combo' ? 'text-gray-200' : '' }}">
                            Combo
                        </a>
                        <a href="{{ route('admin.customers.loved') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.customers.loved' ? 'text-gray-200' : '' }}">
                            Loved
                        </a>
                        <a href="{{ route('admin.customers.groups') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.customers.groups' ? 'text-gray-200' : '' }}">
                            Customer Group
                        </a>
                        <a href="{{ route('admin.customers.reviews.index') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ str_starts_with(Route::currentRouteName(), 'admin.customers.reviews.') ? 'text-gray-200' : '' }}">
                            Reviews
                        </a>
                    </div>
                </div>

                @php
                    $purchaseActive = str_starts_with(Route::currentRouteName(), 'admin.purchase.');
                @endphp
                <div x-data="dropdown" x-init="open = {{ $purchaseActive ? 'true' : 'false' }} && $store.sidebar.full" class="relative">
                    <div @click="toggle('purchase')" x-data="tooltip" @mouseover="show = true"
                        @mouseleave="show = false"
                        class="flex justify-between text-gray-400 hover:text-gray-200 hover:bg-gray-800 items-center space-x-2 rounded-md p-2 cursor-pointer
                        {{ $purchaseActive ? 'text-gray-200 bg-gray-800' : '' }}"
                        :class="{
                            'justify-start': $store.sidebar.full,
                            'sm:justify-center': !$store.sidebar.full
                        }">
                        <div class="relative flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.684 2.674-7.14a1.06 1.06 0 0 0-.999-1.335H5.85m4.5 8.475H5.85m0 0-.383-1.437M12 14.25l3.75-3.75M12 14.25l-3.75-3.75M12 14.25V6" />
                            </svg>
                            <h1 x-cloak :class="!$store.sidebar.full ? (show ? visibleClass : 'sm:hidden') : ''">
                                Purchase
                            </h1>
                        </div>
                        <svg x-cloak :class="$store.sidebar.full ? '' : 'sm:hidden'" xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div x-cloak x-show="open" @click.outside="open=false"
                        :class="$store.sidebar.full ? expandedClass : shrinkedClass" class="text-gray-400 space-y-3">
                        <a href="{{ route('admin.purchase.suppliers') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ str_starts_with(Route::currentRouteName(), 'admin.purchase.suppliers') ? 'text-gray-200' : '' }}">
                            Suppliers
                        </a>
                        <a href="{{ route('admin.purchase.invoices') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.purchase.invoices' ? 'text-gray-200' : '' }}">
                            Invoices
                        </a>
                        <a href="{{ route('admin.purchase.orders') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.purchase.orders' ? 'text-gray-200' : '' }}">
                            Purchase Orders
                        </a>
                    </div>
                </div>

                @php
                    $inventoryActive = str_starts_with(Route::currentRouteName(), 'admin.inventory.');
                @endphp
                <div x-data="dropdown" x-init="open = {{ $inventoryActive ? 'true' : 'false' }} && $store.sidebar.full" class="relative">
                    <div @click="toggle('inventory')" x-data="tooltip" @mouseover="show = true"
                        @mouseleave="show = false"
                        class="flex justify-between text-gray-400 hover:text-gray-200 hover:bg-gray-800 items-center space-x-2 rounded-md p-2 cursor-pointer
                        {{ $inventoryActive ? 'text-gray-200 bg-gray-800' : '' }}"
                        :class="{
                            'justify-start': $store.sidebar.full,
                            'sm:justify-center': !$store.sidebar.full
                        }">
                        <div class="relative flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m6 4.125 2.25 2.25m0 0 2.25 2.25M12 13.875l2.25-2.25M12 13.875l-2.25 2.25M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
                            </svg>
                            <h1 x-cloak :class="!$store.sidebar.full ? (show ? visibleClass : 'sm:hidden') : ''">
                                Inventory
                            </h1>
                        </div>
                        <svg x-cloak :class="$store.sidebar.full ? '' : 'sm:hidden'" xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div x-cloak x-show="open" @click.outside="open=false"
                        :class="$store.sidebar.full ? expandedClass : shrinkedClass" class="text-gray-400 space-y-3">
                        <a href="{{ route('admin.inventory.stock') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.inventory.stock' ? 'text-gray-200' : '' }}">
                            Stock
                        </a>
                        <a href="{{ route('admin.inventory.stock-in') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.inventory.stock-in' ? 'text-gray-200' : '' }}">
                            Stock In
                        </a>
                        <a href="{{ route('admin.inventory.batches') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.inventory.batches' ? 'text-gray-200' : '' }}">
                            Batches
                        </a>
                        <a href="{{ route('admin.inventory.movements') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.inventory.movements' ? 'text-gray-200' : '' }}">
                            Movements
                        </a>
                        <a href="{{ route('admin.inventory.warehouses') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.inventory.warehouses' ? 'text-gray-200' : '' }}">
                            Warehouses
                        </a>
                        <a href="{{ route('admin.inventory.settings') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.inventory.settings' ? 'text-gray-200' : '' }}">
                            Settings
                        </a>
                    </div>
                </div>

                @php
                    $salesActive = str_starts_with(Route::currentRouteName(), 'admin.sales.');
                @endphp
                <div x-data="dropdown" x-init="open = {{ $salesActive ? 'true' : 'false' }} && $store.sidebar.full" class="relative">
                    <div @click="toggle('sales')" x-data="tooltip" @mouseover="show = true"
                        @mouseleave="show = false"
                        class="flex justify-between text-gray-400 hover:text-gray-200 hover:bg-gray-800 items-center space-x-2 rounded-md p-2 cursor-pointer
                        {{ $salesActive ? 'text-gray-200 bg-gray-800' : '' }}"
                        :class="{
                            'justify-start': $store.sidebar.full,
                            'sm:justify-center': !$store.sidebar.full
                        }">
                        <div class="relative flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 0 0-3 3h15.75m-12.75-3h11.218c1.121-2.3 1.994-4.684 2.674-7.14a1.06 1.06 0 0 0-.999-1.335H5.85m4.5 8.475H5.85m0 0-.383-1.437M12 14.25l3.75-3.75M12 14.25l-3.75-3.75M12 14.25V6M9 19.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Zm10.5 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0Z" />
                            </svg>
                            <h1 x-cloak :class="!$store.sidebar.full ? (show ? visibleClass : 'sm:hidden') : ''">
                                Sales
                            </h1>
                        </div>
                        <svg x-cloak :class="$store.sidebar.full ? '' : 'sm:hidden'" xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div x-cloak x-show="open" @click.outside="open=false"
                        :class="$store.sidebar.full ? expandedClass : shrinkedClass" class="text-gray-400 space-y-3">
                        <a href="{{ route('admin.sales.orders') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ str_starts_with(Route::currentRouteName(), 'admin.sales.orders') ? 'text-gray-200' : '' }}">
                            Orders
                        </a>
                        <a href="{{ route('admin.sales.coupons') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ str_starts_with(Route::currentRouteName(), 'admin.sales.coupons') ? 'text-gray-200' : '' }}">
                            Coupons
                        </a>
                        <a href="{{ route('admin.sales.offers') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ str_starts_with(Route::currentRouteName(), 'admin.sales.offers') ? 'text-gray-200' : '' }}">
                            Offers
                        </a>
                        <a href="{{ route('admin.sales.pos.screen') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ str_starts_with(Route::currentRouteName(), 'admin.sales.pos.screen') ? 'text-gray-200' : '' }}">
                            POS Screen
                        </a>
                        <a href="{{ route('admin.sales.pos.sessions') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ str_starts_with(Route::currentRouteName(), 'admin.sales.pos.sessions') ? 'text-gray-200' : '' }}">
                            POS Sessions
                        </a>
                        <a href="{{ route('admin.sales.pos.registers') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ str_starts_with(Route::currentRouteName(), 'admin.sales.pos.registers') ? 'text-gray-200' : '' }}">
                            POS Registers
                        </a>
                    </div>
                </div>

                @php
                    $marketingActive = str_starts_with(Route::currentRouteName(), 'admin.marketing.');
                @endphp
                <div x-data="dropdown" x-init="open = {{ $marketingActive ? 'true' : 'false' }} && $store.sidebar.full" class="relative">
                    <div @click="toggle('marketing')" x-data="tooltip" @mouseover="show = true"
                        @mouseleave="show = false"
                        class="flex justify-between text-gray-400 hover:text-gray-200 hover:bg-gray-800 items-center space-x-2 rounded-md p-2 cursor-pointer
                        {{ $marketingActive ? 'text-gray-200 bg-gray-800' : '' }}"
                        :class="{
                            'justify-start': $store.sidebar.full,
                            'sm:justify-center': !$store.sidebar.full
                        }">
                        <div class="relative flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25a3 3 0 0 1 3 3m3 0a6 6 0 0 1-7.029 5.912c-.563-.097-1.159.026-1.563.43L10.5 17.25H8.25v2.25H6v2.25H2.25v-2.818c0-.597.237-1.17.659-1.591l6.499-6.499c.404-.404.527-1 .43-1.563A6 6 0 1 1 21.75 8.25Z" />
                            </svg>
                            <h1 x-cloak :class="!$store.sidebar.full ? (show ? visibleClass : 'sm:hidden') : ''">
                                Marketing
                            </h1>
                        </div>
                        <svg x-cloak :class="$store.sidebar.full ? '' : 'sm:hidden'" xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div x-cloak x-show="open" @click.outside="open=false"
                        :class="$store.sidebar.full ? expandedClass : shrinkedClass" class="text-gray-400 space-y-3">
                        <a href="{{ route('admin.marketing.dashboard') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.marketing.dashboard' ? 'text-gray-200' : '' }}">
                            Dashboard
                        </a>

                        <div class="pt-1">
                            <span class="block text-[10px] uppercase tracking-wider text-gray-600 mb-1.5">Journey Explorer</span>
                            <div class="space-y-2.5 pl-1">
                                <a href="{{ route('admin.marketing.journeys.visitors') }}"
                                    class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.marketing.journeys.visitors' ? 'text-gray-200' : '' }}">
                                    Visitor Journeys
                                </a>
                                <a href="{{ route('admin.marketing.journeys.customers') }}"
                                    class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.marketing.journeys.customers' ? 'text-gray-200' : '' }}">
                                    Customer Journeys
                                </a>
                                <a href="{{ route('admin.marketing.journeys.anonymous') }}"
                                    class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.marketing.journeys.anonymous' ? 'text-gray-200' : '' }}">
                                    Anonymous Visitors
                                </a>
                            </div>
                        </div>

                        <div class="pt-1">
                            <span class="block text-[10px] uppercase tracking-wider text-gray-600 mb-1.5">Campaign Tracking</span>
                            <div class="space-y-2.5 pl-1">
                                <a href="{{ route('admin.marketing.campaigns.index') }}"
                                    class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.marketing.campaigns.index' ? 'text-gray-200' : '' }}">
                                    All Campaigns
                                </a>
                                <a href="{{ route('admin.marketing.campaigns.meta') }}"
                                    class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.marketing.campaigns.meta' ? 'text-gray-200' : '' }}">
                                    Meta Campaigns
                                </a>
                                <a href="{{ route('admin.marketing.campaigns.google') }}"
                                    class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.marketing.campaigns.google' ? 'text-gray-200' : '' }}">
                                    Google Campaigns
                                </a>
                                <a href="{{ route('admin.marketing.campaigns.tiktok') }}"
                                    class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.marketing.campaigns.tiktok' ? 'text-gray-200' : '' }}">
                                    TikTok Campaigns
                                </a>
                                <a href="{{ route('admin.marketing.campaigns.other') }}"
                                    class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.marketing.campaigns.other' ? 'text-gray-200' : '' }}">
                                    Other / UTM
                                </a>
                            </div>
                        </div>

                        <a href="{{ route('admin.marketing.sources.index') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.marketing.sources.index' ? 'text-gray-200' : '' }}">
                            Source Analytics
                        </a>

                        <div class="pt-1">
                            <span class="block text-[10px] uppercase tracking-wider text-gray-600 mb-1.5">Product Analytics</span>
                            <div class="space-y-2.5 pl-1">
                                <a href="{{ route('admin.marketing.products.performance') }}"
                                    class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.marketing.products.performance' ? 'text-gray-200' : '' }}">
                                    Product Performance
                                </a>
                                <a href="{{ route('admin.marketing.products.journeys') }}"
                                    class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.marketing.products.journeys' ? 'text-gray-200' : '' }}">
                                    Product Journeys
                                </a>
                            </div>
                        </div>

                        <div class="pt-1">
                            <span class="block text-[10px] uppercase tracking-wider text-gray-600 mb-1.5">Customer Analytics</span>
                            <div class="space-y-2.5 pl-1">
                                <a href="{{ route('admin.marketing.customers.tracking') }}"
                                    class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.marketing.customers.tracking' ? 'text-gray-200' : '' }}">
                                    Customer Tracking
                                </a>
                                <a href="{{ route('admin.marketing.customers.returning') }}"
                                    class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.marketing.customers.returning' ? 'text-gray-200' : '' }}">
                                    Returning Customers
                                </a>
                            </div>
                        </div>

                        <div class="pt-1">
                            <span class="block text-[10px] uppercase tracking-wider text-gray-600 mb-1.5">Audience &amp; Devices</span>
                            <div class="space-y-2.5 pl-1">
                                <a href="{{ route('admin.marketing.audience.devices') }}"
                                    class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.marketing.audience.devices' ? 'text-gray-200' : '' }}">
                                    Devices
                                </a>
                                <a href="{{ route('admin.marketing.audience.ip') }}"
                                    class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.marketing.audience.ip' ? 'text-gray-200' : '' }}">
                                    IP Analysis
                                </a>
                            </div>
                        </div>

                        <a href="{{ route('admin.marketing.events.index') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.marketing.events.index' ? 'text-gray-200' : '' }}">
                            Event Explorer
                        </a>
                        <a href="{{ route('admin.marketing.attribution.index') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.marketing.attribution.index' ? 'text-gray-200' : '' }}">
                            Attribution
                        </a>
                        <a href="{{ route('admin.marketing.reports.index') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.marketing.reports.index' ? 'text-gray-200' : '' }}">
                            Reports
                        </a>
                        <a href="{{ route('admin.marketing.settings.index') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.marketing.settings.index' ? 'text-gray-200' : '' }}">
                            Tracking Settings
                        </a>
                    </div>
                </div>

                <div class="mt-4 mb-1">
                    <h2 class="text-gray-500 text-md font-semibold" :class="{ 'hidden': !$store.sidebar.full }"
                        x-transition>Settings</h2>
                </div>

                @php
                    $languagesActive = in_array(Route::currentRouteName(), [
                        'admin.settings.languages', 'admin.settings.languages.create', 'admin.settings.languages.edit'
                    ]);
                @endphp
                <a href="{{ route('admin.settings.languages') }}" x-data="tooltip" x-on:mouseover="show = true"
                    x-on:mouseleave="show = false"
                    class="relative flex items-center hover:text-gray-200 hover:bg-gray-800 space-x-2 rounded-md p-2 cursor-pointer justify-start text-gray-400
                    {{ $languagesActive ? 'text-gray-200 bg-gray-800' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 21l5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" />
                    </svg>
                    <h1 x-cloak x-bind:class="!$store.sidebar.full && show ? visibleClass : '' || !$store.sidebar.full ? 'sm:hidden' : ''">
                        Languages</h1>
                </a>

                @php
                    $permissionsActive = in_array(Route::currentRouteName(), [
                        'admin.roles.list', 'admin.roles.create', 'admin.roles.edit', 'admin.permissions.panels'
                    ]);
                @endphp
                <div x-data="dropdown" x-init="open = {{ $permissionsActive ? 'true' : 'false' }} && $store.sidebar.full" class="relative">
                    <div @click="toggle('permissions')" x-data="tooltip" @mouseover="show = true"
                        @mouseleave="show = false"
                        class="flex justify-between text-gray-400 hover:text-gray-200 hover:bg-gray-800 items-center space-x-2 rounded-md p-2 cursor-pointer
                        {{ $permissionsActive ? 'text-gray-200 bg-gray-800' : '' }}"
                        :class="{
                            'justify-start': $store.sidebar.full,
                            'sm:justify-center': !$store.sidebar.full
                        }">
                        <div class="relative flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                            </svg>
                            <h1 x-cloak :class="!$store.sidebar.full ? (show ? visibleClass : 'sm:hidden') : ''">
                                Permissions
                            </h1>
                        </div>
                        <svg x-cloak :class="$store.sidebar.full ? '' : 'sm:hidden'" xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div x-cloak x-show="open" @click.outside="open=false"
                        :class="$store.sidebar.full ? expandedClass : shrinkedClass" class="text-gray-400 space-y-3">
                        <a href="{{ route('admin.roles.list') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ in_array(Route::currentRouteName(), ['admin.roles.list', 'admin.roles.create', 'admin.roles.edit']) ? 'text-gray-200' : '' }}">
                            Roles
                        </a>
                        <a href="{{ route('admin.permissions.panels') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.permissions.panels' ? 'text-gray-200' : '' }}">
                            Panels
                        </a>
                    </div>
                </div>

                <a href="{{ route('admin.activity-log') }}" x-data="tooltip" x-on:mouseover="show = true"
                    x-on:mouseleave="show = false"
                    class="relative flex items-center hover:text-gray-200 hover:bg-gray-800 space-x-2 rounded-md p-2 cursor-pointer justify-start text-gray-400
                    {{ Route::currentRouteName() == 'admin.activity-log' ? 'text-gray-200 bg-gray-800' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                    <h1 x-cloak x-bind:class="!$store.sidebar.full && show ? visibleClass : '' || !$store.sidebar.full ? 'sm:hidden' : ''">
                        Activity Log</h1>
                </a>

                <a href="{{ route('admin.site-settings') }}" x-data="tooltip" x-on:mouseover="show = true"
                    x-on:mouseleave="show = false"
                    class="relative flex items-center hover:text-gray-200 hover:bg-gray-800 space-x-2 rounded-md p-2 cursor-pointer justify-start text-gray-400
                    {{ Route::currentRouteName() == 'admin.site-settings' ? 'text-gray-200 bg-gray-800' : '' }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <h1 x-cloak x-bind:class="!$store.sidebar.full && show ? visibleClass : '' || !$store.sidebar.full ? 'sm:hidden' : ''">
                        Site Settings</h1>
                </a>

                @php
                    $advanceActive = in_array(Route::currentRouteName(), [
                        'admin.settings.advance.developer-tools', 'admin.settings.advance.system-health', 'admin.settings.advance.license-configuration'
                    ]) || str_starts_with(Route::currentRouteName(), 'admin.settings.advance.sms-configuration.')
                        || str_starts_with(Route::currentRouteName(), 'admin.settings.advance.email-configuration.')
                        || str_starts_with(Route::currentRouteName(), 'admin.settings.advance.notification-configuration.')
                        || str_starts_with(Route::currentRouteName(), 'admin.settings.advance.courier.');
                @endphp
                <div x-data="dropdown" x-init="open = {{ $advanceActive ? 'true' : 'false' }} && $store.sidebar.full" class="relative">
                    <div @click="toggle('advance')" x-data="tooltip" @mouseover="show = true"
                        @mouseleave="show = false"
                        class="flex justify-between text-gray-400 hover:text-gray-200 hover:bg-gray-800 items-center space-x-2 rounded-md p-2 cursor-pointer
                        {{ $advanceActive ? 'text-gray-200 bg-gray-800' : '' }}"
                        :class="{
                            'justify-start': $store.sidebar.full,
                            'sm:justify-center': !$store.sidebar.full
                        }">
                        <div class="relative flex items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.25 9.75 16.5 12l-2.25 2.25m-4.5 0L7.5 12l2.25-2.25M6 20.25h12A2.25 2.25 0 0 0 20.25 18V6A2.25 2.25 0 0 0 18 3.75H6A2.25 2.25 0 0 0 3.75 6v12A2.25 2.25 0 0 0 6 20.25Z" />
                            </svg>
                            <h1 x-cloak :class="!$store.sidebar.full ? (show ? visibleClass : 'sm:hidden') : ''">
                                Advance
                            </h1>
                        </div>
                        <svg x-cloak :class="$store.sidebar.full ? '' : 'sm:hidden'" xmlns="http://www.w3.org/2000/svg"
                            class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                            <path fill-rule="evenodd"
                                d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                                clip-rule="evenodd" />
                        </svg>
                    </div>
                    <div x-cloak x-show="open" @click.outside="open=false"
                        :class="$store.sidebar.full ? expandedClass : shrinkedClass" class="text-gray-400 space-y-3">
                        @can('developer_tools.view')
                        <a href="{{ route('admin.settings.advance.developer-tools') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.settings.advance.developer-tools' ? 'text-gray-200' : '' }}">
                            Developer Tools
                        </a>
                        @endcan
                        @can('system_health.view')
                        <a href="{{ route('admin.settings.advance.system-health') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.settings.advance.system-health' ? 'text-gray-200' : '' }}">
                            System Health
                        </a>
                        @endcan
                        @can('license_configuration.view')
                        <a href="{{ route('admin.settings.advance.license-configuration') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ Route::currentRouteName() === 'admin.settings.advance.license-configuration' ? 'text-gray-200' : '' }}">
                            License Configuration
                        </a>
                        @endcan
                        @can('sms_configuration.view')
                        <a href="{{ route('admin.settings.advance.sms-configuration.dashboard') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ str_starts_with(Route::currentRouteName(), 'admin.settings.advance.sms-configuration.') ? 'text-gray-200' : '' }}">
                            SMS Configuration
                        </a>
                        @endcan
                        @can('email_configuration.view')
                        <a href="{{ route('admin.settings.advance.email-configuration.providers') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ str_starts_with(Route::currentRouteName(), 'admin.settings.advance.email-configuration.') ? 'text-gray-200' : '' }}">
                            Email Configuration
                        </a>
                        @endcan
                        @can('notification_configuration.view')
                        <a href="{{ route('admin.settings.advance.notification-configuration.dashboard') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ str_starts_with(Route::currentRouteName(), 'admin.settings.advance.notification-configuration.') ? 'text-gray-200' : '' }}">
                            Notification Configuration
                        </a>
                        @endcan
                        @can('courier_configuration.view')
                        <a href="{{ route('admin.settings.advance.courier.dashboard') }}"
                            class="block hover:text-gray-200 cursor-pointer {{ str_starts_with(Route::currentRouteName(), 'admin.settings.advance.courier.') ? 'text-gray-200' : '' }}">
                            Courier
                        </a>
                        @endcan
                    </div>
                </div>

            </div>
        </div>

        <div>
            <hr class="border-gray-700">

            <div x-data="{ openProfile: false }" class="relative px-2 py-2">
                <div @click="openProfile = !openProfile"
                    class="flex items-center justify-between rounded-md p-2 cursor-pointer text-gray-300 hover:bg-gray-800 hover:text-white transition"
                    :class="{
                        'justify-center': !$store.sidebar.full,
                        'justify-between': $store.sidebar.full
                    }">
                    <div class="flex items-center gap-3 overflow-hidden">
                        <img src="{{ 'https://ui-avatars.com/api/?name=' . urlencode(auth()->user()->name) . '&background=111827&color=ffffff&bold=true' }}"
                            alt="Profile"
                            class="w-10 h-10 rounded-full object-cover border border-gray-700 shrink-0">
                        <div x-cloak x-show="$store.sidebar.full" x-transition class="min-w-0">
                            <h4 class="text-sm font-semibold text-white truncate">{{ auth()->user()->name }}</h4>
                            <p class="text-xs text-gray-400 truncate">{{ auth()->user()->email }}</p>
                        </div>
                    </div>
                    <svg x-cloak x-show="$store.sidebar.full"
                        xmlns="http://www.w3.org/2000/svg"
                        class="w-4 h-4 text-gray-400 transition-transform"
                        :class="{ 'rotate-180': openProfile }"
                        fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                    </svg>
                </div>

                <div x-cloak x-show="openProfile" x-transition
                    @click.outside="openProfile = false"
                    class="absolute bottom-16 left-2 right-2 bg-gray-800 border border-gray-700 rounded-lg shadow-lg overflow-hidden z-50">
                    <a href="{{ route('admin.profile') }}"
                        class="block px-4 py-3 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
                        My Profile
                    </a>
                    <a href="{{ route('admin.settings') }}"
                        class="block px-4 py-3 text-sm text-gray-300 hover:bg-gray-700 hover:text-white">
                        Account Settings
                    </a>
                    <button type="button" @click="$refs.logoutForm.submit()"
                        class="w-full text-left px-4 py-3 text-sm text-red-400 hover:bg-gray-700">
                        Logout
                    </button>
                </div>
            </div>

            <div class="px-4 py-3 flex items-center gap-2"
                :class="$store.sidebar.full ? 'justify-between' : 'sm:justify-center'">
                <span x-cloak x-show="$store.sidebar.full" class="text-xs text-gray-600">
                    {{ config('app.name') }}
                </span>
                <span class="text-xs font-mono text-gray-600">v{{ config('app.version') }}</span>
            </div>
        </div>
    </div>
