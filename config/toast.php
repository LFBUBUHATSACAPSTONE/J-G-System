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
];
