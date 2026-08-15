<div x-data x-init="$store.pageName = { name: 'Roles & Permissions', slug: 'role' }">

    {{-- Page Header --}}
    <div class="flex flex-wrap items-center justify-end gap-4 mb-6">
        <a href="{{ route('admin.roles.create') }}"
            class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white shadow-sm hover:bg-gray-700 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
            </svg>
            New Role
        </a>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Total Roles</p>
            <p class="mt-1 text-3xl font-bold text-gray-800">{{ $roles->count() }}</p>
        </div>
        <div class="bg-white rounded-xl border border-gray-200 p-5">
            <p class="text-xs font-medium text-gray-400 uppercase tracking-wider">Total Permissions</p>
            <p class="mt-1 text-3xl font-bold text-gray-800">{{ $roles->sum(fn($r) => $r->permissions->count()) }}</p>
        </div>
    </div>

    {{-- Main Card --}}
    <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

        {{-- Toolbar --}}
        <div class="px-5 py-4 border-b border-gray-100 flex flex-wrap items-center justify-between gap-4">
            <p class="text-sm font-semibold text-gray-700">All Roles</p>
            <div class="relative w-full sm:w-64">
                <svg xmlns="http://www.w3.org/2000/svg" class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M17 11A6 6 0 1 1 5 11a6 6 0 0 1 12 0z" />
                </svg>
                <input type="text" wire:model.live="search" placeholder="Search roles or permissions..."
                    class="w-full rounded-lg border border-gray-200 bg-gray-50 pl-9 pr-4 py-2 text-sm text-gray-700 placeholder:text-gray-400 focus:border-gray-400 focus:ring-0 focus:outline-none transition" />
            </div>
        </div>

        {{-- Table --}}
        <div class="overflow-x-auto">
            <table class="min-w-full">
                <thead>
                    <tr class="bg-gray-50 border-b border-gray-100">
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider w-10">#</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Role</th>
                        <th class="px-6 py-3 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Permissions</th>
                        <th class="sticky right-0 bg-gray-50 px-6 py-3 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse ($roles as $index => $role)
                        <tr class="group hover:bg-gray-50 transition-colors">

                            {{-- Index --}}
                            <td class="px-6 py-4 text-sm text-gray-400">{{ $index + 1 }}</td>

                            {{-- Role Name --}}
                            <td class="px-6 py-4">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gray-900 text-white text-xs font-bold uppercase shrink-0">
                                        {{ substr($role->name, 0, 2) }}
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-gray-800">{{ $role->name }}</p>
                                        <p class="text-xs text-gray-400">{{ $role->permissions->count() }} permission{{ $role->permissions->count() !== 1 ? 's' : '' }}</p>
                                    </div>
                                </div>
                            </td>

                            {{-- Permissions --}}
                            <td class="px-6 py-4">
                                <div class="flex flex-wrap gap-1.5 max-w-md">
                                    @forelse ($role->permissions->take(5) as $permission)
                                        @php
                                            $colors = [
                                                'create' => 'bg-green-50 text-green-700 ring-green-200',
                                                'edit'   => 'bg-blue-50 text-blue-700 ring-blue-200',
                                                'delete' => 'bg-red-50 text-red-700 ring-red-200',
                                                'view'   => 'bg-purple-50 text-purple-700 ring-purple-200',
                                            ];
                                            $color = 'bg-gray-100 text-gray-600 ring-gray-200';
                                            foreach ($colors as $keyword => $cls) {
                                                if (str_contains(strtolower($permission->name), $keyword)) {
                                                    $color = $cls;
                                                    break;
                                                }
                                            }
                                        @endphp
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium ring-1 ring-inset {{ $color }}">
                                            {{ $permission->name }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-gray-400 italic">No permissions</span>
                                    @endforelse
                                    @if ($role->permissions->count() > 5)
                                        <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium bg-gray-100 text-gray-500 ring-1 ring-inset ring-gray-200">
                                            +{{ $role->permissions->count() - 5 }} more
                                        </span>
                                    @endif
                                </div>
                            </td>

                            {{-- Actions --}}
                            <td class="sticky right-0 bg-white group-hover:bg-gray-50 px-6 py-4">
                                <div class="flex items-center justify-end gap-1">

                                    {{-- View --}}
                                    <button wire:click="openViewModal({{ $role->id }})"
                                        title="View"
                                        class="inline-flex items-center justify-center h-8 w-8 rounded-lg text-gray-500 hover:bg-gray-100 hover:text-gray-800 transition-colors">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                    </button>

                                    {{-- Edit --}}
                                    @can('role.edit')
                                        <a href="{{ route('admin.roles.edit', $role->id) }}"
                                            title="Edit"
                                            class="inline-flex items-center justify-center h-8 w-8 rounded-lg text-gray-500 hover:bg-blue-50 hover:text-blue-600 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.25 2.25 0 1 1 3.182 3.182L7.5 19.213l-4.5 1.5 1.5-4.5 12.362-12.226z" />
                                            </svg>
                                        </a>
                                    @endcan

                                    {{-- Delete --}}
                                    @can('role.delete')
                                        <button
                                            wire:click="deleteRole({{ $role->id }})"
                                            wire:confirm="Are you sure you want to delete the '{{ $role->name }}' role?"
                                            title="Delete"
                                            class="inline-flex items-center justify-center h-8 w-8 rounded-lg text-gray-500 hover:bg-red-50 hover:text-red-600 transition-colors">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0 1 16.138 21H7.862a2 2 0 0 1-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 0 0-1-1h-4a1 1 0 0 0-1 1v3M4 7h16" />
                                            </svg>
                                        </button>
                                    @endcan

                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-6 py-16 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                </svg>
                                <p class="text-sm font-medium text-gray-500">No roles found</p>
                                <p class="text-xs text-gray-400 mt-1">Try a different search or create a new role.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Footer --}}
        @if ($roles->count() > 0)
            <div class="px-6 py-3 border-t border-gray-100 bg-gray-50">
                <p class="text-xs text-gray-400">Showing {{ $roles->count() }} role{{ $roles->count() !== 1 ? 's' : '' }}</p>
            </div>
        @endif
    </div>


    {{-- View Modal --}}
    <div x-cloak x-data="{ open: @entangle('viewModal') }" x-show="open"
        class="fixed inset-0 z-50 flex items-center justify-center bg-black/40 p-4"
        x-transition:enter="transition ease-out duration-150"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-100"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0">

        <div @click.away="open = false"
            class="bg-white w-full max-w-lg max-h-[90vh] rounded-xl shadow-xl overflow-hidden flex flex-col"
            x-show="open"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 scale-95"
            x-transition:enter-end="opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-100"
            x-transition:leave-start="opacity-100 scale-100"
            x-transition:leave-end="opacity-0 scale-95">

            {{-- Modal Header --}}
            <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100 shrink-0">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-lg bg-gray-900 text-white text-xs font-bold uppercase">
                        {{ substr($view['name'] ?? 'R', 0, 2) }}
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-800">{{ $view['name'] ?? '—' }}</h2>
                        <p class="text-xs text-gray-400">Role Details</p>
                    </div>
                </div>
                <button wire:click="closeViewModal" class="inline-flex items-center justify-center h-8 w-8 rounded-lg text-gray-400 hover:bg-gray-100 hover:text-gray-600 transition-colors">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            {{-- Modal Body --}}
            <div class="px-6 py-5 overflow-y-auto">
                <p class="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">
                    Permissions ({{ isset($view['permissions']) ? count($view['permissions']) : 0 }})
                </p>
                <div class="flex flex-wrap gap-2">
                    @if (!empty($view['permissions']))
                        @foreach ($view['permissions'] as $permission)
                            @php
                                $colors = [
                                    'create' => 'bg-green-50 text-green-700 ring-green-200',
                                    'edit'   => 'bg-blue-50 text-blue-700 ring-blue-200',
                                    'delete' => 'bg-red-50 text-red-700 ring-red-200',
                                    'view'   => 'bg-purple-50 text-purple-700 ring-purple-200',
                                ];
                                $color = 'bg-gray-100 text-gray-600 ring-gray-200';
                                foreach ($colors as $keyword => $cls) {
                                    if (str_contains(strtolower($permission['name']), $keyword)) {
                                        $color = $cls;
                                        break;
                                    }
                                }
                            @endphp
                            <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium ring-1 ring-inset {{ $color }}">
                                {{ $permission['name'] }}
                            </span>
                        @endforeach
                    @else
                        <p class="text-sm text-gray-400 italic">No permissions assigned to this role.</p>
                    @endif
                </div>
            </div>

            {{-- Modal Footer --}}
            <div class="px-6 py-4 border-t border-gray-100 bg-gray-50 flex justify-end gap-2 shrink-0">
                @can('role.edit')
                    <a href="{{ route('admin.roles.edit', $view['id'] ?? 0) }}"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 transition-colors">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.25 2.25 0 1 1 3.182 3.182L7.5 19.213l-4.5 1.5 1.5-4.5 12.362-12.226z" />
                        </svg>
                        Edit Role
                    </a>
                @endcan
                <button wire:click="closeViewModal"
                    class="inline-flex items-center rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 transition-colors">
                    Close
                </button>
            </div>
        </div>
    </div>

</div>

@push('scripts')
    @if (session()->has('success'))
        <script>
            document.addEventListener('livewire:init', () => {
                Toast.fire({ icon: 'success', title: '{{ session('success') }}' });
            });
        </script>
    @endif
    @if (session()->has('error'))
        <script>
            document.addEventListener('livewire:init', () => {
                Toast.fire({ icon: 'error', title: '{{ session('error') }}' });
            });
        </script>
    @endif
@endpush
