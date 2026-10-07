<?php

use Illuminate\Support\Facades\Route;

//USED FOR FRONT-END TESTING PURPOSES ONLY. REMOVE THIS ROUTE IN PRODUCTION
use Illuminate\Http\Request;

// Routes for the booking flow. Required from routes/user.php, which web.php requires.
// `user.booking` is the page; the four POST routes are the steps, and each one is still a
// front-end stub. When a real controller exists, replace that closure with a controller call and
// keep the route name.

// Temporary front-end stubs. Delete once the real controllers replace them.
Route::post('/booking/client-information', function (Request $r) {
  if ($r->input('email') === 'taken@example.com') {
    return response()->json([
      'message' => 'That email is already associated with a booking.',
      'errors'  => ['email' => ['That email is already associated with a booking.']],
    ], 422);
  }

  return response()->json(['ok' => true]);
})->name('booking.client-information');

Route::post('/booking/event-information', function (Request $r) {
  if ($r->input('event_type') === 'Others' && ! trim((string) $r->input('event_type_other'))) {
    return response()->json([
      'message' => 'Please specify your event type.',
      'errors'  => ['event_type_other' => ['Please specify your event type.']],
    ], 422);
  }

  return response()->json(['ok' => true]);
})->name('booking.event-information');

// ---- Event capacity (max 3 approved events per day: config/scheduling.php) --------------------
// STUB DATA. The real backend counts approved events per day (docs/event-capacity.md).
//   $bookingStubFullDates        : days already full; reported by the availability route.
//   $bookingStubRaceDates        : look free on load, but full when Event Schedule is submitted
//                                  (another client took the last slot).
//   $bookingStubSummaryRaceDates : pass Event Schedule, then are "taken" before the final submit,
//                                  so Booking Summary answers 422 once with `full_dates`.
// Keep these variable names unique: every routes file shares one scope.
$bookingStubFullDates = ['2026-10-14', '2026-10-15', '2026-10-28'];
$bookingStubRaceDates = ['2026-10-21'];
$bookingStubSummaryRaceDates = ['2026-10-22'];

// ---- Reschedule within the original month ---------
// STUB DATA. The real backend loads the booking and reads the month from the STORED start date,
// never from the request. Open the booking page with /user/booking?reschedule=<id>:
//   JG70001 : user-cancelled, month 2026-10          -> reschedule mode on, month locked
//   JG70002 : user-cancelled, month 2026-12          -> no days left (the stub marks the whole month full)
//   JG70003 : user-cancelled, month 2025-01 (passed) -> 403
//   JG70004 : user-cancelled, already rescheduled    -> 403
//   JG70005 : declined by the admin                  -> 403
$bookingStubReschedules = [
  'JG70001' => ['reference' => '#JG70001', 'month' => '2026-10', 'status' => 'user_cancelled', 'rescheduled' => false],
  'JG70002' => ['reference' => '#JG70002', 'month' => '2026-12', 'status' => 'user_cancelled', 'rescheduled' => false],
  'JG70003' => ['reference' => '#JG70003', 'month' => '2025-01', 'status' => 'user_cancelled', 'rescheduled' => false],
  'JG70004' => ['reference' => '#JG70004', 'month' => '2026-10', 'status' => 'user_cancelled', 'rescheduled' => true],
  'JG70005' => ['reference' => '#JG70005', 'month' => '2026-10', 'status' => 'declined', 'rescheduled' => false],
];
$bookingStubFullMonths = ['2026-12'];

// [booking, null] when it may be rescheduled, else [null, message] (the caller answers 403).
$bookingStubReschedule = function (?string $id) use ($bookingStubReschedules) {
  $booking = $bookingStubReschedules[$id] ?? null;

  if (! $booking) {
    return [null, 'This booking cannot be rescheduled.'];
  }
  if ($booking['status'] !== 'user_cancelled') {
    return [null, 'Only a booking you cancelled can be rescheduled.'];
  }
  if ($booking['rescheduled']) {
    return [null, 'This booking was already rescheduled once.'];
  }
  if ($booking['month'] < now()->format('Y-m')) {
    return [null, 'The original month has passed, so this booking can no longer be rescheduled.'];
  }

  return [$booking, null];
};

// Days of one month at the event limit: the listed full dates, or the whole month for a full month.
$bookingStubFullForMonth = function (string $month) use ($bookingStubFullDates, $bookingStubFullMonths) {
  if (in_array($month, $bookingStubFullMonths, true)) {
    $count = \Carbon\Carbon::parse($month . '-01')->daysInMonth;

    return array_map(fn($d) => sprintf('%s-%02d', $month, $d), range(1, $count));
  }

  return array_values(array_filter($bookingStubFullDates, fn($d) => str_starts_with($d, $month)));
};

