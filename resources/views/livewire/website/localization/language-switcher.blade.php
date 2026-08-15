<div x-data="{ open: false }" @click.outside="open = false"
    x-on:language-switched.window="
        // Mirror the cookie choice into localStorage (matching the sidebar_full
        // pattern used in the admin panel), then reload so the server-rendered
        // dir/lang/__() strings pick up the new cookie. The switch itself already
        // happened over Livewire's background request — this is just the reload.
        localStorage.setItem('frontend_locale', $event.detail.code);
        window.location.reload();
    "
    class="relative inline-block text-left">
    <button @click="open = !open" type="button"
        class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-gray-700 hover:text-gray-900 transition">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418"/>
        </svg>
        {{ $activeLanguage->native_name ?? __('messages.language') }}
        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
    </button>

    <div x-cloak x-show="open" x-transition
        class="absolute end-0 mt-2 w-40 rounded-lg bg-white shadow-lg border border-gray-200 overflow-hidden z-50">
        @foreach ($languages as $language)
            <button wire:click="switchTo('{{ $language->code }}')" wire:loading.attr="disabled" type="button"
                class="w-full flex items-center gap-2 px-4 py-2.5 text-sm text-start hover:bg-gray-50 transition
                    {{ optional($activeLanguage)->code === $language->code ? 'text-indigo-600 font-medium' : 'text-gray-700' }}">
                @if($language->flag_emoji)
                    <span>{{ $language->flag_emoji }}</span>
                @endif
                {{ $language->native_name }}
            </button>
        @endforeach
    </div>
</div>
