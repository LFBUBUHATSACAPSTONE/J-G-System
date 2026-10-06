<?php

use Illuminate\Support\Facades\Route;

//USED FOR FRONT-END TESTING PURPOSES ONLY. REMOVE THIS ROUTE IN PRODUCTION
use Illuminate\Http\Request;

// Routes for the booking flow. Required from routes/user.php, which web.php requires.
// `user.booking` is the page; the four POST routes are the steps, and each one is still a
// front-end stub. When a real controller exists, replace that closure with a controller call and
// keep the route name.

Route::get('/user/booking', function () {
  return view('user.booking');
})->name('user.booking');

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

// Read-only. The calendar calls this when the step opens and on every month change.
// Real shape: { limit: int, month: 'YYYY-MM', full: ['YYYY-MM-DD', ...] } (only days at the limit).
Route::get('/booking/availability', function (Request $r) use ($bookingStubFullDates) {
  $month = (string) $r->query('month');

  if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
    return response()->json(['message' => 'Invalid month.', 'errors' => ['month' => ['Use YYYY-MM.']]], 422);
  }

  return response()->json([
    'limit' => config('scheduling.max_events_per_day'),
    'month' => $month,
    'full'  => array_values(array_filter($bookingStubFullDates, fn($d) => str_starts_with($d, $month))),
  ]);
})->name('booking.availability');

// Every day of the range must still be under the limit. If not: 422 with `full_dates` (the
// calendar marks them full and clears the selection). The real backend does this check inside
// the same transaction that saves the schedule, never from the availability route's answer.
Route::post('/booking/event-schedule', function (Request $r) use ($bookingStubFullDates, $bookingStubRaceDates, $bookingStubSummaryRaceDates) {
  $start = $r->input('event_start_date');
  $end = $r->input('event_end_date');

  if ($start && ! $end) {
    return response()->json([
      'message' => 'Select an end date on the calendar.',
      'errors'  => ['event_end_date' => ['Select an end date on the calendar.']],
    ], 422);
  }

  if ($start && $end) {
    $days = collect(\Carbon\CarbonPeriod::create($start, $end))->map->toDateString()->all();
    $full = array_values(array_intersect($days, array_merge($bookingStubFullDates, $bookingStubRaceDates)));

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

Route::post('/booking/booking-summary', function (Request $r) {
  if (! $r->input('payment_option')) {
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
