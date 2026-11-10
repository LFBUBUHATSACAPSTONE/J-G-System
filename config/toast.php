<?php

// Toast notifications (docs/toast-notifications.md, docs/toast-notifications-phases.md).
// The views and scripts hard-code no timing or label: change them here. The region component passes
// these values to resources/js/shared/toast.js through a data attribute.
return [

  // How many toasts are on screen at once. The rest wait in a queue and appear as others close.
  'max_visible' => 3,

  // The same tone + text shown again within this time is merged into one toast with a count
  // ("x2") instead of a second toast, and its timer restarts.
  'merge_window_ms' => 5000,

  // How long each tone stays. 0 = until the person closes it (errors are never lost by timing out).
  // The timer pauses while the pointer or keyboard focus is on the toast and while the tab is hidden.
  'durations_ms' => [
    'success' => 5000,
    'info'    => 5000,
    'warning' => 8000,
    'danger'  => 0,
  ],

  // Server flash keys (session('status'), session('error')) and the tone each one becomes.
  // Controllers keep setting these exactly as before; resources/views/components/toast/flash.blade.php reads them.
  'flash_tones' => [
    'status' => 'success',
    'error'  => 'danger',
  ],

  'labels' => [
    'region' => 'Notifications',
    'close'  => 'Close notification',
    'merged' => 'x:count', // shown on a merged toast, e.g. "x2"
  ],

  // Toasts between the two parties (Phase 4, docs/toast-notifications-phases.md section 6).
  // The browser asks the existing `updates` endpoints (admin.messages.updates / user.messages.updates);
  // nothing here needs a new route. Fed so far: messages both ways (4B) and "booking approved" for the client (4C).
  'feed' => [
    // Pace of the page's own poll (pages that have no chat poller). Same idea as chat.js: slow, paused while the
    // tab is hidden, doubling after each failure up to max_ms.
    'poll_ms' => 15000,
    'max_ms'  => 60000,

    // New text for these toasts lives here, not in the views or scripts. :count is filled in by the script.
    'text' => [
      'admin' => [
        'message'  => 'New message from a client',
        'messages' => ':count new client messages',
        'open'     => 'Open',
      ],
      'user' => [
        'message'  => 'New reply from J&G',
        'messages' => ':count new replies from J&G',
        'open'     => 'Open chat',
        // 4C: a booking of this client was approved (:reference / :count are filled in by the script).
        'booking_approved'  => 'Your booking :reference was approved',
        'bookings_approved' => ':count of your bookings were approved',
        'open_bookings'     => 'My Bookings',
      ],
    ],
  ],

];
