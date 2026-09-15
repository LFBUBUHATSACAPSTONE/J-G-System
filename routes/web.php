<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/user/landing', function () {
    return view('user.landing');
})->name('user.landing');
