import Swiper from 'swiper';
import { Navigation } from 'swiper/modules';
import 'swiper/css';
import 'swiper/css/navigation';
import { Fancybox } from '@fancyapps/ui';
import '@fancyapps/ui/dist/fancybox/fancybox.css';
import { Notyf } from 'notyf';
import 'notyf/notyf.min.css';

// Global lightbox for every `[data-fancybox]` element on the page (product
// image/video gallery, video-gallery row, etc). Fancybox v6's bind() delegates
// from document, so elements added later by Alpine/Livewire re-renders are
// picked up automatically — no per-component re-binding needed.
Fancybox.bind('[data-fancybox]', {
    groupAll: false,
    Thumbs: { type: 'classic' },
});

// Toast notifications for Livewire's `notify` event (add-to-cart, wishlist
// toggle, stock errors, etc — dispatched from CartManager/TogglesWishlist).
// Plain library defaults — no custom colors/CSS.
const notyf = new Notyf({
    duration: 3200,
    position: { x: 'right', y: 'top' },
});

// Livewire's own JS bundle (@livewireScripts) already ships and starts Alpine.
// Importing a second `alpinejs` package here and calling Alpine.start() on it
// would create two competing Alpine instances — Livewire's wire:/$wire/x-intersect
// directives only work under Livewire's instance, so a second Alpine.start()
// silently breaks them (e.g. #[Lazy]/#[Defer] components never swap in their
// real content because $wire.__lazyLoad() is undefined on the wrong instance).
// Register onto the existing global instance via alpine:init instead.
// Storefront visitor identity cookie. The legacy website theme sets this via
// its own device-registration flow (see layouts/website.blade.php); the
// ecomx-fashion theme doesn't have that wired up yet, so we mint one here on
// first use — same cookie name/shape the backend already reads everywhere
// (Cart controller, WishlistDrawer Livewire component: request()->cookie('_sfdid'))
// so that registration flow can be reused here later without any renaming.
function getCookie(name) {
    return document.cookie.split(';').map((c) => c.trim()).find((c) => c.startsWith(name + '='))?.split('=')[1];
}
function ensureDeviceId() {
    let id = getCookie('_sfdid');
    if (!id) {
        id = (window.crypto?.randomUUID ? window.crypto.randomUUID() : `${Date.now()}-${Math.random().toString(16).slice(2)}`);
        document.cookie = `_sfdid=${id};path=/;max-age=${60 * 60 * 24 * 365};samesite=lax`;
    }
    return id;
}
ensureDeviceId();

// Registered at top level (not nested inside alpine:init) — livewire:init
// fires before alpine:init in the normal boot sequence, so nesting this
// there meant it was registered for an event that had already passed and
// the listener never attached (header wishlist badge silently stayed at 0).
// Alpine.store('ui') is read lazily inside the callback, not at registration
// time, since by the time a real toggle happens both events have long fired.
document.addEventListener('livewire:init', () => {
    Livewire.on('wishlist-updated', ({ count }) => {
        window.Alpine.store('ui').wishlistCount = count;
    });

    Livewire.on('notify', ({ message, type }) => {
        notyf[type === 'error' ? 'error' : 'success'](message);
    });

    // Real DB-backed cart (App\Livewire\EcomxFashion\CartManager, mounted once
    // in the layout) renders its own line items directly via wire:click.
    // `cart-updated` only ever touches the header badge count — every cart
    // mutation (add/remove/qty/clear) fires this, including ones that happen
    // while the drawer is already open, so it must never also open/reopen it
    // (that caused the slide-in animation to replay on remove/qty changes).
    Livewire.on('cart-updated', ({ cartcount }) => {
        window.Alpine.store('ui').cartCount = cartcount;
    });

    // `cart-open` is dispatched only by addToCart() — the one mutation that
    // should actually slide the drawer in.
    Livewire.on('cart-open', () => {
        window.Alpine.store('ui').cartOpen = true;
    });
});

