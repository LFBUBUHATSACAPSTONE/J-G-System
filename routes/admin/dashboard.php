<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

// Routes for the admin dashboard. Required from routes/admin.php, which web.php requires.
// Everything here is a FRONT-END STUB (placeholder data, nothing is saved). When the backend is
// built, replace the closure with a controller call and keep this file as the page's routes.
// The route NAME must stay `admin.dashboard`: config/admin.php (page meta) and the sidebar
// active state both key off it.
//
// The placeholder people, references, ids and dates match the Bookings stub
// (routes/admin/bookings.php), so every link on this page lands on a real row there.

Route::get('/admin/dashboard', function () {
  return view('admin.dashboard', [
    // `value` = the count. `change` = the difference vs last month as a whole number
    // (+2, 0, -1). Leave `change` out to hide the badge. Keys = config/admin/dashboard.php 'cards'.
    'stats' => [
      'total'     => ['value' => 5, 'change' => 2],
      'confirmed' => ['value' => 1, 'change' => 1],
      'pending'   => ['value' => 1, 'change' => 0],
      'payment'   => ['value' => 1, 'change' => 1],
      'cancelled' => ['value' => 2, 'change' => 1],
    ],

    // Bookings that need an admin action (status pending or pending_payment), OLDEST first
    // so the longest wait is on top. `submitted_at` is when the client sent the request.
    'attention' => [
      [
        'id' => 1,
        'reference' => '#JG12345',
        'status' => 'pending',
        'client' => 'Arjay Dela Cruz',
        'package' => 'Budget Party',
        'event' => 'Sample Birthday',
        'date' => Carbon::parse('2025-12-30'),
        'submitted_at' => now()->subDays(3),
      ],
      [
        'id' => 3,
        'reference' => '#JG63497',
        'status' => 'pending_payment',
        'client' => 'Lhester Pile',
        'package' => 'Luxe Lite',
        'event' => 'Pile Family Reunion',
        'date' => Carbon::parse('2025-12-31'),
        'submitted_at' => now()->subHours(5),
      ],
    ],

    // Approved bookings with a verified payment, SOONEST first.
    'upcomingEvents' => [
      [
        'id' => 2,
        'date' => Carbon::parse('2025-12-22'),
        'event' => "Aye's Concert",
        'client' => 'Ayessa Dumay',
        'package' => 'Modern Glam',
        'time' => '5:00 PM - 12:00 AM',
      ],
    ],

    // Booking COUNT per package (the view works out the percentages). `id` = package id, the
    // same one the Bookings Package filter uses; it makes the legend row a link.
    'packageRate' => [
      ['id' => 'budget-lite',    'label' => 'Budget Lite',    'count' => 18],
      ['id' => 'luxe-lite',      'label' => 'Luxe Lite',      'count' => 25],
      ['id' => 'budget-party',   'label' => 'Budget Party',   'count' => 25],
      ['id' => 'modern-glam',    'label' => 'Modern Glam',    'count' => 10],
      ['id' => 'budget-wedding', 'label' => 'Budget Wedding', 'count' => 17],
      ['id' => 'elite-symphony', 'label' => 'Elite Symphony', 'count' => 5],
    ],
  ]);
})->name('admin.dashboard');
