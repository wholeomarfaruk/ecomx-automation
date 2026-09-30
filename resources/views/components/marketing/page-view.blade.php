@php($pageViewPayload = request()->attributes->get('marketing_page_view_payload'))

@if ($pageViewPayload)
    {{-- This page's PageView, prepared by MarketingTracker with the same
         event_id Conversions API receives — so a GTM Meta Pixel tag mapping
         marketing.event_id deduplicates against the server event. Pushed
         straight to dataLayer (not the pending-events queue) so it also
         works on pages without the storefront JS, e.g. landing pages. --}}
    <script>
        window.dataLayer = window.dataLayer || [];
        window.dataLayer.push({{ Illuminate\Support\Js::from($pageViewPayload) }});
    </script>
@endif
