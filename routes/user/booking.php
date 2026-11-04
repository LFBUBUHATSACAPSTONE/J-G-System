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

Route::post('/booking/event-schedule', function (Request $r) {
  if ($r->input('event_start_date') && ! $r->input('event_end_date')) {
    return response()->json([
      'message' => 'Select an end date on the calendar.',
      'errors'  => ['event_end_date' => ['Select an end date on the calendar.']],
    ], 422);
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

  return response()->json(['ok' => true]);
})->name('booking.booking-summary');
