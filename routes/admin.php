<?php

// Admin routes. One file per admin section lives in routes/admin/ (stub data today, the real
// backend routes later) and is required here. web.php requires THIS file, so the chain is:
//
//   web.php  ->  admin.php  ->  admin/<section>.php
//
// Route NAMES (admin.dashboard, admin.bookings, ...) must not change: config/admin.php (page meta
// and the sidebar's active state) and the views build their URLs from them.

// Order follows the sidebar. Login (the sign-in gate) comes first and stays OUTSIDE any future
// auth middleware group, otherwise a guest could never reach it.
require __DIR__ . '/admin/login.php';
require __DIR__ . '/admin/dashboard.php';
require __DIR__ . '/admin/bookings.php';
require __DIR__ . '/admin/messages.php';
require __DIR__ . '/admin/booking-history.php';
require __DIR__ . '/admin/packages.php';
require __DIR__ . '/admin/calendar.php';
require __DIR__ . '/admin/account.php';

// When admin login exists, protect every section at once by wrapping the requires above:
//
//   Route::middleware(['auth', 'admin'])->group(function () {
//       require __DIR__.'/admin/dashboard.php';
//       ...
//   });