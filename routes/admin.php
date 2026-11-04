<?php

use Illuminate\Support\Facades\Route;

// Admin routes. One file per admin section lives in routes/admin/ and is required here. web.php
// requires THIS file, so the chain is:
//
//   web.php  ->  admin.php  ->  admin/<section>.php
//
// Route NAMES (admin.dashboard, admin.bookings, ...) must not change: config/admin.php (page meta
// and the sidebar's active state) and the views build their URLs from them.

// Signed-in regular users are blocked from every admin URL; guests can still reach the
// sign-in and password-recovery routes. Protected panel features also require an admin role.
Route::middleware('admin.area')->group(function () {
    require __DIR__ . '/admin/login.php';

    // `auth:web,admin` answers guests first. Both guard sessions are accepted for explicit admin
    // accounts, and EnsureRole rejects every other role before any panel feature runs.
    Route::middleware(['auth:web,admin', 'role:admin'])->group(function () {
        require __DIR__ . '/admin/dashboard.php';
        require __DIR__ . '/admin/bookings.php';
        require __DIR__ . '/admin/messages.php';
        require __DIR__ . '/admin/booking-history.php';
        require __DIR__ . '/admin/packages.php';
        require __DIR__ . '/admin/calendar.php';
        require __DIR__ . '/admin/account.php';
    });
});
