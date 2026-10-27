<?php

// Reason for cancelling or declining a booking (shared by the admin Bookings page and the
// client's My Bookings). FRONT END copy and limits: the modal's length rule and the headings in
// View Details. The backend should validate with the same numbers (docs/backend-impact.md).
return [

  // Length of the reason, in characters (after trimming). Read by the modal (data-min / data-max).
  'min' => 10,
  'max' => 500,

  // Heading above the reason in View Details, by the status the booking ended in.
  'labels' => [
    'declined'        => 'Reason for declining',
    'cancelled'       => 'Reason for cancellation',
    'admin_cancelled' => 'Reason for cancellation',
    'user_cancelled'  => 'Reason for cancellation',
  ],

  // Who gave the reason (admin side shows it; the client side shows "You" / "J&G").
  'by' => [
    'declined'        => 'J&G',
    'cancelled'       => 'J&G',
    'admin_cancelled' => 'J&G',
    'user_cancelled'  => 'Client',
  ],
];