document.addEventListener('alpine:init', () => {
    const Alpine = window.Alpine;

    // Global store: cart/wishlist state + auth/support modals. Palette is a
    // fixed, site-wide setting rendered server-side (data-pal on <html> via
    // PaletteRegistry/admin "Appearance" panel) — not user-overridable here.
    Alpine.store('ui', {
        authOpen: false,
        supportOpen: false,
        cartOpen: false,
        searchOpen: false,
        wishlistOpen: false,
        // Cart line items themselves are rendered directly by the Livewire
        // component (App\Livewire\EcomxFashion\CartManager /
        // livewire.ecomx-fashion.cart-manager view) via wire:click — no
        // client-side cart array here. This store only tracks the header
        // badge count, kept in sync via the `cart-updated` event (see above).
        cartCount: 0,
        // Wishlist persistence, the "Loved Items" drawer body, and each heart
        // button's filled/empty state are all rendered server-side (Livewire
        // `wire:click="toggleWishlist(...)"` — see WishlistDrawer and the
        // App\Livewire\Concerns\TogglesWishlist trait used by every product
        // listing page). This is just the header badge count, kept in sync
        // via the `wishlist-updated` event dispatched after every toggle/remove.
        wishlistCount: 0,
        get wishlist() {
            return this.wishlistCount;
        },
        get cart() {
            return this.cartCount;
        },
    });

    // Reusable carousel behaviour (Trending, Flash sale, Related, Reviews).
    Alpine.data('carousel', (perPage = 4) => ({
        perPage,
        scrollByPage(dir) {
            const row = this.$refs.row;
            if (row) row.scrollBy({ left: dir * row.clientWidth, behavior: 'smooth' });
        },
    }));

    // PDP gallery: wraps a Swiper instance (arrows + drag/swipe) and keeps its
    // active slide two-way synced with the given Alpine root's `active` index
    // (thumbnails / colour-pick set `active` directly; Swiper drives it back
    // on swipe/arrow). `guard` breaks the slideTo <-> slideChange feedback loop.
    Alpine.data('gallerySwiper', (root) => ({
        swiper: null,
        guard: false,
        init() {
            this.swiper = new Swiper(this.$el, {
                modules: [Navigation],
                observer: true,
                observeParents: true,
                autoHeight: true,
                navigation: {
                    nextEl: this.$el.querySelector('.swiper-button-next'),
                    prevEl: this.$el.querySelector('.swiper-button-prev'),
                },
                initialSlide: root.active,
                on: {
                    slideChange: (sw) => {
                        this.guard = true;
                        root.active = sw.activeIndex;
                        this.guard = false;
                    },
                },
            });
            // Images load after Swiper's initial measurement — re-measure once they
            // do so autoHeight isn't baked in from a zero-height (not-yet-loaded) img.
            this.$el.querySelectorAll('img').forEach((img) => {
                if (img.complete) return;
                img.addEventListener('load', () => this.swiper && this.swiper.update(), { once: true });
            });
            Alpine.effect(() => {
                const val = root.active;
                if (!this.guard && this.swiper && this.swiper.activeIndex !== val) {
                    this.swiper.slideTo(val);
                }
            });
        },
        destroy() {
            if (this.swiper) this.swiper.destroy(true, false);
        },
    }));

    // PDP buy-box: colour/size selection, variant pricing, add-to-cart/buy-now.
    // `config` carries everything the server computed (media/colors/sizes/
    // variantMatrix/basePrice/baseSale/size/productId/checkoutUrl) — see
    // product-gallery-buy-box.blade.php's `x-data="productPage({...})"`.
    Alpine.data('productPage', (config) => ({
        media: config.media,
        colors: config.colors,
        sizes: config.sizes,
        hasColors: config.hasColors,
        hasSizes: config.hasSizes,
        variantMatrix: config.variantMatrix,
        basePrice: config.basePrice,
        baseSale: config.baseSale,
        active: 0,
        color: 0,
        size: config.size,
        spot: false,
        guide: false,
        review: false,
        reviewSent: false,
        rating: 0,
        alert: false,
        added: false,
        buyingNow: false,
        pickColor(i) {
            if (!this.colors[i]) return;
            this.color = i;
            let idx = this.media.findIndex((m) => m.img === this.colors[i]?.main);
            if (idx >= 0) this.active = idx;
            if (this.size && this.sizeOutOfStock(this.size)) this.size = null;
        },
        sizeStock(sizeName) {
            let colorKey = (this.hasColors && this.colors[this.color]) ? this.colors[this.color].name : '*';
            let v = this.variantMatrix[colorKey + '|' + sizeName];
            return v ? v.stock : null;
        },
        sizeOutOfStock(sizeName) {
            let stock = this.sizeStock(sizeName);
            return stock !== null && stock <= 0;
        },
        colorOutOfStock(colorIndex) {
            let colorName = this.colors[colorIndex]?.name;
            if (!colorName) return false;
            if (!this.hasSizes) {
                let v = this.variantMatrix[colorName + '|*'];
                return v ? v.stock <= 0 : false;
            }
            return (this.sizes ?? []).every((sizeName) => {
                let v = this.variantMatrix[colorName + '|' + sizeName];
                return v ? v.stock <= 0 : false;
            });
        },
        get selectedVariant() {
            if (!this.hasColors && !this.hasSizes) return null;
            let colorKey = (this.hasColors && this.colors[this.color]) ? this.colors[this.color].name : '*';
            let sizeKey = this.hasSizes ? this.size : '*';
            if (this.hasSizes && !this.size) return null;
            return this.variantMatrix[colorKey + '|' + sizeKey] ?? null;
        },
        get currentPrice() {
            let v = this.selectedVariant;
            if (v) return v.salePrice ?? v.price;
            return this.baseSale ?? this.basePrice;
        },
        get currentComparePrice() {
            let v = this.selectedVariant;
            if (v) return v.salePrice ? v.price : null;
            return this.baseSale ? this.basePrice : null;
        },
        addToCart() {
            if (this.hasSizes && !this.size) { this.spot = true; setTimeout(() => this.spot = false, 4000); this.$refs.sizeBox.scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }
            this.$dispatch('add-to-cart', { productId: config.productId });
            this.added = true;
        },
        buyNow() {
            if (this.hasSizes && !this.size) { this.spot = true; setTimeout(() => this.spot = false, 4000); this.$refs.sizeBox.scrollIntoView({ behavior: 'smooth', block: 'center' }); return; }
            this.buyingNow = true;
            let offUpdated = Livewire.on('cart-updated', () => {
                offUpdated(); offNotify();
                window.location.href = config.checkoutUrl;
            });
            let offNotify = Livewire.on('notify', (payload) => {
                if (payload.type !== 'error') return;
                offUpdated(); offNotify();
                this.buyingNow = false;
            });
            this.$dispatch('add-to-cart', { productId: config.productId });
        },
    }));

    // Countdown timer for the flash-sale band. Always counts down to
    // 23:59:59 Bangladesh time (Asia/Dhaka, UTC+6, no DST) *today*, so it
    // reads the same real end-of-day target on every refresh instead of
    // restarting from a fixed duration — and rolls over to the next day
    // automatically once it hits zero.
    Alpine.data('countdown', () => ({
        end: 0,
        h: '00', m: '00', s: '00',
        endOfDayDhaka() {
            const DHAKA_OFFSET_MS = 6 * 3600000; // UTC+6, fixed (no DST)
            const nowDhaka = new Date(Date.now() + DHAKA_OFFSET_MS);
            const y = nowDhaka.getUTCFullYear();
            const mo = nowDhaka.getUTCMonth();
            const d = nowDhaka.getUTCDate();
            // 23:59:59 Dhaka time expressed back in real UTC millis.
            return Date.UTC(y, mo, d, 23, 59, 59, 999) - DHAKA_OFFSET_MS;
        },
        tick() {
            let left = this.end - Date.now();
            if (left <= 0) {
                this.end = this.endOfDayDhaka();
                left = this.end - Date.now();
            }
            const p = (n) => String(n).padStart(2, '0');
            this.h = p(Math.floor(left / 3600000));
            this.m = p(Math.floor(left / 60000) % 60);
            this.s = p(Math.floor(left / 1000) % 60);
        },
        init() {
            this.end = this.endOfDayDhaka();
            this.tick();
            setInterval(() => this.tick(), 1000);
        },
    }));
});

// Scroll-reveal: adds .is-in to .reveal elements as they enter the viewport.
const revealObserver = new IntersectionObserver((entries) => {
    entries.forEach((entry) => {
        if (entry.isIntersecting) {
            entry.target.classList.add('is-in');
            revealObserver.unobserve(entry.target);
        }
    });
}, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

function observeReveals() {
    document.querySelectorAll('.reveal:not(.is-in)').forEach((el) => revealObserver.observe(el));
}

document.addEventListener('DOMContentLoaded', observeReveals);
document.addEventListener('livewire:navigated', observeReveals);

// Catch elements added/morphed in by Livewire re-renders.
new MutationObserver(observeReveals).observe(document.body, { childList: true, subtree: true });
