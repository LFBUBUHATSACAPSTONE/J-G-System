<?php

use App\Http\Controllers\Admin\BookingController;
use App\Models\Booking;
use App\Models\Package;
use Illuminate\Support\Facades\Route;

Route::get('/admin/bookings', function () {
    $bookings = Booking::query()
        ->with(['eventDetails', 'schedule', 'payment', 'package'])
        ->orderByDesc('submitted_at')
        ->orderByDesc('created_at')
        ->get()
        ->map(fn (Booking $booking) => $booking->toAdminArray())
        ->all();

    $packages = Package::query()
        ->orderBy('sort_order')
        ->orderBy('id')
        ->get()
        ->map(fn (Package $package) => $package->toAdminArray())
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
