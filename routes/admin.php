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


// ? ADMIN CALENDAR STUB ROUTE:
Route::get('/admin/calendar', function () {
  $requested = request('month');
  $month = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $requested)
    ? Carbon::parse("{$requested}-01")
    : Carbon::parse('2025-12-01');

  $booking = fn($id, $ref, $status, $client, $package, $name, $location, $start, $end, $from, $to) => [
    'id' => $id,
    'reference' => $ref,
    'status' => $status,
    'client' => ['name' => $client],
    'package' => ['name' => $package],
    'event' => [
      'name' => $name,
      'location' => $location,
      'start_date' => Carbon::parse($start),
      'end_date' => Carbon::parse($end),
      'start_time' => $from,
      'end_time' => $to,
    ],
  ];

  return view('admin.calendar', [
    'month' => $month,
    'events' => [
      $booking(2, '#JG39201', 'approved', 'Ayessa Dumay', 'Modern Glam', "Aye's Concert", 'San Rafael River Adventure', '2025-12-22', '2025-12-22', '5:00 PM', '12:00 AM'),
      $booking(1, '#JG12345', 'approved', 'Arjay Dela Cruz', 'Budget Party', 'Sample Birthday', 'Sample Venue, Bulacan', '2025-12-30', '2025-12-30', '6:00 PM', '11:00 PM'),
      $booking(3, '#JG63497', 'pending_payment', 'Lhester Pile', 'Luxe Lite', 'Pile Family Reunion', 'Sample Resort, Bulacan', '2025-12-31', '2026-01-01', '10:00 AM', '10:00 PM'),
      // A second booking on the same date, to try the "2 bookings" cell and the stacked cards.
      $booking(6, '#JG55210', 'approved', 'Sample Client', 'Budget Wedding', 'Sample Wedding', 'Sample Garden, Bulacan', '2025-12-31', '2025-12-31', '4:00 PM', '9:00 PM'),
      // Cancelled / declined: kept in the data on purpose, the page must leave them off.
      $booking(4, '#JG11085', 'cancelled', 'RJ Valmadrid', 'Budget Lite', 'Sample Event', 'Sample Venue, Bulacan', '2025-12-23', '2025-12-23', '3:00 PM', '8:00 PM'),
      $booking(5, '#JG67677', 'declined', 'Paler Perez', 'Budget Lite', 'Sample Concert', 'Sample Venue, Bulacan', '2025-12-13', '2025-12-13', '7:00 PM', '12:00 AM'),
    ],
  ]);
})->name('admin.calendar');

