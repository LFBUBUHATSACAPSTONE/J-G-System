<?php

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Validation\Rule;

//TEMPORARY FRONT-END TESTING STUBS

// Admin routes. Names must match the `route` keys in config/admin.php, because the sidebar builds its links and its active state from them.
// TODO: add auth + admin-role middleware once the backend lands, e.g.
Route::prefix('admin')->name('admin.')->group(function () {
  Route::view('/dashboard', 'admin.dashboard')->name('dashboard');
  Route::view('/bookings', 'admin.bookings')->name('bookings');
  Route::view('/messages', 'admin.messages')->name('messages');
  Route::view('/booking-history', 'admin.booking-history')->name('booking-history');
  Route::view('/packages', 'admin.packages')->name('packages');
  Route::view('/calendar', 'admin.calendar')->name('calendar');
  Route::view('/account', 'admin.account')->name('account');
});

// ? ADMIN DASHBOARD STUB ROUTE:
Route::get('/admin/dashboard', function () {
  return view('admin.dashboard', [
    // `delta` and `direction` are optional. Leave `delta` out to hide the badge.
    'stats' => [
      'total'     => ['value' => 4, 'delta' => 99.8, 'direction' => 'up'],
      'confirmed' => ['value' => 1, 'delta' => 99.8, 'direction' => 'up'],
      'pending'   => ['value' => 1, 'delta' => 99.8, 'direction' => 'up'],
      'cancelled' => ['value' => 2],
    ],

    'upcomingEvents' => [
      ['date' => Carbon::parse('2025-12-22'), 'package' => 'Modern Glam'],
      ['date' => Carbon::parse('2025-12-31'), 'package' => 'Luxe Lite'],
    ],

    'pendingApprovals' => [
      ['reference' => '#JG12345', 'date' => Carbon::parse('2025-12-30'), 'package' => 'Budget Party'],
    ],

    // Booking COUNT per package (the view works out the percentages).
    'packageRate' => [
      ['label' => 'Budget Lite', 'count' => 18],
      ['label' => 'Luxe Lite', 'count' => 25],
      ['label' => 'Budget Party', 'count' => 25],
      ['label' => 'Modern Glam', 'count' => 10],
      ['label' => 'Budget Wedding', 'count' => 17],
      ['label' => 'Elite Symphony', 'count' => 5],
    ],
  ]);
})->name('admin.dashboard');


