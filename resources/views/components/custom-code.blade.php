@props(['position'])
@php
    // Admin > Site Settings > Custom Code. Output raw on purpose — these are
    // admin-authored <script>/<meta>/<noscript> snippets (verification tags,
    // chat widgets, extra pixels) that must reach the page unescaped.
    // Setting::get() json-decodes, so a non-string value is skipped rather
    // than cast (an array would throw and take the whole storefront down).
    $code = \App\Models\Setting::get($position, '', 'custom_code');
    $code = is_string($code) ? $code : '';
@endphp
@if (trim($code) !== '')
{!! $code !!}
@endif
