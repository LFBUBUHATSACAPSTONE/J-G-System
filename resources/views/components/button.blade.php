@props([
'type' => 'button',
'disabled' => false,
])

@php
$classes = [];
$classString = implode(' ', $classes);
@endphp

<div class="d-flex justify-content-center">
  <button
    type="{{ $type }}"
    {{ $attributes->merge(['class' => $classString]) }}
    @if($disabled) disabled @endif>
    {{ $slot }}
  </button>
</div>