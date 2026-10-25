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
  // The client may ask to cancel from My Bookings. That request moves the booking to
  // `pending_cancellation` and waits for the admin's answer in View Details:
  // Approve -> `cancelled`, or Decline -> the status the booking had before the request.
  // A direct admin Cancel (below) also ends in `cancelled`, with the admin's reason stored.
  //
  // A booking reaches Booking History when its payment is verified (approved + payment.state
  // paid/partial) or when it is cancelled / declined / cancelled by the client: see
  // config/admin/history.php. So "Verify Payment" is the action that moves a booking from this
  // page into History.
  //
  // label     => text of the status badge (and the Status-filter text if it has its own group)
  // icon      => Phosphor icon name (ph-<icon>) shown inside the badge; decorative, the label carries the meaning
  // group     => which Status-filter option it belongs to (see 'status_filters')
  // highlight => true draws the yellow outline + chip on the row
  // chip      => text of that chip (defaults to the label)
  // actions   => outline buttons in the Actions column (POST to admin.bookings.status with
  //              `action`). `tone`: danger | success. EVERY action opens the shared action modal
  //              (components/booking/action-modal.blade.php) before anything is sent. Its copy is the
  //              action's `modal` array (":ref" becomes the booking reference):
  //                title, message, confirm (button text),
  //                reason_label + reason_hint  => present = the modal also asks for a required reason
  //              (Decline and Cancel). The reason is posted as `reason` with the usual `action`.
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
        [
          'action' => 'decline',
          'label' => 'Decline',
          'tone' => 'danger',
          'modal'  => [
            'title'        => 'Decline this booking?',
            'message'      => 'Booking :ref will be declined. The client will see your reason in their booking details.',
            'confirm'      => 'Decline booking',
            'reason_label' => 'Reason for declining',
            'reason_hint'  => 'Shown to the client. Tell them why the request cannot be accepted.',
          ],
        ],
        [
          'action' => 'approve',
          'label' => 'Approve',
          'tone' => 'success',
          'modal'  => [
            'title'   => 'Approve this booking?',
            'message' => 'Booking :ref will be approved and the client will be asked to send their payment for verification.',
            'confirm' => 'Approve booking',
          ],
        ],
      ],
      'badge' => ['tone' => 'warning', 'solid' => false],
    ],
    // Key stays `pending_payment` (routes, calendar and History use it); only the label changed.
    // Meaning: the admin approved the request, the client's payment proof (reference number +
    // receipt) is in, and the admin still has to check it.
    'pending_payment' => [
      'label'     => 'Payment to Verify',
      'icon'      => 'receipt',
      'group'     => 'payment',
      'highlight' => false,
      'actions'   => [
        [
          'action' => 'cancel',
          'label' => 'Cancel',
          'tone' => 'danger',
          'modal'  => [
            'title'        => 'Cancel this booking?',
            'message'      => 'Booking :ref will be cancelled. The client will see your reason in their booking details.',
            'confirm'      => 'Cancel booking',
            'reason_label' => 'Reason for cancellation',
            'reason_hint'  => 'Shown to the client. Tell them why the booking is being cancelled.',
          ],
        ],
        [
          'action'  => 'verify',
          'label'   => 'Verify Payment',
          'tone'    => 'success',
          'modal'   => [
            'title'   => 'Verify this payment?',
            'message' => 'Mark the payment for booking :ref as verified? The booking becomes Approved and moves to Booking History.',
            'confirm' => 'Verify payment',
          ],
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
        [
          'action' => 'cancel',
          'label' => 'Cancel',
          'tone' => 'danger',
          'modal'  => [
            'title'        => 'Cancel this booking?',
            'message'      => 'Booking :ref will be cancelled. The client will see your reason in their booking details.',
            'confirm'      => 'Cancel booking',
            'reason_label' => 'Reason for cancellation',
            'reason_hint'  => 'Shown to the client. Tell them why the booking is being cancelled.',
          ],
        ],
      ],
      'badge' => ['tone' => 'success', 'solid' => false],
    ],
    // The client asked to cancel. Its Badge is a warning, not a danger, until an admin answers
    // in View Details. No row buttons: the decision belongs in the modal, where the client's
    // reason is visible next to the two buttons.
    'pending_cancellation' => [
      'label'     => 'Cancellation Requested',
      'icon'      => 'clock',
      'group'     => 'cancellation',
      'highlight' => true,
      'chip'      => 'Cancellation Request',
      'actions'   => [],
      'badge'     => ['tone' => 'warning', 'solid' => false],
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
    // Kept for rows cancelled by an earlier version of the direct-cancel flow. New direct
    // cancellations use `cancelled` plus admin_cancellation_reason (null action).
    'admin_cancelled' => [
      'label'     => 'Cancelled by Admin',
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
    // The CLIENT cancelled it from My Bookings (Account\AccountBookingController), so it is
    // tracked apart from the Cancelled the admin performs here. It has its own Status-filter
    // option ('user_cancelled' below) and its own Booking History tab.
    'user_cancelled' => [
      'label'     => 'User Cancelled',
      'icon'      => 'x-circle',
      'group'     => 'user_cancelled',
      'highlight' => false,
      'actions'   => [],
      'badge'     => ['tone' => 'danger', 'solid' => false],
    ],
  ],

  // "Status:" dropdown. Keys must match the `group` values above.
  'status_filters' => [
    'all'            => 'All',
    'approved'       => 'Approved',
    'pending'        => 'Pending Approval',
    'cancellation'   => 'Cancellation Requests',
    'payment'        => 'Payment to Verify',
    'cancelled'      => 'Cancelled',
    'user_cancelled' => 'User Cancelled',
    'completed'      => 'Completed',
  ],

  // "Sorted by:" dropdown. Keys are handled in resources/js/admin/bookings.js.
  'sorts' => [
    'default' => 'Default',      // the order the backend sent
    'alpha'   => 'Alphabetical', // client name, A to Z
    'date'    => 'Date',         // event date, soonest first
  ],

  // Venue type choices in the booking modal (same values as the user-side form).
  'venue_types' => ['Indoor', 'Outdoor', 'Both'],

  // Event Type dropdown in the View Details modal. Same list, same order as the user-side booking
  // form (resources/views/components/booking/event-information.blade.php). "Others" turns the
  // dropdown into a text field (see booking-modal.blade.php); keep it last.
  'event_types' => ['Baby Shower', 'Bridal Shower', 'Birthday Party', 'Concert', 'Family Reunion', 'Team-Building Event', 'Wedding', 'Others'],
];
