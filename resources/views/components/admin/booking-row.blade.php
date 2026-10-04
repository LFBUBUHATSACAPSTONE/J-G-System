{{-- One booking row. Everything comes from the $booking array
     and from config/admin/bookings.php (how each status looks / which actions it gets).
     The data-* attributes on <tr> drive the JS filter/sort/search; data-booking on the
     View Details button carries the modal's content.
     History page: pass :history="true" and the row resolves its look from the event dates
     (Upcoming / Ongoing / Completed) using config/admin-history.php, with no action buttons.
     Cancelled and Declined bookings keep their own pill. Bookings page: nothing changes. --}}
@props(['booking', 'index' => 0, 'history' => false])

@php
// `Str` is Laravel's default global alias, no import needed.
$key = $booking['status'];
$status = config("admin.bookings.statuses.{$key}") ?? [
'label' => Str::headline($key), 'group' => $key, 'highlight' => false,
'actions' => [], 'badge' => ['tone' => 'neutral', 'solid' => false],
];

$event = $booking['event'];
$start = $event['start_date'];
$end = $event['end_date'] ?? $start;

// History page only: swap the status look for the timeline phase. The phase comes from the
// dates (never stored), so nothing has to flip a booking to "completed" overnight.
// A booking is Completed from the day AFTER its end date; it is Ongoing from the start date
// through the end date; before that it is Upcoming.
if ($history) {
$phases = config('admin.history.phases');

if (in_array($key, config('admin.history.terminal_statuses'), true)) {
$phaseKey = 'cancelled';
} else {
$today = today();
$phaseKey = $today->lt($start->copy()->startOfDay())
? 'upcoming'
: ($today->gt($end->copy()->startOfDay()) ? 'completed' : 'ongoing');
}

// Keys the phase does not define (e.g. label/badge for Cancelled) stay as the status had them.
$status = array_merge($status, $phases[$phaseKey], ['group' => $phaseKey, 'actions' => []]);
}

// Status column: a read-only badge. History rows get the phase's badge/icon from the merge above.
$badge = $status['badge'] ?? ['tone' => 'neutral', 'solid' => false];
$icon = $status['icon'] ?? null;

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
'editable' => $status['editable'] ?? true, // false hides Edit in the modal (History: Completed, Cancelled)
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
  class="admin-bookings__row{{ ($status['highlight'] ?? false) ? ' ' . ($status['highlight_class'] ?? 'is-pending') : '' }}"
  data-booking-row
  data-index="{{ $index }}"
  data-name="{{ Str::lower($booking['client']['name']) }}"
  data-date="{{ $start->toDateString() }}"
  data-month="{{ $start->format('Y-m') }}"
  data-rank="{{ $status['rank'] ?? 0 }}"
  data-package="{{ $package['id'] }}"
  data-status-group="{{ $status['group'] }}"
  data-search="{{ $search }}">

  <td role="cell" class="admin-bookings__cell admin-bookings__cell--client" data-label="Client">
    @if ($status['highlight'] ?? false)
    <span class="admin-bookings__chip{{ isset($status['chip_class']) ? ' ' . $status['chip_class'] : '' }}">{{ $status['chip'] ?? $status['label'] }}</span>
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
      class="admin-btn admin-btn--soft admin-btn--sm"
      data-bs-toggle="modal"
      data-bs-target="#bookingModal"
      data-booking="{{ json_encode($payload) }}"
      aria-label="View details for booking {{ $ref }}">
      <i class="ph ph-eye" aria-hidden="true"></i>
      <span>View Details</span>
    </button>
  </td>

      @foreach ($status['actions'] ?? [] as $action)
      @php
      // Danger actions always confirm. Any other action can opt in with a 'confirm' text in the
      // config (":ref" becomes the booking reference), e.g. Verify Payment.
      $confirm = $action['confirm'] ?? (($action['tone'] ?? '') === 'danger' ? $action['label'] . ' booking :ref?' : null);
      @endphp
      <form
        method="POST"
        action="{{ $statusUrl }}"
        class="admin-bookings__action-form"
        @if ($confirm) data-confirm="{{ str_replace(':ref', $ref, $confirm) }}" @endif>
        @csrf
        <button
          type="submit"
          name="action"
          value="{{ $action['action'] }}"
          class="admin-pill admin-pill--{{ $action['tone'] }}"
          aria-label="{{ $action['label'] }}, booking {{ $ref }}">
          {{ $action['label'] }}
        </button>
      </form>
      @endforeach
    </div>
  </td>
</tr>