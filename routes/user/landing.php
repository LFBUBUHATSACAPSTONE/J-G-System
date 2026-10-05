<?php

use Illuminate\Support\Facades\Route;

// Routes for the public landing page. Required from routes/user.php, which web.php requires.

Route::get('/', function () {
  return view('welcome');
});

Route::get('/user/landing', function () {
  return view('user.landing');
})->name('user.landing');
