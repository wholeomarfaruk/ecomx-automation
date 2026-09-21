<?php

namespace App\Support;

/**
 * Single source of truth for the small hand-rolled SVG icon set shared by
 * every storefront theme (and anything else that wants a plain, dependency-
 * free icon). Used by the canonical <x-icon> component (resources/views/
 * components/icon.blade.php) — themes no longer keep their own copy of this
 * array; see EcomxFashion's and EcomxAnyniche's icon.blade.php, both now
 * thin wrappers around the shared component.
 *
 * Two sets, kept separate because they render differently:
 *  - ICONS: outline glyphs, stroked with currentColor (fill="none").
 *  - BRAND: social-network marks. The wrapping <svg> defaults to
 *           fill="none" stroke="none", so every shape in a BRAND entry MUST
 *           set its own fill (or stroke) explicitly — most are solid marks
 *           (fill="currentColor" on each path), but a few (e.g. "instagram",
 *           "linkedin") mix stroked outline shapes with a filled accent.
 *           Forgetting this on a shape renders it invisible — that was the
 *           original "instagram" bug (its outer frame/lens had no fill or
 *           stroke and only the small dot showed).
 *
 * This is the *union* of what ecomx-fashion and ecomx-anyniche each had
 * before (anyniche additionally had truck/shield/return/support, used by
 * its Trust section and offcanvas menus) — nothing was dropped, so neither
 * theme's existing icon calls change behavior.
 */
