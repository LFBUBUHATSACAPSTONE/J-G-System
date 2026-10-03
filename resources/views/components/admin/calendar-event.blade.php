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
@endphp

<article {{ $attributes->class(['admin-cal__event', "admin-cal__event--{$tone}"]) }}>
  <header class="admin-cal__event-head">
    <span class="admin-cal__event-ref">{{ $booking['reference'] }}</span>
    <span class="admin-cal__badge admin-cal__badge--{{ $tone }}">{{ $label }}</span>
  </header>

  <h3 class="admin-cal__event-name">{{ $event['name'] }}</h3>

  <dl class="admin-cal__event-list">
    <dt>Package</dt>
    <dd>{{ $booking['package']['name'] }}</dd>
    <dt>Client</dt>
    <dd>{{ $booking['client']['name'] }}</dd>
    <dt>Date</dt>
    <dd>{{ $dates }}</dd>
    <dt>Time</dt>
    <dd>{{ $event['start_time'] }} – {{ $event['end_time'] }}</dd>
    <dt>Venue</dt>
    <dd>{{ $event['location'] }}</dd>
  </dl>

  <a class="admin-link admin-cal__event-link" href="{{ $bookingsUrl }}">View in Bookings</a>
</article>