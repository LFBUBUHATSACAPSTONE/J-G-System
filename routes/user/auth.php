<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\ModalAuthController;
use App\Http\Controllers\Auth\ModalPasswordController;
use App\Http\Controllers\Auth\ModalVerificationController;
use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;

// Routes for sign in, sign up, password reset, verification and Google login. Required from
// routes/user.php, which web.php requires.
//
// ORDER MATTERS: Auth::routes() registers the default /login, /register and /password/* routes
// first. The modal routes below then replace the POST ones with the same method and URI, so they
// must stay AFTER it.

// REMOVES BROKEN ROUTES
Auth::routes(['reset' => false]);

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::get('/logout', function (\Illuminate\Http\Request $request) {
    Auth::logout();
    $request->session()->invalidate();
    $request->session()->regenerateToken();

    return redirect()->route('user.landing');
})->name('logout.get');

Route::get('/login', function () {
    return redirect()->route('user.landing');
});

Route::get('/register', function () {
    return redirect()->route('user.landing');
});

Route::post('/login', [ModalAuthController::class, 'login'])->name('login');
Route::post('/register', [RegisterController::class, 'register'])->name('register');
Route::post('/password/email', [ModalPasswordController::class, 'forgotPassword'])->name('password.email');
Route::post('/verification/confirm', [ModalVerificationController::class, 'verificationConfirm'])->name('verification.confirm');
Route::post('/verification/resend', [ModalVerificationController::class, 'verificationResend'])->name('verification.resend');
Route::post('/password/update', [ModalPasswordController::class, 'passwordUpdate'])->name('password.update');

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
