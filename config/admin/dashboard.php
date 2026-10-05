<?php

// Dashboard registry. The view reads from here, so adding a KPI card or changing a limit is one
// edit, not a Blade edit. Dashboard DATA (the numbers, the lists) comes from the controller.
return [

  // KPI cards, in display order. Keys must match the keys of the controller's $stats array.
  //
  // label   => card title
  // status  => the Bookings "Status" filter value the card links to (keys of
  //            config/admin/bookings.php 'status_filters'). 'all' = no filter.
  // higher  => is a higher number than last month 'good', 'bad' or 'neutral'? Drives the badge
  //            colour only (more cancellations must never look green).
  // hint    => caption shown when the controller sends no `change` for that card
  // featured=> the filled purple variant
  'cards' => [
    'total'     => ['label' => 'Total Bookings',    'status' => 'all',       'higher' => 'good',    'hint' => 'All time',                'featured' => true],
    'confirmed' => ['label' => 'Confirmed Events',  'status' => 'approved',  'higher' => 'good',    'hint' => 'Approved and paid'],
    'pending'   => ['label' => 'Pending Approval',  'status' => 'pending',   'higher' => 'neutral', 'hint' => 'Waiting for your review'],
    'payment'   => ['label' => 'Payment to Verify', 'status' => 'payment',   'higher' => 'neutral', 'hint' => 'GCash proof to check'],
    'cancelled' => ['label' => 'Cancelled Events',  'status' => 'cancelled', 'higher' => 'bad',     'hint' => 'Cancelled and declined'],
  ],

  // "Needs your attention": how many rows to show before "View all".
  'attention_limit' => 5,

  // Button text per booking status in the attention list (keys of config/admin/bookings.php 'statuses').
  'attention_actions' => [
    'pending'         => 'Review',
    'pending_payment' => 'Verify payment',
  ],

  // "Upcoming events": how many rows to show.
  'upcoming_limit' => 5,

  // Package Rate donut: the largest N packages get their own slice, the rest are grouped as "Other".
  'chart_top' => 5,
];
