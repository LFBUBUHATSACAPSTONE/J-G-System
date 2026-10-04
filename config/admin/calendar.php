<?php

// Calendar page registry. The view reads from here,
// so a new status or holiday is one edit, not a Blade edit. Booking DATA comes from the controller.
return [

  // Which booking statuses appear on the calendar, and how. Any status NOT listed here
  // (cancelled, declined) is left off, so those dates look free again.
  // tone  => key into 'tones' below (drives the cell colour)
  // label => badge text in the side panel
  'statuses' => [
    'approved'        => ['tone' => 'confirmed', 'label' => 'Confirmed'],
    'completed'       => ['tone' => 'confirmed', 'label' => 'Completed'],
    'pending'         => ['tone' => 'pending',   'label' => 'Pending'],
    'pending_payment' => ['tone' => 'pending',   'label' => 'Pending Pay'],
  ],

  // Cell colours, in PRIORITY order: when one date holds bookings of different tones, the first
  // tone listed wins. Pending is first because it is the one that needs the admin's action.
  // The same list builds the legend.
  'tones' => [
    'pending'   => 'Pending',
    'confirmed' => 'Confirmed',
  ],

  // Fixed-date Philippine holidays, keyed 'm-d'. Movable ones (Holy Week, Eid, National Heroes
  // Day...) change every year: the controller passes them as an optional $holidays variable,
  // keyed 'Y-m-d'. That list wins over this one on the same date.
  'holidays' => [
    '01-01' => "New Year's Day",
    '02-25' => 'EDSA Anniversary',
    '04-09' => 'Araw ng Kagitingan',
    '05-01' => 'Labor Day',
    '06-12' => 'Independence Day',
    '08-21' => 'Ninoy Aquino Day',
    '11-01' => "All Saints' Day",
    '11-02' => "All Souls' Day",
    '11-30' => 'Bonifacio Day',
    '12-08' => 'Immaculate Conception',
    '12-24' => 'Christmas Eve',
    '12-25' => 'Christmas Day',
    '12-30' => 'Rizal Day',
    '12-31' => "New Year's Eve",
  ],
];
