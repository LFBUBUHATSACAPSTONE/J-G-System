<?php

use App\Models\Package;
use App\Services\Auth\CurrentAccount;
use Illuminate\Support\Facades\Route;

// Routes for the public landing page. Required from routes/user.php, which web.php requires.
Route::get('/', function (CurrentAccount $account) {
    // The home page of whoever is signed in: the panel for an admin, the landing page for a
    // customer (and for a guest, who has to sign in there first).
    return redirect()->route($account->landingRoute());
});

Route::get('/user/landing', function () {
    $packages = Package::query()->with('equipment')->where('available', true)->orderBy('sort_order')->orderBy('id')->get()
        ->map(fn (Package $package) => $package->toPublicArray())
        ->all();

    return view('user.landing', compact('packages'));
})->name('user.landing');
