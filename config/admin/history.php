<?php

// Booking History registry. The views read from here,
// so a new state, tab or sort is one edit, not a Blade edit. Booking DATA comes from the controller.
return [

  // Which bookings belong on this page. The CONTROLLER applies this rule (the view shows whatever it
  // is given); the stub route applies it too, so you can see it working.
  //
  //   - cancelled / declined bookings always belong (they no longer need a decision), and
  //   - approved / completed bookings belong once a payment is verified (`payment.state` below).
  //
  // 'partial' = a verified down payment. Remove it from this list if only fully paid bookings
  // should appear here.
  'terminal_statuses' => ['cancelled', 'declined'],
  'history_statuses'  => ['approved', 'completed'],
  'paid_states'       => ['paid', 'partial'],

  // Timeline phase of a booking, worked out from its dates on the page (never stored):
  //   Upcoming  = today is before the start date
  //   Ongoing   = today is between the start and end date, inclusive
  //   Completed = from the day AFTER the end date
  // Cancelled and Declined are not phases: they keep their own pill from config/admin-bookings.php
  // and share the `cancelled` group, so these two keys only add the rank and the edit lock.
  //
  // rank        => Default sort order (lowest first)
  // group       => tab the row belongs to (matches 'tabs' below)
  // highlight   => draws an outline + chip on the row; highlight_class / chip / chip_class style it
  // editable    => false hides Edit in the View Details modal
  // badge       => the non-clickable pill in the Status column (tone: brand | success | warning | danger | neutral)
  'phases' => [
    'ongoing' => [
      'label'           => 'Ongoing',
      'group'           => 'ongoing',
      'rank'            => 0,
      'editable'        => true,
      'highlight'       => true,
      'highlight_class' => 'is-ongoing',
      'chip'            => 'Happening now',
      'chip_class'      => 'admin-bookings__chip--ongoing',
      'badge'           => ['tone' => 'brand', 'solid' => true],
    ],
    'upcoming' => [
      'label'     => 'Upcoming',
      'group'     => 'upcoming',
      'rank'      => 1,
      'editable'  => true,
      'highlight' => false,
      'badge'     => ['tone' => 'brand', 'solid' => false],
    ],
    'completed' => [
      'label'     => 'Completed',
      'group'     => 'completed',
      'rank'      => 2,
      'editable'  => false,
      'highlight' => false,
      'badge'     => ['tone' => 'success', 'solid' => false],
    ],
    'cancelled' => [
      'rank'     => 3,
      'editable' => false,
    ],
  ],

  // State tabs above the filters. Keys must match the `group` values above ('all' shows everything).
  'tabs' => [
    'all'       => 'All',
    'ongoing'   => 'Ongoing',
    'upcoming'  => 'Upcoming',
    'completed' => 'Completed',
    'cancelled' => 'Cancelled', // includes Declined
  ],

  // "Sorted by:" dropdown. Keys are handled in resources/js/admin/history.js.
  'sorts' => [
    'default' => 'Default',               // Ongoing, Upcoming (soonest first), Completed, Cancelled (latest first)
    'alpha'   => 'Alphabetical',          // client name, A to Z
    'date'    => 'Date, soonest first',   // event start date
    'recent'  => 'Date, latest first',    // event start date
  ],
];
