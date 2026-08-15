@props([
    'lines' => 3,   // number of lines to show
    'class' => '',
])

<div {{ $attributes->merge(['class' => 'animate-pulse space-y-3 ' . $class]) }}>
    @for ($i = 0; $i < $lines; $i++)
        <div class="h-4 bg-gray-200 rounded {{ $i === $lines - 1 ? 'w-3/4' : 'w-full' }}"></div>
    @endfor
</div>
