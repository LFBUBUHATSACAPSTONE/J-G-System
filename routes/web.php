<?php

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\ModalAuthController;
use Illuminate\Support\Facades\Route;

// Front-End Verification Testing
use Illuminate\Support\Facades\Auth;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/user/landing', function () {
    return view('user.landing');
})->name('user.landing');

// Remove the argument once back-end is connected (this is use for the active stubs)
Auth::routes(['reset' => false]);

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::post('/login', [ModalAuthController::class, 'login'])->name('login');
Route::post('/register', [RegisterController::class, 'register'])->name('register');
Route::post('/password/email', [ModalAuthController::class, 'forgotPassword'])->name('password.email');
Route::post('/verification/confirm', [ModalAuthController::class, 'verificationConfirm'])->name('verification.confirm');
Route::post('/verification/resend', [ModalAuthController::class, 'verificationResend'])->name('verification.resend');
Route::post('/password/update', [ModalAuthController::class, 'passwordUpdate'])->name('password.update');

Route::get('/auth/google/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');