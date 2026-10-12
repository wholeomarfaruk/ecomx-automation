{{-- Toggle switch (spec §2): 44×26 track, 20px white knob. --}}
@props(['on' => false, 'label' => ''])

<button type="button" role="switch" aria-checked="{{ $on ? 'true' : 'false' }}" aria-label="{{ $label }}"
        {{ $attributes->class(['relative h-[26px] w-[44px] flex-none rounded-[13px] border-0 p-0 transition-colors', 'bg-[#4f46e5]' => $on, 'bg-[#d1d5db]' => ! $on]) }}>
    <span class="absolute top-[3px] h-[20px] w-[20px] rounded-full bg-white shadow-[0_1px_2px_rgba(0,0,0,0.2)] transition-all {{ $on ? 'left-[21px]' : 'left-[3px]' }}"></span>
</button>
