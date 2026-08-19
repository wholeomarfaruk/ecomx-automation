@props(['id','w'=>700,'alt'=>''])
@php
    $isRealUrl = str_starts_with((string) $id, 'http://') || str_starts_with((string) $id, 'https://') || str_starts_with((string) $id, '/');
    $src = $isRealUrl ? $id : config('ecomx-fashion.unsplash') . $id . '?q=80&w=' . $w . '&auto=format&fit=crop';
@endphp
<img src="{{ $src }}" alt="{{ $alt }}" loading="lazy" decoding="async" {{ $attributes }}>
