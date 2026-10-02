<?php

use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\ModalAuthController;
use App\Http\Controllers\Auth\ModalPasswordController;
use App\Http\Controllers\Auth\ModalVerificationController;
use Illuminate\Support\Facades\Route;

//USED FOR FRONT-END TESTING PURPOSES ONLY. REMOVE THIS ROUTE IN PRODUCTION
use Illuminate\Http\Request;

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


// Temporary front-end stubs. Delete once the real controllers replace them.
Route::post('/booking/client-information', function (Request $r) {
    if ($r->input('email') === 'taken@example.com') {
        return response()->json([
            'message' => 'That email is already associated with a booking.',
            'errors'  => ['email' => ['That email is already associated with a booking.']],
        ], 422);
    }

    return response()->json(['ok' => true]);
})->name('booking.client-information');

Route::post('/booking/event-information', function (Request $r) {
    if ($r->input('event_type') === 'Others' && ! trim((string) $r->input('event_type_other'))) {
        return response()->json([
            'message' => 'Please specify your event type.',
            'errors'  => ['event_type_other' => ['Please specify your event type.']],
        ], 422);
    }

    return response()->json(['ok' => true]);
})->name('booking.event-information');

Route::post('/booking/event-schedule', function (Request $r) {
    if ($r->input('event_start_date') && ! $r->input('event_end_date')) {
        return response()->json([
            'message' => 'Select an end date on the calendar.',
            'errors'  => ['event_end_date' => ['Select an end date on the calendar.']],
        ], 422);
    }

    return response()->json(['ok' => true]);
})->name('booking.event-schedule');

Route::post('/booking/booking-summary', function (Request $r) {
    if (! $r->input('payment_option')) {
        return response()->json([
            'message' => 'Please select a payment option.',
            'errors'  => ['payment_option' => ['Please select a payment option.']],
        ], 422);
    }

    return response()->json(['ok' => true]);
})->name('booking.booking-summary');
