<?php
return [

  // Shown after the page label in the browser tab: "Bookings | J&G Admin"
  'name' => 'J&G Admin',

  'default_description' => 'J&G Audio admin panel for managing bookings, packages and messages.',

  'author' => 'J&G Audio',

  // Used for the favicon. Path is relative to /public.
  'logo' => 'images/logo/J&G_official_logo.webp',

  // Admin is private: these defaults apply to every admin page.
  // Per-page overrides go in 'pages' below.
  'noindex' => true,

  // Per-page details, keyed by route name (must match routes/admin.php).
  // title       => overrides the label taken from 'nav'
  // description => meta description for that page
  'pages' => [
    'admin.dashboard' => [
      'description' => 'Overview of upcoming events, pending bookings and recent activity.',
    ],
    'admin.bookings' => [
      'description' => 'Review, confirm or decline incoming booking requests and verify GCash payments.',
    ],
    'admin.messages' => [
      'title'       => 'Messages',
      'description' => 'Client inquiries and messages.',
    ],
    'admin.booking-history' => [
      'description' => 'Completed and cancelled bookings.',
    ],
    'admin.packages' => [
      'description' => 'Manage service packages, inclusions and pricing.',
    ],
    'admin.calendar' => [
      'description' => 'Event calendar and availability.',
    ],
    'admin.account' => [
      'description' => 'Admin account and security settings.',
    ],
  ],

  // component reads this, so adding a page is one entry here, not a Blade edit.
  //
  // route  => named route the link points to (unnamed routes break active state)
  // active => routeIs() pattern; defaults to the route name. Use a wildcard
  // ('admin.bookings*') so child pages keep the item highlighted.
  // icon   => Phosphor class suffix (ph-<icon>)
  'nav' => [
    ['label' => 'Dashboard',       'route' => 'admin.dashboard',       'active' => 'admin.dashboard',        'icon' => 'chart-bar'],
    ['label' => 'Bookings',        'route' => 'admin.bookings',        'active' => 'admin.bookings*',        'icon' => 'notebook'],
    ['label' => 'Message',         'route' => 'admin.messages',        'active' => 'admin.messages*',        'icon' => 'chat-text'],
    ['label' => 'Booking History', 'route' => 'admin.booking-history', 'active' => 'admin.booking-history*', 'icon' => 'clock-counter-clockwise'],
    ['label' => 'Packages',        'route' => 'admin.packages',        'active' => 'admin.packages*',        'icon' => 'package'],
    ['label' => 'Calendar',        'route' => 'admin.calendar',        'active' => 'admin.calendar*',        'icon' => 'calendar-dots'],
    ['label' => 'Account',         'route' => 'admin.account',         'active' => 'admin.account*',         'icon' => 'user-circle'],
  ],
];
