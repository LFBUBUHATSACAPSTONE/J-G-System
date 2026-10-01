<?php

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\ModalAuthController;
use App\Http\Controllers\Auth\ModalPasswordController;
use App\Http\Controllers\Auth\ModalVerificationController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/user/landing', function () {
    return view('user.landing');
})->name('user.landing');

Route::get('/user/booking', function () {
    return view('user.booking');
})->name('user.booking');


Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::post('/login', [ModalAuthController::class, 'login'])->name('login');
Route::post('/register', [RegisterController::class, 'register'])->name('register');
Route::post('/password/email', [ModalPasswordController::class, 'forgotPassword'])->name('password.email');
Route::post('/verification/confirm', [ModalVerificationController::class, 'verificationConfirm'])->name('verification.confirm');
Route::post('/verification/resend', [ModalVerificationController::class, 'verificationResend'])->name('verification.resend');
Route::post('/password/update', [ModalPasswordController::class, 'passwordUpdate'])->name('password.update');

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
