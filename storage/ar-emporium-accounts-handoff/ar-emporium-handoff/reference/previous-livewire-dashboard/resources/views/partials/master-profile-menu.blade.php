{{--
    Master Profile sidebar group — its own top-level group, above Accounts.
    One person/company = one profile; roles: customer, supplier, lender, owner, login.
    Route names are guesses: links fall back to "#" until a matching route exists.
--}}
@php
    $link = fn (string $name, array $params = []) => Route::has($name) ? route($name, $params) : '#';
    $base = 'admin.profiles.index';

    $roles = [
        'customer' => 'Customers',
        'supplier' => 'Suppliers',
        'lender'   => 'Lenders',
        'owner'    => 'Owners',
        'login'    => 'Login Profiles',
    ];

    $onProfiles  = request()->routeIs('admin.profiles.*');
    $currentRole = request('role');
@endphp

<div x-data="{ open: {{ $onProfiles ? 'true' : 'false' }} }" class="flex flex-col gap-0.5">
    <button type="button" @click="open = !open" :aria-expanded="open"
            class="flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-slate-300 hover:bg-slate-800 hover:text-white">
        <svg class="h-[18px] w-[18px]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="9" cy="8" r="3.5"/><path d="M2.5 20c.8-3.5 3.4-5.5 6.5-5.5s5.7 2 6.5 5.5"/><path d="M16 4.5a3.5 3.5 0 0 1 0 7"/><path d="M18.5 14.8c1.6.7 2.6 2.4 3 5.2"/></svg>
        <span class="flex-1">Master Profile</span>
        <svg class="h-4 w-4 transition" :class="open && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="m6 9 6 6 6-6"/></svg>
    </button>

    <div x-show="open" x-collapse class="ml-[18px] flex flex-col gap-0.5 border-l border-slate-700 pl-3">
        <a href="{{ $link($base) }}" wire:navigate
           @class([
               'flex items-center justify-between rounded-md px-2 py-1.5 text-[13.5px]',
               'bg-slate-800 font-semibold text-white' => $onProfiles && ! $currentRole && ! request()->routeIs('admin.profiles.create'),
               'text-slate-300 hover:text-white' => ! ($onProfiles && ! $currentRole && ! request()->routeIs('admin.profiles.create')),
           ])>
            All Profiles
            @isset($profileCount)
                <span class="text-xs text-slate-500">{{ $profileCount }}</span>
            @endisset
        </a>

        @foreach ($roles as $role => $label)
            <a href="{{ $link($base, ['role' => $role]) }}" wire:navigate
               @class([
                   'rounded-md px-2 py-1.5 text-[13px]',
                   'bg-slate-800 font-semibold text-white' => $onProfiles && $currentRole === $role,
                   'text-slate-400 hover:text-white' => ! ($onProfiles && $currentRole === $role),
               ])>{{ $label }}</a>
        @endforeach

        <a href="{{ $link('admin.profiles.create') }}" wire:navigate
           class="rounded-md px-2 py-1.5 text-[13px] font-semibold text-indigo-300 hover:text-white">+ New profile</a>
    </div>
</div>
