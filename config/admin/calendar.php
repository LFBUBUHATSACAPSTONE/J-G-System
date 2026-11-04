<?php

// Calendar page registry.
// so a new status or holiday is one edit, not a Blade edit. Booking DATA comes from the controller.
return [

  // Which booking statuses are LISTED on the calendar (side panel). Cancelled and declined are left off.
  // tone  => badge colour of the card in the side panel
  // label => badge text in the side panel
  'statuses' => [
    'approved'        => ['tone' => 'confirmed', 'label' => 'Confirmed'],
    'completed'       => ['tone' => 'confirmed', 'label' => 'Completed'],
    'pending'         => ['tone' => 'pending',   'label' => 'Pending Approval'],
    'pending_payment' => ['tone' => 'pending',   'label' => 'Payment to Verify'],
  ],

  // Which tones COLOUR a date (and build the legend), in priority order. Only 'confirmed' is listed:
  // a date shows (green) only when it holds an approved / completed booking. Tapping it lists every
  // booking on that date, pending ones included. A date with ONLY pending bookings is not coloured and
  // not clickable. Add 'pending' => 'Pending' back here to colour those dates yellow again.
  'tones' => [
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
