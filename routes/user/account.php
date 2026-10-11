<?php

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

// User account section (opened from the user icon in the landing navigation). Required from
// routes/user.php, which web.php requires.
//
// FRONT-END STUBS ONLY: no middleware, no database, no validation on the server. The pages render
// from the logged-in user when there is one and from sample values when there is not, and the
// profile PATCH just answers 200. The JS does the validation (resources/js/account/profile.js).
// Route NAMES (user.account, user.account.profile, user.account.profile.update, user.account.bookings,
// user.account.bookings.cancel, user.account.messages, user.account.security, user.account.password.update) must not change: the navigation, the sidebar (config/account.php)
// and the JS build from them.
// When the backend is connected: add the `auth` middleware to these routes and replace the stubs.

// The navigation links here; it lands on the first tab.
Route::get('/user/account', fn() => redirect()->route('user.account.profile'))
  ->name('user.account');

Route::get('/user/account/profile', fn() => view('user.account.profile', ['user' => request()->user()]))
  ->name('user.account.profile');

// Contract the real controller must keep: 200 {ok, message, profile{...}} on success;
// 422 {message, errors{field: [msg]}} on rejection. Stub: always succeeds, saves nothing.
Route::patch('/user/account/profile', fn(Request $request) => response()->json([
  'ok' => true,
  'message' => 'Your profile has been updated.',
  'profile' => $request->only(['first_name', 'last_name', 'phone', 'address']),
]))->name('user.account.profile.update');

// My Bookings. FRONT-END STUB: sample bookings dated RELATIVE TO TODAY (so every tab always has
// something to show), no database read, no `bookings.user_id` yet. The real controller must return
// only the logged-in user's bookings, in the same array shape (docs/backend-impact.md, section 27).
// Nothing here touches the Booking model or the booking flow.
Route::get('/user/account/bookings', function () {
  $day = fn(int $offset) => Carbon::today()->addDays($offset);

  $booking = fn(int $id, string $ref, string $status, array $package, array $event, array $payment, array $extra = []) => array_merge([
    'id' => $id,
    'reference' => $ref,
    'status' => $status,
    'package' => $package,
    'event' => $event,
    'payment' => $payment,
    'can_reschedule' => false,
    'reschedule_note' => null,
  ], $extra);

  $event = fn(string $name, string $type, int $from, int $to, string $start, string $end, string $location, ?int $guests = null, ?string $venue = 'Indoor', ?string $other = null) => [
    'name' => $name,
    'type' => $type,
    'type_other' => $other,
    'location' => $location,
    'contact_person' => null,
    'guests' => $guests,
    'venue_type' => $venue,
    'start_date' => $day($from),
    'end_date' => $day($to),
    'start_time' => $start,
    'end_time' => $end,
  ];

  return view('user.account.bookings', ['bookings' => [
    $booking(
      1,
      '#JG12345',
      'approved',
      ['id' => 'modern-glam', 'name' => 'Modern Glam', 'price' => 35000],
      $event('Arjay & Co. Debut', 'Debut', 20, 20, '5:00 PM', '12:00 AM', 'Marikina Sports Center', 150, 'Indoor', null),
      ['method' => 'down', 'state' => 'partial', 'amount' => 10500]
    ),
    $booking(
      2,
      '#JG63497',
      'pending_payment',
      ['id' => 'luxe-lite', 'name' => 'Luxe Lite', 'price' => 25000],
      $event('Dela Cruz Family Reunion', 'Family Reunion', 12, 13, '10:00 AM', '10:00 PM', 'Sample Resort, Bulacan', 120, 'Outdoor'),
      ['method' => 'full', 'state' => 'pending', 'amount' => 25000]
    ),
    $booking(
      3,
      '#JG81234',
      'pending',
      ['id' => 'budget-party', 'name' => 'Budget Party', 'price' => 10000],
      $event('Mia\'s 18th Birthday', 'Birthday Party', 35, 35, '6:00 PM', '11:00 PM', 'Brgy. Hall, Antipolo', 60, 'Both'),
      ['method' => 'down', 'state' => 'pending', 'amount' => 3000]
    ),
    $booking(
      4,
      '#JG39201',
      'completed',
      ['id' => 'budget-wedding', 'name' => 'Budget Wedding', 'price' => 18000],
      $event('Santos Wedding', 'Wedding', -30, -30, '9:00 AM', '11:00 PM', 'San Rafael River Adventure', 150, 'Both'),
      ['method' => 'full', 'state' => 'paid', 'amount' => 18000]
    ),
    $booking(
      5,
      '#JG55210',
      'user_cancelled',
      ['id' => 'budget-lite', 'name' => 'Budget Lite', 'price' => 5000],
      $event('Office Year-End Party', 'Others', 3, 3, '4:00 PM', '9:00 PM', 'Sample Venue, Bulacan', 60, 'Indoor', 'Corporate Party'),
      ['method' => 'down', 'state' => 'partial', 'amount' => 1500],
      ['can_reschedule' => true]
    ),
    $booking(
      6,
      '#JG70018',
      'user_cancelled',
      ['id' => 'budget-party', 'name' => 'Budget Party', 'price' => 10000],
      $event('Baby Shower', 'Baby Shower', 16, 16, '1:00 PM', '6:00 PM', 'Sample Garden, Bulacan', 40, 'Outdoor'),
      ['method' => 'full', 'state' => 'paid', 'amount' => 10000],
      ['reschedule_note' => 'This booking was already rescheduled once.']
    ),
    $booking(
      7,
      '#JG11085',
      'declined',
      ['id' => 'elite-symphony', 'name' => 'Elite Symphony', 'price' => 45000],
      $event('Sample Concert', 'Concert', -9, -9, '7:00 PM', '12:00 AM', 'Sample Venue, Bulacan', 300, 'Outdoor'),
      []
    ),
    $booking(
      8,
      '#JG90001',
      'cancelled',
      ['id' => 'budget-lite', 'name' => 'Budget Lite', 'price' => 5000],
      $event('Team Building', 'Team-Building Event', -5, -5, '8:00 AM', '5:00 PM', 'Sample Resort, Bulacan', 45, 'Outdoor'),
      ['method' => 'full', 'state' => 'pending', 'amount' => 5000]
    ),
  ]]);
})->name('user.account.bookings');