class IconLibrary
{
    public const ICONS = [
        // ── Original set (kept, unchanged paths — every existing call site
        //    that names one of these keeps rendering exactly as before) ──
        'search' => '<circle cx="11" cy="11" r="7"/><path d="m21 21-4.3-4.3"/>',
        'phone' => '<path d="M22 16.9v3a2 2 0 0 1-2.2 2 19.8 19.8 0 0 1-8.6-3.1 19.5 19.5 0 0 1-6-6A19.8 19.8 0 0 1 2.1 4.2 2 2 0 0 1 4.1 2h3a2 2 0 0 1 2 1.7c.1 1 .4 2 .7 2.9a2 2 0 0 1-.5 2.1L8 10a16 16 0 0 0 6 6l1.3-1.3a2 2 0 0 1 2.1-.5c.9.3 1.9.6 2.9.7a2 2 0 0 1 1.7 2.1z"/>',
        'heart' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 0 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8z"/>',
        'cart' => '<circle cx="9" cy="21" r="1.5"/><circle cx="19" cy="21" r="1.5"/><path d="M2 3h3l2.6 12.6a2 2 0 0 0 2 1.4h9.2a2 2 0 0 0 2-1.6L22.5 7H6"/>',
        'user' => '<circle cx="12" cy="8" r="4"/><path d="M4 21c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5"/>',
        'home' => '<path d="M3 10.5 12 3l9 7.5"/><path d="M5 9.5V21h14V9.5"/>',
        'grid' => '<rect x="3" y="3" width="7" height="7" rx="1.5"/><rect x="14" y="3" width="7" height="7" rx="1.5"/><rect x="3" y="14" width="7" height="7" rx="1.5"/><rect x="14" y="14" width="7" height="7" rx="1.5"/>',
        'star' => '<path d="M12 2.5l2.9 6.3 6.9.7-5.2 4.7 1.5 6.8L12 17.6l-6.1 3.4 1.5-6.8-5.2-4.7 6.9-.7z"/>',
        'arrow-left' => '<path d="M19 12H5"/><path d="M11 18l-6-6 6-6"/>',
        'arrow-right' => '<path d="M5 12h14"/><path d="M13 18l6-6-6-6"/>',
        'pencil' => '<path d="M12 20h9"/><path d="M16.5 3.5a2.1 2.1 0 0 1 3 3L7 19l-4 1 1-4z"/>',
        'chevron-down' => '<path d="m6 9 6 6 6-6"/>',
        'truck' => '<path d="M2 8h11v8H2z"/><path d="M13 11h4l4 3v2h-8z"/><circle cx="6.5" cy="18" r="1.7"/><circle cx="16.5" cy="18" r="1.7"/>',
        'shield' => '<path d="M12 3l7 3v6c0 4.5-3 7.7-7 9-4-1.3-7-4.5-7-9V6z"/><path d="m9 12 2 2 4-4"/>',
        'return' => '<path d="M3 12a9 9 0 1 0 3-6.7"/><path d="M3 4v4h4"/>',
        'support' => '<circle cx="12" cy="12" r="8"/><circle cx="12" cy="12" r="3"/><path d="M5 5l3.5 3.5M19 5l-3.5 3.5M5 19l3.5-3.5M19 19l-3.5-3.5"/>',

        // ── Expanded set — common storefront-nav/menu needs (offers, contact,
        //    account, content types, actions). Feather/Lucide-style paths,
        //    24x24 viewBox, drawn to match the stroke weight of the set above. ──
        'chevron-up' => '<path d="m18 15-6-6-6 6"/>',
        'chevron-left' => '<path d="m15 18-6-6 6-6"/>',
        'chevron-right' => '<path d="m9 18 6-6-6-6"/>',
        'plus' => '<path d="M12 5v14M5 12h14"/>',
        'minus' => '<path d="M5 12h14"/>',
        // Named 'close', not 'x': a bare "x" read as an unclear/typo-looking
        // label wherever the name itself is shown (e.g. the admin icon
        // gallery's caption under each glyph) — 'close' is unambiguous and
        // matches what this glyph is actually for.
        'close' => '<path d="M18 6 6 18M6 6l12 12"/>',
        'check' => '<path d="M20 6 9 17l-5-5"/>',
        'check-circle' => '<path d="M22 11.1V12a10 10 0 1 1-5.9-9.1"/><path d="m9 11 3 3L22 4"/>',
        'tag' => '<path d="M20.6 12.2 12.8 20A2 2 0 0 1 10 20L3 13a2 2 0 0 1 0-2.8L10.7 2.5A2 2 0 0 1 12.2 2H19a2 2 0 0 1 2 2v6.8a2 2 0 0 1-.4 1.4z"/><circle cx="15.5" cy="7.5" r="1.5"/>',
        'percent' => '<line x1="19" y1="5" x2="5" y2="19"/><circle cx="6.5" cy="6.5" r="2.5"/><circle cx="17.5" cy="17.5" r="2.5"/>',
        'gift' => '<rect x="3" y="8" width="18" height="4" rx="1"/><path d="M12 8v13M19 12v9H5v-9"/><path d="M12 8c-1.5 0-4-1-4-3.2A2.3 2.3 0 0 1 10.3 2.5C12.5 2.5 12 6 12 8zm0 0c1.5 0 4-1 4-3.2A2.3 2.3 0 0 0 13.7 2.5C11.5 2.5 12 6 12 8z"/>',
        'bell' => '<path d="M6 8a6 6 0 0 1 12 0c0 5 2 6 2 6H4s2-1 2-6"/><path d="M10.3 21a1.9 1.9 0 0 0 3.4 0"/>',
        'map-pin' => '<path d="M20 10c0 5.5-8 12-8 12s-8-6.5-8-12a8 8 0 0 1 16 0z"/><circle cx="12" cy="10" r="3"/>',
        'mail' => '<rect x="2" y="4" width="20" height="16" rx="2"/><path d="m2 6 10 7 10-7"/>',
        'clock' => '<circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.2 2"/>',
        'calendar' => '<rect x="3" y="4.5" width="18" height="16.5" rx="2"/><path d="M16 2.5v4M8 2.5v4M3 9.5h18"/>',
        'package' => '<path d="M12 2 3 7v10l9 5 9-5V7z"/><path d="M3 7 12 12l9-5M12 12v10"/>',
        'layers' => '<path d="m12 2 9 5-9 5-9-5z"/><path d="m3 12 9 5 9-5M3 17l9 5 9-5"/>',
        'bookmark' => '<path d="M6 3h12v18l-6-4-6 4z"/>',
        'info' => '<circle cx="12" cy="12" r="9"/><path d="M12 11v6M12 7.5h.01"/>',
        'help-circle' => '<circle cx="12" cy="12" r="9"/><path d="M9.5 9a2.5 2.5 0 1 1 3.6 2.2c-.9.5-1.6 1-1.6 2.3M12 17h.01"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.7 1.7 0 0 0 .3 1.9l.1.1a2 2 0 1 1-2.8 2.8l-.1-.1a1.7 1.7 0 0 0-1.9-.3 1.7 1.7 0 0 0-1 1.6V21a2 2 0 1 1-4 0v-.2a1.7 1.7 0 0 0-1.1-1.6 1.7 1.7 0 0 0-1.9.3l-.1.1a2 2 0 1 1-2.8-2.8l.1-.1a1.7 1.7 0 0 0 .3-1.9 1.7 1.7 0 0 0-1.6-1H3a2 2 0 1 1 0-4h.2a1.7 1.7 0 0 0 1.6-1 1.7 1.7 0 0 0-.3-1.9l-.1-.1a2 2 0 1 1 2.8-2.8l.1.1a1.7 1.7 0 0 0 1.9.3H9a1.7 1.7 0 0 0 1-1.6V3a2 2 0 1 1 4 0v.2a1.7 1.7 0 0 0 1 1.6 1.7 1.7 0 0 0 1.9-.3l.1-.1a2 2 0 1 1 2.8 2.8l-.1.1a1.7 1.7 0 0 0-.3 1.9V9a1.7 1.7 0 0 0 1.6 1H21a2 2 0 1 1 0 4h-.2a1.7 1.7 0 0 0-1.6 1z"/>',
        'filter' => '<path d="M22 3H2l8 9.5V19l4 2v-8.5z"/>',
        'sliders' => '<line x1="4" y1="21" x2="4" y2="14"/><line x1="4" y1="10" x2="4" y2="3"/><line x1="12" y1="21" x2="12" y2="12"/><line x1="12" y1="8" x2="12" y2="3"/><line x1="20" y1="21" x2="20" y2="16"/><line x1="20" y1="12" x2="20" y2="3"/><circle cx="4" cy="12" r="2"/><circle cx="12" cy="10" r="2"/><circle cx="20" cy="14" r="2"/>',
        'thumbs-up' => '<path d="M7 22V11l5-9a2.5 2.5 0 0 1 2.5 3l-1 5H19a2 2 0 0 1 2 2.4l-1.6 8A2 2 0 0 1 17.4 22H7z"/><path d="M7 11H3v11h4"/>',
        'message-circle' => '<path d="M21 11.5a8.4 8.4 0 0 1-8.9 8.4 8.6 8.6 0 0 1-3.9-.9L3 21l1.8-5.2A8.3 8.3 0 0 1 3.5 11.5 8.4 8.4 0 0 1 12 3a8.3 8.3 0 0 1 9 8.5z"/>',
        'credit-card' => '<rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/>',
        'wallet' => '<path d="M20 7H5a2 2 0 0 1 0-4h13v4"/><path d="M20 7v13H6a2 2 0 0 1-2-2V6"/><path d="M18 14.5a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3z"/>',
        'lock' => '<rect x="4" y="10.5" width="16" height="10.5" rx="2"/><path d="M7.5 10.5V7a4.5 4.5 0 0 1 9 0v3.5"/>',
        'unlock' => '<rect x="4" y="10.5" width="16" height="10.5" rx="2"/><path d="M7.5 10.5V7a4.5 4.5 0 0 1 8.6-1.8"/>',
        'eye' => '<path d="M1.5 12S5 5 12 5s10.5 7 10.5 7-3.5 7-10.5 7-10.5-7-10.5-7z"/><circle cx="12" cy="12" r="3"/>',
        'download' => '<path d="M12 3v12m0 0 4.5-4.5M12 15 7.5 10.5"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>',
        'upload' => '<path d="M12 21V9m0 0 4.5 4.5M12 9l-4.5 4.5"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/>',
        'share' => '<circle cx="18" cy="5" r="2.7"/><circle cx="6" cy="12" r="2.7"/><circle cx="18" cy="19" r="2.7"/><path d="m8.4 10.6 7.2-4.2M8.4 13.4l7.2 4.2"/>',
        // Fixed: the previous paths' arrowhead corners didn't share an endpoint
        // with their arcs (a coordinate gap), so the glyph rendered as a
        // disconnected shape instead of two clean circular arrows. Replaced
        // with the standard, verified Feather "refresh-cw" path data.
        'refresh' => '<polyline points="23 4 23 10 17 10"/><polyline points="1 20 1 14 7 14"/><path d="M3.51 9a9 9 0 0 1 14.85-3.36L23 10M1 14l4.64 4.36A9 9 0 0 0 20.49 15"/>',
        'external-link' => '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><path d="M15 3h6v6M10 14 21 3"/>',
        'image' => '<rect x="3" y="3" width="18" height="18" rx="2"/><circle cx="8.5" cy="8.5" r="1.7"/><path d="m21 15-5-5L5 21"/>',
        'video' => '<path d="M23 7 16 12l7 5z"/><rect x="1" y="5" width="15" height="14" rx="2"/>',
        'list' => '<line x1="9" y1="6" x2="21" y2="6"/><line x1="9" y1="12" x2="21" y2="12"/><line x1="9" y1="18" x2="21" y2="18"/><circle cx="4" cy="6" r="1.3"/><circle cx="4" cy="12" r="1.3"/><circle cx="4" cy="18" r="1.3"/>',
        'bar-chart' => '<line x1="4" y1="21" x2="4" y2="13"/><line x1="12" y1="21" x2="12" y2="7"/><line x1="20" y1="21" x2="20" y2="3"/>',
        'trending-up' => '<polyline points="3 17 9 11 13 15 21 6"/><polyline points="14 6 21 6 21 13"/>',
        'zap' => '<path d="M13 2 3 14h8l-1 8 10-12h-8z"/>',
        'moon' => '<path d="M21 12.8A9 9 0 1 1 11.2 3 7 7 0 0 0 21 12.8z"/>',
        'sun' => '<circle cx="12" cy="12" r="4.5"/><path d="M12 2.5v2M12 19.5v2M4.2 4.2l1.4 1.4M18.4 18.4l1.4 1.4M2.5 12h2M19.5 12h2M4.2 19.8l1.4-1.4M18.4 5.6l1.4-1.4"/>',
        'globe' => '<circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3a13.5 13.5 0 0 1 0 18 13.5 13.5 0 0 1 0-18z"/>',
        'flag' => '<path d="M5 3v18"/><path d="M5 4h11l-2 4 2 4H5"/>',
        'clipboard' => '<rect x="7" y="4" width="10" height="16" rx="2"/><path d="M9 4a1 1 0 0 1 1-1h4a1 1 0 0 1 1 1v1H9z"/>',
        'folder' => '<path d="M3 6a1 1 0 0 1 1-1h5l2 2h9a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1z"/>',
        'file' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><path d="M14 2v6h6"/>',
        'camera' => '<path d="M4 8h3l1.5-2.5h7L17 8h3a1 1 0 0 1 1 1v10a1 1 0 0 1-1 1H4a1 1 0 0 1-1-1V9a1 1 0 0 1 1-1z"/><circle cx="12" cy="13.5" r="3.5"/>',
        'box' => '<path d="M21 8v8l-9 5-9-5V8l9-5z"/><path d="M3 8l9 5 9-5M12 13v8"/>',
    ];

