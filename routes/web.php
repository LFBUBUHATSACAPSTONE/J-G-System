<?php

use Illuminate\Support\Facades\Route;

// Front-End Verification Testing
use Illuminate\Http\Request;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/user/landing', function () {
    return view('user.landing');
})->name('user.landing');

// Remove the argument once back-end is connected
Auth::routes(['reset' => false]);

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::post('/login', fn() => back())->name('login');
Route::post('/register', fn() => back())->name('register');
// Route::post('/password/email', fn() => back())->name('password.email');
// Route::post('/verification/confirm', fn() => back())->name('verification.confirm');
// Route::post('/password/update', fn() => back())->name('password.update');

// Front-End Testing: Removed if backend already exists, let real controllers handles
Route::post('/password/email', fn() => response()->json(['ok' => true]))->name('password.email');
Route::post(
    '/verification/confirm',
    fn(Request $r) =>
    implode('', (array) $r->input('code')) === '123456'
        ? response()->json(['token' => 'test-token', 'email' => 'test@example.com'])
        : response()->json(['message' => 'Invalid verification code.'], 422)
)->name('verification.confirm');
Route::post('/password/update', fn() => response()->json(['ok' => true]))->name('password.update');