// Cancel one of the user's own bookings. Contract the real controller must keep:
//   200 {ok, message, booking{id, status: 'user_cancelled', can_reschedule}} on success;
//   403/409/422 {message} when the booking is not the user's, not cancellable, or already started.
// Stub: always succeeds and saves nothing, so cancelling here never changes a real booking.
Route::post('/user/account/bookings/{booking}/cancel', fn(string $booking) => response()->json([
  'ok' => true,
  'message' => 'Your booking has been cancelled.',
  'booking' => ['id' => $booking, 'status' => 'user_cancelled', 'can_reschedule' => true],
]))->whereNumber('booking')->name('user.account.bookings.cancel');

// Messages. FRONT-END STUB: a sample thread (dated relative to now), nothing read or saved. The page
// reuses the shared chat components and posts to the existing `user.messages.send` route, so there
// is no new send route. The real controller passes the same `$conversation` as the landing widget
// (docs/backend-impact.md, section 28).
Route::get('/user/account/messages', function () {
  $m = fn(string $from, string $body, $at, array $files = []) => ['type' => 'message', 'from' => $from, 'body' => $body, 'at' => $at, 'attachments' => $files];

  return view('user.account.messages', ['conversation' => [
    'unread' => 1,
    'items' => [
      ['type' => 'booking', 'at' => Carbon::now()->subDay(), 'booking' => [
        'reference' => '#JG12345',
        'status' => 'approved',
        'package' => ['id' => 'modern-glam', 'name' => 'Modern Glam', 'price' => 35000],
        'event' => [
          'name' => 'Arjay & Co. Debut',
          'type' => 'Debut',
          'location' => 'Marikina Sports Center',
          'start_date' => Carbon::now()->addDays(20),
          'end_date' => Carbon::now()->addDays(20),
          'start_time' => '5:00 PM',
          'end_time' => '12:00 AM',
        ],
      ]],
      $m('admin', 'Your booking has been approved and confirmed.', Carbon::now()->subHours(5)),
      $m('client', 'Thank you! Here is my GCash receipt.', Carbon::now()->subHours(4), [['name' => 'gcash-receipt.pdf', 'url' => '#', 'kind' => 'file', 'size' => '240 KB']]),
      $m('admin', 'Thank you! Your payment has been received.', Carbon::now()->subHours(3)),
      $m('client', 'Can we change the guest count to 120?', Carbon::now()->subHour()),
      $m('admin', 'No problem. The event guest count has been updated to 120.', Carbon::now()->subMinutes(20)),
    ],
  ]]);
})->name('user.account.messages');

// Security. FRONT-END STUBS, no middleware: the page renders for the logged-in user (sample values when
// there is none) and the password PATCH just answers 200 and changes nothing. The form
// (resources/js/account/security.js) only calls the PATCH route when it exists.
// Optional view variable: 'hasPassword' => false for an account with no password of its own (signed up with
// Google only); only the backend can tell, so it is not passed here and the view defaults to true.
Route::get('/user/account/security', fn() => view('user.account.security', ['user' => request()->user()]))
  ->name('user.account.security');

// Contract the real controller must keep: PATCH JSON {current_password, password, password_confirmation};
// 200 {ok, message}; 422 {message, errors{field: [msg]}} (wrong current password, rule not met, same as the
// old one); 401 / 403 {message}. Stub: always succeeds, changes nothing.
Route::patch('/user/account/password', fn() => response()->json([
  'ok' => true,
  'message' => 'Your password has been updated.',
]))->name('user.account.password.update');
