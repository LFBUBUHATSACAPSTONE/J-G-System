{{-- One booking row. Everything comes from the $booking array
     and from config/admin/bookings.php (how each status looks / which actions it gets).
     The data-* attributes on <tr> drive the JS filter/sort/search; data-booking on the
     View Details button carries the modal's content.
     History page: pass :history="true" and the row resolves its look from the event dates
     (Upcoming / Ongoing / Completed) using config/admin/history.php, with no action buttons.
     Cancelled and Declined bookings keep their own badge. Bookings page: nothing changes.
     Two separate cells on purpose: STATUS is a read-only badge, ACTIONS holds every button
     (View Details + the status's actions from config/admin/bookings.php). A status must never look
     like a button, so the two never share a cell or a shape. --}}
@props(['booking', 'index' => 0, 'history' => false, 'fullDates' => []])

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

// Event limit: a booking waiting for approval whose days include one already at the limit can't
// be approved. Only the Bookings page passes $fullDates (the History page never has Approve).
// The server still decides: it re-checks when Approve is pressed (docs/event-capacity.md).
$dayFull = ! $history
&& ($booking['status'] === 'pending')
&& count(array_intersect($fullDates, collect(\Carbon\CarbonPeriod::create($start, $end))->map->toDateString()->all())) > 0;

// Route::has() keeps the page rendering before the POST routes exist.
$statusUrl = Route::has('admin.bookings.status')
? route('admin.bookings.status', ['booking' => $booking['id']])
: '#';

// Event type shown to the admin: for "Others" it is the text the client specified.
$typeLabel = (($event['type'] ?? null) === 'Others' && filled($event['type_other'] ?? null))
? $event['type_other']
: ($event['type'] ?? null);

// Down payment: show the share of the package cost the client chose (user side: at least 30%).
// The backend may pass payment.down_payment_percent, or payment.down_payment_amount (pesos) and the
// percentage is worked out from the package price. Full payments and missing data are left as is.
$payment = $booking['payment'] ?? [];
$downPercent = $payment['down_payment_percent']
?? ((! empty($payment['down_payment_amount']) && ! empty($package['price']))
? $payment['down_payment_amount'] / $package['price'] * 100
: null);
if ($downPercent !== null && ! empty($payment['label']) && ! str_contains($payment['label'], '%')) {
$payment['label'] .= ' (' . rtrim(rtrim(number_format($downPercent, 1), '0'), '.') . '%)';
}

// Down payment value in pesos, shown in the modal's "Down Payment Value" field.
if (! empty($payment['down_payment_amount'])) {
$amount = (float) $payment['down_payment_amount'];
$payment['down_payment_label'] = 'Php ' . number_format($amount, fmod($amount, 1.0) == 0.0 ? 0 : 2);
}

$payload = [
'id' => $booking['id'],
'reference' => $ref,
'client' => $booking['client'],
'event' => [
'name' => $event['name'] ?? null,
'type' => $typeLabel,
'location' => $event['location'] ?? null,
'contact_person' => $event['contact_person'] ?? null,
'guests' => $event['guests'] ?? null,
'venue_type' => $event['venue_type'] ?? null,
'schedule' => $schedule,
'start_time' => $event['start_time'] ?? null,
'end_time' => $event['end_time'] ?? null,
],
'editable' => $status['editable'] ?? true, // false hides Edit in the modal (History: Completed, Cancelled)
'payment' => $payment,
'package' => [
'name' => $package['name'],
'price_label' => isset($package['price']) ? 'Php ' . number_format($package['price']) : null,
],
];

$search = Str::lower(implode(' ', [
$booking['client']['name'], $ref, $package['name'], $typeLabel ?? '', $status['label'], $schedule,
]));
@endphp

<tr
  role="row"
  class="admin-bookings__row{{ ($status['highlight'] ?? false) ? ' ' . ($status['highlight_class'] ?? 'is-pending') : '' }}"
  data-booking-row
  data-index="{{ $index }}"
  data-booking-id="{{ $booking['id'] }}"
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
    @if ($dayFull)
    <span class="admin-bookings__note" id="day-full-{{ $booking['id'] }}">
      <i class="ph ph-calendar-x" aria-hidden="true"></i>
      Day full ({{ config('scheduling.max_events_per_day') }}/{{ config('scheduling.max_events_per_day') }} approved)
    </span>
    @endif
  </td>

  <td role="cell" class="admin-bookings__cell" data-label="Schedule">
    <time datetime="{{ $start->toDateString() }}">{{ $schedule }}</time>
  </td>

  <td role="cell" class="admin-bookings__cell" data-label="Package">{{ $package['name'] }}</td>

  <td role="cell" class="admin-bookings__cell" data-label="Event Type">{{ $typeLabel ?? '--' }}</td>

  <td role="cell" class="admin-bookings__cell admin-bookings__cell--status" data-label="Status">
    <span class="admin-badge admin-badge--{{ $badge['tone'] }}{{ ($badge['solid'] ?? false) ? ' admin-badge--solid' : '' }}">
      @if ($icon)
      <i class="ph ph-{{ $icon }}" aria-hidden="true"></i>
      @endif
      {{ $status['label'] }}
    </span>
  </td>

  <td role="cell" class="admin-bookings__cell admin-bookings__cell--actions" data-label="">
    <div class="admin-bookings__actions">
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
        @php $blocked = $dayFull && $action['action'] === 'approve'; @endphp
        <button
          type="submit"
          name="action"
          value="{{ $action['action'] }}"
          class="admin-pill admin-pill--{{ $action['tone'] }}"
          @if ($blocked) disabled aria-describedby="day-full-{{ $booking['id'] }}" title="This day already has the maximum number of approved events." @endif
          aria-label="" {{ $action['label'] }}, booking {{ $ref }}">
          {{ $action['label'] }}
        </button>
      </form>
      @endforeach
    </div>
  </td>
</tr>