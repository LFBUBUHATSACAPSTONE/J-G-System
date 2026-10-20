<?php

use App\Http\Controllers\Admin\AccountController;
use Illuminate\Support\Facades\Route;

// Account page (App\Http\Controllers\Admin\AccountController). Required from routes/admin.php.
// BACKEND CONNECTED: the PATCH stub that echoed its input is gone; the controller saves to the
// signed-in user's row (nothing is ever deleted) and answers JSON. The GET still renders the
// page, and answers JSON when the request asks for it. Route NAMES (admin.account,
// admin.account.update) must not change: the sidebar (config/admin.php) and the view build
// from them.
//
// JSON shapes are documented in the controller and in resources/js/admin/account.js:
//   PATCH  200 {ok, message, admin{...}}  |  422 {message, errors}  |  429 {message}  |  401 {message}

Route::get('/admin/account', [AccountController::class, 'show'])
  ->name('admin.account');

Route::patch('/admin/account', [AccountController::class, 'update'])
  ->name('admin.account.update');
