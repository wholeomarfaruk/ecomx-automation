@php
    $supportPhone = \App\Support\ContactInfo::phone();
    $supportTel = \App\Support\ContactInfo::telHref();
    $supportWhatsapp = \App\Support\ContactInfo::whatsappUrl();
    $supportMessenger = \App\Support\ContactInfo::messengerUrl();
    $supportHours = \App\Support\ContactInfo::hours();
@endphp
<button class="jtc-fab" aria-label="Call support" @click="$store.ui.supportOpen = true">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="20" height="20"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13 1 .37 1.94.72 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.87.35 1.81.59 2.81.72A2 2 0 0 1 22 16.92z"></path></svg>
    <span>Call support</span>
</button>

<div class="jtc-modal-scrim" :class="$store.ui.supportOpen && 'is-open'" @click="$store.ui.supportOpen = false" x-cloak>
    <div class="jtc-modal jtc-modal--support" @click.stop>
        <div class="jtc-modal__head jtc-modal__head--brand">
            <button class="jtc-modal__close jtc-modal__close--light" aria-label="Close" @click="$store.ui.supportOpen = false">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="18" height="18"><line x1="6" y1="6" x2="18" y2="18"></line><line x1="18" y1="6" x2="6" y2="18"></line></svg>
            </button>
            <h3>We're here to help</h3>
            <p>Reach {{ \App\Models\Setting::get('site_name', 'AnyNiche') ?: 'AnyNiche' }} any way you like. @if($supportHours)<br>{{ $supportHours }} @endif</p>
        </div>

        <div class="jtc-support__list">
            @if($supportTel)
            <a href="{{ $supportTel }}" class="jtc-support__item">
                <span class="jtc-support__icon jtc-support__icon--phone">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="21" height="21"><path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72c.13 1 .37 1.94.72 2.81a2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45c.87.35 1.81.59 2.81.72A2 2 0 0 1 22 16.92z"></path></svg>
                </span>
                <span style="flex:1"><span class="jtc-support__title">Call us</span><span class="jtc-support__desc">{{ $supportPhone }}</span></span>
                <svg class="jtc-support__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>
            @endif

            @if($supportWhatsapp)
            <a href="{{ $supportWhatsapp }}" target="_blank" rel="noopener" class="jtc-support__item">
                <span class="jtc-support__icon jtc-support__icon--wa">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="23" height="23"><path d="M12.04 2C6.58 2 2.13 6.45 2.13 11.91c0 1.75.46 3.45 1.32 4.95L2 22l5.25-1.38a9.9 9.9 0 0 0 4.79 1.22h.01c5.46 0 9.9-4.45 9.9-9.91 0-2.65-1.03-5.14-2.9-7.01A9.82 9.82 0 0 0 12.04 2zm0 18.15h-.01a8.2 8.2 0 0 1-4.19-1.15l-.3-.18-3.11.82.83-3.04-.2-.31a8.19 8.19 0 0 1-1.26-4.38c0-4.54 3.7-8.24 8.24-8.24 2.2 0 4.27.86 5.83 2.42a8.19 8.19 0 0 1 2.41 5.83c0 4.54-3.7 8.24-8.24 8.24zm4.52-6.16c-.25-.12-1.47-.72-1.69-.81-.23-.08-.39-.12-.56.12-.16.25-.64.81-.79.98-.14.16-.29.18-.54.06-.25-.12-1.05-.39-1.99-1.23-.74-.66-1.23-1.47-1.38-1.72-.14-.25-.01-.38.11-.5.11-.11.25-.29.37-.43.12-.14.16-.25.25-.41.08-.16.04-.31-.02-.43-.06-.12-.56-1.34-.76-1.84-.2-.48-.4-.42-.56-.43h-.48c-.16 0-.43.06-.66.31-.23.25-.86.85-.86 2.07 0 1.22.89 2.4 1.01 2.56.12.16 1.75 2.67 4.23 3.74.59.26 1.05.41 1.41.52.59.19 1.13.16 1.56.1.48-.07 1.47-.6 1.68-1.18.21-.58.21-1.07.14-1.18-.06-.11-.22-.17-.47-.29z"></path></svg>
                </span>
                <span style="flex:1"><span class="jtc-support__title">WhatsApp</span><span class="jtc-support__desc">Chat with us instantly</span></span>
                <svg class="jtc-support__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>
            @endif

            @if($supportMessenger)
            <a href="{{ $supportMessenger }}" target="_blank" rel="noopener" class="jtc-support__item">
                <span class="jtc-support__icon jtc-support__icon--msg">
                    <svg viewBox="0 0 24 24" fill="currentColor" width="23" height="23"><path d="M12 2C6.36 2 2 6.13 2 11.7c0 2.9 1.19 5.4 3.14 7.13.16.14.26.35.27.57l.05 1.78c.02.57.6.94 1.12.71l1.99-.88c.17-.07.36-.09.54-.04 1 .27 2.06.42 3.14.42 5.64 0 10-4.13 10-9.7C22 6.13 17.64 2 12 2zm6 7.46-2.93 4.66c-.47.74-1.47.93-2.18.4l-2.33-1.75a.6.6 0 0 0-.72 0l-3.15 2.39c-.42.32-.97-.18-.69-.63l2.93-4.66c.47-.74 1.47-.93 2.18-.4l2.33 1.75c.21.16.51.16.72 0l3.15-2.39c.42-.32.97.18.69.63z"></path></svg>
                </span>
                <span style="flex:1"><span class="jtc-support__title">Messenger</span><span class="jtc-support__desc">Message us on Facebook</span></span>
                <svg class="jtc-support__chev" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16"><polyline points="9 18 15 12 9 6"></polyline></svg>
            </a>
            @endif
        </div>
    </div>
</div>
