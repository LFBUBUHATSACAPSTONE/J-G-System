<?php

// Bookings page registry.
// here, so adding a status or a sort option is one edit, not a Blade edit.
// Booking DATA is not here: it comes from the controller.
return [

  // How each booking status looks and what the admin can do with it.
  //
  // Lifecycle (the server decides each next status; the front end never changes one itself):
  //
  //   pending --Approve--> pending_payment --Verify Payment--> approved
  //      \--Decline--> declined       \--Cancel--> cancelled      approved --Cancel--> cancelled
  //
  // A booking reaches Booking History when its payment is verified (approved + payment.state
  // paid/partial) or when it is cancelled / declined: see config/admin-history.php. So
  // "Verify Payment" is the action that moves a booking from this page into History.
  //
  // label     => text of the status badge (and the Status-filter text if it has its own group)
  // icon      => Phosphor icon name (ph-<icon>) shown inside the badge; decorative, the label carries the meaning
  // group     => which Status-filter option it belongs to (see 'status_filters')
  // highlight => true draws the yellow outline + chip on the row
  // chip      => text of that chip (defaults to the label)
  // actions   => outline buttons in the Actions column (POST to admin.bookings.status with
  //              `action`). `tone`: danger | success. Danger actions ask for confirmation first;
  //              any action can add its own `confirm` text (":ref" becomes the booking reference).
  // badge     => the read-only status badge in the Status column, never clickable.
  //              `tone`: success | warning | danger | brand | neutral; `solid` = strong fill (used sparingly).
  'statuses' => [
    'pending' => [
      'label'     => 'Pending Approval',
      'icon'      => 'hourglass-medium',
      'group'     => 'pending',
      'highlight' => true,
      'chip'      => 'Needs Review',
      'actions'   => [
        ['action' => 'decline', 'label' => 'Decline', 'tone' => 'danger'],
        ['action' => 'approve', 'label' => 'Approve', 'tone' => 'success'],
      ],
      'badge' => ['tone' => 'warning', 'solid' => false],
    ],
    // Key stays `pending_payment` (routes, calendar and History use it); only the label changed.
    // Meaning: the admin approved the request, the client's GCash proof (reference number +
    // receipt) is in, and the admin still has to check it.
    'pending_payment' => [
      'label'     => 'Payment to Verify',
      'icon'      => 'receipt',
      'group'     => 'payment',
      'highlight' => false,
      'actions'   => [
        ['action' => 'cancel', 'label' => 'Cancel', 'tone' => 'danger'],
        [
          'action'  => 'verify',
          'label'   => 'Verify Payment',
          'tone'    => 'success',
          'confirm' => 'Mark the payment for booking :ref as verified? The booking becomes Approved and moves to Booking History.',
        ],
      ],
      'badge' => ['tone' => 'warning', 'solid' => false],
    ],
    'approved' => [
      'label'     => 'Approved',
      'icon'      => 'check-circle',
      'group'     => 'approved',
      'highlight' => false,
      'actions'   => [
        ['action' => 'cancel', 'label' => 'Cancel', 'tone' => 'danger'],
      ],
      'badge' => ['tone' => 'success', 'solid' => false],
    ],
    'completed' => [
      'label'     => 'Completed',
      'icon'      => 'checks',
      'group'     => 'completed',
      'highlight' => false,
      'actions'   => [],
      'badge'     => ['tone' => 'success', 'solid' => false],
    ],
    'cancelled' => [
      'label'     => 'Cancelled',
      'icon'      => 'x-circle',
      'group'     => 'cancelled',
      'highlight' => false,
      'actions'   => [],
      'badge'     => ['tone' => 'danger', 'solid' => false],
    ],
    'declined' => [
      'label'     => 'Declined',
      'icon'      => 'prohibit',
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
    'pending'   => 'Pending Approval',
    'payment'   => 'Payment to Verify',
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
