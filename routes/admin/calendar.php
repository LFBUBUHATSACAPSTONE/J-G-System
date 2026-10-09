<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

// Routes for the Calendar page. Required from routes/admin.php, which web.php requires.
// Everything here is a FRONT-END STUB (placeholder data, nothing is saved). When the backend is
// built, replace each closure with a controller call and keep this file as the page's routes.
//
// Route NAME must stay `admin.calendar`: config/admin.php (page meta + sidebar active state)
// and the month arrows in the view key off it.
//
// The page opens on the CURRENT month when no ?month= is given. The placeholder bookings reuse
// the Bookings stub's people and shape, with the statuses of the mockup (two green, one yellow
// spanning two days, a second booking on the same date, plus a cancelled and a declined one),
// and are placed relative to the current month so the demo always has something to show.

Route::get('/admin/calendar', function () {
  $requested = request('month');
  $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $requested)
    ? Carbon::parse("{$requested}-01")
    : Carbon::now()->startOfMonth();

  $booking = fn($id, $ref, $status, $client, $package, $name, $location, $start, $end, $from, $to) => [
    'id' => $id,
    'reference' => $ref,
    'status' => $status,
    'client' => ['name' => $client],
    'package' => ['name' => $package],
    'event' => [
      'name' => $name,
      'location' => $location,
      'start_date' => Carbon::parse($start),
      'end_date' => Carbon::parse($end),
      'start_time' => $from,
      'end_time' => $to,
    ],
  ];

  // Placeholder dates: day-of-month offsets inside the current month (all valid in any month).
  $d = fn(int $day) => Carbon::now()->startOfMonth()->addDays($day - 1)->toDateString();

  return view('admin.calendar', [
    'month' => $month,
    'events' => [
      $booking(2, '#JG39201', 'approved', 'Ayessa Dumay', 'Modern Glam', "Aye's Concert", 'San Rafael River Adventure', $d(22), $d(22), '5:00 PM', '12:00 AM'),
      $booking(1, '#JG12345', 'approved', 'Arjay Dela Cruz', 'Budget Party', 'Sample Birthday', 'Sample Venue, Bulacan', $d(26), $d(26), '6:00 PM', '11:00 PM'),
      $booking(3, '#JG63497', 'pending_payment', 'Lhester Pile', 'Luxe Lite', 'Pile Family Reunion', 'Sample Resort, Bulacan', $d(27), $d(28), '10:00 AM', '10:00 PM'),
      // A second booking on the same date, to try the "2 bookings" cell and the stacked cards.
      $booking(6, '#JG55210', 'approved', 'Sample Client', 'Budget Wedding', 'Sample Wedding', 'Sample Garden, Bulacan', $d(27), $d(27), '4:00 PM', '9:00 PM'),
      // Cancelled / declined: kept in the data on purpose, the page must leave them off.
      $booking(4, '#JG11085', 'cancelled', 'RJ Valmadrid', 'Budget Lite', 'Sample Event', 'Sample Venue, Bulacan', $d(23), $d(23), '3:00 PM', '8:00 PM'),
      $booking(5, '#JG67677', 'declined', 'Paler Perez', 'Budget Lite', 'Sample Concert', 'Sample Venue, Bulacan', $d(13), $d(13), '7:00 PM', '12:00 AM'),
    ],
  ]);
})->name('admin.calendar');
