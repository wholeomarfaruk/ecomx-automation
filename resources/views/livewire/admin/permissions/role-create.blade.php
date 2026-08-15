@php $isEdit = $roleId > 0; @endphp
<div x-data x-init="$store.pageName = { name: '{{ $isEdit ? 'Edit Role' : 'Create Role' }}', slug: 'role-create' }">

    {{-- Page Header --}}
    <div class="flex flex-wrap items-center justify-end gap-4 mb-6">
        <a href="{{ route('admin.roles.list') }}"
            class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-medium text-gray-600 hover:bg-gray-50 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18" />
            </svg>
            Back to Roles
        </a>
    </div>

    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

            {{-- Left: Role Name Card --}}
            <div class="lg:col-span-1">
                <div class="bg-white rounded-xl border border-gray-200 p-6 sticky top-6">
                    <h2 class="text-sm font-semibold text-gray-800 mb-4">Role Details</h2>

                    <div class="space-y-4">
                        <div>
                            <label class="block text-xs font-medium text-gray-500 mb-1.5">
                                Role Name <span class="text-red-500">*</span>
                            </label>
                            <input
                                wire:model="name"
                                type="text"
                                placeholder="e.g. Editor, Manager"
                                class="w-full rounded-lg border border-gray-200 bg-gray-50 px-4 py-2.5 text-sm text-gray-800 placeholder:text-gray-400 focus:border-gray-400 focus:bg-white focus:ring-0 focus:outline-none transition" />
                            @error('name')
                                <p class="mt-1.5 text-xs text-red-500">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="pt-2 border-t border-gray-100">
                            <p class="text-xs text-gray-400 mb-3">
                                <span class="font-semibold text-gray-600">{{ count($permissions) }}</span>
                                permission{{ count($permissions) !== 1 ? 's' : '' }} selected
                            </p>
                            <button type="submit"
                                class="w-full inline-flex items-center justify-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-medium text-white hover:bg-gray-700 transition-colors">
                                @if($isEdit)
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.862 3.487a2.25 2.25 0 1 1 3.182 3.182L7.5 19.213l-4.5 1.5 1.5-4.5 12.362-12.226z" />
                                    </svg>
                                    Update Role
                                @else
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4" />
                                    </svg>
                                    Create Role
                                @endif
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Right: Permissions Card --}}
            <div class="lg:col-span-2">
                <div class="bg-white rounded-xl border border-gray-200 overflow-hidden">

                    {{-- Permissions Header --}}
                    <div class="flex items-center justify-between px-6 py-4 border-b border-gray-100">
                        <div>
                            <h2 class="text-sm font-semibold text-gray-800">Permissions</h2>
                            <p class="text-xs text-gray-400 mt-0.5">Assign permissions to this role</p>
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer select-none">
                            <span class="text-xs font-medium text-gray-500">Select All</span>
                            <div class="relative">
                                <input type="checkbox" wire:model.live="selectAll" class="sr-only peer" />
                                <div class="w-9 h-5 bg-gray-200 rounded-full peer peer-checked:bg-gray-900 transition-colors"></div>
                                <div class="absolute top-0.5 left-0.5 h-4 w-4 bg-white rounded-full shadow transition-transform peer-checked:translate-x-4"></div>
                            </div>
                        </label>
                    </div>

                    {{-- Permission Groups --}}
                    <div class="divide-y divide-gray-50 p-4 space-y-2">

                        @forelse ($permissionsGrouped as $module => $modulePerms)
                            @php
                                $modulePermNames = $modulePerms->pluck('name')->toArray();
                                $selectedCount   = count(array_intersect($modulePermNames, $permissions));
                                $allChecked      = $selectedCount === count($modulePermNames);

                                $moduleColors = [
                                    'create' => 'bg-green-50 text-green-700 ring-green-200',
                                    'edit'   => 'bg-blue-50 text-blue-700 ring-blue-200',
                                    'delete' => 'bg-red-50 text-red-700 ring-red-200',
                                    'view'   => 'bg-purple-50 text-purple-700 ring-purple-200',
                                ];
                            @endphp
                            <div class="rounded-lg border border-gray-100 overflow-hidden">

                                {{-- Module Header --}}
                                <div class="flex items-center justify-between px-4 py-3 bg-gray-50">
                                    <div class="flex items-center gap-2">
                                        <div class="h-6 w-6 rounded bg-gray-800 flex items-center justify-center">
                                            <span class="text-white text-xs font-bold uppercase">{{ substr($module, 0, 1) }}</span>
                                        </div>
                                        <span class="text-sm font-semibold text-gray-700 capitalize">{{ $module }}</span>
                                        <span class="text-xs text-gray-400">({{ $selectedCount }}/{{ count($modulePermNames) }})</span>
                                    </div>
                                    <button type="button"
                                        wire:click="toggleModule({{ json_encode($modulePermNames) }})"
                                        class="text-xs font-medium px-3 py-1 rounded-md transition-colors
                                            {{ $allChecked
                                                ? 'bg-gray-200 text-gray-600 hover:bg-gray-300'
                                                : 'bg-gray-900 text-white hover:bg-gray-700' }}">
                                        {{ $allChecked ? 'Deselect' : 'Select All' }}
                                    </button>
                                </div>

                                {{-- Module Permissions --}}
                                <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 p-4">
                                    @foreach ($modulePerms as $permission)
                                        @php
                                            $action     = explode('.', $permission->name)[1] ?? '';
                                            $badgeColor = $moduleColors[$action] ?? 'bg-gray-100 text-gray-600 ring-gray-200';
                                            $isChecked  = in_array($permission->name, $permissions);
                                        @endphp
                                        <label class="flex items-center gap-2.5 rounded-lg border p-2.5 cursor-pointer transition-colors
                                            {{ $isChecked ? 'border-gray-900 bg-gray-900/5' : 'border-gray-100 hover:border-gray-200 hover:bg-gray-50' }}">
                                            <input
                                                type="checkbox"
                                                wire:model.live="permissions"
                                                value="{{ $permission->name }}"
                                                class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900 cursor-pointer" />
                                            <span class="text-xs font-medium {{ $isChecked ? 'text-gray-800' : 'text-gray-500' }} capitalize">
                                                {{ $action ?: $permission->name }}
                                            </span>
                                        </label>
                                    @endforeach
                                </div>
                            </div>
                        @empty
                            <div class="py-12 text-center">
                                <svg xmlns="http://www.w3.org/2000/svg" class="mx-auto h-10 w-10 text-gray-300 mb-3" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.5">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />
                                </svg>
                                <p class="text-sm font-medium text-gray-500">No permissions found</p>
                                <p class="text-xs text-gray-400 mt-1">Run the permission seeder to add permissions.</p>
                            </div>
                        @endforelse

                    </div>
                </div>
            </div>

        </div>
    </form>

</div>
