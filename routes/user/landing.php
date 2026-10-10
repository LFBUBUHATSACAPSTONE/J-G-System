<?php

use App\Models\Package;
use Illuminate\Support\Facades\Route;

// Routes for the public landing page. Required from routes/user.php, which web.php requires.
Route::get('/', function () {
  return redirect()->route('user.landing');
});

Route::get('/user/landing', function () {
  $packages = Package::query()
    ->where('available', true)
    ->orderBy('sort_order')
    ->get()
    ->map(fn (Package $package) => [
      'id' => $package->id,
      'name' => $package->name,
      'price' => $package->price,
      'features' => $package->features ?? [],
    ]);

  return view('user.landing', compact('packages'));
})->name('user.landing');