// The booking page. With ?reschedule=<id> it opens in reschedule mode (see the stubs above).
Route::get('/user/booking', function (Request $r) use ($bookingStubReschedule) {
  $id = $r->query('reschedule');

  if (! $id) {
    return view('user.booking');
  }

  [$booking, $why] = $bookingStubReschedule($id);
  if ($why) {
    return response()->view('user.booking', ['rescheduleBlocked' => $why], 403);
  }

  return view('user.booking', ['reschedule' => [
    'booking_id' => $id,
    'reference'  => $booking['reference'],
    'month'    => $booking['month'],
  ]]);
})->name('user.booking');

// Read-only. The calendar calls this when the step opens and on every month change.
// Real shape: { limit: int, month: 'YYYY-MM', full: ['YYYY-MM-DD', ...] } (only days at the limit).
Route::get('/booking/availability', function (Request $r) use ($bookingStubFullForMonth) {
  $month = (string) $r->query('month');

  if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
    return response()->json(['message' => 'Invalid month.', 'errors' => ['month' => ['Use YYYY-MM.']]], 422);
  }

  return response()->json([
    'limit' => config('scheduling.max_events_per_day'),
    'month' => $month,
    'full'  => $bookingStubFullForMonth($month),
  ]);
})->name('booking.availability');

// Every day of the range must still be under the limit. If not: 422 with `full_dates` (the
// calendar marks them full and clears the selection). The real backend does this check inside
// the same transaction that saves the schedule, never from the availability route's answer.
Route::post('/booking/event-schedule', function (Request $r) use ($bookingStubFullDates, $bookingStubRaceDates, $bookingStubSummaryRaceDates, $bookingStubFullMonths, $bookingStubReschedule) {
  $start = $r->input('event_start_date');
  $end = $r->input('event_end_date');

  // Reschedule: the month comes from the stored booking, not from the request.
  if ($rescheduleId = $r->input('reschedule_id')) {
    [$stored, $why] = $bookingStubReschedule($rescheduleId);

    if ($why) {
      return response()->json(['message' => $why], 403);
    }

    foreach (array_filter([$start, $end]) as $date) {
      if (! str_starts_with($date, $stored['month'])) {
        $message = 'Rescheduling is limited to ' . \Carbon\Carbon::parse($stored['month'] . '-01')->format('F Y') . '.';

        return response()->json(['message' => $message, 'errors' => ['event_start_date' => [$message]]], 422);
      }
    }
  }

  if ($start && ! $end) {
    return response()->json([
      'message' => 'Select an end date on the calendar.',
      'errors'  => ['event_end_date' => ['Select an end date on the calendar.']],
    ], 422);
  }

  if ($start && $end) {
    $days = collect(\Carbon\CarbonPeriod::create($start, $end))->map->toDateString()->all();
    $full = array_values(array_intersect($days, array_merge($bookingStubFullDates, $bookingStubRaceDates, array_filter($days, fn($d) => in_array(substr($d, 0, 7), $bookingStubFullMonths, true)))));

    if ($full) {
      $message = count($days) > 1
        ? 'One or more of the days you picked is fully booked. Please choose other dates.'
        : 'That day is fully booked. Please choose another date.';

      return response()->json([
        'message'    => $message,
        'errors'     => ['event_start_date' => [$message]],
        'full_dates' => $full,
      ], 422);
    }

    // Stub only: remember the chosen days so the final submit can fail once.
    session(['stub_summary_conflict' => array_values(array_intersect($days, $bookingStubSummaryRaceDates))]);
  }

  return response()->json(['ok' => true]);
})->name('booking.event-schedule');

Route::post('/booking/booking-summary', function (Request $r) use ($bookingStubReschedule) {
  // A reschedule carries the payment over, so no payment option is needed.
  if ($rescheduleId = $r->input('reschedule_id')) {
    [, $why] = $bookingStubReschedule($rescheduleId);

    if ($why) {
      return response()->json(['message' => $why], 403);
    }
  } elseif (! $r->input('payment_option')) {
    return response()->json([
      'message' => 'Please select a payment option.',
      'errors'  => ['payment_option' => ['Please select a payment option.']],
    ], 422);
  }

  // Final submit is where the booking is really created, so the capacity check runs here too.
  // Stub: a day from $bookingStubSummaryRaceDates (see event-schedule) is "taken" once.
  // The real backend re-checks the saved schedule's days, whatever the input.
  if ($taken = session()->pull('stub_summary_conflict')) {
    $message = 'Your selected day was just taken. Please choose another date.';

    return response()->json([
      'message'    => $message,
      'errors'     => ['event_start_date' => [$message]],
      'full_dates' => $taken,
    ], 422);
  }

  return response()->json(['ok' => true]);
})->name('booking.booking-summary');
