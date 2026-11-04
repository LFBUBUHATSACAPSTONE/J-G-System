{{--
  Booking Confirmation step (booking flow, final). Phase 9F: styles are utilities, class strings in the PHP block below
  (was `.booking-confirmation*` in `booking/_booking-confirmation.scss`, removed).
  Read-only end state: no form, no fields. The JS (booking-confirmation.js) only needs `data-booking-continue`;
  `data-booking-reference` is kept as a hook. The step is centred in the space under the tracker by its wrapper in
  `user/booking.blade.php` (`$confirmViewClass`); this file centres the card in that wrapper.
--}}
@php
$stepClass = 'relative z-1 flex justify-center';
$cardClass = 'booking-card max-w-160 px-[1rem] py-6 text-center min-[576px]:p-6';
$checkClass = 'mx-auto mb-4 text-auth-accent';
$titleClass = 'text-fluid-confirm-title font-bold text-auth-accent [margin:0_0_1rem]';
$bodyClass = 'text-fluid-base leading-[1.6] text-auth-text [margin:0_0_1rem]';
$refClass = 'font-bold text-auth-accent';
$thanksClass = 'text-fluid-lg font-bold text-auth-accent [margin:1.5rem_0_0.5rem]';
$subtextClass = 'text-fluid-md leading-[1.6] text-auth-text-muted [margin:0_0_2rem]';
// Centred (this card has no side column): as wide as the button row of the other steps, at most 360px.
$actionsClass = 'mx-auto w-full max-w-90';
@endphp
<div class="{{ $stepClass }}">
  <div class="{{ $cardClass }}">
    <svg class="{{ $checkClass }}" width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <circle cx="32" cy="32" r="29" stroke="currentColor" stroke-width="3" />
      <path d="M32 18 V32 L41 38" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
    </svg>

    <h2 class="{{ $titleClass }}">Awaiting Verification</h2>

    <p class="{{ $bodyClass }}">
      Your booking request and selected payment amount have been recorded and are waiting for admin review.
      Your booking reference number is
      <span class="{{ $refClass }}" data-booking-reference>#{{ $referenceNumber ?? 'JG00000' }}</span>
    </p>

    <p class="{{ $bodyClass }}">
      Once your payment is verified, you will receive the official
      confirmation message via your registered email or contact number.
    </p>

    <p class="{{ $thanksClass }}">
      Thank you for choosing J&amp;G Audio Lighting and Sounds.
    </p>

    <p class="{{ $subtextClass }}">
      We appreciate your trust and look forward to delivering a seamless
      event experience.
    </p>

    <div class="{{ $actionsClass }}">
      <x-booking.step-button data-booking-continue>Continue</x-booking.step-button>
    </div>
  </div>
</div>