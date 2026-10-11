<?php

use App\Http\Controllers\Account\AccountBookingController;
use App\Http\Controllers\Account\AccountDeletionController;
use App\Http\Controllers\Account\AccountProfileController;
use App\Http\Controllers\Account\AccountSecurityController;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

// User account section (opened from the user icon in the landing navigation). Required from
// routes/user.php, which web.php requires.
//
// Every route here is behind the `auth` middleware: a guest gets 401 JSON on a JSON request and is sent
// to the landing page on a page request (see AppServiceProvider).
//
// BACKEND CONNECTED: Profile, My Bookings, Security and Delete Account use real controllers in
// app/Http/Controllers/Account/. They return JSON for every action (and for the page routes when the
// request sends `Accept: application/json`); a browser still gets the Blade page. Log out is
// `POST /logout` in routes/user/auth.php. Nothing is ever deleted from the database (see
// AccountDeletionController). Messages is still a front-end stub.
// Route NAMES (user.account, user.account.profile, user.account.profile.update, user.account.bookings,
// user.account.bookings.cancel, user.account.messages, user.account.security, user.account.password.update,
// user.account.destroy) must not change: the navigation, the sidebar (config/account.php) and the JS
// build from them.

Route::middleware('auth')->group(function () {

// The navigation links here; it lands on the first tab.
Route::get('/user/account', fn () => redirect()->route('user.account.profile'))
    ->name('user.account');

Route::get('/user/account/profile', [AccountProfileController::class, 'show'])
    ->name('user.account.profile');

// 200 {ok, message, profile{...}} on success; 422 {message, errors{field: [msg]}} on rejection.
Route::patch('/user/account/profile', [AccountProfileController::class, 'update'])
    ->name('user.account.profile.update');

// My Bookings: only the logged-in user's own bookings (bookings.user_id).
Route::get('/user/account/bookings', [AccountBookingController::class, 'index'])
    ->name('user.account.bookings');

// Cancel one of the user's own bookings. 200 {ok, message, booking{id, status: 'user_cancelled',
// can_reschedule}}; 403 / 409 {message} when it is not theirs, not cancellable or already started.
Route::post('/user/account/bookings/{booking}/cancel', [AccountBookingController::class, 'cancel'])
    ->whereNumber('booking')
    ->name('user.account.bookings.cancel');

// Messages. FRONT-END STUB: a sample thread (dated relative to now), nothing read or saved. The page
// reuses the shared chat components and posts to the existing `user.messages.send` route, so there
// is no new send route. The real controller passes the same `$conversation` as the landing widget
// (docs/backend-impact.md, section 28).
Route::get('/user/account/messages', function () {
    $m = fn (string $from, string $body, $at, array $files = []) => ['type' => 'message', 'from' => $from, 'body' => $body, 'at' => $at, 'attachments' => $files];

    return view('user.account.messages', ['conversation' => [
        'unread' => 1,
        'items' => [
            ['type' => 'booking', 'at' => Carbon::now()->subDay(), 'booking' => [
                'reference' => '#JG12345',
                'status' => 'approved',
                'package' => ['id' => 'modern-glam', 'name' => 'Modern Glam', 'price' => 35000],
                'event' => [
                    'name' => 'Arjay & Co. Debut',
                    'type' => 'Debut',
                    'location' => 'Marikina Sports Center',
                    'start_date' => Carbon::now()->addDays(20),
                    'end_date' => Carbon::now()->addDays(20),
                    'start_time' => '5:00 PM',
                    'end_time' => '12:00 AM',
                ],
            ]],
            $m('admin', 'Your booking has been approved and confirmed.', Carbon::now()->subHours(5)),
            $m('client', 'Thank you! Here is my GCash receipt.', Carbon::now()->subHours(4), [['name' => 'gcash-receipt.pdf', 'url' => '#', 'kind' => 'file', 'size' => '240 KB']]),
            $m('admin', 'Thank you! Your payment has been received.', Carbon::now()->subHours(3)),
            $m('client', 'Can we change the guest count to 120?', Carbon::now()->subHour()),
            $m('admin', 'No problem. The event guest count has been updated to 120.', Carbon::now()->subMinutes(20)),
        ],
    ]]);
})->name('user.account.messages');

// Security: the page, and the change-password action.
// PATCH JSON {current_password, password, password_confirmation}; 200 {ok, message};
// 422 {message, errors{field: [msg]}}; 429 {message} after too many wrong current passwords.
Route::get('/user/account/security', [AccountSecurityController::class, 'show'])
    ->name('user.account.security');

Route::patch('/user/account/password', [AccountSecurityController::class, 'updatePassword'])
    ->name('user.account.password.update');

// Delete Account (the sidebar modal). A SOFT delete: the user can no longer log in, no row is removed.
// 200 {ok, message, redirect}; 409 {message} while the account has active bookings.
Route::delete('/user/account', [AccountDeletionController::class, 'destroy'])
    ->name('user.account.destroy');

});