// ? ADMIN BOOKING HISTORY STUB ROUTE:
Route::get('/admin/booking-history', function () {
  $today = Carbon::today();
  $day = fn(int $offset) => $today->copy()->addDays($offset);

  $client = fn(string $name, string $email) => [
    'name' => $name,
    'email' => $email,
    'phone' => '0917 000 0000',
    'address' => 'Sample Barangay, Sample City, Bulacan',
  ];

  $event = fn(string $name, string $type, int $from, int $to, string $start, string $end, ?int $guests = 80, string $venue = 'Indoor') => [
    'name' => $name,
    'type' => $type,
    'location' => 'Sample Venue, Bulacan',
    'contact_person' => null,
    'guests' => $guests,
    'venue_type' => $venue,
    'start_date' => $day($from),
    'end_date' => $day($to),
    'start_time' => $start,
    'end_time' => $end,
  ];

  $paid = ['label' => 'GCash - Full Payment', 'status' => 'Fully Paid', 'state' => 'paid'];
  $partial = ['label' => 'GCash - Down Payment', 'status' => 'Down payment paid', 'state' => 'partial'];
  $unpaid = ['label' => 'GCash - Down Payment', 'status' => 'Awaiting verification', 'state' => 'pending'];

  $all = [
    // Completed (from the day after the end date).
    [
      'id' => 2,
      'reference' => '#JG39201',
      'status' => 'approved',
      'client' => $client('Ayessa Dumay', 'ayessa@example.com'),
      'event' => $event("Aye's Concert", 'Concert', -12, -12, '5:00 PM', '12:00 AM', null, 'Both'),
      'payment' => $paid,
      'package' => ['id' => 'modern-glam', 'name' => 'Modern Glam', 'price' => 35000]
    ],
    [
      'id' => 3,
      'reference' => '#JG63497',
      'status' => 'completed',
      'client' => $client('Lhester Pile', 'lhester@example.com'),
      'event' => $event('Pile Family Reunion', 'Family Reunion', -20, -19, '10:00 AM', '10:00 PM', 120, 'Outdoor'),
      'payment' => $paid,
      'package' => ['id' => 'luxe-lite', 'name' => 'Luxe Lite', 'price' => 25000]
    ],

    // Ongoing (today is between the start and end date).
    [
      'id' => 1,
      'reference' => '#JG12345',
      'status' => 'approved',
      'client' => $client('Arjay Dela Cruz', 'arjay@example.com'),
      'event' => $event('60th Birthday', 'Birthday Party', 0, 0, '8:00 AM', '5:00 PM', 67),
      'payment' => $paid,
      'package' => ['id' => 'budget-party', 'name' => 'Budget Party', 'price' => 10000]
    ],
    [
      'id' => 4,
      'reference' => '#JG70018',
      'status' => 'approved',
      'client' => $client('Mia Santos', 'mia@example.com'),
      'event' => $event('Santos Wedding Weekend', 'Wedding', -1, 1, '9:00 AM', '11:00 PM', 150, 'Both'),
      'payment' => $paid,
      'package' => ['id' => 'budget-wedding', 'name' => 'Budget Wedding', 'price' => 18000]
    ],

    // Upcoming and already paid (a verified down payment counts, see config 'paid_states').
    [
      'id' => 5,
      'reference' => '#JG81234',
      'status' => 'approved',
      'client' => $client('Rica Mendoza', 'rica@example.com'),
      'event' => $event('Mendoza Debut', 'Debut', 9, 9, '6:00 PM', '11:00 PM', 180),
      'payment' => $partial,
      'package' => ['id' => 'elite-symphony', 'name' => 'Elite Symphony', 'price' => 45000]
    ],
    [
      'id' => 6,
      'reference' => '#JG55210',
      'status' => 'approved',
      'client' => $client('Paolo Reyes', 'paolo@example.com'),
      'event' => $event('Reyes Office Party', 'Corporate Event', 30, 30, '4:00 PM', '9:00 PM', 60),
      'payment' => $paid,
      'package' => ['id' => 'budget-lite', 'name' => 'Budget Lite', 'price' => 8000]
    ],

    // Cancelled and declined (always history).
    [
      'id' => 7,
      'reference' => '#JG11085',
      'status' => 'cancelled',
      'client' => $client('RJ Valmadrid', 'rj@example.com'),
      'event' => $event('Sample Event', 'Others', -5, -5, '3:00 PM', '8:00 PM', null, 'Indoor'),
      'payment' => ['label' => 'GCash - Full Payment', 'status' => 'Refund pending', 'state' => 'pending'],
      'package' => ['id' => 'budget-lite', 'name' => 'Budget Lite', 'price' => 8000]
    ],
    [
      'id' => 8,
      'reference' => '#JG67677',
      'status' => 'declined',
      'client' => $client('Paler Perez', 'paler@example.com'),
      'event' => $event('Sample Concert', 'Concert', -9, -9, '7:00 PM', '12:00 AM', 300, 'Outdoor'),
      'payment' => [],
      'package' => ['id' => 'budget-lite', 'name' => 'Budget Lite', 'price' => 8000]
    ],

    // NOT history: still needs a decision or has no verified payment. The filter below drops these.
    [
      'id' => 9,
      'reference' => '#JG90001',
      'status' => 'pending',
      'client' => $client('Not In History A', 'a@example.com'),
      'event' => $event('Pending Request', 'Birthday Party', 14, 14, '6:00 PM', '11:00 PM'),
      'payment' => $unpaid,
      'package' => ['id' => 'budget-party', 'name' => 'Budget Party', 'price' => 10000]
    ],
    [
      'id' => 10,
      'reference' => '#JG90002',
      'status' => 'pending_payment',
      'client' => $client('Not In History B', 'b@example.com'),
      'event' => $event('Waiting For Payment', 'Wedding', 20, 20, '2:00 PM', '9:00 PM'),
      'payment' => $unpaid,
      'package' => ['id' => 'luxe-lite', 'name' => 'Luxe Lite', 'price' => 25000]
    ],
    [
      'id' => 11,
      'reference' => '#JG90003',
      'status' => 'approved',
      'client' => $client('Not In History C', 'c@example.com'),
      'event' => $event('Approved, Not Paid Yet', 'Concert', 25, 25, '5:00 PM', '11:00 PM'),
      'payment' => $unpaid,
      'package' => ['id' => 'modern-glam', 'name' => 'Modern Glam', 'price' => 35000]
    ],
  ];

  // The history rule (config/admin-history.php): cancelled / declined always; approved / completed
  // once a payment is verified.
  $isHistory = fn(array $booking) => in_array($booking['status'], config('admin-history.terminal_statuses'), true)
    || (in_array($booking['status'], config('admin-history.history_statuses'), true)
      && in_array($booking['payment']['state'] ?? null, config('admin-history.paid_states'), true));

  return view('admin.booking-history', [
    // Admin-managed, so the dropdown is built from this list, not hard-coded.
    'packages' => [
      ['id' => 'budget-lite',    'name' => 'Budget Lite'],
      ['id' => 'budget-party',   'name' => 'Budget Party'],
      ['id' => 'budget-wedding', 'name' => 'Budget Wedding'],
      ['id' => 'luxe-lite',      'name' => 'Luxe Lite'],
      ['id' => 'modern-glam',    'name' => 'Modern Glam'],
      ['id' => 'elite-symphony', 'name' => 'Elite Symphony'],
    ],
    'bookings' => array_values(array_filter($all, $isHistory)),
  ]);
})->name('admin.booking-history');
