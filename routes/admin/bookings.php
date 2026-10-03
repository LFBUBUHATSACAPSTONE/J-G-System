<?php

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

// Routes for the Bookings page. Required from routes/admin.php, which web.php requires.
// Everything here is a FRONT-END STUB (placeholder data, nothing is saved). When the backend is
// built, replace each closure with a controller call and keep this file as the page's routes.
//
// Route NAMES must stay as they are: config/admin.php (page meta + sidebar active state)
// keys off `admin.bookings`, and the row/modal markup builds URLs from the other two.
//
// Everything below is placeholder data. The rest is made up so every status and filter can be tried.

Route::get('/admin/bookings', function () {
  return view('admin.bookings', [
    // Admin-managed, so the dropdown is built from this list, not hard-coded.
    'packages' => [
      ['id' => 'budget-lite',    'name' => 'Budget Lite'],
      ['id' => 'budget-party',   'name' => 'Budget Party'],
      ['id' => 'budget-wedding', 'name' => 'Budget Wedding'],
      ['id' => 'luxe-lite',      'name' => 'Luxe Lite'],
      ['id' => 'modern-glam',    'name' => 'Modern Glam'],
      ['id' => 'elite-symphony', 'name' => 'Elite Symphony'],
    ],

    'bookings' => [
      [
        'id' => 1,
        'reference' => '#JG12345',
        'status' => 'pending',
        'client' => ['name' => 'Arjay Dela Cruz', 'email' => 'arjay@example.com', 'phone' => '0917 000 0001', 'address' => 'Sample Barangay, Sample City, Bulacan'],
        'event' => [
          'name' => 'Sample Birthday',
          'type' => 'Birthday Party',
          'location' => 'Sample Venue, Bulacan',
          'contact_person' => null,
          'guests' => 80,
          'venue_type' => 'Indoor',
          'start_date' => Carbon::parse('2025-12-30'),
          'end_date' => Carbon::parse('2025-12-30'),
          'start_time' => '6:00 PM',
          'end_time' => '11:00 PM',
        ],
        'payment' => ['label' => 'GCash - Down Payment', 'status' => 'Awaiting verification', 'state' => 'pending'],
        'package' => ['id' => 'budget-party', 'name' => 'Budget Party', 'price' => 12000],
      ],
      [
        'id' => 2,
        'reference' => '#JG39201',
        'status' => 'approved',
        'client' => ['name' => 'Ayessa Dumay', 'email' => 'ayessadumay.basc@gmail.com', 'phone' => '0912 345 6789', 'address' => 'Poblacion, San Ildefonso Bulacan'],
        'event' => [
          'name' => "Aye's Concert",
          'type' => 'Concert',
          'location' => 'San Rafael River Adventure',
          'contact_person' => '0906 026 7988',
          'guests' => null,
          'venue_type' => 'Both',
          'start_date' => Carbon::parse('2025-12-22'),
          'end_date' => Carbon::parse('2025-12-22'),
          'start_time' => '5:00 PM',
          'end_time' => '12:00 AM',
        ],
        'payment' => ['label' => 'GCash - Full Payment', 'status' => 'Fully Paid', 'state' => 'paid'],
        'package' => ['id' => 'modern-glam', 'name' => 'Modern Glam', 'price' => 35000],
      ],
      [
        'id' => 3,
        'reference' => '#JG63497',
        'status' => 'pending_payment',
        'client' => ['name' => 'Lhester Pile', 'email' => 'lhester@example.com', 'phone' => '0917 000 0003', 'address' => 'Sample Barangay, Sample City, Bulacan'],
        'event' => [
          'name' => 'Pile Family Reunion',
          'type' => 'Family Reunion',
          'location' => 'Sample Resort, Bulacan',
          'contact_person' => '0917 000 0033',
          'guests' => 120,
          'venue_type' => 'Outdoor',
          'start_date' => Carbon::parse('2025-12-31'),
          'end_date' => Carbon::parse('2026-01-01'),
          'start_time' => '10:00 AM',
          'end_time' => '10:00 PM',
        ],
        'payment' => ['label' => 'GCash - Down Payment', 'status' => 'Down payment paid', 'state' => 'partial'],
        'package' => ['id' => 'luxe-lite', 'name' => 'Luxe Lite', 'price' => 20000],
      ],
      [
        'id' => 4,
        'reference' => '#JG11085',
        'status' => 'cancelled',
        'client' => ['name' => 'RJ Valmadrid', 'email' => 'rj@example.com', 'phone' => '0917 000 0004', 'address' => 'Sample Barangay, Sample City, Bulacan'],
        'event' => [
          'name' => 'Sample Event',
          'type' => 'Others',
          'location' => 'Sample Venue, Bulacan',
          'contact_person' => null,
          'guests' => null,
          'venue_type' => null,
          'start_date' => Carbon::parse('2025-12-23'),
          'end_date' => Carbon::parse('2025-12-23'),
          'start_time' => '3:00 PM',
          'end_time' => '8:00 PM',
        ],
        'payment' => ['label' => 'GCash - Full Payment', 'status' => 'Refund pending', 'state' => 'pending'],
        'package' => ['id' => 'budget-lite', 'name' => 'Budget Lite', 'price' => 8000],
      ],
      [
        'id' => 5,
        'reference' => '#JG67677',
        'status' => 'declined',
        'client' => ['name' => 'Paler Perez', 'email' => 'paler@example.com', 'phone' => '0917 000 0005', 'address' => 'Sample Barangay, Sample City, Bulacan'],
        'event' => [
          'name' => 'Sample Concert',
          'type' => 'Concert',
          'location' => 'Sample Venue, Bulacan',
          'contact_person' => null,
          'guests' => 300,
          'venue_type' => 'Outdoor',
          'start_date' => Carbon::parse('2025-12-13'),
          'end_date' => Carbon::parse('2025-12-13'),
          'start_time' => '7:00 PM',
          'end_time' => '12:00 AM',
        ],
        'payment' => [],
        'package' => ['id' => 'budget-lite', 'name' => 'Budget Lite', 'price' => 8000],
      ],
    ],
  ]);
})->name('admin.bookings');

// Approve / Decline / Cancel buttons (a normal form POST, so the server re-renders the page).
Route::post('/admin/bookings/{booking}/status', function (Request $request, $booking) {
  $action = $request->validate(['action' => ['required', 'in:approve,decline,cancel']])['action'];

  return back()->with('status', "Booking {$booking}: '{$action}' received (stub, nothing was saved).");
})->name('admin.bookings.status');

// "Save changes" in the booking modal (editable event fields only).
Route::patch('/admin/bookings/{booking}', function (Request $request, $booking) {
  $request->validate([
    'event_name' => ['nullable', 'string', 'max:255'],
    'event_location' => ['nullable', 'string', 'max:255'],
    'venue_contact_person' => ['nullable', 'string', 'max:255'],
    'guest_count' => ['nullable', 'integer', 'min:0'],
    'venue_type' => ['nullable', 'in:Indoor,Outdoor,Both'],
  ]);

  return back()->with('status', "Booking {$booking}: changes received (stub, nothing was saved).");
})->name('admin.bookings.update');
