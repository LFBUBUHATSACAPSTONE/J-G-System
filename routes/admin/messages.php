<?php

use App\Http\Controllers\Admin\MessageController;
use Illuminate\Support\Facades\Route;

Route::get('/admin/messages', [MessageController::class, 'index'])->name('admin.messages');
Route::get('/admin/messages/{conversation}', [MessageController::class, 'show'])->name('admin.messages.show');
Route::post('/admin/messages/{conversation}', [MessageController::class, 'store'])->name('admin.messages.send');