// ? ADMIN BOOKINGS STUB ROUTES:
Route::get('/admin/bookings', function () {
  return view('admin.bookings', [
    // Admin-managed, so the dropdown is built from this list, not hard-coded.
    'packages' => [
      ['id' => 'budget-lite',    'name' => 'Budget Lite'],
      ['id' => 'budget-party',   'name' => 'Budget Party'],
      ['id' => 'budget-wedding', 'name' => 'Budget Wedding'],
      ['id' => 'luxe-lite',      'name' => 'Luxe Lite'],
      ['id' => 'modern-glam',    'name' => 'Modern Glam'],
      ['id' => 'elite-symphony', 'name' => 'Elite Symphony'],
    ],

    'bookings' => [
      [
        'id' => 1,
        'reference' => '#JG12345',
        'status' => 'pending',
        'client' => ['name' => 'Arjay Dela Cruz', 'email' => 'arjay@example.com', 'phone' => '0917 000 0001', 'address' => 'Sample Barangay, Sample City, Bulacan'],
        'event' => [
          'name' => 'Sample Birthday',
          'type' => 'Birthday Party',
          'location' => 'Sample Venue, Bulacan',
          'contact_person' => null,
          'guests' => 80,
          'venue_type' => 'Indoor',
          'start_date' => Carbon::parse('2025-12-30'),
          'end_date' => Carbon::parse('2025-12-30'),
          'start_time' => '6:00 PM',
          'end_time' => '11:00 PM',
        ],
        'payment' => ['label' => 'GCash - Down Payment', 'status' => 'Awaiting verification', 'state' => 'pending'],
        'package' => ['id' => 'budget-party', 'name' => 'Budget Party', 'price' => 12000],
      ],
      [
        'id' => 2,
        'reference' => '#JG39201',
        'status' => 'approved',
        'client' => ['name' => 'Ayessa Dumay', 'email' => 'ayessadumay.basc@gmail.com', 'phone' => '0912 345 6789', 'address' => 'Poblacion, San Ildefonso Bulacan'],
        'event' => [
          'name' => "Aye's Concert",
          'type' => 'Concert',
          'location' => 'San Rafael River Adventure',
          'contact_person' => '0906 026 7988',
          'guests' => null,
          'venue_type' => 'Both',
          'start_date' => Carbon::parse('2025-12-22'),
          'end_date' => Carbon::parse('2025-12-22'),
          'start_time' => '5:00 PM',
          'end_time' => '12:00 AM',
        ],
        'payment' => ['label' => 'GCash - Full Payment', 'status' => 'Fully Paid', 'state' => 'paid'],
        'package' => ['id' => 'modern-glam', 'name' => 'Modern Glam', 'price' => 35000],
      ],
      [
        'id' => 3,
        'reference' => '#JG63497',
        'status' => 'pending_payment',
        'client' => ['name' => 'Lhester Pile', 'email' => 'lhester@example.com', 'phone' => '0917 000 0003', 'address' => 'Sample Barangay, Sample City, Bulacan'],
        'event' => [
          'name' => 'Pile Family Reunion',
          'type' => 'Family Reunion',
          'location' => 'Sample Resort, Bulacan',
          'contact_person' => '0917 000 0033',
          'guests' => 120,
          'venue_type' => 'Outdoor',
          'start_date' => Carbon::parse('2025-12-31'),
          'end_date' => Carbon::parse('2026-01-01'),
          'start_time' => '10:00 AM',
          'end_time' => '10:00 PM',
        ],
        'payment' => ['label' => 'GCash - Down Payment', 'status' => 'Down payment paid', 'state' => 'partial'],
        'package' => ['id' => 'luxe-lite', 'name' => 'Luxe Lite', 'price' => 20000],
      ],
      [
        'id' => 4,
        'reference' => '#JG11085',
        'status' => 'cancelled',
        'client' => ['name' => 'RJ Valmadrid', 'email' => 'rj@example.com', 'phone' => '0917 000 0004', 'address' => 'Sample Barangay, Sample City, Bulacan'],
        'event' => [
          'name' => 'Sample Event',
          'type' => 'Others',
          'location' => 'Sample Venue, Bulacan',
          'contact_person' => null,
          'guests' => null,
          'venue_type' => null,
          'start_date' => Carbon::parse('2025-12-23'),
          'end_date' => Carbon::parse('2025-12-23'),
          'start_time' => '3:00 PM',
          'end_time' => '8:00 PM',
        ],
        'payment' => ['label' => 'GCash - Full Payment', 'status' => 'Refund pending', 'state' => 'pending'],
        'package' => ['id' => 'budget-lite', 'name' => 'Budget Lite', 'price' => 8000],
      ],
      [
        'id' => 5,
        'reference' => '#JG67677',
        'status' => 'declined',
        'client' => ['name' => 'Paler Perez', 'email' => 'paler@example.com', 'phone' => '0917 000 0005', 'address' => 'Sample Barangay, Sample City, Bulacan'],
        'event' => [
          'name' => 'Sample Concert',
          'type' => 'Concert',
          'location' => 'Sample Venue, Bulacan',
          'contact_person' => null,
          'guests' => 300,
          'venue_type' => 'Outdoor',
          'start_date' => Carbon::parse('2025-12-13'),
          'end_date' => Carbon::parse('2025-12-13'),
          'start_time' => '7:00 PM',
          'end_time' => '12:00 AM',
        ],
        'payment' => [],
        'package' => ['id' => 'budget-lite', 'name' => 'Budget Lite', 'price' => 8000],
      ],
    ],
  ]);
})->name('admin.bookings');

// Approve / Decline / Cancel buttons (a normal form POST, so the server re-renders the page).
Route::post('/admin/bookings/{booking}/status', function (Request $request, $booking) {
  $action = $request->validate(['action' => ['required', 'in:approve,decline,cancel']])['action'];

  return back()->with('status', "Booking {$booking}: '{$action}' received (stub, nothing was saved).");
})->name('admin.bookings.status');

// "Save changes" in the booking modal (editable event fields only).
Route::patch('/admin/bookings/{booking}', function (Request $request, $booking) {
  $request->validate([
    'event_name' => ['nullable', 'string', 'max:255'],
    'event_location' => ['nullable', 'string', 'max:255'],
    'venue_contact_person' => ['nullable', 'string', 'max:255'],
    'guest_count' => ['nullable', 'integer', 'min:0'],
    'venue_type' => ['nullable', 'in:Indoor,Outdoor,Both'],
  ]);

  return back()->with('status', "Booking {$booking}: changes received (stub, nothing was saved).");
})->name('admin.bookings.update');


// ? ADMIN PACKAGES STUB ROUTES:
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
    'availability' => ['required', Rule::in(array_keys(config('admin-packages.availability')))],
  ])['availability'];

  return redirect()->route('admin.packages')
    ->with('status', "Package {$package}: '{$availability}' received (stub, nothing was saved).");
})->name('admin.packages.availability');
