<?php

use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\View;

// User-side chat (the floating widget on the landing page). Required from routes/user.php.
// FRONT-END STUB: nothing is saved and nothing reaches the admin Messages page yet.
// Keep the route NAME `user.messages.send`: the widget builds its form action from it.

// Sample thread so the widget can be previewed. Delete this composer when the real data exists
// and pass `$chatConversation` from the landing controller instead.
View::composer('user.landing', function ($view) {
  $view->with('chatConversation', [
    'unread' => 1,
    'items' => [
      ['type' => 'booking', 'at' => Carbon::now()->subHours(5), 'booking' => [
        'reference' => '#JG12345',
        'status' => 'pending',
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
      ]],
      ['type' => 'message', 'from' => 'admin', 'body' => 'Your booking has been approved and confirmed.', 'at' => Carbon::now()->subHours(3), 'attachments' => []],
    ],
  ]);
});

// Same contract and limits as admin.messages.send.
Route::post('/messages', function (Request $request) {
  $att = config('admin.messages.attachments');

  $request->validate([
    'body'          => ['nullable', 'string', 'max:' . config('admin.messages.max_length'), 'required_without:attachments'],
    'attachments'   => ['nullable', 'array', 'max:' . $att['max_files']],
    'attachments.*' => ['file', 'mimes:' . $att['mimes'], 'max:' . $att['max_kb']],
  ]);

  return response()->json(['ok' => true]);
})->name('user.messages.send');
