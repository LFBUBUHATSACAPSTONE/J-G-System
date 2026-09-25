<?php

use App\Http\Controllers\Auth\RegisterController;
use Illuminate\Support\Facades\Route;

// Front-End Verification Testing
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Crypt;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/user/landing', function () {
    return view('user.landing');
})->name('user.landing');

// Remove the argument once back-end is connected (this is use for the active stubs)
Auth::routes(['reset' => false]);

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::post('/login', [App\Http\Controllers\Auth\ModalAuthController::class, 'login'])->name('login');
Route::post('/register', [RegisterController::class, 'register'])->name('register');
Route::post('/password/email', [App\Http\Controllers\Auth\ModalAuthController::class, 'forgotPassword'])->name('password.email');
Route::post('/verification/confirm', [App\Http\Controllers\Auth\ModalAuthController::class, 'verificationConfirm'])->name('verification.confirm');
Route::post('/verification/resend', [App\Http\Controllers\Auth\ModalAuthController::class, 'verificationResend'])->name('verification.resend');
Route::post('/password/update', [App\Http\Controllers\Auth\ModalAuthController::class, 'passwordUpdate'])->name('password.update');