<?php

// Admin Packages page: availability registry. Views read from here, so renaming a state or changing its colour is one edit, not a Blade edit.
//
// key => value sent by the Available / Unavailable buttons (`availability` field)
// label => button text
// tone => admin-pill tone (success | danger); the look is in resources/sass/admin/_packages.scss
// confirm => optional confirm() prompt before the form posts ({name} is the package name)
return [

  'availability' => [
    'available' => [
      'label' => 'Available',
      'tone' => 'success',
      'confirm' => null,
    ],
    'unavailable' => [
      'label' => 'Unavailable',
      'tone' => 'danger',
      'confirm' => 'Mark {name} as unavailable? Clients will not be able to select it when booking.',
    ],
  ],

  // Package cards on phones (below 768px): how many features always show; the rest sit behind "See more".
  // From 768px up every feature shows. Same idea as `previewCount` on the user-side package cards.
  'preview_features' => 2,

  // Package modal: the most the browser lets through before it shows a message. These mirror the server's
  // rules (name max 100, price max 10,000,000, features max 2000). Equipment rows use `max_length` in
  // config/package-equipment.php. The browser check is a convenience; the server still validates.
  'limits' => [
    'name' => 100,
    'price' => 10000000,
    'features' => 2000,
  ],

  // Printed before every price: "Php 5,000".
  'currency_prefix' => 'Php',
];
