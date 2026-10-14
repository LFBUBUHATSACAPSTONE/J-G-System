<?php

use App\Http\Controllers\Admin\CalendarController;
use Illuminate\Support\Facades\Route;

// Routes for the Calendar page. Required from routes/admin.php, which web.php requires.
// The page renders server-side and the JSON endpoint returns the same data for API/JS use.
//
// Route NAME must stay `admin.calendar`: config/admin.php (page meta + sidebar active state)
// and the month arrows in the view key off it.
//
// The page opens on the CURRENT month when no ?month= is given. The month arrows are plain links
// (?month=YYYY-MM), so the page still works without JS.

Route::get('/admin/calendar', [CalendarController::class, 'show'])
  ->name('admin.calendar');

Route::get('/admin/calendar/data', [CalendarController::class, 'index'])
  ->name('admin.calendar.data');
