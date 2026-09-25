<?php

use Illuminate\Support\Facades\Route;

// Front-End Verification Testing
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

Route::get('/', function () {
    return view('welcome');
});
