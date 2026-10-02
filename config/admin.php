<?php

// Admin navigation registry.
// the sidebar component reads this, so adding a page is one entry here, not a Blade edit.
//
// route  => named route the link points to (unnamed routes break active state)
// active => routeIs() pattern; defaults to the route name. Use a wildcard ('admin.bookings*') so child pages keep the item highlighted.
// icon => Phosphor class suffix (ph-<icon>)
return [
  'nav' => [
    ['label' => 'Dashboard', 'route' => 'admin.dashboard', 'active' => 'admin.dashboard', 'icon' => 'chart-bar'],
    ['label' => 'Bookings', 'route' => 'admin.bookings', 'active' => 'admin.bookings*', 'icon' => 'notebook'],
    ['label' => 'Message', 'route' => 'admin.messages', 'active' => 'admin.messages*', 'icon' => 'chat-text'],
    ['label' => 'Booking History', 'route' => 'admin.booking-history', 'active' => 'admin.booking-history*', 'icon' => 'clock-counter-clockwise'],
    ['label' => 'Packages', 'route' => 'admin.packages', 'active' => 'admin.packages*', 'icon' => 'package'],
    ['label' => 'Calendar', 'route' => 'admin.calendar', 'active' => 'admin.calendar*', 'icon' => 'calendar-dots'],
    ['label' => 'Account', 'route' => 'admin.account', 'active' => 'admin.account*', 'icon' => 'user-circle'],
  ],
];
