<?php

// Booking History registry.
// so a new state, tab or sort is one edit, not a Blade edit. Booking DATA comes from the controller.
return [

  // Which bookings belong on this page. The CONTROLLER applies this rule (the view shows whatever it
  // is given); the stub route applies it too, so you can see it working.
  //
  //   - cancelled / declined bookings always belong (they no longer need a decision), and
  //   - approved / completed bookings belong once a payment is verified (`payment.state` below).
  //     On the Bookings page that is the "Verify Payment" action: it turns Payment to Verify
  //     (`pending_payment`) into Approved, and the booking shows up here from then on.
  //
  // 'partial' = a verified down payment. Remove it from this list if only fully paid bookings
  // should appear here.
  // `user_cancelled` (the client cancelled it themselves) is terminal too, and it is the one
  // status that gets its OWN group below instead of sharing the `cancelled` one.
  'terminal_statuses' => ['cancelled', 'declined', 'user_cancelled'],
  'history_statuses'  => ['approved', 'completed'],
  'paid_states'       => ['paid', 'partial'],

  // Terminal statuses that keep their own group (and so their own tab in 'tabs' below) instead
  // of being folded into `cancelled`. The row still reads its badge/label from
  // config/admin/bookings.php, only the group and the rank come from 'phases'.
  'own_group_statuses' => ['user_cancelled'],

  // Timeline phase of a booking, worked out from its dates on the page (never stored):
  //   Upcoming  = today is before the start date
  //   Ongoing   = today is between the start and end date, inclusive
  //   Completed = from the day AFTER the end date
  // Cancelled and Declined are not phases: they keep their own badge from config/admin-bookings.php
  // and share the `cancelled` group, so these two keys only add the rank and the edit lock.
  // `user_cancelled` is the exception (see 'own_group_statuses' above): the same rank and lock,
  // but the row keeps its OWN group, which is what the "User Cancelled" tab filters on.
  //
  // rank        => Default sort order (lowest first)
  // group       => tab the row belongs to (matches 'tabs' below)
  // highlight   => draws an outline + chip on the row; highlight_class / chip / chip_class style it
  // editable    => no longer read: the History modal is view-only for every phase (row sets editable = ! $history)
  // icon        => Phosphor icon name (ph-<icon>) inside the badge; decorative
  // badge       => the read-only badge in the Status column (tone: brand | success | warning | danger | neutral)
  'phases' => [
    'ongoing' => [
      'label'           => 'Ongoing',
      'icon'            => 'broadcast',
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
      'icon'      => 'calendar-blank',
      'group'     => 'upcoming',
      'rank'      => 1,
      'editable'  => true,
      'highlight' => false,
      'badge'     => ['tone' => 'brand', 'solid' => false],
    ],
    'completed' => [
      'label'     => 'Completed',
      'icon'      => 'checks',
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
    'user_cancelled' => [
      'rank'     => 3,
      'editable' => false,
    ],
  ],

  // State tabs above the filters. Keys must match the `group` values above ('all' shows everything).
  'tabs' => [
    'all'            => 'All',
    'ongoing'        => 'Ongoing',
    'upcoming'       => 'Upcoming',
    'completed'      => 'Completed',
    'cancelled'      => 'Cancelled', // includes Declined
    'user_cancelled' => 'User Cancelled', // cancelled by the client from My Bookings
  ],

  // "Sorted by:" dropdown. Keys are handled in resources/js/admin/history.js.
  'sorts' => [
    'default' => 'Default',               // Ongoing, Upcoming (soonest first), Completed, Cancelled (latest first)
    'alpha'   => 'Alphabetical',          // client name, A to Z
    'date'    => 'Date, soonest first',   // event start date
    'recent'  => 'Date, latest first',    // event start date
  ],
];