    public const BRAND = [
        'whatsapp' => '<path fill="currentColor" d="M12 2a10 10 0 0 0-8.6 15.1L2 22l5.1-1.3A10 10 0 1 0 12 2zm0 18.2a8.2 8.2 0 0 1-4.2-1.2l-.3-.2-3 .8.8-3-.2-.3A8.2 8.2 0 1 1 12 20.2zm4.6-6.1c-.3-.1-1.5-.7-1.7-.8-.2-.1-.4-.1-.6.1-.2.3-.7.8-.8 1-.1.2-.3.2-.5.1a6.7 6.7 0 0 1-3.3-2.9c-.3-.4 0-.5.2-.7l.5-.6c.1-.2.1-.3 0-.5-.1-.1-.6-1.4-.8-1.9-.2-.5-.4-.4-.6-.4h-.5c-.2 0-.5.1-.7.3a3 3 0 0 0-.9 2.2c0 1.3.9 2.5 1.1 2.7.1.2 1.9 2.9 4.6 4a15 15 0 0 0 1.5.6c.6.2 1.2.2 1.7.1.5-.1 1.5-.6 1.7-1.2.2-.6.2-1.1.2-1.2-.1-.1-.3-.2-.6-.3z"/>',
        'messenger' => '<path fill="currentColor" d="M12 2C6.3 2 2 6.2 2 11.6c0 2.9 1.2 5.4 3.2 7.1V22l3-1.6c.8.2 1.7.3 2.6.3 5.7 0 10-4.2 10-9.6S17.7 2 12 2zm1 12.3l-2.5-2.7L5.6 14l5.4-5.7 2.6 2.7L18.4 9 13 14.3z"/>',
        'facebook' => '<path fill="currentColor" d="M13.5 21v-8h2.7l.4-3.1h-3.1V7.9c0-.9.25-1.5 1.55-1.5h1.65V3.6c-.3-.04-1.3-.12-2.45-.12-2.4 0-4.05 1.46-4.05 4.15v2.27H7.5V13h2.7v8h3.3z"/>',
        // Fixed: previously only the small camera-dot carried fill="currentColor",
        // so the outer rounded-square frame and lens circle inherited the wrapping
        // <svg>'s fill="none" stroke="none" and rendered invisible — only the dot
        // showed. Now a proper stroke-based glyph (frame + lens + dot), each
        // shape explicitly stroked so it's visible regardless of the wrapper.
        'instagram' => '<rect x="3" y="3" width="18" height="18" rx="5" fill="none" stroke="currentColor" stroke-width="1.7"/><circle cx="12" cy="12" r="4" fill="none" stroke="currentColor" stroke-width="1.7"/><circle cx="17.3" cy="6.7" r="1.2" fill="currentColor" stroke="none"/>',
        'youtube' => '<path fill="currentColor" d="M21.6 7.2a2.5 2.5 0 0 0-1.76-1.77C18.25 5 12 5 12 5s-6.25 0-7.84.43A2.5 2.5 0 0 0 2.4 7.2 26 26 0 0 0 2 12a26 26 0 0 0 .4 4.8 2.5 2.5 0 0 0 1.76 1.77C5.75 19 12 19 12 19s6.25 0 7.84-.43a2.5 2.5 0 0 0 1.76-1.77A26 26 0 0 0 22 12a26 26 0 0 0-.4-4.8zM10 15.2V8.8L15.6 12 10 15.2z"/>',
        'tiktok' => '<path fill="currentColor" d="M16.5 2h-3v13.2a2.7 2.7 0 1 1-2.2-2.65V9.5a5.8 5.8 0 1 0 5.2 5.77V8.9a6.6 6.6 0 0 0 4 1.35V7.2a3.6 3.6 0 0 1-4-3.2z"/>',

        // ── Added: more platforms for the social/menu picker. ──
        'twitter' => '<path fill="currentColor" d="M18.3 2h3.2l-7 8 8.2 12h-6.4l-5-6.6L4.8 22H1.6l7.5-8.6L1.3 2h6.5l4.5 6zM17.1 20h1.8L7 4H5.1z"/>',
        'linkedin' => '<rect x="2" y="2" width="20" height="20" rx="3" fill="none" stroke="currentColor" stroke-width="1.7"/><path fill="currentColor" stroke="none" d="M7.3 9.7h2.6v8.3H7.3zM8.6 8.6a1.5 1.5 0 1 1 0-3 1.5 1.5 0 0 1 0 3zM12.3 9.7h2.5v1.2h.03c.35-.65 1.2-1.35 2.5-1.35 2.65 0 3.15 1.75 3.15 4v4.5h-2.6v-4c0-.95-.02-2.2-1.35-2.2-1.35 0-1.55 1.05-1.55 2.13v4.07h-2.6z"/>',
        'pinterest' => '<path fill="currentColor" d="M12 2a10 10 0 0 0-3.6 19.3c0-.8-.03-1.75.2-2.6.2-.85 1.3-5.5 1.3-5.5a3.5 3.5 0 0 1-.3-1.5c0-1.4.8-2.45 1.8-2.45.85 0 1.27.65 1.27 1.4 0 .85-.55 2.15-.83 3.35-.24 1 .5 1.85 1.5 1.85 1.8 0 3.05-2.3 3.05-5.05 0-2.1-1.4-3.65-4-3.65-2.9 0-4.7 2.15-4.7 4.6 0 .85.25 1.45.63 1.9.18.2.2.3.14.55l-.22.85c-.07.28-.28.38-.53.28-1.5-.6-2.2-2.25-2.2-4.1 0-3.05 2.55-6.7 7.65-6.7 4.1 0 6.8 2.95 6.8 6.15 0 4.2-2.3 7.35-5.7 7.35-1.14 0-2.2-.62-2.57-1.3l-.72 2.75c-.22.9-.66 1.8-1.06 2.5A10 10 0 1 0 12 2z"/>',
        'telegram' => '<path fill="currentColor" d="M21.9 3.5 18.6 20a1 1 0 0 1-1.5.6l-4.9-3.6-2.4 2.3a.7.7 0 0 1-1.2-.4l.4-4.5L18 6.2c.4-.35-.1-.5-.6-.2L7.1 12.6l-4.4-1.4c-.9-.3-1-1 .2-1.4L20.7 2.7c.8-.3 1.5.2 1.2.8z"/>',
        'snapchat' => '<path fill="currentColor" d="M12 2.2c2.7 0 4.5 2.1 4.5 4.8 0 .9-.06 1.9.1 2.6.15.05.6.12 1-.1.4-.2.9-.05 1 .4.1.4-.15.75-.55 1-.5.3-1.35.65-1.8 1.05-.35.3-.25.8.05 1.2.7 1 2 1.65 3.3 1.9.35.06.35.55.02.66-.35.13-.9.28-1.1.5-.14.16-.1.4-.35.5-.35.15-1.1.02-1.6.2-.42.16-.5.75-1.1.85-.7.12-1.55-.35-2.55-.35-.9 0-1.65.5-3 .5s-2.1-.5-3-.5c-1 0-1.85.47-2.55.35-.6-.1-.68-.7-1.1-.85-.5-.18-1.25-.05-1.6-.2-.25-.1-.2-.34-.35-.5-.2-.22-.75-.37-1.1-.5-.33-.11-.33-.6.02-.66 1.3-.25 2.6-.9 3.3-1.9.3-.4.4-.9.05-1.2-.45-.4-1.3-.75-1.8-1.05-.4-.25-.65-.6-.55-1 .1-.45.6-.6 1-.4.4.22.85.15 1-.1.16-.7.1-1.7.1-2.6 0-2.7 1.8-4.8 4.5-4.8z"/>',
        'discord' => '<path fill="currentColor" d="M18.9 5.3A16.6 16.6 0 0 0 14.8 4l-.2.4a13 13 0 0 1 3.6 1.4 15.3 15.3 0 0 0-12.4 0 13 13 0 0 1 3.6-1.4L9.2 4a16.6 16.6 0 0 0-4.1 1.3C2.7 8.7 2 12 2.3 15.2a16.7 16.7 0 0 0 5 2.6l.7-1.1a10.8 10.8 0 0 1-1.7-.8l.4-.3a12 12 0 0 0 10.6 0l.4.3c-.5.3-1.1.6-1.7.8l.7 1.1a16.7 16.7 0 0 0 5-2.6c.4-3.6-.5-6.9-3.8-9.9zM9 13.4c-.75 0-1.35-.7-1.35-1.55S8.25 10.3 9 10.3s1.37.7 1.35 1.55c0 .85-.6 1.55-1.35 1.55zm6 0c-.75 0-1.35-.7-1.35-1.55S14.25 10.3 15 10.3s1.37.7 1.35 1.55c0 .85-.6 1.55-1.35 1.55z"/>',
        'twitch' => '<path fill="currentColor" d="M4 2 2.5 5.5v14H7V22l3-2.5h3.5L19 15V2zm13 12-3 2.5h-3.5L8 19v-2.5H5V4h12z"/><path fill="currentColor" d="M15.5 6.5h-1.8v5h1.8zM11 6.5H9.2v5H11z"/>',
        'threads' => '<path fill="currentColor" d="M12 2C6.7 2 3.2 5.3 3.2 12c0 6.5 3.4 10 8.8 10 3.6 0 6.3-1.6 7.6-4.5l-2-1c-.9 1.9-2.7 3-5.3 3-3 0-4.9-1.6-5.6-4.3.9.35 2 .5 3.3.5 4.7 0 7.6-2 7.6-5.3 0-2.9-2.2-4.9-5.7-4.9-2.7 0-4.8 1.1-6 3l1.9 1.2c.8-1.3 2.1-2 3.9-2 2 0 3.3 1 3.3 2.6 0 1.7-1.7 3-5.3 3-.9 0-1.7-.1-2.4-.3.15-2.7 1.5-4.3 3.9-4.3.6 0 1.1.1 1.5.3l.6-2c-.65-.25-1.4-.4-2.3-.4-3.9 0-6.2 2.5-6.2 6.6 0 4.6 2.9 7.4 7.8 7.4 3.5 0 6-1.5 7.3-4.2 1-2 1-4.3.3-6.1C18.9 3.9 16 2 12 2z"/>',
        'viber' => '<path fill="currentColor" d="M12.1 2C7.3 2 4.3 4.4 4 8.7c-.15 2 .2 3.9 1.2 5.5L4 22l7.9-1.2c.4 0 .8.06 1.2.06 4.9 0 8.9-3.2 8.9-9.3S17 2 12.1 2zm4.6 12.3c-.3.6-1.4 1.2-2 1.3-.5.1-1.15.15-3.7-.85-3.1-1.2-5.1-4.3-5.25-4.5-.15-.2-1.25-1.65-1.25-3.15s.8-2.2 1.05-2.5c.25-.3.55-.35.75-.35h.55c.18 0 .4-.03.63.5.24.55.8 1.95.87 2.1.07.15.12.32.02.5-.1.2-.15.32-.3.5-.15.17-.32.38-.45.5-.15.15-.32.32-.14.6.18.32.8 1.3 1.7 2.1 1.17 1.05 2.15 1.37 2.47 1.53.32.15.5.13.7-.08.2-.2.83-.97 1.05-1.3.22-.32.44-.27.75-.16.3.1 1.9.9 2.24 1.06.32.16.55.24.63.37.08.15.08.85-.22 1.45z"/>',
    ];

    /** Whether a name exists in either set. */
    public static function has(string $name): bool
    {
        return isset(self::ICONS[$name]) || isset(self::BRAND[$name]);
    }

    /** Whether a name is a filled brand mark (renders without a stroke). */
    public static function isBrand(string $name): bool
    {
        return isset(self::BRAND[$name]);
    }

    /** Raw inner SVG markup (paths/shapes only, no wrapping <svg>) for a name, or '' if unknown. */
    public static function markup(string $name): string
    {
        return self::BRAND[$name] ?? self::ICONS[$name] ?? '';
    }

    /** All known icon names (outline set first, then brand marks) — e.g. for an admin picker dropdown. */
    public static function names(): array
    {
        return array_merge(array_keys(self::ICONS), array_keys(self::BRAND));
    }
}
