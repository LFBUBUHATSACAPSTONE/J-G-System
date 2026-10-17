<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Admin sign-in gate: the page an admin sees first at /admin. Required from routes/admin.php.
// FRONT END ONLY today. Route NAMES (admin.login, admin.login.submit) must not change: the view
// and resources/js/admin-login.js build from them. See the contract in resources/js/admin-login.js.

Route::view('/admin', 'admin.login')->name('admin.login');

// USED FOR FRONT-END TESTING PURPOSES ONLY. Signs nobody in: it always answers 422 so the error
// state can be seen. Replace the closure with the real controller, keeping the name and the JSON shape:
//   200 {redirect?}  |  422 {message, errors}  |  403 {message}  |  429 {message}
Route::post('/admin/login', fn() => response()->json([
  'message' => "We couldn't sign you in — check that your email and password are correct.",
], 422))->name('admin.login.submit');

// ---- Forgot password flow (same four steps as the user auth modal) ----------------------------
// USED FOR FRONT-END TESTING PURPOSES ONLY. These closures fake the answers so every state of the
// sign-in card can be seen. Replace each closure with the real controller, keeping the route name
// and the JSON shape written beside it (the contracts are also at the top of
// resources/js/admin-login/*.js). Admin accounts are created by the owner, so there is no sign-up.
//
// Stub behaviour: any valid email gets a code; the code that passes is 123456; any password that
// passes the front-end rules is accepted.

// 1. Send the code.  POST JSON {email}
//    200 {verification_token, destination?}  |  422 {message}  |  429 {message}
Route::post('/admin/password/email', function (Request $request) {
  $email = (string) $request->input('email');
  [$local, $domain] = array_pad(explode('@', $email, 2), 2, '');

  return response()->json([
    'verification_token' => 'stub-admin-token',
    'destination' => ($local[0] ?? '') . '***@' . $domain,
  ]);
})->name('admin.password.email');

// 2. Check the code.  POST JSON {code, context: "reset", verification_token}
//    200 {token, email}  |  422 {message}  |  429 {message}
Route::post('/admin/verification/confirm', function (Request $request) {
  $code = is_array($request->input('code')) ? implode('', $request->input('code')) : (string) $request->input('code');

  if ($code !== '123456') {
    return response()->json(['message' => 'Invalid verification code. Please try again.'], 422);
  }

  return response()->json(['token' => 'stub-reset-token', 'email' => 'admin@example.com']);
})->name('admin.verification.confirm');

// 3. Send a new code.  POST JSON {context: "reset", verification_token}
//    200 {destination?, verification_token?}  |  422 {message}  |  429 {message}
Route::post('/admin/verification/resend', fn() => response()->json([
  'verification_token' => 'stub-admin-token',
]))->name('admin.verification.resend');

// 4. Save the new password.  POST JSON {token, email, password, password_confirmation}
//    200 {message?}  |  422 {message, errors{password}}  |  429 {message}
Route::post('/admin/password/update', fn() => response()->json([
  'message' => 'Your password has been updated.',
]))->name('admin.password.update');
