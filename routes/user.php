<?php

// User-side routes. One file per section lives in routes/user/ (front-end stubs today, the real
// controllers as they are connected) and is required here. web.php requires THIS file, so the
// chain is:
//
//   web.php  ->  user.php  ->  user/<section>.php
//
// Route NAMES (user.landing, login, booking.client-information, ...) must not change: the Blade
// views and the front-end JS build their URLs from them. See docs/user-routes.md.
//
// Order matters inside a section (see auth.php), and sections run in the order below.
require __DIR__ . '/user/landing.php';
require __DIR__ . '/user/auth.php';
require __DIR__ . '/user/booking.php';
