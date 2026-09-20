<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});
Route::get('/user/landing', function () {
    return view('user.landing');
})->name('user.landing');

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

Route::post('/login', fn() => back())->name('login');
Route::post('/register', fn() => back())->name('register');
Route::post('/password/email', fn() => back())->name('password.email');
Route::post('/verification/confirm', fn() => back())->name('verification.confirm');
Route::post('/password/update', fn() => back())->name('password.update');
