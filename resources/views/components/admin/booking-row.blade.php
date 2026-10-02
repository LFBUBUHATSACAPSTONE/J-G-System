{{-- One booking row. Everything comes from the $booking array and from config/admin-bookings.php (how each status looks / which actions it gets).

The data-* attributes on <tr> drive the JS filter/sort/search; 
  
data-booking on the View Details button carries the modal's content. --}}


@props(['booking', 'index' => 0])

@php
// `Str` is Laravel's default global alias, no import needed.
$key = $booking['status'];
$status = config("admin-bookings.statuses.{$key}") ?? [
'label' => Str::headline($key), 'group' => $key, 'highlight' => false,
'actions' => [], 'badge' => ['tone' => 'neutral', 'solid' => false],
];

$event = $booking['event'];
$start = $event['start_date'];
$end = $event['end_date'] ?? $start;

if ($start->isSameDay($end)) {
$schedule = $start->format('F j, Y');
} elseif ($start->year === $end->year) {
$schedule = $start->format('F j') . ' – ' . $end->format('F j, Y');
} else {
$schedule = $start->format('F j, Y') . ' – ' . $end->format('F j, Y');
}

$ref = $booking['reference'];
$package = $booking['package'];

// Route::has() keeps the page rendering before the POST routes exist.
$statusUrl = Route::has('admin.bookings.status')
? route('admin.bookings.status', ['booking' => $booking['id']])
: '#';

$payload = [
'id' => $booking['id'],
'reference' => $ref,
'client' => $booking['client'],
'event' => [
'name' => $event['name'] ?? null,
'type' => $event['type'] ?? null,
'location' => $event['location'] ?? null,
'contact_person' => $event['contact_person'] ?? null,
'guests' => $event['guests'] ?? null,
'venue_type' => $event['venue_type'] ?? null,
'schedule' => $schedule,
'start_time' => $event['start_time'] ?? null,
'end_time' => $event['end_time'] ?? null,
],
'payment' => $booking['payment'] ?? [],
'package' => [
'name' => $package['name'],
'price_label' => isset($package['price']) ? 'Php ' . number_format($package['price']) : null,
],
];

$search = Str::lower(implode(' ', [
$booking['client']['name'], $ref, $package['name'], $event['type'] ?? '', $status['label'], $schedule,
]));
@endphp

<tr
  role="row"
  class="admin-bookings__row{{ ($status['highlight'] ?? false) ? ' is-pending' : '' }}"
  data-booking-row
  data-index="{{ $index }}"
  data-name="{{ Str::lower($booking['client']['name']) }}"
  data-date="{{ $start->toDateString() }}"
  data-package="{{ $package['id'] }}"
  data-status-group="{{ $status['group'] }}"
  data-search="{{ $search }}">

  <td role="cell" class="admin-bookings__cell admin-bookings__cell--client" data-label="Client">
    @if ($status['highlight'] ?? false)
    <span class="admin-bookings__chip">{{ $status['label'] }}</span>
    @endif
    <span class="admin-bookings__name">{{ $booking['client']['name'] }}</span>
    <span class="admin-bookings__ref">{{ $ref }}</span>
  </td>

  <td role="cell" class="admin-bookings__cell" data-label="Schedule">
    <time datetime="{{ $start->toDateString() }}">{{ $schedule }}</time>
  </td>

  <td role="cell" class="admin-bookings__cell" data-label="Package">{{ $package['name'] }}</td>

  <td role="cell" class="admin-bookings__cell" data-label="Event Type">{{ $event['type'] ?? '--' }}</td>

  <td role="cell" class="admin-bookings__cell" data-label="">
    <button
      type="button"
      class="admin-link"
      data-bs-toggle="modal"
      data-bs-target="#bookingModal"
      data-booking="{{ json_encode($payload) }}"
      aria-label="View details for booking {{ $ref }}">
      View Details
    </button>
  </td>

  <td role="cell" class="admin-bookings__cell admin-bookings__cell--status" data-label="Status">
    <div class="admin-bookings__status">
      @foreach ($status['actions'] ?? [] as $action)
      <form
        method="POST"
        action="{{ $statusUrl }}"
        @if (($action['tone'] ?? '' )==='danger' ) data-confirm="{{ $action['label'] }} booking {{ $ref }}?" @endif>
        @csrf
        <button
          type="submit"
          name="action"
          value="{{ $action['action'] }}"
          class="admin-pill admin-pill--{{ $action['tone'] }}"
          aria-label="{{ $action['label'] }} booking {{ $ref }}">
          {{ $action['label'] }}
        </button>
      </form>
      @endforeach

      @if (! empty($status['badge']))
      <span class="admin-pill admin-pill--{{ $status['badge']['tone'] }}{{ ($status['badge']['solid'] ?? false) ? ' admin-pill--solid' : '' }}">
        {{ $status['label'] }}
      </span>
      @endif
    </div>
  </td>
</tr>