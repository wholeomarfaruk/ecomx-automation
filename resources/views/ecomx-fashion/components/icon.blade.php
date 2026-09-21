{{--
    Thin re-export: the actual icon set now lives in App\Support\IconLibrary,
    rendered by the shared resources/views/components/icon.blade.php. Kept
    here so this theme's existing bare <x-icon name="..."> call sites (see
    e.g. partials/header.blade.php, livewire/cart-manager.blade.php) don't
    need to change — see that file for the prop list.
--}}
@props(['name', 'size' => 18])
<x-icon :name="$name" :size="$size" {{ $attributes }} />
