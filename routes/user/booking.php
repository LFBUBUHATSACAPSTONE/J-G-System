<?php

use App\Http\Controllers\BookingController;
use Illuminate\Support\Facades\Route;

Route::get('/user/booking', [BookingController::class, 'show'])->name('user.booking');

Route::post('/booking/client-information', [BookingController::class, 'saveClientInformation'])
    ->name('booking.client-information');

Route::post('/booking/event-information', [BookingController::class, 'saveEventInformation'])
    ->name('booking.event-information');

Route::get('/booking/availability', [BookingController::class, 'availability'])
    ->name('booking.availability');

Route::post('/booking/event-schedule', [BookingController::class, 'saveEventSchedule'])
    ->name('booking.event-schedule');

Route::post('/booking/booking-summary', [BookingController::class, 'submitBooking'])
    ->name('booking.booking-summary');
