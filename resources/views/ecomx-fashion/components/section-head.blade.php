@props(['kicker'=>null,'title','link'=>null,'linkLabel'=>'View all →'])
<div style="display:flex;justify-content:space-between;align-items:flex-end;gap:16px;margin-bottom:24px">
    <div>
        @if($kicker)<p class="kicker">{{ $kicker }}</p>@endif
        <h2 class="h-section">{{ $title }}</h2>
    </div>
    @if($link)<a href="{{ $link }}" style="font-size:13px;font-weight:500;color:var(--ac2);white-space:nowrap">{{ $linkLabel }}</a>@endif
</div>
