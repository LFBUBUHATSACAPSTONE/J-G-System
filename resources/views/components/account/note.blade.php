{{-- Small status strip under a booking card's details (My Bookings): "request sent", "request declined", the
     reschedule note. Tones: pending | declined | neutral. Replaces the old `account-booking__note` Sass class
     (removed in Phase 6, so it was unstyled). --}}
@props(['tone' => 'neutral', 'icon' => null])
@php
$tones = [
    'pending' => 'bg-warning-soft/50 text-warning-text',
    'declined' => 'bg-brand-soft text-brand-text',
    'neutral' => 'bg-panel text-ink-muted',
];
@endphp
<p {{ $attributes->class(['m-0 mt-4 flex items-start gap-2 rounded-field px-3 py-2 text-sm', $tones[$tone] ?? $tones['neutral']]) }}>
  @if ($icon)
  <i class="ph ph-{{ $icon }} mt-0.5 shrink-0 text-base leading-none" aria-hidden="true"></i>
  @endif
  <span>{{ $slot }}</span>
</p>