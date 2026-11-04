{{-- Booking card inside a chat thread.
     Current booking: full colour. Finished (status in config/admin/messages.php 'past_statuses',
     or end date already passed): grey. :admin="true" adds the "View in Bookings" link. --}}
@props(['booking', 'admin' => false])

@php
$key = $booking['status'];
$status = config("admin.bookings.statuses.{$key}") ?? ['label' => Str::headline($key), 'badge' => null];
$event = $booking['event'];
$start = $event['start_date'];
$end = $event['end_date'] ?? $start;

$past = in_array($key, config('admin.messages.past_statuses'), true) || $end->copy()->endOfDay()->isPast();
$tone = $past ? 'past' : ($status['badge']['tone'] ?? 'warning');

if ($start->isSameDay($end)) {
$schedule = $start->format('F j, Y');
} elseif ($start->year === $end->year) {
$schedule = $start->format('F j') . ' – ' . $end->format('F j, Y');
} else {
$schedule = $start->format('F j, Y') . ' – ' . $end->format('F j, Y');
}
$time = trim(($event['start_time'] ?? '') . ' – ' . ($event['end_time'] ?? ''), ' –');
@endphp

<article class="chat-booking{{ $past ? ' is-past' : '' }}" aria-label="Booking {{ $booking['reference'] }}{{ $past ? ', finished' : '' }}">
  <header class="chat-booking__head">
    <div>
      <h3 class="chat-booking__package">{{ $booking['package']['name'] }}</h3>
      <p class="chat-booking__ref">{{ $booking['reference'] }} · Php {{ number_format($booking['package']['price']) }}</p>
    </div>
    <span class="chat-status chat-status--{{ $tone }}">{{ $past && $key === 'approved' ? 'Finished' : $status['label'] }}</span>
  </header>

  <dl class="chat-booking__details">
    <div>
      <dt><x-chat.icon name="music" /><span class="visually-hidden">Event</span></dt>
      <dd>{{ $event['name'] }} ({{ $event['type'] }})</dd>
    </div>
    <div>
      <dt><x-chat.icon name="calendar" /><span class="visually-hidden">Date</span></dt>
      <dd>{{ $schedule }}</dd>
    </div>
    @if ($time)
    <div>
      <dt><x-chat.icon name="clock" /><span class="visually-hidden">Time</span></dt>
      <dd>{{ $time }}</dd>
    </div>
    @endif
    <div>
      <dt><x-chat.icon name="map-pin" /><span class="visually-hidden">Location</span></dt>
      <dd>{{ $event['location'] }}</dd>
    </div>
  </dl>

  @if ($admin && ! $past && Route::has('admin.bookings'))
  <a class="chat-booking__link" href="{{ route('admin.bookings') }}">View in Bookings</a>
  @endif
</article>