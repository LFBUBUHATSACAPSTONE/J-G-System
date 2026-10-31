{{-- Account button or link (pass `href` for a link). Variants: primary | secondary | danger | danger-outline.
     Width comes from the parent (a footer's `[&>*]:w-full`), not from here. js/account/bookings.js builds the
     Reschedule link with the primary strings: keep the two in sync. --}}
@props(['variant' => 'primary', 'href' => null, 'type' => 'button'])
@php
$variants = [
'primary' => 'border-transparent bg-brand text-on-brand not-disabled:hover:bg-brand-hover',
'secondary' => 'border-transparent bg-brand-soft text-brand-text not-disabled:hover:bg-brand-soft-hover',
'danger' => 'border-transparent bg-danger text-on-danger',
'danger-outline' => 'border-danger-border bg-transparent text-danger-text not-disabled:hover:bg-danger-soft',
];
$class = 'inline-block cursor-pointer rounded-field border-2 px-6 py-2 text-center text-base leading-normal font-semibold no-underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text disabled:cursor-not-allowed disabled:opacity-50 ' . ($variants[$variant] ?? $variants['primary']);
@endphp
@if ($href)
<a href="{{ $href }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</a>
@else
<button type="{{ $type }}" {{ $attributes->merge(['class' => $class]) }}>{{ $slot }}</button>
@endif