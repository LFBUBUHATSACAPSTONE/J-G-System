<?php

use App\Http\Controllers\Auth\User\GoogleAuthController;
use App\Http\Controllers\Auth\User\LogoutController;
use App\Http\Controllers\Auth\User\ModalAuthController;
use App\Http\Controllers\Auth\User\ModalPasswordController;
use App\Http\Controllers\Auth\User\ModalVerificationController;
use App\Http\Controllers\Auth\User\RegisterController;
use App\Http\Controllers\User\HomeController;
use Illuminate\Support\Facades\Route;

// Routes for sign in, sign up, password reset, verification and Google login. Required from
// routes/user.php, which web.php requires.
//
// The app signs in and signs up through the landing page's modal, so it never renders
// Laravel UI's server-side auth pages. Their views (auth.login, auth.register,
// auth.passwords.confirm) do not exist, so a direct hit to /login, /register or
// /password/confirm used to throw a 500. The routes below replace them: the old form URLs
// point at the page that hosts the modal, and the POST routes answer JSON.
//
// Route NAMES (login, logout, register, password.email, password.update, verification.confirm,
// verification.resend) must not change: the Blade forms and the front-end JS build from them.

Route::get('/login', fn () => redirect()->route('user.landing'));
Route::get('/register', fn () => redirect()->route('user.landing'));

Route::get('/home', [HomeController::class, 'index'])->name('home');

Route::post('/login', [ModalAuthController::class, 'login'])->name('login');

// Logs the customer session out (JSON). Same URI, method and name as before.
Route::post('/logout', [LogoutController::class, 'logout'])->name('logout');
Route::post('/register', [RegisterController::class, 'register'])->name('register');
Route::post('/password/email', [ModalPasswordController::class, 'forgotPassword'])->name('password.email');
Route::post('/verification/confirm', [ModalVerificationController::class, 'verificationConfirm'])->name('verification.confirm');
Route::post('/verification/resend', [ModalVerificationController::class, 'verificationResend'])->name('verification.resend');
Route::post('/password/update', [ModalPasswordController::class, 'passwordUpdate'])->name('password.update');

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
