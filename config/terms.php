<?php

/*
 * The Terms and Conditions text. ONE source for both places that show it:
 *   - the standalone page (GET /terms, route `terms`, resources/views/user/terms.blade.php), and
 *   - the "terms" view of the landing auth modal (resources/views/components/auth/terms-view.blade.php).
 * Both render it through <x-terms.content>, so editing the text here changes both.
 *
 * `version` is bumped whenever the wording changes. Nothing stores it yet; it is here so that the day the
 * sign-up starts recording what a user agreed to (terms_version / terms_accepted_at), the value already exists.
 *
 * `body` is a list of blocks: a string is a paragraph, ['list' => [...]] is a bulleted list.
 */
return [

  'version' => '2026-11-03',

  'title' => 'Terms and Conditions',

  'subtitle' => 'Lights and Sounds | Stage | LED Wall | Truss System | Event Technical Services',

  'intro' => 'These Terms and Conditions explain the rules and responsibilities of J&G Audio Pro Lights & Sounds and our clients. Our goal is to provide quality service, clear communication, and a smooth event experience.',

  'sections' => [
    [
      'title' => 'Booking and Reservation',
      'body' => [
        'Clients must provide the correct event date, time, venue, and requested services.',
        'A 30% down payment is required for every booking to reserve the event date. The booking will be confirmed once the payment is received and acknowledged by J&G Audio Pro Lights & Sounds.',
      ],
    ],
    [
      'title' => 'Payment Terms',
      'body' => [
        'The remaining 70% balance must be paid based on the payment schedule agreed upon by both parties.',
        'Any extra services or equipment not included in the original quotation may have additional charges. All payments and additional costs must be clearly discussed with the client.',
      ],
    ],
    [
      'title' => 'Cancellation and Rescheduling',
      'body' => [
        'If the client needs to cancel or change the event date, they must inform J&G Audio Pro as early as possible.',
        'Rescheduling depends on the availability of our equipment and team. Any cancellation charges or refund arrangements will depend on the agreed terms, actual expenses, and applicable Philippine laws.',
      ],
    ],
    [
      'title' => 'Services Included',
      'body' => [
        'J&G Audio Pro will provide the equipment and services listed in the approved quotation. These may include:',
        ['list' => [
          'Lights and sounds rental',
          'Stage',
          'LED Wall rental',
          'Trusses',
          'Technical crew',
          'Equipment delivery, setup, operation, and dismantling, if included',
        ]],
        'Any additional equipment or service must be discussed and approved by both parties.',
      ],
    ],
    [
      'title' => 'Setup and Dismantling',
      'body' => [
        'The client must make sure that the venue is ready and accessible at the agreed setup time.',
        'Our team must have enough time to install, test, and prepare the equipment before the event starts. Dismantling will follow the agreed schedule.',
        'Delays caused by late venue access or changes requested by the client may affect the setup schedule.',
      ],
    ],
    [
      'title' => 'Additional Charges',
      'body' => [
        'Additional charges may apply to overtime, extra equipment, transportation, additional manpower, or special setup requirements if these are not included in the package.',
        'J&G Audio Pro will inform the client about these charges and get approval before proceeding whenever reasonably possible.',
      ],
    ],
    [
      'title' => 'Client Responsibilities',
      'body' => [
        'The client must provide correct event details and coordinate with the venue regarding access, permits, setup space, and other requirements.',
        'The client must also inform J&G Audio Pro about any changes that may affect the setup or event schedule.',
      ],
    ],
    [
      'title' => 'Equipment Safety and Damage',
      'body' => [
        'J&G Audio Pro will take proper care of its equipment during delivery, setup, and operation.',
        'If equipment is lost or damaged due to the client’s actions or the actions of people under the client’s responsibility, the client may be asked to pay for reasonable repair or replacement costs, based on the facts and applicable law.',
        'Normal wear and tear and problems not caused by the client will not automatically be charged to them.',
      ],
    ],
    [
      'title' => 'Power Supply and Weather Conditions',
      'body' => [
        'The power supply and venue requirements must be discussed before the event.',
        'For outdoor events, proper protection against rain, wind, and other weather conditions must be arranged. J&G Audio Pro may stop equipment operation if the setup becomes unsafe.',
      ],
    ],
    [
      'title' => 'Delays and Unexpected Events',
      'body' => [
        'Unexpected situations such as severe weather, power interruptions, venue problems, or other emergencies may affect the event.',
        'Both parties must communicate properly and discuss possible solutions, such as adjusting the setup or rescheduling the event.',
      ],
    ],
    [
      'title' => 'Service Responsibility',
      'body' => [
        'J&G Audio Pro will do its best to provide the agreed services and maintain the quality of its setup and equipment.',
        'If a problem happens, our team will make reasonable efforts to address it. Any concerns or claims will be reviewed fairly based on the agreement and applicable laws.',
      ],
    ],
    [
      'title' => 'Refund Policy',
      'body' => [
        'Refund requests will be reviewed based on the reason for cancellation, the agreed terms, services already provided, reasonable expenses already paid, and applicable laws.',
        'The 30% down payment is not automatically non-refundable in every situation. Refunds must be handled fairly and in accordance with applicable Philippine laws.',
      ],
    ],
    [
      'title' => 'Changes to the Package',
      'body' => [
        'Any changes to the event date, venue, schedule, equipment, or package must be discussed with J&G Audio Pro.',
        'If the changes affect the total price, an updated quotation must be provided and approved by both parties.',
      ],
    ],
    [
      'title' => 'Agreement and Acceptance',
      'body' => [
        'By confirming the booking, accepting the quotation, or signing the service agreement, the client confirms that they have read and understood the applicable Terms and Conditions.',
        'Any special arrangements must be clearly discussed and recorded in writing.',
      ],
    ],
    [
      'title' => 'Disputes and Philippine Laws',
      'body' => [
        'If a disagreement happens, both parties should first try to settle the issue through proper communication and discussion.',
        'If no agreement can be reached, either party may use the proper legal process under Philippine law.',
      ],
    ],
  ],

  'tagline' => 'Professional Service. Reliable Setup. Memorable Events.',
];
