<div x-data x-show="$store.ui.supportOpen" x-cloak class="modal" @click.self="$store.ui.supportOpen=false">
    <div class="modal__box modal__box--sm">
        <div class="modal__head" style="margin-bottom:4px">
            <p class="modal__title" style="font-size:22px">Talk to us</p>
            <button class="modal__close" @click="$store.ui.supportOpen=false" aria-label="Close">✕</button>
        </div>
        @php
            $contact = \App\Support\ContactInfo::class;
            $channels = array_filter([
                $contact::telHref() ? ['Call us', $contact::phone(), $contact::telHref(), 'phone', 'var(--ac-soft)', 'var(--pri)'] : null,
                $contact::whatsappUrl() ? ['WhatsApp', 'Chat with an advisor', $contact::whatsappUrl(), 'whatsapp', 'rgba(37,211,102,.12)', '#128C4A'] : null,
                $contact::messengerUrl() ? ['Messenger', 'Message us on Facebook', $contact::messengerUrl(), 'messenger', 'rgba(0,132,255,.12)', '#0084FF'] : null,
            ]);
            // Admin > Site Settings > Social Links; empty ones are hidden.
            $socials = array_filter([
                'facebook' => \App\Models\Setting::get('facebook', '', 'social'),
                'instagram' => \App\Models\Setting::get('instagram', '', 'social'),
                'youtube' => \App\Models\Setting::get('youtube', '', 'social'),
                'tiktok' => \App\Models\Setting::get('tiktok', '', 'social'),
            ], fn ($v) => is_string($v) && trim($v) !== '');
        @endphp
        @if($contact::hours())<p class="muted" style="font-size:12.5px;margin:0 0 20px">{{ $contact::hours() }}</p>@else<div style="margin-bottom:16px"></div>@endif
        <div style="display:flex;flex-direction:column;gap:10px">
            @foreach($channels as [$t,$sub,$href,$icon,$bg,$fg])
                <a href="{{ $href }}" target="_blank" rel="noopener" style="display:flex;align-items:center;gap:14px;padding:14px 16px;border:1px solid rgba(var(--pri-rgb),.12);border-radius:12px;background:#fff">
                    <span style="flex:none;width:40px;height:40px;border-radius:999px;background:{{ $bg }};color:{{ $fg }};display:flex;align-items:center;justify-content:center"><x-icon :name="$icon" /></span>
                    <span><span style="display:block;font-size:13.5px;font-weight:600">{{ $t }}</span><span class="muted" style="display:block;font-size:12px">{{ $sub }}</span></span>
                </a>
            @endforeach
        </div>
        @if($socials)
        <div style="margin-top:20px;padding-top:18px;border-top:1px solid rgba(var(--pri-rgb),.1)">
            <p class="kicker" style="color:rgba(var(--pri-rgb),.45)">Follow us</p>
            <div style="display:flex;gap:10px">
                @foreach($socials as $soc => $socUrl)
                    <a href="{{ $socUrl }}" target="_blank" rel="noopener" class="icon-btn" style="width:42px;height:42px" aria-label="{{ ucfirst($soc) }}"><x-icon :name="$soc" /></a>
                @endforeach
            </div>
        </div>
        @endif
    </div>
</div>
