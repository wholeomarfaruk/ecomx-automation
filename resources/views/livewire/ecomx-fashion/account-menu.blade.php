<div>
@auth
    @php
        $authUser = auth()->user();
        $authAvatarUrl = null;
        try {
            $authAvatarUrl = $authUser->avatar_id ? file_path($authUser->avatar_id) : null;
        } catch (\Throwable $e) {
            $authAvatarUrl = null;
        }
        $authInitial = strtoupper(mb_substr(trim($authUser->name) ?: '?', 0, 1));
    @endphp
    <div class="dropdown-wrap" x-data="{ open: false }" style="position:relative">
        <button class="account-trigger" @click="open=!open" aria-label="Account" aria-haspopup="true" :aria-expanded="open">
            @if ($authAvatarUrl)
                <img src="{{ $authAvatarUrl }}" alt="{{ $authUser->name }}" class="account-trigger__avatar">
            @else
                <span class="account-trigger__initial">{{ $authInitial }}</span>
            @endif
            <span class="account-trigger__chevron"><x-icon name="chevron-down" /></span>
        </button>
        <div class="dropdown" x-show="open" x-cloak @click.outside="open=false" x-transition style="right:0;left:auto">
            <div style="padding:10px 12px;border-bottom:1px solid rgba(var(--pri-rgb),.08);margin-bottom:4px">
                <p style="font-size:13px;font-weight:600;color:var(--pri);margin:0">{{ $authUser->name }}</p>
                @if($authUser->phone)
                    <p style="font-size:11.5px;color:rgba(var(--pri-rgb),.5);margin:2px 0 0">{{ $authUser->phone }}</p>
                @endif
            </div>
            <a href="{{ route('ecomx-fashion.track') }}" class="dropdown__link" wire:navigate>My Orders</a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="dropdown__link" style="width:100%;text-align:left;border:none;background:none;cursor:pointer;font-family:inherit">Logout</button>
            </form>
        </div>
    </div>
@else
    <button class="icon-btn" @click="$store.ui.authOpen=true" aria-label="Account"><x-icon name="user" /></button>
@endauth
</div>
