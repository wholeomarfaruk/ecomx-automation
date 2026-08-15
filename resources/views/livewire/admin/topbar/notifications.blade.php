<div x-data="{ open: false }" class="relative" wire:poll.30s>
    <button @click="open = !open" @click.outside="open = false"
        class="relative flex items-center justify-center w-10 h-10 rounded-full text-gray-500 hover:text-gray-700 hover:bg-gray-100 transition focus:outline-none">
        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
        </svg>

        @if ($unreadCount > 0)
            <span class="absolute -top-0.5 -right-0.5 flex items-center justify-center h-5 min-w-5 px-1 rounded-full bg-red-500 text-white text-[11px] font-semibold leading-none">
                {{ $unreadCount > 9 ? '9+' : $unreadCount }}
            </span>
        @endif
    </button>

    <div x-cloak x-show="open" x-transition
        class="absolute right-0 mt-2 w-[calc(100vw-2rem)] max-w-80 bg-white border border-gray-200 rounded-lg shadow-lg overflow-hidden z-50">
        <div class="flex items-center justify-between px-4 py-3 border-b border-gray-100">
            <h3 class="text-sm font-semibold text-gray-800">Notifications</h3>
            @if ($unreadCount > 0)
                <button wire:click="markAllAsRead" class="text-xs text-indigo-600 hover:text-indigo-800 font-medium">
                    Mark all as read
                </button>
            @endif
        </div>

        <div class="max-h-80 overflow-y-auto divide-y divide-gray-100">
            @forelse ($notifications as $notification)
                <button wire:click="markAsRead('{{ $notification->id }}')"
                    class="w-full text-left px-4 py-3 flex gap-3 hover:bg-gray-50 transition {{ is_null($notification->read_at) ? 'bg-indigo-50/50' : '' }}">
                    <span class="mt-1.5 h-2 w-2 rounded-full shrink-0 {{ is_null($notification->read_at) ? 'bg-indigo-500' : 'bg-transparent' }}"></span>
                    <span class="min-w-0">
                        <span class="block text-sm text-gray-700 truncate">{{ $notification->data['message'] ?? 'New notification' }}</span>
                        <span class="block text-xs text-gray-400 mt-0.5">{{ local_time($notification->created_at)->diffForHumans() }}</span>
                    </span>
                </button>
            @empty
                <div class="px-4 py-8 text-center text-sm text-gray-400">
                    No notifications yet
                </div>
            @endforelse
        </div>
    </div>
</div>
