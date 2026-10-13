<?php

// Messages registry (admin page + user-side chat widget). Views read from here, so changing a
// limit or a quick reply is one edit, not a Blade edit. Conversation DATA comes from the controller.
return [

  // Booking statuses that make a booking card in the chat grey ("finished"). A booking whose
  // end date has passed is also grey, whatever its status.
  'past_statuses' => ['completed', 'cancelled', 'declined'],

  // Booking statuses that get a card in EVERY chat thread (both sides). Only APPROVED
  // bookings are reflected. Each card is pinned at the exact instant its booking was
  // approved and never moves, so a later approval simply adds another card below.
  // `null`/[] would mean "every status"; keep this at approved for the intended flow.
  'reflected_booking_statuses' => ['approved'],

  // How long after sending a message its sender can still edit or unsend it, in minutes.
  // Outside the window the buttons disappear and the server refuses the change. 0 = no limit.
  'edit_window_minutes' => 15,

  // One-click replies above the admin composer. Clicking fills the box; the admin still presses Send.
  'quick_replies' => [
    'Your booking has been approved and confirmed.',
    'Please review your event details. If you would like to update or change any information, feel free to message us.',
    'Thank you! Your payment has been received.',
  ],

  // Same limits on the page (JS) and in the stub routes; the real backend must enforce them too.
  'attachments' => [
    'max_files' => 5,
    'max_kb'    => 5120,
    'mimes'     => 'jpg,jpeg,png,webp,pdf',
    'accept'    => 'image/jpeg,image/png,image/webp,application/pdf',
  ],

  'max_length' => 1000,
];
