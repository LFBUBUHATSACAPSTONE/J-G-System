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

  // Printed before every price: "Php 5,000".
  'currency_prefix' => 'Php',
];
