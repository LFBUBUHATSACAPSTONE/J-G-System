{{--
  Reminder: any future CTA that redirects to login should use the same two attributes,
     e.g. data-bs-toggle="modal" data-bs-target="#authModal" data-auth-view="login"
--}}

@props([
'type' => 'button',
'disabled' => false,
])

@php
$classes = [];
$classString = implode(' ', $classes);
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $classString]) }} @if($disabled) disabled @endif><span>{{ $slot }}</span></button>