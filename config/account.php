<?php

// User account config. Same idea as config/admin.php: the account views read from here, so a
// label, icon or page description is one edit, not a Blade edit.
//
// This project covers the account FRONT END only. The backend and database are built separately.
return [

  // Shown after the page label in the browser tab: "Profile - J&G Account"
  'name' => 'J&G Account',

  'default_description' => 'Manage your J&G Audio Lights and Sounds profile, bookings and payments.',

  'author' => 'J&G Audio Lights and Sounds',

  // Used for the favicon. Path is relative to /public.
  'logo' => 'images/logo/J&G_official_logo.webp',

  // Account pages are private: never index them.
  'noindex' => true,

  // Sidebar tabs, in order. `route` is a named route. A tab whose route does not exist yet is
  // drawn disabled with a "Soon" tag (the sidebar checks Route::has()), so the full menu is
  // visible while the pages are built one by one. See docs/user-account.md for what each tab holds.
  //   label  => text in the sidebar
  //   icon   => Phosphor icon name (ph-<icon>)
  //   route  => named route the link points to
  //   active => optional route pattern that keeps the tab highlighted (defaults to `route`)
  //   danger => optional, styles the tab as a destructive action
  'nav' => [
    ['label' => 'Profile',        'icon' => 'user',          'route' => 'user.account.profile'],
    ['label' => 'My Bookings',    'icon' => 'calendar-check', 'route' => 'user.account.bookings', 'active' => 'user.account.bookings*'],
    ['label' => 'Payments',       'icon' => 'wallet',        'route' => 'user.account.payments'],
    ['label' => 'Messages',       'icon' => 'chat-circle',   'route' => 'user.account.messages'],
    ['label' => 'Security',       'icon' => 'lock-key',      'route' => 'user.account.security'],
    ['label' => 'Delete Account', 'icon' => 'trash',         'route' => 'user.account.delete', 'danger' => true],
  ],

  // Per-page details, keyed by route name.
  //   title       => browser tab label (falls back to the nav label)
  //   heading     => big gradient title at the top of the page
  //   subheading  => caption under the heading
  //   description => meta description
  'pages' => [
    'user.account.profile' => [
      'heading'     => 'Profile',
      'subheading'  => 'Keep your contact details up to date',
      'description' => 'Update your name, phone number and address.',
    ],
  ],
];
