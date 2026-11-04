{{--
  Personal Information step (booking flow). Phase 9B: styles are utilities, class strings in the PHP block below.
  The JS (client-information.js, auth/validation.js) toggles `d-none` on the error lines and `is-invalid` on the
  inputs; the utilities read them (`[&:not(.d-none)]:block`, `[&.is-invalid]:`), so the JS did not change.
  Hooks kept for the JS: ids, `name`s, `data-field-error`, `data-personal-info-error`, `data-booking-cancel`.
  The Cancel / Continue row is components/booking/step-actions + step-button, shared by every step.
--}}
@php
$stepClass = 'relative z-1 flex flex-col items-start gap-6';
$cardClass = 'booking-card flex-1 px-[1rem] py-6 min-[576px]:p-6';
// 24px under the title on phones, 32px from 992px.
$titleClass = 'text-fluid-booking-title font-bold [margin:0_0_1.5rem] min-[992px]:[margin:0_0_2rem]';
// Three names in one row from 480px, stacked below it.
$rowClass = 'flex flex-col min-[480px]:flex-row min-[480px]:gap-4';
$fieldClass = 'relative mb-6 min-[480px]:min-w-0 min-[480px]:flex-1';
$labelClass = 'mb-2 block text-fluid-sm font-semibold text-auth-text';
$requiredClass = 'text-auth-danger';
// autofill-dark keeps Chrome's autofill colour off the field; its inset box-shadow must not meet a shadow utility,
// so the focus ring is an outline. Invalid wins over focus (the second invalid string beats focus:border-auth-accent).
$inputClass = 'autofill-dark w-full rounded-field border-[1px] border-transparent bg-auth-field-bg px-[1rem] py-[0.75rem] text-fluid-sm text-auth-text placeholder:text-auth-text-faint focus:border-auth-accent focus:outline-3 focus:outline-offset-0 focus:outline-auth-focus-soft [&.is-invalid]:border-auth-danger [&.is-invalid]:focus:border-auth-danger';
// <small> is inline: shown with block, hidden by the JS's d-none (a plain `block` would beat d-none).
  $fieldErrorClass = 'mt-1 text-[0.78rem] text-auth-danger [&:not(.d-none)]:block';
  $errorClass = 'mb-[1rem] text-[0.85rem] text-auth-banner-error';
  @endphp
  <div class="{{ $stepClass }}">
    <div class="{{ $cardClass }}">
      <h2 class="{{ $titleClass }}">Personal Information</h2>

      <form
        id="personal-information-form"
        method="POST"
        action="{{ route('booking.client-information') }}"
        novalidate>
        @csrf

        <div class="{{ $rowClass }}">
          <div class="{{ $fieldClass }}">
            <label for="pi-first-name" class="{{ $labelClass }}">
              <span>First Name</span>
              <span class="{{ $requiredClass }}">*</span>
            </label>
            <input type="text" id="pi-first-name" name="first_name" class="{{ $inputClass }}" autocomplete="given-name" placeholder="Juan" value="{{ $clientInformation['first_name'] ?? '' }}" required>
            <small class="{{ $fieldErrorClass }} d-none" data-field-error="first_name"></small>
          </div>
          <div class="{{ $fieldClass }}">
            <label for="pi-middle-name" class="{{ $labelClass }}">
              <span>Middle Name (optional)</span>
            </label>
            <input type="text" id="pi-middle-name" name="middle_name" class="{{ $inputClass }}" autocomplete="additional-name" placeholder="Santos" value="{{ $clientInformation['middle_name'] ?? '' }}">
            <small class="{{ $fieldErrorClass }} d-none" data-field-error="middle_name"></small>
          </div>
          <div class="{{ $fieldClass }}">
            <label for="pi-last-name" class="{{ $labelClass }}">
              <span>Last Name</span>
              <span class="{{ $requiredClass }}">*</span>
            </label>
            <input type="text" id="pi-last-name" name="last_name" class="{{ $inputClass }}" autocomplete="family-name" placeholder="Dela Cruz" value="{{ $clientInformation['last_name'] ?? '' }}" required>
            <small class="{{ $fieldErrorClass }} d-none" data-field-error="last_name"></small>
          </div>
        </div>

        <div class="{{ $fieldClass }}">
          <label for="pi-email" class="{{ $labelClass }}">
            <span>Email</span>
            <span class="{{ $requiredClass }}">*</span>
          </label>
          <input type="text" id="pi-email" name="email" class="{{ $inputClass }}" autocomplete="email" placeholder="juanDelaCruz@gmail.com" value="{{ $clientInformation['email'] ?? '' }}" required>
          <small class="{{ $fieldErrorClass }} d-none" data-field-error="email"></small>
        </div>

        <div class="{{ $fieldClass }}">
          <label for="pi-contact-number" class="{{ $labelClass }}">
            <span>Contact Number</span>
            <span class="{{ $requiredClass }}">*</span>
          </label>
          <input type="text" id="pi-contact-number" name="contact_number" class="{{ $inputClass }}" autocomplete="tel" placeholder="09XXX-XXX-XXXX" value="{{ $clientInformation['contact_number'] ?? '' }}" required>
          <small class="{{ $fieldErrorClass }} d-none" data-field-error="contact_number"></small>
        </div>

        <div class="{{ $fieldClass }}">
          <label for="pi-address" class="{{ $labelClass }}">
            <span>Address</span>
            <span class="{{ $requiredClass }}">*</span>
          </label>
          <input type="text" id="pi-address" name="address" class="{{ $inputClass }}" autocomplete="street-address" placeholder="Barangay, City, Province" value="{{ $clientInformation['address'] ?? '' }}" required>
          <small class="{{ $fieldErrorClass }} d-none" data-field-error="address"></small>
        </div>

        <p class="{{ $errorClass }} d-none" role="alert" data-personal-info-error></p>
      </form>
    </div>

    <x-booking.step-actions>
      <x-booking.step-button variant="cancel" data-booking-cancel>Cancel</x-booking.step-button>
      <x-booking.step-button type="submit" form="personal-information-form">Continue</x-booking.step-button>
    </x-booking.step-actions>
  </div>