<?php

use App\Http\Controllers\Admin\BookingHistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/booking-history', [BookingHistoryController::class, 'show'])
  ->name('admin.booking-history');

<<<<<<< HEAD
Route::get('/admin/booking-history/data', [BookingHistoryController::class, 'index'])
  ->name('admin.booking-history.data');
=======
Route::get('/admin/booking-history', function () {
  $today = Carbon::today();
  $day = fn(int $offset) => $today->copy()->addDays($offset);

  $client = fn(string $name, string $email) => [
    'name' => $name,
    'email' => $email,
    'phone' => '0917 000 0000',
    'address' => 'Sample Barangay, Sample City, Bulacan',
  ];

  $event = fn(string $name, string $type, int $from, int $to, string $start, string $end, ?int $guests = 80, string $venue = 'Indoor') => [
    'name' => $name,
    'type' => $type,
    'location' => 'Sample Venue, Bulacan',
    'contact_person' => null,
    'guests' => $guests,
    'venue_type' => $venue,
    'start_date' => $day($from),
    'end_date' => $day($to),
    'start_time' => $start,
    'end_time' => $end,
  ];

  $paid = ['label' => 'Full Payment', 'status' => 'Fully Paid', 'state' => 'paid', 'proof_url' => 'data:image/svg+xml;utf8,%3Csvg xmlns=%22http://www.w3.org/2000/svg%22 width=%22320%22 height=%22200%22%3E%3Crect width=%22320%22 height=%22200%22 fill=%22%23ddd%22/%3E%3Ctext x=%22160%22 y=%22105%22 text-anchor=%22middle%22 font-family=%22sans-serif%22 font-size=%2218%22 fill=%22%23555%22%3ESample proof%3C/text%3E%3C/svg%3E'];
  $partial = ['label' => 'Down Payment', 'status' => 'Down payment paid', 'state' => 'partial'];
  $unpaid = ['label' => 'Down Payment', 'status' => 'Awaiting verification', 'state' => 'pending'];

  $all = [
    // Completed (from the day after the end date).
    [
      'id' => 2,
      'reference' => '#JG39201',
      'status' => 'approved',
      'client' => $client('Ayessa Dumay', 'ayessa@example.com'),
      'event' => $event("Aye's Concert", 'Concert', -12, -12, '5:00 PM', '12:00 AM', null, 'Both'),
      'payment' => $paid,
      'package' => ['id' => 'modern-glam', 'name' => 'Modern Glam', 'price' => 35000]
    ],
    [
      'id' => 3,
      'reference' => '#JG63497',
      'status' => 'completed',
      'client' => $client('Lhester Pile', 'lhester@example.com'),
      'event' => $event('Pile Family Reunion', 'Family Reunion', -20, -19, '10:00 AM', '10:00 PM', 120, 'Outdoor'),
      'payment' => $paid,
      'package' => ['id' => 'luxe-lite', 'name' => 'Luxe Lite', 'price' => 25000]
    ],

    // Ongoing (today is between the start and end date).
    [
      'id' => 1,
      'reference' => '#JG12345',
      'status' => 'approved',
      'client' => $client('Arjay Dela Cruz', 'arjay@example.com'),
      'event' => $event('60th Birthday', 'Birthday Party', 0, 0, '8:00 AM', '5:00 PM', 67),
      'payment' => $paid,
      'package' => ['id' => 'budget-party', 'name' => 'Budget Party', 'price' => 10000]
    ],
    [
      'id' => 4,
      'reference' => '#JG70018',
      'status' => 'approved',
      'client' => $client('Mia Santos', 'mia@example.com'),
      'event' => $event('Santos Wedding Weekend', 'Wedding', -1, 1, '9:00 AM', '11:00 PM', 150, 'Both'),
      'payment' => $paid,
      'package' => ['id' => 'budget-wedding', 'name' => 'Budget Wedding', 'price' => 18000]
    ],

    // Upcoming and already paid (a verified down payment counts, see config 'paid_states').
    [
      'id' => 5,
      'reference' => '#JG81234',
      'status' => 'approved',
      'client' => $client('Rica Mendoza', 'rica@example.com'),
      'event' => $event('Mendoza Debut', 'Debut', 9, 9, '6:00 PM', '11:00 PM', 180),
      'payment' => $partial,
      'package' => ['id' => 'elite-symphony', 'name' => 'Elite Symphony', 'price' => 45000]
    ],
    [
      'id' => 6,
      'reference' => '#JG55210',
      'status' => 'approved',
      'client' => $client('Paolo Reyes', 'paolo@example.com'),
      'event' => $event('Reyes Office Party', 'Corporate Event', 30, 30, '4:00 PM', '9:00 PM', 60),
      'payment' => $paid,
      'package' => ['id' => 'budget-lite', 'name' => 'Budget Lite', 'price' => 8000]
    ],

    // Cancelled and declined (always history).
    [
      'id' => 7,
      'reference' => '#JG11085',
      'status' => 'cancelled',
      'client' => $client('RJ Valmadrid', 'rj@example.com'),
      'event' => $event('Sample Event', 'Others', -5, -5, '3:00 PM', '8:00 PM', null, 'Indoor'),
      'payment' => ['label' => 'Full Payment', 'status' => 'Refund pending', 'state' => 'pending'],
      'package' => ['id' => 'budget-lite', 'name' => 'Budget Lite', 'price' => 8000]
    ],
    [
      'id' => 8,
      'reference' => '#JG67677',
      'status' => 'declined',
      'client' => $client('Paler Perez', 'paler@example.com'),
      'event' => $event('Sample Concert', 'Concert', -9, -9, '7:00 PM', '12:00 AM', 300, 'Outdoor'),
      'payment' => [],
      'package' => ['id' => 'budget-lite', 'name' => 'Budget Lite', 'price' => 8000]
    ],

    // NOT history: still needs a decision or has no verified payment. The filter below drops these.
    [
      'id' => 9,
      'reference' => '#JG90001',
      'status' => 'pending',
      'client' => $client('Not In History A', 'a@example.com'),
      'event' => $event('Pending Request', 'Birthday Party', 14, 14, '6:00 PM', '11:00 PM'),
      'payment' => $unpaid,
      'package' => ['id' => 'budget-party', 'name' => 'Budget Party', 'price' => 10000]
    ],
    [
      'id' => 10,
      'reference' => '#JG90002',
      'status' => 'pending_payment',
      'client' => $client('Not In History B', 'b@example.com'),
      'event' => $event('Payment To Verify', 'Wedding', 20, 20, '2:00 PM', '9:00 PM'),
      'payment' => $unpaid,
      'package' => ['id' => 'luxe-lite', 'name' => 'Luxe Lite', 'price' => 25000]
    ],
    [
      'id' => 11,
      'reference' => '#JG90003',
      'status' => 'approved',
      'client' => $client('Not In History C', 'c@example.com'),
      'event' => $event('Approved, Not Paid Yet', 'Concert', 25, 25, '5:00 PM', '11:00 PM'),
      'payment' => $unpaid,
      'package' => ['id' => 'modern-glam', 'name' => 'Modern Glam', 'price' => 35000]
    ],
  ];

  // The history rule (config/admin/history.php): cancelled / declined always; approved / completed
  // once a payment is verified.
  $isHistory = fn(array $booking) => in_array($booking['status'], config('admin.history.terminal_statuses'), true)
    || (in_array($booking['status'], config('admin.history.history_statuses'), true)
      && in_array($booking['payment']['state'] ?? null, config('admin.history.paid_states'), true));

  return view('admin.booking-history', [
    // Admin-managed, so the dropdown is built from this list, not hard-coded.
    'packages' => [
      ['id' => 'budget-lite',    'name' => 'Budget Lite'],
      ['id' => 'budget-party',   'name' => 'Budget Party'],
      ['id' => 'budget-wedding', 'name' => 'Budget Wedding'],
      ['id' => 'luxe-lite',      'name' => 'Luxe Lite'],
      ['id' => 'modern-glam',    'name' => 'Modern Glam'],
      ['id' => 'elite-symphony', 'name' => 'Elite Symphony'],
    ],
    'bookings' => array_values(array_filter($all, $isHistory)),
  ]);
})->name('admin.booking-history');
>>>>>>> origin
