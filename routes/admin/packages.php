<?php

use App\Http\Controllers\Admin\PackageController;
use App\Models\Package;
use Illuminate\Support\Facades\Route;

Route::get('/admin/packages', function () {
    $packages = Package::query()->orderBy('sort_order')->orderBy('id')->get()
        ->map(fn (Package $package) => $package->toAdminArray())
        ->all();

    return view('admin.packages', compact('packages'));
})->name('admin.packages');

Route::get('/admin/packages/data', [PackageController::class, 'index'])->name('admin.packages.data');
Route::post('/admin/packages', [PackageController::class, 'store'])->name('admin.packages.store');
Route::patch('/admin/packages/{package}', [PackageController::class, 'update'])->name('admin.packages.update');
Route::patch('/admin/packages/{package}/availability', [PackageController::class, 'updateAvailability'])
    ->name('admin.packages.availability');
