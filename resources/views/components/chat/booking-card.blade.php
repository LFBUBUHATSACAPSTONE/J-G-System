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

$past = in_array($key, config('admin.messages.past_statuses'), true)
|| ($end !== null && $end->copy()->endOfDay()->isPast());
$tone = $past ? 'past' : ($status['badge']['tone'] ?? 'warning');

if ($start === null) {
$schedule = 'Date to be scheduled';
} elseif ($end !== null && $start->isSameDay($end)) {
$schedule = $start->format('F j, Y');
} elseif ($end !== null && $start->year === $end->year) {
$schedule = $start->format('F j') . ' – ' . $end->format('F j, Y');
} elseif ($end !== null) {
$schedule = $start->format('F j, Y') . ' – ' . $end->format('F j, Y');
} else {
$schedule = $start->format('F j, Y');
}
$time = trim(($event['start_time'] ?? '') . ' – ' . ($event['end_time'] ?? ''), ' –');
// Utility strings, whole. The status pill tone comes from config, so it is picked from a map.
$cardClass = $past ? 'chat-booking is-past mx-auto max-w-md self-stretch rounded-panel border border-chat-past-border bg-chat-past px-4 py-3.5 text-ink-muted [&.is-new]:motion-safe:animate-chat-booking-pop' : 'chat-booking mx-auto max-w-md self-stretch rounded-panel border border-chat-tint-border bg-chat-tint px-4 py-3.5 text-ink [&.is-new]:motion-safe:animate-chat-booking-pop';
$statusTones = [
'success' => 'bg-success-soft text-success-text',
'warning' => 'bg-warning-soft text-warning-text',
'danger' => 'bg-danger-soft text-danger-text',
'neutral' => 'bg-panel text-ink',
'past' => 'bg-chat-past-pill text-chat-past-pill-text',
];
$statusClass = 'chat-status flex-none rounded-full px-3 py-0.5 text-[0.8125rem] font-semibold ' . ($statusTones[$tone] ?? $statusTones['neutral']);
$headClass = 'chat-booking__head flex items-start justify-between gap-3';
$packageClass = 'chat-booking__package m-0 text-[1.0625rem] leading-[1.2] font-semibold';
$refClass = 'chat-booking__ref m-0 text-sm text-ink-muted';
$detailsClass = 'chat-booking__details mt-3 mb-0 grid gap-1.5 text-sm';
$rowClass = 'flex items-start gap-2';
$dtClass = 'flex-none font-normal text-ink-muted';
$ddClass = 'm-0 wrap-anywhere';
$linkClass = 'chat-booking__link mt-3 inline-block text-sm font-semibold text-brand-text underline focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text';
@endphp

<article class="{{ $cardClass }}" data-booking-id="{{ $booking['id'] }}" aria-label="Booking {{ $booking['reference'] }}{{ $past ? ', finished' : '' }}">
  <header class="{{ $headClass }}">
    <div>
      <h3 class="{{ $packageClass }}">{{ $booking['package']['name'] }}</h3>
      <p class="{{ $refClass }}">{{ $booking['reference'] }} · Php {{ number_format($booking['package']['price']) }}</p>
    </div>
    <span class="{{ $statusClass }}">{{ $past && $key === 'approved' ? 'Finished' : $status['label'] }}</span>
  </header>

  <dl class="{{ $detailsClass }}">
    <div class="{{ $rowClass }}">
      <dt class="{{ $dtClass }}"><x-chat.icon name="music" /><span class="visually-hidden">Event</span></dt>
      <dd class="{{ $ddClass }}">{{ $event['name'] }} ({{ $event['type'] }})</dd>
    </div>
    <div class="{{ $rowClass }}">
      <dt class="{{ $dtClass }}"><x-chat.icon name="calendar" /><span class="visually-hidden">Date</span></dt>
      <dd class="{{ $ddClass }}">{{ $schedule }}</dd>
    </div>
    @if ($time)
    <div class="{{ $rowClass }}">
      <dt class="{{ $dtClass }}"><x-chat.icon name="clock" /><span class="visually-hidden">Time</span></dt>
      <dd class="{{ $ddClass }}">{{ $time }}</dd>
    </div>
    @endif
    <div class="{{ $rowClass }}">
      <dt class="{{ $dtClass }}"><x-chat.icon name="map-pin" /><span class="visually-hidden">Location</span></dt>
      <dd class="{{ $ddClass }}">{{ $event['location'] }}</dd>
    </div>
  </dl>

  @if ($admin && ! $past && Route::has('admin.bookings'))
  <a class="{{ $linkClass }}" href="{{ route('admin.bookings') }}">View in Bookings</a>
  @endif
</article>