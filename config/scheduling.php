<?php

// Event capacity rules, shared by the user booking flow and the admin pages.
// The views read from here, so changing the limit is one edit. The real backend must enforce the
// same numbers:  the front end only reflects them.
return [

  // Most events that can be approved on one calendar day. A multi-day event counts once on
  // EVERY day it covers.
  'max_events_per_day' => 3,

  // Statuses that take up a slot. A booking only counts once the admin has approved it, so
  // `pending` never does. `pending_payment` already counts: the admin said yes at Approve, the
  // client is only waiting for the payment check, so the day stays held for them.
  // Cancelled / declined bookings are not listed, so they free their days.
  'counted_statuses' => ['pending_payment', 'approved'],
];
