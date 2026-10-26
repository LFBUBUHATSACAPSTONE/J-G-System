{{-- One booking card in the Calendar side panel. 
     Only the fields below are read.

     Props: booking (array), tone (confirmed|pending), label (badge text) 

--}}

@props(['booking', 'tone', 'label'])

@php
$event = $booking['event'];
$sameDay = $event['start_date']->isSameDay($event['end_date']);
$dates = $sameDay
? $event['start_date']->format('M j, Y')
: $event['start_date']->format('M j') . ' – ' . $event['end_date']->format('M j, Y');
$bookingsUrl = \Illuminate\Support\Facades\Route::has('admin.bookings') ? route('admin.bookings') : '#';

// Whole class strings, picked by tone (confirmed | pending).
$eventBase = 'rounded-panel border-l-4 bg-panel p-4 text-ink';
$eventTones = ['confirmed' => 'border-l-success-border', 'pending' => 'border-l-warning-border'];
$badgeTones = ['confirmed' => 'bg-success', 'pending' => 'bg-warning'];
@endphp

<article {{ $attributes->class([$eventBase, $eventTones[$tone] ?? 'border-l-transparent']) }}>
  <header class="flex items-center justify-between gap-2">
    <span class="text-fluid-sm font-bold text-brand-text">{{ $booking['reference'] }}</span>
    <span class="rounded-full px-3 py-1 text-[0.8rem] font-semibold whitespace-nowrap text-ink {{ $badgeTones[$tone] ?? '' }}">{{ $label }}</span>
  </header>

  <h3 class="my-2 text-fluid-name font-bold">{{ $event['name'] }}</h3>

  <dl class="mt-0 mb-3 grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-fluid-sm">
    <dt class="font-normal text-ink-muted">Package</dt>
    <dd class="m-0 wrap:anywhere">{{ $booking['package']['name'] }}</dd>
    <dt class="font-normal text-ink-muted">Client</dt>
    <dd class="m-0 wrap:anywhere">{{ $booking['client']['name'] }}</dd>
    <dt class="font-normal text-ink-muted">Date</dt>
    <dd class="m-0 wrap:anywhere">{{ $dates }}</dd>
    <dt class="font-normal text-ink-muted">Time</dt>
    <dd class="m-0 wrap:anywhere">{{ $event['start_time'] }} – {{ $event['end_time'] }}</dd>
    <dt class="font-normal text-ink-muted">Venue</dt>
    <dd class="m-0 wrap:anywhere">{{ $event['location'] }}</dd>
  </dl>

  <a class="text-fluid-sm font-semibold text-brand-text underline focus-visible:rounded-xs focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text" href="{{ $bookingsUrl }}">View in Bookings</a>
</article>