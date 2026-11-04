<?php

use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

Route::view('/admin/dashboard', 'admin.dashboard')->name('admin.dashboard');
Route::get('/admin/dashboard/data', DashboardController::class)->name('admin.dashboard.data');
