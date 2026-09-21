{{--
    Canonical icon component, backed by App\Support\IconLibrary. Registered
    globally (auto-discovered from resources/views/components/), and also
    re-exported under both storefront themes' own tag syntax — see
    resources/views/ecomx-fashion/components/icon.blade.php (bare <x-icon>)
    and resources/views/ecomx-anyniche/components/icon.blade.php
    (<x-anyniche::icon>) — so existing call sites in either theme keep
    working unchanged.

    Props:
      name  (required) icon key, e.g. "heart", "whatsapp" — see IconLibrary::names().
      size  (optional) square pixel size, default 18 (matches every existing call site).
      class (optional) extra classes on the <svg>, merged via $attributes.
--}}
@props(['name', 'size' => 18])
@php
    $isBrand = \App\Support\IconLibrary::isBrand($name);
    $markup = \App\Support\IconLibrary::markup($name);
@endphp
@if ($isBrand)
<svg {{ $attributes->merge(['width' => $size, 'height' => $size]) }} viewBox="0 0 24 24" fill="none" stroke="none" aria-hidden="true">{!! $markup !!}</svg>
@else
<svg {{ $attributes->merge(['width' => $size, 'height' => $size]) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">{!! $markup !!}</svg>
@endif
