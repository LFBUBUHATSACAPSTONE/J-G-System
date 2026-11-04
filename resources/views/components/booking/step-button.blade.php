{{-- Cancel / Previous / Continue button of a booking step (Phase 9B; was `.client-info__btn--confirm` and `--cancel`
     plus `rounded-2 text-pale--white font-button--responsive`). `variant`: confirm (gradient, bold, fluid size) or
     cancel (transparent with a white outline that fades on hover, then the gradient fill). Same markup as <x-button>
     (the label sits in a <span>). From 480px it shares the row equally (`flex-1`), which only matters inside a flex
     row. Extra attributes (data-*, form, disabled) pass through. --}}
@props(['variant' => 'confirm', 'type' => 'button'])
@php
$variants = [
'confirm' => 'btn-color-gradient--primary text-fluid-button font-bold',
'cancel' => 'btn-color-gradient--secondary outline-1 outline-solid outline-white hover:outline-transparent',
];
$classes = 'rounded-md px-6 py-[0.75rem] text-pale-white min-[480px]:flex-1 '.($variants[$variant] ?? $variants['confirm']);
@endphp
<button type="{{ $type }}" {{ $attributes->class([$classes]) }}><span>{{ $slot }}</span></button>