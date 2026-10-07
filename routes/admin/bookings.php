<?php

use App\Http\Controllers\Admin\BookingController;
use App\Models\Booking;
use Illuminate\Support\Facades\Route;

// The dashboard remains server-rendered for its initial page load. Mutations and the
// bookings data endpoint are handled by BookingController and always return JSON.
Route::get('/admin/bookings', function () {
    $bookings = Booking::query()
        ->orderByDesc('submitted_at')
        ->orderByDesc('created_at')
        ->get()
        ->map(fn (Booking $booking) => $booking->toAdminArray());

    $packages = $bookings
        ->map(fn (array $booking) => $booking['package'])
        ->unique('id')
        ->values()
        ->all();

    return view('admin.bookings', [
        'bookings' => $bookings,
        'packages' => $packages,
        'fullDates' => Booking::fullCapacityDates(),
    ]);
})->name('admin.bookings');

Route::get('/admin/bookings/data', [BookingController::class, 'index'])
    ->name('admin.bookings.data');

Route::post('/admin/bookings/{booking}/status', [BookingController::class, 'updateStatus'])
    ->whereNumber('booking')
    ->name('admin.bookings.status');

Route::patch('/admin/bookings/{booking}', [BookingController::class, 'update'])
    ->whereNumber('booking')
    ->name('admin.bookings.update');
