<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LogoutController;
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

// The app signs in and signs up through the landing page's modal, so it never renders
// Laravel UI's server-side auth pages. Their views (auth.login, auth.register,
// auth.passwords.confirm) do not exist, so a direct hit to /login, /register or
// /password/confirm threw a 500. Drop the unused confirm routes, and point the old form
// URLs at the page that hosts the modal instead.
Auth::routes(['reset' => false, 'confirm' => false]);

Route::get('/login', fn () => redirect()->route('user.landing'));
Route::get('/register', fn () => redirect()->route('user.landing'));

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::post('/login', [ModalAuthController::class, 'login'])->name('login');

// Replaces the default Laravel UI logout (a redirect) with a JSON one. Same URI, method and name.
Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');
Route::post('/register', [RegisterController::class, 'register'])->name('register');
Route::post('/password/email', [ModalPasswordController::class, 'forgotPassword'])->name('password.email');
Route::post('/verification/confirm', [ModalVerificationController::class, 'verificationConfirm'])->name('verification.confirm');
Route::post('/verification/resend', [ModalVerificationController::class, 'verificationResend'])->name('verification.resend');
Route::post('/password/update', [ModalPasswordController::class, 'passwordUpdate'])->name('password.update');

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
