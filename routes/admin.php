<?php

use Illuminate\Support\Facades\Route;

// Admin routes. One file per admin section lives in routes/admin/ and is required here. web.php
// requires THIS file, so the chain is:
//
//   web.php  ->  admin.php  ->  admin/<section>.php
//
// Route NAMES (admin.dashboard, admin.bookings, ...) must not change: config/admin.php (page meta
// and the sidebar's active state) and the views build their URLs from them.

// The sign-in card (and logout) stay OUTSIDE the gate, otherwise a guest could never reach them.
require __DIR__ . '/admin/login.php';

// Everything else is an admin-only area: a signed-in account whose role is not `admin` is bounced
// (EnsureRole). `auth:web,admin` answers guests first: a page request is sent to admin.login, a
// JSON request gets 401. Both sessions are accepted here, so an admin who is signed in on the
// panel (its own session) and an admin who is signed in on the customer session (the tests sign
// admins in that way) can both open the panel.
Route::middleware(['auth:web,admin', 'role:admin'])->group(function () {
    require __DIR__ . '/admin/dashboard.php';
    require __DIR__ . '/admin/bookings.php';
    require __DIR__ . '/admin/messages.php';
    require __DIR__ . '/admin/booking-history.php';
    require __DIR__ . '/admin/packages.php';
    require __DIR__ . '/admin/calendar.php';
    require __DIR__ . '/admin/account.php';
});
