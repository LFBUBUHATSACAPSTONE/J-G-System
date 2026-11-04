<?php

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;

// Routes for the admin Messages page. Required from routes/admin.php.
// FRONT-END STUB: placeholder data, nothing is saved. Replace each closure with a controller call
// and keep the route NAMES (`admin.messages`, `admin.messages.send`): config/admin.php (page meta,
// sidebar active state) and the views depend on them.

Route::get('/admin/messages', function () {
  // One thread per client. Ordered newest activity first (the backend should do the same).
  $arjayBooking = [
    'reference' => '#JG12345',
    'status' => 'approved',
    'package' => ['id' => 'modern-glam', 'name' => 'Modern Glam', 'price' => 35000],
    'event' => [
      'name' => 'Arjay & Co. Debut',
      'type' => 'Debut',
      'location' => 'Marikina Sports Center',
      'start_date' => Carbon::now()->addDays(20),
      'end_date' => Carbon::now()->addDays(20),
      'start_time' => '5:00 PM',
      'end_time' => '12:00 AM',
    ],
  ];
  $pastBooking = [
    'reference' => '#JG39201',
    'status' => 'completed',
    'package' => ['id' => 'budget-lite', 'name' => 'Budget Lite', 'price' => 5000],
    'event' => [
      'name' => 'Barangay Fiesta',
      'type' => 'Festival',
      'location' => 'Brgy. Hall, Antipolo',
      'start_date' => Carbon::now()->subDays(30),
      'end_date' => Carbon::now()->subDays(30),
      'start_time' => '4:00 PM',
      'end_time' => '10:00 PM',
    ],
  ];

  $m = fn($from, $body, $at, $files = []) => ['type' => 'message', 'from' => $from, 'body' => $body, 'at' => $at, 'attachments' => $files];

  $conversations = [
    [
      'id' => 1,
      'unread' => 1,
      'client' => ['name' => 'Arjay Dela Cruz', 'phone' => '(63+)912 345 6789'],
      'items' => [
        ['type' => 'booking', 'booking' => $pastBooking, 'at' => Carbon::now()->subDays(40)],
        ['type' => 'booking', 'booking' => $arjayBooking, 'at' => Carbon::now()->subDay()],
        $m('admin', 'Your booking has been approved and confirmed.', Carbon::now()->subHours(3)),
        $m('admin', 'Please review your event details. If you would like to update or change any information, feel free to message us.', Carbon::now()->subHours(3)),
        $m('client', 'Thank you! I’d like to request a change in the number of event guests.', Carbon::now()->subHours(2)),
        $m('admin', 'No problem. How many guests would you like to update it to?', Carbon::now()->subHours(2)),
        $m('client', 'Please change it to 120 guests.', Carbon::now()->subHour()),
        $m('admin', 'Noted. The event guest count has been updated to 120.', Carbon::now()->subMinutes(30)),
        $m('client', 'Here is my GCash receipt.', Carbon::now()->subMinutes(8), [['name' => 'gcash-receipt.pdf', 'url' => '#', 'kind' => 'file', 'size' => '240 KB']]),
        $m('client', 'thank you!', Carbon::now()->subMinutes(6)),
        $m('admin', 'You’re welcome! If you need any further changes, feel free to message us.', Carbon::now()->subMinutes(4)),
      ],
    ],
    [
      'id' => 2,
      'unread' => 0,
      'client' => ['name' => 'Ayessa Dumay', 'phone' => '(63+)917 111 2222'],
      'items' => [$m('client', 'Hi, is Modern Glam available on Dec 31?', Carbon::now()->subHours(20)), $m('admin', 'Please review your event details...', Carbon::now()->subHours(18))]
    ],
    [
      'id' => 3,
      'unread' => 0,
      'client' => ['name' => 'RJ Valmadrid', 'phone' => '(63+)918 333 4444'],
      'items' => [
        ['type' => 'booking', 'booking' => array_merge($pastBooking, ['reference' => '#JG20418', 'status' => 'cancelled']), 'at' => Carbon::now()->subDays(3)],
        $m('client', 'Please cancel my booking.', Carbon::now()->subDays(2)->subHour()),
        $m('admin', 'Your cancellation request has been processed.', Carbon::now()->subDays(2))
      ]
    ],
    [
      'id' => 4,
      'unread' => 0,
      'client' => ['name' => 'Lhester Pile', 'phone' => '(63+)919 555 6666'],
      'items' => [$m('admin', 'Your booking is confirmed.', Carbon::now()->subDays(3)->subHour()), $m('client', 'Thank you po', Carbon::now()->subDays(3))]
    ],
    [
      'id' => 5,
      'unread' => 2,
      'client' => ['name' => 'Paler Perez', 'phone' => '(63+)920 777 8888'],
      'items' => [$m('client', 'ganon talaga yun', Carbon::now()->subDays(7))]
    ],
  ];

  return view('admin.messages', ['conversations' => $conversations]);
})->name('admin.messages');

// Send a message or attachments to a client. Backend contract: 200 {"ok": true} on success;
// 422 {"message", "errors": {field: [msg]}} on a validation failure (Laravel does this for JSON requests).
Route::post('/admin/messages/{conversation}', function (Request $request, $conversation) {
  $att = config('admin.messages.attachments');

  $request->validate([
    'body'          => ['nullable', 'string', 'max:' . config('admin.messages.max_length'), 'required_without:attachments'],
    'attachments'   => ['nullable', 'array', 'max:' . $att['max_files']],
    'attachments.*' => ['file', 'mimes:' . $att['mimes'], 'max:' . $att['max_kb']],
  ]);

  return response()->json(['ok' => true]);
})->name('admin.messages.send');
