<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

// Routes for the Packages page. Required from routes/admin.php, which web.php requires.
// Everything here is a FRONT-END STUB (placeholder data, nothing is saved). When the backend is
// built, replace each closure with a controller call and keep this file as the page's routes. Route NAMES must stay as they are: config/admin.php (page meta + sidebar
// active state) keys off `admin.packages`, and the card/modal markup builds URLs from the rest.

$stubPackages = fn() => [
  ['id' => 'budget-lite', 'name' => 'Budget Lite', 'price' => 5000, 'available' => false, 'features' => [
    'Ideal for small and intimate events',
    'Basic yet clear sound setup',
    'Simple lighting for ambience',
    'Good for meetings and mini gatherings',
    'Easy and quick installation'
  ]],
  ['id' => 'budget-party', 'name' => 'Budget Party', 'price' => 10000, 'available' => true, 'features' => [
    'Perfect for birthdays and school programs',
    'Brighter party lighting',
    'Improved sound coverage',
    'Great for corporate events',
    'Fun and lively atmosphere'
  ]],
  ['id' => 'budget-wedding', 'name' => 'Budget Wedding', 'price' => 18000, 'available' => true, 'features' => [
    'Best for simple weddings',
    'LED wall with live feed',
    'Clean and elegant audio',
    'For church or reception setups',
    'Balanced sound and lighting'
  ]],
  ['id' => 'luxe-lite', 'name' => 'Luxe Lite', 'price' => 25000, 'available' => true, 'features' => [
    'For formal programs and receptions',
    'Enhanced lighting setup',
    'Clear sound for speeches and music',
    'Supports basic band needs',
    'Professional presentation finish'
  ]],
  ['id' => 'modern-glam', 'name' => 'Modern Glam', 'price' => 35000, 'available' => true, 'features' => [
    'Ideal for debuts and luxury weddings',
    'Upgraded visual lighting',
    'Strong event audio',
    'Works well for indoor venues',
    'Grand ambience setting'
  ]],
  ['id' => 'elite-symphony', 'name' => 'Elite Symphony', 'price' => 45000, 'available' => true, 'features' => [
    'Best for concerts and grand events',
    'Full premium audio and lighting',
    'Concert-level production',
    'Complete event stage setup',
    'Maximum visual and sound impact'
  ]],
];

// Shared by store + update. `features` is one feature per line (textarea).
$packageRules = fn() => [
  'name' => ['required', 'string', 'max:100'],
  'price' => ['required', 'integer', 'min:1', 'max:10000000'],
  'features' => ['required', 'string', 'max:2000'],
];

Route::get('/admin/packages', function () use ($stubPackages) {
  return view('admin.packages', ['packages' => $stubPackages()]);
})->name('admin.packages');

// "Add New Package" -> Save (modal in create mode).
// Stub trigger for a 422-style redirect with errors: reuse an existing name.
Route::post('/admin/packages', function (Request $request) use ($stubPackages, $packageRules) {
  $request->validate([
    'name' => [...$packageRules()['name'], Rule::notIn(array_column($stubPackages(), 'name'))],
  ] + $packageRules(), ['name.not_in' => 'A package with this name already exists.']);

  return redirect()->route('admin.packages')
    ->with('status', "Package '{$request->input('name')}' received (stub, nothing was saved).");
})->name('admin.packages.store');

// Modal Edit -> Save. {package} is the immutable package id (slug).
Route::patch('/admin/packages/{package}', function (Request $request, string $package) use ($stubPackages, $packageRules) {
  $others = collect($stubPackages())->where('id', '!=', $package)->pluck('name')->all();

  $request->validate([
    'name' => [...$packageRules()['name'], Rule::notIn($others)],
  ] + $packageRules(), ['name.not_in' => 'A package with this name already exists.']);

  return redirect()->route('admin.packages')
    ->with('status', "Package {$package}: changes received (stub, nothing was saved).");
})->name('admin.packages.update');

// Available / Unavailable buttons on a card.
Route::patch('/admin/packages/{package}/availability', function (Request $request, string $package) {
  $availability = $request->validate([
    'availability' => ['required', Rule::in(array_keys(config('admin.packages.availability')))],
  ])['availability'];

  return redirect()->route('admin.packages')
    ->with('status', "Package {$package}: '{$availability}' received (stub, nothing was saved).");
})->name('admin.packages.availability');
