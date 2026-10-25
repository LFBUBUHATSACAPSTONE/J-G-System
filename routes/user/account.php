<?php

use App\Http\Controllers\Account\AccountBookingController;
use App\Http\Controllers\Account\AccountDeletionController;
use App\Http\Controllers\Account\AccountProfileController;
use App\Http\Controllers\Account\AccountSecurityController;
use App\Http\Controllers\User\MessageController;
use App\Services\Auth\CurrentAccount;
use Illuminate\Support\Facades\Route;

// User account section (opened from the user icon in the landing navigation). Required from
// routes/user.php, which web.php requires.
//
// Every route here is behind the `auth:web` middleware and the `role:user` gate: a guest gets 401
// JSON on a JSON request and is sent to the landing page on a page request (see AppServiceProvider),
// and an admin account is bounced to its own dashboard. The customer area is for the `user` role
// only.
//
// BACKEND CONNECTED: Profile, My Bookings, Security and Delete Account use real controllers in
// app/Http/Controllers/Account/. They return JSON for every action (and for the page routes when the
// request sends `Accept: application/json`); a browser still gets the Blade page. Log out is
// `POST /logout` in routes/user/auth.php. Nothing is ever deleted from the database (see
// AccountDeletionController). Messages is connected too (App\Http\Controllers\User\MessageController).
// Route NAMES (user.account, user.account.profile, user.account.profile.update, user.account.bookings,
// user.account.bookings.cancel, user.account.messages, user.account.security, user.account.password.update,
// user.account.destroy) must not change: the navigation, the sidebar (config/account.php) and the JS
// build from them.

// The navigation links here; it lands on the first tab of the account that is signed in: the
// customer's profile tab, or the admin's own account page. It sits OUTSIDE the `role:user` gate,
// so an admin who opens it lands on their own profile instead of being bounced.
Route::middleware(['auth:web'])->group(function () {
    Route::get('/user/account', fn (CurrentAccount $account) => redirect()->route($account->profileRoute()))
        ->name('user.account');
});

Route::middleware(['auth:web', 'role:user'])->group(function () {

Route::get('/user/account/profile', [AccountProfileController::class, 'show'])
    ->name('user.account.profile');

// 200 {ok, message, profile{...}} on success; 422 {message, errors{field: [msg]}} on rejection.
Route::patch('/user/account/profile', [AccountProfileController::class, 'update'])
    ->name('user.account.profile.update');

// My Bookings: only the logged-in user's own bookings (bookings.user_id).
Route::get('/user/account/bookings', [AccountBookingController::class, 'index'])
    ->name('user.account.bookings');

// Ask J&G to cancel one of the user's own bookings. This is a REQUEST, not an instant cancel:
// the booking moves to pending_cancellation and waits for an admin decision.
// 200 {ok, message, booking{id, status, cancellation}}; 403 / 409 {message} when it is not theirs,
// not cancellable, already pending or already started; 422 when the required reason is invalid.
Route::post('/user/account/bookings/{booking}/cancel', [AccountBookingController::class, 'requestCancellation'])
    ->whereNumber('booking')
    ->name('user.account.bookings.cancel');

// Messages. The real controller passes the same $conversation as the landing widget.
Route::get('/user/account/messages', [MessageController::class, 'index'])->name('user.account.messages');

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
