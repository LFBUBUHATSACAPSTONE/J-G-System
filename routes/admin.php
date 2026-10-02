<?php

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
