{{-- Status pill. Tones: success | warning | danger | brand | neutral. JS-painted badges (js/account/bookings.js
     paintBadge) use the same strings: keep the two in sync. --}}
@props(['tone' => 'neutral'])
@php
$tones = [
'success' => 'bg-success-soft text-success-text',
'warning' => 'bg-warning-soft text-warning-text',
'danger' => 'bg-danger-soft text-danger-text',
'brand' => 'bg-brand-soft text-brand-text',
'neutral' => 'bg-panel text-ink-muted',
];
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-1 rounded-full px-3 py-1 text-[0.8125rem] leading-[1.4] font-medium [&[hidden]]:hidden [&_i]:text-base [&_i]:leading-none', $tones[$tone] ?? $tones['neutral']]) }}>{{ $slot }}</span>