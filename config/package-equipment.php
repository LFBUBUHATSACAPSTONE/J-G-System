<?php

// Package equipment: the rows of the landing "Package Comparison Table" and the Equipment fields of the
// admin New / Edit Package modal. Both read from here, so adding or renaming a row is one edit in this file.
//
// sections => the groups shown in the table and in the modal.
//   key   => group id (used for element ids only)
//   title => group heading
//   rows  => row_key => label. The row_key is the field name the modal sends: equipment[row_key].
//            Keep a row_key once it is in use: a package's saved values are stored under it.
//
// A package's values are a map of row_key => text, for example ['main_speaker' => '2 Units'].
//   Blank / missing   => the table prints the empty mark below.
//   One of yes_values => the table prints a check mark (type "Yes" in the modal).
//   Anything else     => printed as typed ("4 Units + Subwoofer").
//
// defaults => the values the six original packages had when the table was hard-coded, keyed by package id.
// They are used only while a package has no saved `equipment` (the key is missing or null). A package that
// has `equipment` (even an empty array, as a new package does) never falls back to them.
return [

  'sections' => [
    [
      'key' => 'audio',
      'title' => 'Audio System',
      'rows' => [
        'audio_system' => 'Audio System',
        'main_speaker' => 'Main Speaker (FOH)',
        'wireless_mics' => 'Wireless Microphones',
        'wired_mics' => 'Wired Microphones',
        'mixer' => 'Mixer',
        'dj_controller' => 'DJ Controller + Laptop',
        'di_box' => 'DI Box / Band Instruments',
        'cables' => 'Cables & Accessories',
      ],
    ],
    [
      'key' => 'lighting',
      'title' => 'Lighting System',
      'rows' => [
        'ambient_lights' => 'Basic Ambient Lights',
        'par_led' => 'Par LED Effect',
        'moving_head' => 'Moving Head / Beam Lights',
        'fog' => 'Fog / Smoke Machine',
        'light_controller' => 'Lighting Controller',
        'truss' => 'Truss / Stands',
      ],
    ],
    [
      'key' => 'video',
      'title' => 'Video System',
      'rows' => [
        'led_wall' => 'LED Wall Display',
        'live_camera' => 'Live Feed Camera',
        'video_switcher' => 'Video Processor / Switcher',
      ],
    ],
  ],

  'yes_values' => ['yes', 'y', 'included', '✓'],

  'empty_mark' => '—',

  // Typed into a row, these would print as the word itself in the table instead of the empty mark, so the
  // modal asks the admin to leave the row blank. Compared in lower case.
  'blank_words' => ['no', 'none', 'n/a', 'na', 'nil', 'not included', '-', '–', '—'],

  'max_length' => 60,

  'defaults' => [
    'budget-lite' => [
      'audio_system' => 'Basic',
      'main_speaker' => '2 Units',
      'wireless_mics' => '2',
      'mixer' => 'Behringer 1204',
      'dj_controller' => 'Yes',
      'cables' => 'Yes',
      'ambient_lights' => 'Yes',
      'fog' => '1',
      'light_controller' => '1',
      'truss' => 'Basic',
    ],
    'budget-party' => [
      'audio_system' => 'Mid Setup',
      'main_speaker' => '2 Units + Subwoofer',
      'wireless_mics' => '4',
      'wired_mics' => '2',
      'mixer' => 'Mackie ProFX16',
      'dj_controller' => 'Yes',
      'cables' => 'Yes',
      'ambient_lights' => 'Yes',
      'par_led' => 'Yes',
      'moving_head' => 'SU600 Beam',
      'fog' => '2',
      'light_controller' => '2',
      'truss' => 'Yes',
    ],
    'budget-wedding' => [
      'audio_system' => 'Premium',
      'main_speaker' => '2 Units',
      'wireless_mics' => '4',
      'wired_mics' => '2',
      'mixer' => 'Mackie ProFX16',
      'dj_controller' => 'Yes',
      'cables' => 'Yes',
      'ambient_lights' => 'Yes',
      'par_led' => '18 Units',
      'moving_head' => 'SU600 Beam',
      'fog' => '2',
      'light_controller' => '2',
      'truss' => 'Truss',
      'led_wall' => '9x12 Lightlink',
      'live_camera' => 'Yes',
      'video_switcher' => 'Yes',
    ],
    'luxe-lite' => [
      'audio_system' => 'Full Band Ready',
      'main_speaker' => '4 Units + Subwoofer',
      'wireless_mics' => '4',
      'wired_mics' => '4',
      'mixer' => 'Mackie DL16SE',
      'dj_controller' => 'Yes',
      'di_box' => 'Acoustic Set Up',
      'cables' => 'Yes',
      'ambient_lights' => 'Yes',
      'par_led' => '20 Units',
      'moving_head' => '4 Beam 450',
      'fog' => '2 + Haze',
      'light_controller' => 'Mini Pearl',
      'truss' => '6 Truss',
      'led_wall' => '9×12 Gloshine',
      'live_camera' => 'Yes',
      'video_switcher' => 'Yes',
    ],
    'modern-glam' => [
      'audio_system' => 'Full Band Ready',
      'main_speaker' => '2 Units + Subwoofer',
      'wireless_mics' => '4',
      'wired_mics' => '4',
      'mixer' => 'Mackie DL16SE',
      'dj_controller' => 'Yes',
      'di_box' => 'Full band Set',
      'cables' => 'Yes',
      'ambient_lights' => 'Yes',
      'par_led' => '20 Units',
      'moving_head' => '6 Beam 450',
      'fog' => '2 + Haze',
      'light_controller' => 'Mini Pearl',
      'truss' => '6 Truss',
      'led_wall' => '9×12 GTOP',
      'live_camera' => 'Panasonic',
      'video_switcher' => 'Yes',
    ],
    'elite-symphony' => [
      'audio_system' => 'Full Band + Stage',
      'main_speaker' => '4 Units + Subwoofer',
      'wireless_mics' => '4',
      'wired_mics' => '4',
      'mixer' => 'Mackie DL16SE',
      'dj_controller' => 'Yes',
      'di_box' => 'Full band Set',
      'cables' => 'Yes',
      'ambient_lights' => 'Yes',
      'par_led' => '20 Units',
      'moving_head' => '8 Beam 450 + MAC aura',
      'fog' => '2 + Haze',
      'light_controller' => 'Mini Pearl',
      'truss' => '6 Truss',
      'led_wall' => '9×12 GTOP Waterproof',
      'live_camera' => 'Panasonic',
      'video_switcher' => 'Yes',
    ],
  ],

];
