<div class="booking-confirmation position-relative z-1">
  <div class="booking-confirmation__card">
    <svg class="booking-confirmation__check" width="64" height="64" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
      <circle cx="32" cy="32" r="29" stroke="currentColor" stroke-width="3" />
      <path d="M20 33.5 L28 41.5 L44 24" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" />
    </svg>

    <h2 class="booking-confirmation__title">Booking Successful!</h2>

    <p class="booking-confirmation__body">
      Your booking reference number is
      <span class="booking-confirmation__ref">#{{ $referenceNumber ?? 'JG00000' }}</span>
    </p>

    <p class="booking-confirmation__body">
      You will receive the official confirmation message shortly via your
      registered email or contact number.
    </p>

    <p class="booking-confirmation__thanks">
      Thank you for choosing J&amp;G Audio Lighting and Sounds.
    </p>

    <p class="booking-confirmation__subtext">
      We appreciate your trust and look forward to delivering a seamless
      event experience.
    </p>

    <div class="booking-confirmation__actions">
      <x-button type="button" class="client-info__btn--confirm
      btn-color-gradient--primary font-button--responsive text-pale--white rounded-2" data-booking-continue>Continue</x-button>
    </div>
  </div>
</div>