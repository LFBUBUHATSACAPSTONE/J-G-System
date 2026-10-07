<?php

use App\Http\Controllers\Admin\BookingHistoryController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/booking-history', [BookingHistoryController::class, 'show'])
  ->name('admin.booking-history');

Route::get('/admin/booking-history/data', [BookingHistoryController::class, 'index'])
  ->name('admin.booking-history.data');
