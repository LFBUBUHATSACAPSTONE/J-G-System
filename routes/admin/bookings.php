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
// Status flow and the buttons each status gets: config/admin/bookings.php.
//
// Everything below is placeholder data. 

// ---- Event capacity (max 3 approved events per day: config/scheduling.php) --------------------
// STUB DATA. The real controller counts events whose status is in config('scheduling.counted_statuses')
// per day (a multi-day event counts on each of its days). See docs/event-capacity.md.
//   $adminStubFullDates   : days already at the limit when the page loads. A pending booking that
//                           touches one gets its Approve button disabled with a "Day full" note.
//   $adminStubRaceDates   : days that are full only by the time Approve is pressed (the race).
//   $adminStubBookingDays : the days each stub booking covers, so the status POST can re-check
//                           the way the server must. Booking 6 is the race case.
$adminStubFullDates = ['2025-12-30'];
$adminStubRaceDates = ['2026-01-15'];
$adminStubBookingDays = [1 => ['2025-12-30'], 6 => ['2026-01-15']];

Route::get('/admin/bookings', function () use ($adminStubFullDates) {
  return view('admin.bookings', [
    // Days at the event limit, as 'Y-m-d' strings. The rows read it; nothing is counted in Blade.
    'fullDates' => $adminStubFullDates,

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
        'payment' => ['label' => 'GCash - Down Payment', 'status' => 'Awaiting verification', 'state' => 'pending'],
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
      [
        'id' => 6,
        'reference' => '#JG55120',
        'status' => 'pending',
        'client' => ['name' => 'Joanna Reyes', 'email' => 'joanna@example.com', 'phone' => '0917 000 0006', 'address' => 'Sample Barangay, Sample City, Bulacan'],
        'event' => [
          'name' => 'Sample Debut',
          'type' => 'Debut',
          'location' => 'Sample Hall, Bulacan',
          'contact_person' => null,
          'guests' => 150,
          'venue_type' => 'Indoor',
          'start_date' => Carbon::parse('2026-01-15'),
          'end_date' => Carbon::parse('2026-01-15'),
          'start_time' => '5:00 PM',
          'end_time' => '11:00 PM',
        ],
        'payment' => [],
        'package' => ['id' => 'luxe-lite', 'name' => 'Luxe Lite', 'price' => 20000],
      ],
    ],
  ]);
})->name('admin.bookings');

// Approve / Decline / Cancel / Verify Payment buttons (a normal form POST, so the server re-renders the page).
// Next status the real controller should set:
//   approve  : pending         -> pending_payment   (shown as "Payment to Verify")
//   verify   : pending_payment -> approved          (payment.state becomes paid / partial, so the
//                                                    booking now belongs in Booking History)
//   decline  : pending         -> declined
//   cancel   : pending_payment | approved -> cancelled
//
// APPROVE IS THE CAPACITY GATE. Two pending bookings can sit on the same day, so the page can be
// stale: the server must re-count and approve in ONE transaction (docs/event-capacity.md). If any
// day of the booking is already at the limit, nothing changes and the page gets `error` flashed
// (a JSON caller gets 409 { message, full_dates }). The booking stays pending.
Route::post('/admin/bookings/{booking}/status', function (Request $request, $booking) use ($adminStubFullDates, $adminStubRaceDates, $adminStubBookingDays) {
  $action = $request->validate(['action' => ['required', 'in:approve,decline,cancel,verify']])['action'];

  if ($action === 'approve') {
    $full = array_values(array_intersect(
      $adminStubBookingDays[(int) $booking] ?? [],
      array_merge($adminStubFullDates, $adminStubRaceDates),
    ));

    if ($full) {
      $message = "Booking {$booking} was not approved: " . (count($full) > 1 ? 'some of its days are' : 'its day is')
        . ' already at the limit of ' . config('scheduling.max_events_per_day') . ' approved events.';

      if ($request->expectsJson()) {
        return response()->json(['message' => $message, 'full_dates' => $full], 409);
      }

      return back()->with('error', $message);
    }
  }

  $message = $action === 'verify'
    ? "Booking {$booking}: payment verified (stub, nothing was saved). Once saved, it is Approved and appears in Booking History."
    : "Booking {$booking}: '{$action}' received (stub, nothing was saved).";

  return back()->with('status', $message);
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
