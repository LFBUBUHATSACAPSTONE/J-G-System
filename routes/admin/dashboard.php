<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

// Routes for the admin dashboard. Required from routes/admin.php, which web.php requires.
// Everything here is a FRONT-END STUB (placeholder data, nothing is saved). When the backend is
// built, replace each closure with a controller call and keep this file as the page's routes.
// The route NAME must stay `admin.dashboard`: config/admin.php (page meta) and
// the sidebar active state both key off it.
//
// All numbers below are placeholders 
//The real values come from the user-side bookings data; see

Route::get('/admin/dashboard', function () {
  return view('admin.dashboard', [
    // `delta` and `direction` are optional. Leave `delta` out to hide the badge.
    'stats' => [
      'total'     => ['value' => 4, 'delta' => 99.8, 'direction' => 'up'],
      'confirmed' => ['value' => 1, 'delta' => 99.8, 'direction' => 'up'],
      'pending'   => ['value' => 1, 'delta' => 99.8, 'direction' => 'up'],
      'cancelled' => ['value' => 2],
    ],

    'upcomingEvents' => [
      ['date' => Carbon::parse('2025-12-22'), 'package' => 'Modern Glam'],
      ['date' => Carbon::parse('2025-12-31'), 'package' => 'Luxe Lite'],
    ],

    'pendingApprovals' => [
      ['reference' => '#JG12345', 'date' => Carbon::parse('2025-12-30'), 'package' => 'Budget Party'],
    ],

    // Booking COUNT per package (the view works out the percentages).
    'packageRate' => [
      ['label' => 'Budget Lite',    'count' => 18],
      ['label' => 'Luxe Lite',      'count' => 25],
      ['label' => 'Budget Party',   'count' => 25],
      ['label' => 'Modern Glam',    'count' => 10],
      ['label' => 'Budget Wedding', 'count' => 17],
      ['label' => 'Elite Symphony', 'count' => 5],
    ],
  ]);
})->name('admin.dashboard');
