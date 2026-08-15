@props([
    'title'       => 'No data found',
    'description' => 'There is nothing to display here yet.',
    'icon'        => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center py-16 text-center']) }}>
    @if ($icon)
        <div class="mb-4 text-gray-300">
            {!! $icon !!}
        </div>
    @else
        <svg xmlns="http://www.w3.org/2000/svg" class="w-14 h-14 text-gray-200 mb-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" />
        </svg>
    @endif

    <h3 class="text-sm font-semibold text-gray-700">{{ $title }}</h3>
    <p class="mt-1 text-sm text-gray-400">{{ $description }}</p>

    @isset($action)
        <div class="mt-4">{{ $action }}</div>
    @endisset
</div>
