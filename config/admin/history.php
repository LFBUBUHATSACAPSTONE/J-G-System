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
  // Legacy `user_cancelled` records remain in history under the shared `cancelled` group.
  'terminal_statuses' => ['cancelled', 'admin_cancelled', 'declined', 'user_cancelled'],
  'history_statuses'  => ['approved', 'completed'],
  'paid_states'       => ['paid', 'partial'],

  // Timeline phase of a booking, worked out from its dates on the page (never stored):
  //   Upcoming  = today is before the start date
  //   Ongoing   = today is between the start and end date, inclusive
  //   Completed = from the day AFTER the end date
  // Cancelled and Declined are not phases: they keep their own badge from config/admin-bookings.php
  // and share the `cancelled` group, so these keys only add the rank and the edit lock.
  //
  // rank        => Default sort order (lowest first)
  // group       => tab the row belongs to (matches 'tabs' below)
  // highlight   => draws an outline + a one-line banner at the top of the row; highlight_class / chip (banner text) / chip_icon / chip_class style it
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
      'chip_icon'       => 'broadcast',
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
  ],

  // State tabs above the filters. Keys must match the `group` values above ('all' shows everything).
  'tabs' => [
    'all'            => 'All',
    'ongoing'        => 'Ongoing',
    'upcoming'       => 'Upcoming',
    'completed'      => 'Completed',
    'cancelled'      => 'Cancelled', // includes Declined
  ],

  // "Sorted by:" dropdown. Keys are handled in resources/js/admin/history.js.
  'sorts' => [
    'default' => 'Default',               // Ongoing, Upcoming (soonest first), Completed, Cancelled (latest first)
    'alpha'   => 'Alphabetical',          // client name, A to Z
    'date'    => 'Date, soonest first',   // event start date
    'recent'  => 'Date, latest first',    // event start date
  ],

  // Date range filter (From / To inputs and quick chips). Text lives here so the views hard-code none.
  // The range matches a booking when its event dates (data-date to data-end-date) overlap it.
  // Chips use Asia/Manila dates; the week starts on Monday. Handled in resources/js/admin/history.js.
  'date_filter' => [
    'label'        => 'Event date',
    'from'         => 'From',
    'to'           => 'To',
    'clear'        => 'Clear dates',
    'all'          => 'All',   // pill text while no range is set ("Event date: All")
    'done'         => 'Done',  // closes the From / To panel; filtering is already live
    'chips'        => [
      'today'     => 'Today',
      'tomorrow'  => 'Tomorrow',
      'this_week' => 'This week',
    ],
    // :from and :to become formatted dates (e.g. "Nov 10"). The first two are used when only one end is set.
    'empty'        => 'No bookings from :from to :to',
    'empty_from'   => 'No bookings from :from onward',
    'empty_to'     => 'No bookings up to :to',
    'empty_single' => 'No bookings on :from',
    // Removable chip in the phone filter sheet and its badge ("Event date: Nov 10 – Nov 13").
    'summary'      => [
      'range'  => ':from – :to',
      'single' => ':from',
      'from'   => 'From :from',
      'to'     => 'Up to :to',
    ],
  ],
];
