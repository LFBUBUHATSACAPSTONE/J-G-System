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
  // `pending_cancellation` also counts: the client asked to cancel, but until an admin decides
  // the booking is still active and must keep its slot (declining restores it unchanged).
  // Cancelled / declined bookings are not listed, so they free their days.
  'counted_statuses' => ['pending_payment', 'approved', 'pending_cancellation'],

  // Required break between two bookings that share a calendar day, measured from the end
  // of one event to the start of the next (and mirrored on the other side, so a new
  // booking may not start right before an existing one either). An event ending at 3 PM
  // therefore keeps 3 PM - 5 PM on hold with the default 120 minutes. 0 disables the
  // break and leaves only the overlap check.
  'min_gap_minutes' => 120,
];
