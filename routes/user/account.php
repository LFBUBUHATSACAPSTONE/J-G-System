<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// User account section (opened from the user icon in the landing navigation). Required from
// routes/user.php, which web.php requires.
//
// FRONT-END STUBS ONLY: no middleware, no database, no validation on the server. The pages render
// from the logged-in user when there is one and from sample values when there is not, and the
// profile PATCH just answers 200. The JS does the validation (resources/js/account/profile.js).
// Route NAMES (user.account, user.account.profile, user.account.profile.update) must not change:
// the navigation, the sidebar (config/account.php) and the JS build from them.
// When the backend is connected: add the `auth` middleware to these routes and replace the stubs.

// The navigation links here; it lands on the first tab.
Route::get('/user/account', fn() => redirect()->route('user.account.profile'))
  ->name('user.account');

Route::get('/user/account/profile', fn() => view('user.account.profile', ['user' => request()->user()]))
  ->name('user.account.profile');

// Contract the real controller must keep: 200 {ok, message, profile{...}} on success;
// 422 {message, errors{field: [msg]}} on rejection. Stub: always succeeds, saves nothing.
Route::patch('/user/account/profile', fn(Request $request) => response()->json([
  'ok' => true,
  'message' => 'Your profile has been updated.',
  'profile' => $request->only(['first_name', 'last_name', 'phone', 'address']),
]))->name('user.account.profile.update');
