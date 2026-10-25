{{-- One booking row. Everything comes from the $booking array
     and from config/admin/bookings.php (how each status looks / which actions it gets).
     The data-* attributes on <tr> drive the JS filter/sort/search; data-booking on the
     View Details button carries the modal's content.
     History page: pass :history="true" and the row resolves its look from the event dates
     (Upcoming / Ongoing / Completed) using config/admin/history.php, with no action buttons.
     Cancelled and Declined bookings keep their own badge. In History the modal is view-only
     (payload editable = false) but still shows the "Edited" labels. Bookings page: Edit is on.
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
$status = array_merge($status, $phases[$phaseKey] ?? [], ['group' => $phaseKey, 'actions' => []]);
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

// Event type for the modal's dropdown. A stored type that is not in the user-side list (older
// bookings) is shown as "Others" with its own text, so Edit never forces the admin to re-pick it.
$rawType = $event['type'] ?? null;
$knownTypes = config('admin.bookings.event_types', []);
if ($rawType !== null && $rawType !== 'Others' && ! in_array($rawType, $knownTypes, true)) {
$typeValue = 'Others';
$typeOther = $rawType;
} else {
$typeValue = $rawType;
$typeOther = $rawType === 'Others' ? ($event['type_other'] ?? null) : null;
}

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
'type_value' => $typeValue,
'type_other' => $typeOther,
'start_date' => $start->toDateString(), // the modal works out the Event Location lock from it
'location' => $event['location'] ?? null,
'contact_person' => $event['contact_person'] ?? null,
'guests' => $event['guests'] ?? null,
'venue_type' => $event['venue_type'] ?? null,
'schedule' => $schedule,
'start_time' => $event['start_time'] ?? null,
'end_time' => $event['end_time'] ?? null,
],
// Cancelled / Declined: why, so View Details can show it (null = nothing to show).
// `status_reason` is what the backend should pass on the booking array (docs/backend-impact.md).
'status' => $key,
'reason' => filled($booking['status_reason'] ?? null) && config("cancellation.labels.{$key}")
? ['label' => config("cancellation.labels.{$key}"), 'by' => config("cancellation.by.{$key}"), 'text' => $booking['status_reason']]
: null,
// Client cancellation request + admin decision (pending / approved / declined / direct).
// Filled by the backend from the booking's own columns; the modal shows it and the two buttons.
'cancellation' => $booking['cancellation'] ?? null,
'editable' => ! $history, // History is a record: the modal there is view-only
// Fields the admin changed after the client booked: { 'event.location' => 'original value', ... }.
// Keys are the modal's data-booking-field paths. Empty array = nothing edited.
'edited' => $booking['edited'] ?? [],
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

@php
// Tailwind classes for the row. The status's highlight_class (is-pending / is-ongoing) and chip_class stay on the
// markup as hooks; the look itself is drawn here.
$highlight = ($status['highlight'] ?? false) ? ($status['highlight_class'] ?? 'is-pending') : null;
$cellOutline = 'min-[1200px]:mt-0 min-[1200px]:[&>td]:border-y-2 min-[1200px]:[&>td:first-child]:border-l-2 min-[1200px]:[&>td:last-child]:border-r-2';
$rowClass = 'admin-bookings__row relative mb-6 grid grid-cols-2 gap-x-4 gap-y-3 rounded-card border-2 bg-surface p-6 text-fluid-md text-ink [&[hidden]]:hidden! min-[1200px]:m-0 min-[1200px]:table-row min-[1200px]:border-0 min-[1200px]:bg-transparent min-[1200px]:p-0 '
. match ($highlight) {
'is-pending' => "mt-6 border-warning-border {$cellOutline} min-[1200px]:[&>td]:border-warning-border",
'is-ongoing' => "mt-6 border-brand {$cellOutline} min-[1200px]:[&>td]:border-brand",
default => 'border-transparent',
};
$cell = 'admin-bookings__cell min-w-0 before:block before:text-xs before:text-ink-muted before:content-[attr(data-label)] min-[1200px]:bg-surface min-[1200px]:p-4 min-[1200px]:align-middle min-[1200px]:before:hidden min-[1200px]:first:relative min-[1200px]:first:rounded-l-card min-[1200px]:first:pl-6 min-[1200px]:last:rounded-r-card min-[1200px]:last:pr-6';
$chip = 'admin-bookings__chip absolute -top-[1.15rem] left-5 rounded-full px-3 text-[0.85rem] font-medium '
. ($highlight === 'is-ongoing' ? 'bg-brand text-white' : 'bg-chip-pending text-ink')
. (isset($status['chip_class']) ? ' ' . $status['chip_class'] : '');
@endphp

<tr
  role="row"
  class="{{ $rowClass }}{{ ($status['highlight'] ?? false) ? ' ' . ($status['highlight_class'] ?? 'is-pending') : '' }}"
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

  <td role="cell" class="{{ $cell }} col-span-full" data-label="Client">
    @if ($status['highlight'] ?? false)
    <span class="{{ $chip }}">{{ $status['chip'] ?? $status['label'] }}</span>
    @endif
    <span class="admin-bookings__name font-medium">{{ $booking['client']['name'] }}</span>
    <span class="admin-bookings__ref ml-2 text-brand-text">{{ $ref }}</span>
    @if ($dayFull)
    <span class="admin-bookings__note mt-1 block text-[0.85rem] font-semibold text-danger-text" id="day-full-{{ $booking['id'] }}">
      <i class="ph ph-calendar-x" aria-hidden="true"></i>
      Day full ({{ config('scheduling.max_events_per_day') }}/{{ config('scheduling.max_events_per_day') }} approved)
    </span>
    @endif
  </td>

  <td role="cell" class="{{ $cell }}" data-label="Schedule">
    <time datetime="{{ $start->toDateString() }}">{{ $schedule }}</time>
  </td>

  <td role="cell" class="{{ $cell }}" data-label="Package">{{ $package['name'] }}</td>

  <td role="cell" class="{{ $cell }}" data-label="Event Type">{{ $typeLabel ?? '--' }}</td>

  <td role="cell" class="{{ $cell }} col-span-full" data-label="Status">
    <span class="admin-badge admin-badge--{{ $badge['tone'] }}{{ ($badge['solid'] ?? false) ? ' admin-badge--solid' : '' }}">
      @if ($icon)
      <i class="ph ph-{{ $icon }}" aria-hidden="true"></i>
      @endif
      {{ $status['label'] }}
    </span>
  </td>

  <td role="cell" class="{{ $cell }} col-span-full" data-label="">
    <div class="admin-bookings__actions flex flex-col gap-3 min-[1200px]:items-center">
      <button
        type="button"
        class="admin-btn admin-btn--soft admin-btn--sm min-h-11 w-full min-w-0 min-[1200px]:min-h-10 min-[1200px]:w-auto min-[1200px]:whitespace-nowrap"
        data-bs-toggle="modal"
        data-bs-target="#bookingModal"
        data-booking="{{ json_encode($payload) }}"
        aria-label="View details for booking {{ $ref }}">
        <i class="ph ph-eye" aria-hidden="true"></i>
        <span>View Details</span>
      </button>

      @if (! empty($status['actions']))
      <div class="admin-bookings__actions-row flex flex-nowrap gap-2 min-[1200px]:justify-center">
        @foreach ($status['actions'] ?? [] as $action)
        @php
        // Every action opens the shared action modal (js/shared/action-modal.js). Copy comes from the
        // action's 'modal' array in config/admin/bookings.php (":ref" becomes the booking reference);
        // a 'reason_label' there makes the modal ask for the required reason (Decline, Cancel).
        $m = $action['modal'] ?? [];
        $modalData = [
        'title' => $m['title'] ?? $action['label'] . ' this booking?',
        'message' => str_replace(':ref', $ref, $m['message'] ?? ($action['label'] . ' booking :ref?')),
        'confirm' => $m['confirm'] ?? $action['label'],
        'tone' => $action['tone'] ?? 'brand',
        'reason_label' => $m['reason_label'] ?? null,
        'reason_hint' => $m['reason_hint'] ?? null,
        ];
        @endphp
        <form
          method="POST"
          action="{{ $statusUrl }}"
          class="admin-bookings__action-form flex min-w-0 flex-[1_1_0] min-[1200px]:flex-[0_0_auto]"
          data-action-modal-copy="{{ json_encode($modalData) }}">
          @csrf
          @php $blocked = $dayFull && $action['action'] === 'approve'; @endphp
          <button
            type="submit"
            name="action"
            value="{{ $action['action'] }}"
            class="admin-pill admin-pill--{{ $action['tone'] }} min-h-11 w-full px-3 whitespace-nowrap min-[1200px]:min-h-10 min-[1200px]:w-auto min-[1200px]:px-4 min-[1200px]:py-1 min-[1200px]:text-fluid-sm"
            @if ($blocked) disabled aria-describedby="day-full-{{ $booking['id'] }}" title="This day already has the maximum number of approved events." @endif
            aria-label="{{ $action['label'] }}, booking {{ $ref }}">
            {{ $action['label'] }}
          </button>
        </form>
        @endforeach
      </div>
      @endif
    </div>
  </td>
</tr>
