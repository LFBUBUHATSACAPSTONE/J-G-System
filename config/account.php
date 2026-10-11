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
  //   modal  => optional, id of a modal the tab opens instead of linking to a route (no `route` needed)
  'nav' => [
    ['label' => 'Profile',        'icon' => 'user',          'route' => 'user.account.profile'],
    ['label' => 'My Bookings',    'icon' => 'calendar-check', 'route' => 'user.account.bookings', 'active' => 'user.account.bookings*'],
    ['label' => 'Payments',       'icon' => 'wallet',        'route' => 'user.account.payments'],
    ['label' => 'Messages',       'icon' => 'chat-circle',   'route' => 'user.account.messages'],
    ['label' => 'Security',       'icon' => 'lock-key',      'route' => 'user.account.security'],
    // `modal` instead of `route`: this tab opens the confirmation modal (views/components/account/delete-modal.blade.php)
    // on whatever account page the user is on, so it needs no page and no route of its own.
    ['label' => 'Delete Account', 'icon' => 'trash',         'modal' => 'deleteAccountModal', 'danger' => true],
  ],

  // Per-page details, keyed by route name.
  //   title       => browser tab label (falls back to the nav label)
  //   heading     => big gradient title at the top of the page
  //   subheading  => caption under the heading
  //   description => meta description
  'pages' => [
    'user.account.messages' => [
      'heading'     => 'Messages',
      'subheading'  => 'Chat with J&G Audio Lights and Sounds',
      'description' => 'Message J&G Audio Lights and Sounds about your packages and bookings.',
    ],
    'user.account.bookings' => [
      'heading'     => 'My Bookings',
      'subheading'  => 'Track your requests, payments and event dates',
      'description' => 'See the status of your J&G Audio Lights and Sounds bookings, cancel one, or reschedule it.',
    ],
    'user.account.security' => [
      'heading'     => 'Security',
      'subheading'  => 'Change your password and see how you sign in',
      'description' => 'Change your J&G Audio Lights and Sounds password and see your sign-in methods.',
    ],
    'user.account.profile' => [
      'heading'     => 'Profile',
      'subheading'  => 'Keep your contact details up to date',
      'description' => 'Update your name, phone number and address.',
    ],
  ],

  // Delete Account modal (views/components/account/delete-modal.blade.php, js/account/delete.js).
  // Copy only: the server decides what deletion really does. The list follows the proposal in
  // docs/user-account.md and must be reworded if the backend rule changes.
  //   confirm_word => what the user must type before the button unlocks
  //   items        => what happens, one line each (icon = Phosphor name)
  'delete' => [
    'confirm_word' => 'DELETE',
    'warning'      => 'Deleting your account is permanent. You will not be able to log in again with this email.',
    'items'        => [
      ['icon' => 'user-minus',  'text' => 'Your name, contact number and address are removed from our records.'],
      ['icon' => 'calendar-x',  'text' => 'Active bookings must be cancelled before you can delete your account.'],
      ['icon' => 'archive',     'text' => 'Past booking records are kept for J&G\'s business records, without your personal details.'],
    ],
  ],

  // My Bookings registry (views/user/account/bookings.blade.php, js/account/bookings.js). Booking
  // DATA is not here: it comes from the controller. The server decides every status change; the
  // front end only shows what it is given.
  //   label => text of the status badge (what the CLIENT sees, not the admin wording)
  //   icon  => Phosphor icon name (ph-<icon>); decorative, the label carries the meaning
  //   tone  => badge colour: success | warning | danger | brand | neutral
  //   group => tab: `active` is Upcoming until the last event day has passed, then Past;
  //            `past` and `cancelled` are fixed.
  'bookings' => [
    'statuses' => [
      'pending'         => ['label' => 'Awaiting approval',  'icon' => 'hourglass-medium', 'tone' => 'warning', 'group' => 'active'],
      'pending_payment' => ['label' => 'Payment under review', 'icon' => 'receipt',        'tone' => 'warning', 'group' => 'active'],
      'approved'        => ['label' => 'Confirmed',          'icon' => 'check-circle',     'tone' => 'success', 'group' => 'active'],
      'completed'       => ['label' => 'Completed',          'icon' => 'checks',           'tone' => 'success', 'group' => 'past'],
      'user_cancelled'  => ['label' => 'Cancelled by you',   'icon' => 'x-circle',         'tone' => 'danger',  'group' => 'cancelled'],
      'cancelled'       => ['label' => 'Cancelled',          'icon' => 'x-circle',         'tone' => 'danger',  'group' => 'cancelled'],
      'declined'        => ['label' => 'Declined',           'icon' => 'prohibit',         'tone' => 'danger',  'group' => 'cancelled'],
    ],

    // Statuses the client can still cancel (only before the event starts). The front end only
    // decides whether to show the button; the backend must enforce the same rule.
    'cancellable' => ['pending', 'pending_payment', 'approved'],

    // Tabs above the list. Keys match the `group` values above ('all' shows everything).
    'tabs' => [
      'all'       => 'All',
      'upcoming'  => 'Upcoming',
      'past'      => 'Past',
      'cancelled' => 'Cancelled',
    ],

    // payment.state => text shown next to the payment method.
    'payment_states' => [
      'paid'    => 'Fully paid',
      'partial' => 'Down payment paid',
      'pending' => 'Awaiting verification',
    ],
  ],
];
