<?php

/**
 * Per-route entries under "pages" are resolved automatically by the
 * anonymous Blade components <x-site-head> / <x-site-meta>, keyed off
 * Route::currentRouteName(). Every route rendering those components MUST
 * be a named route, or currentRouteName() returns null and the override
 * silently falls through to the default_title/default_description with
 * no error.
 */

return [
  'default_title' => 'J&G Audio Lights and Sounds - Booking & Admin Portal',
  'default_description' => 'J&G Audio Lights and Sounds equipment rental management for booking payments, equipment availability, maintenance requests, delivery scheduling, and announcements.',
  'author' => 'J&G Audio Lights and Sounds',
  'logo' => '/images/logo/J&G_official_logo',

  /*
    Per-route page metadata
  
    Keyed by named route. Add an entry here whenever a new page needs
    its own title/description/OG tags — do not hardcode these
    */
  'pages' => [

    'user.landing' => [
      'title' => 'J&G Audio Lights and Sounds - Sound & Lighting for Every Event',
      'description' => 'Professional audio and lighting solutions for parties, weddings, and concerts. Reliable equipment, expert service — book your event with J&G today.',
      'og_title'  => 'J&G Audio Lights and Sounds',
      'og_description' => 'Bring your event to life with high-quality sound and lighting, backed by reliable equipment and professional service.',
      'logo' => 'images/logo/J&G_official_logo.webp',
    ],

    'user.booking' => [
      'title' => 'Booking Portal - J&G Audio Lights and Sounds',
      'description' => 'Securely manage and complete payments for your event bookings with J&G Audio Lights and Sounds.',
      'og_title' => 'J&G Audio Lights and Sounds',
      'og_description' => 'Securely manage and complete payments for your event bookings with J&G Audio Lights and Sounds.',
      'logo' => 'images/logo/J&G_official_logo.webp',
      'noindex' => true,
    ]
  ],
];
