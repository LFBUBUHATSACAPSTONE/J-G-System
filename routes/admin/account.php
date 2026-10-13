<?php

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

Route::get('/admin/account', function () {
  $now = Carbon::now();

  return view('admin.account', [
    'admin' => request()->user(), // null while there is no admin login: the view falls back to sample values
    'lastLogin' => $now->copy()->subHours(3),
    'sessions' => [
      ['device' => 'Chrome on Windows', 'icon' => 'desktop', 'location' => 'Sample City, Bulacan', 'last_active' => $now, 'current' => true],
      ['device' => 'Safari on iPhone', 'icon' => 'device-mobile', 'location' => 'Sample City, Bulacan', 'last_active' => $now->copy()->subDay(), 'current' => false],
    ],
  ]);
})->name('admin.account');

Route::patch('/admin/account', function () {
  // Stub: echoes what was sent so the page can refresh its values. Nothing is saved.
  return response()->json([
    'ok' => true,
    'message' => 'Preview only: nothing was saved yet.',
    'admin' => request()->only(['first_name', 'last_name', 'middle_initial', 'email', 'phone']),
  ]);
})->name('admin.account.update');