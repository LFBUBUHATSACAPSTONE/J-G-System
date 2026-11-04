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
// The placeholder bookings reuse the Bookings stub's people and shape, with the dates and
// statuses of the mockup (Dec 22 and Dec 30 green, Dec 31 yellow). Because the data is from
// December 2025, the stub opens on that month when no ?month= is given; a real controller
// should default to the current month.

Route::get('/admin/calendar', function () {
  $requested = request('month');
  $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $requested)
    ? Carbon::parse("{$requested}-01")
    : Carbon::parse('2025-12-01');

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

  return view('admin.calendar', [
    'month' => $month,
    'events' => [
      $booking(2, '#JG39201', 'approved', 'Ayessa Dumay', 'Modern Glam', "Aye's Concert", 'San Rafael River Adventure', '2025-12-22', '2025-12-22', '5:00 PM', '12:00 AM'),
      $booking(1, '#JG12345', 'approved', 'Arjay Dela Cruz', 'Budget Party', 'Sample Birthday', 'Sample Venue, Bulacan', '2025-12-30', '2025-12-30', '6:00 PM', '11:00 PM'),
      $booking(3, '#JG63497', 'pending_payment', 'Lhester Pile', 'Luxe Lite', 'Pile Family Reunion', 'Sample Resort, Bulacan', '2025-12-31', '2026-01-01', '10:00 AM', '10:00 PM'),
      // A second booking on the same date, to try the "2 bookings" cell and the stacked cards.
      $booking(6, '#JG55210', 'approved', 'Sample Client', 'Budget Wedding', 'Sample Wedding', 'Sample Garden, Bulacan', '2025-12-31', '2025-12-31', '4:00 PM', '9:00 PM'),
      // Cancelled / declined: kept in the data on purpose, the page must leave them off.
      $booking(4, '#JG11085', 'cancelled', 'RJ Valmadrid', 'Budget Lite', 'Sample Event', 'Sample Venue, Bulacan', '2025-12-23', '2025-12-23', '3:00 PM', '8:00 PM'),
      $booking(5, '#JG67677', 'declined', 'Paler Perez', 'Budget Lite', 'Sample Concert', 'Sample Venue, Bulacan', '2025-12-13', '2025-12-13', '7:00 PM', '12:00 AM'),
    ],
  ]);
})->name('admin.calendar');
