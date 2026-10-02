<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

// Admin routes. Names must match the `route` keys in config/admin.php, because the sidebar builds its links and its active state from them.
// TODO: add auth + admin-role middleware once the backend lands, e.g.
Route::prefix('admin')->name('admin.')->group(function () {
  Route::view('/dashboard', 'admin.dashboard')->name('dashboard');
  Route::view('/bookings', 'admin.bookings')->name('bookings');
  Route::view('/messages', 'admin.messages')->name('messages');
  Route::view('/booking-history', 'admin.booking-history')->name('booking-history');
  Route::view('/packages', 'admin.packages')->name('packages');
  Route::view('/calendar', 'admin.calendar')->name('calendar');
  Route::view('/account', 'admin.account')->name('account');
});

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
      ['label' => 'Budget Lite', 'count' => 18],
      ['label' => 'Luxe Lite', 'count' => 25],
      ['label' => 'Budget Party', 'count' => 25],
      ['label' => 'Modern Glam', 'count' => 10],
      ['label' => 'Budget Wedding', 'count' => 17],
      ['label' => 'Elite Symphony', 'count' => 5],
    ],
  ]);
})->name('admin.dashboard');
