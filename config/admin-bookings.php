<?php

// Bookings page registry. Same idea as config/admin.php: the Blade views read from
// here, so adding a status or a sort option is one edit, not a Blade edit.
// Booking DATA is not here: it comes from the controller.
return [

  // How each booking status looks and what the admin can do with it.
  //
  // label     => text shown in the status cell (and on the "Pending" chip)
  // group     => which Status-filter option it belongs to (see 'status_filters')
  // highlight => true draws the yellow outline + chip on the row
  // actions   => outline buttons (POST to admin.bookings.status). `tone`: danger | success.
  //              Danger actions ask for confirmation first.
  // badge     => non-clickable status pill. `tone`: success | warning | danger | neutral;
  //              `solid` fills it. A row with no actions gets a full-width badge.
  'statuses' => [
    'pending' => [
      'label'     => 'Pending',
      'group'     => 'pending',
      'highlight' => true,
      'actions'   => [
        ['action' => 'decline', 'label' => 'Decline', 'tone' => 'danger'],
        ['action' => 'approve', 'label' => 'Approve', 'tone' => 'success'],
      ],
      'badge' => null,
    ],
    'pending_payment' => [
      'label'     => 'Pending Pay',
      'group'     => 'pending',
      'highlight' => false,
      'actions'   => [
        ['action' => 'cancel', 'label' => 'Cancel', 'tone' => 'danger'],
      ],
      'badge' => ['tone' => 'warning', 'solid' => true],
    ],
    'approved' => [
      'label'     => 'Approved',
      'group'     => 'approved',
      'highlight' => false,
      'actions'   => [
        ['action' => 'cancel', 'label' => 'Cancel', 'tone' => 'danger'],
      ],
      'badge' => ['tone' => 'success', 'solid' => true],
    ],
    'completed' => [
      'label'     => 'Completed',
      'group'     => 'completed',
      'highlight' => false,
      'actions'   => [],
      'badge'     => ['tone' => 'success', 'solid' => false],
    ],
    'cancelled' => [
      'label'     => 'Cancelled',
      'group'     => 'cancelled',
      'highlight' => false,
      'actions'   => [],
      'badge'     => ['tone' => 'danger', 'solid' => false],
    ],
    'declined' => [
      'label'     => 'Declined',
      'group'     => 'cancelled',
      'highlight' => false,
      'actions'   => [],
      'badge'     => ['tone' => 'danger', 'solid' => false],
    ],
  ],

  // "Status:" dropdown. Keys must match the `group` values above.
  'status_filters' => [
    'all'       => 'All',
    'approved'  => 'Approved',
    'pending'   => 'Pending',
    'cancelled' => 'Cancelled',
    'completed' => 'Completed',
  ],

  // "Sorted by:" dropdown. Keys are handled in resources/js/admin/bookings.js.
  'sorts' => [
    'default' => 'Default',      // the order the backend sent
    'alpha'   => 'Alphabetical', // client name, A to Z
    'date'    => 'Date',         // event date, soonest first
  ],

  // Venue type choices in the booking modal (same values as the user-side form).
  'venue_types' => ['Indoor', 'Outdoor', 'Both'],
];
