<?php

use Illuminate\Support\Facades\Route;

// Admin sign-in gate: the page an admin sees first at /admin. Required from routes/admin.php.
// FRONT END ONLY today. Route NAMES (admin.login, admin.login.submit) must not change: the view
// and resources/js/admin-login.js build from them. See the contract in resources/js/admin-login.js.

Route::view('/admin', 'admin.login')->name('admin.login');

// USED FOR FRONT-END TESTING PURPOSES ONLY. Signs nobody in: it always answers 422 so the error
// state can be seen. Replace the closure with the real controller, keeping the name and the JSON shape:
//   200 {redirect?}  |  422 {message, errors}  |  403 {message}  |  429 {message}
Route::post('/admin/login', fn() => response()->json([
  'message' => "We couldn't sign you in. Check that your email and password are correct.",
], 422))->name('admin.login.submit');
