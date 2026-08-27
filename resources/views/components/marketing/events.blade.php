@props(['events' => []])

@if (!empty($events))
    <script>
        window.__marketingEvents = (window.__marketingEvents || []).concat(
            {{ Illuminate\Support\Js::from(array_values($events)) }}
        );
    </script>
@endif
