{{--
  Event Information step (booking flow). Phase 9C: styles are utilities, class strings in the PHP block below.
  The JS (event-information.js, auth/validation.js) toggles `d-none` on the error lines, the options list and the
  "Please specify" field, and `is-invalid` on the inputs, the select button and the venue-type fieldset; the utilities
  read them (`[&:not(.d-none)]:block`, `[&.is-invalid]:`), so the JS did not change. The select's open state is
  `aria-expanded`, the chosen option is `aria-selected`; both were already set by the JS.
  Hooks kept for the JS: ids, `name`s, `data-select*`, `data-event-type-other-field`, `data-venue-type*`,
  `data-field-error`, `data-event-info-error`, `data-booking-previous`.
  The Previous / Continue row is components/booking/step-actions + step-button, shared by every step.
--}}
@php
$stepClass = 'relative z-1 flex flex-col items-start gap-6';
$cardClass = 'booking-card flex-1 px-[1rem] py-6 min-[576px]:p-6';
$titleClass = 'text-fluid-booking-title font-bold [margin:0_0_1.5rem] min-[992px]:[margin:0_0_2rem]';
$fieldClass = 'relative mb-6';
// A fieldset needs its border, padding and min-width reset; it is the Venue Type field.
$fieldsetClass = $fieldClass.' min-w-0 [border:0] p-[0]';
// Label and legend share one look.
$labelClass = 'mb-2 block text-fluid-sm font-semibold text-auth-text';
$requiredClass = 'text-auth-danger';
// "(Optional)": muted, never the red of the required asterisk, and never split from its word.
$optionalClass = 'font-normal italic whitespace-nowrap text-auth-text-faint';
// autofill-dark keeps Chrome's autofill colour off the field; its inset box-shadow must not meet a shadow utility,
// so the focus ring is an outline. Invalid wins over focus (the second invalid string beats focus:border-auth-accent).
$inputClass = 'autofill-dark w-full rounded-field border-[1px] border-transparent bg-auth-field-bg px-[1rem] py-[0.75rem] text-fluid-sm text-auth-text placeholder:text-auth-text-faint focus:border-auth-accent focus:outline-3 focus:outline-offset-0 focus:outline-auth-focus-soft [&.is-invalid]:border-auth-danger [&.is-invalid]:focus:border-auth-danger';
// <small> is inline: shown with block, hidden by the JS's d-none (a plain `block` would beat d-none).
  $fieldErrorClass = 'mt-1 text-[0.78rem] text-auth-danger [&:not(.d-none)]:block';
  $errorClass = 'mb-[1rem] text-[0.85rem] text-auth-banner-error';
  // Custom select: a button that opens a listbox. The ring shows on keyboard focus and while open; invalid wins over both.
  $selectBtnClass = 'group/select flex w-full cursor-pointer items-center justify-between gap-[0.75rem] rounded-field border-[1px] border-transparent bg-auth-field-bg px-[1rem] py-[0.75rem] text-left text-fluid-sm text-auth-text focus-visible:border-auth-accent focus-visible:shadow-[0_0_0_3px_var(--color-auth-focus-soft)] focus-visible:outline-none aria-expanded:border-auth-accent aria-expanded:shadow-[0_0_0_3px_var(--color-auth-focus-soft)] [&.is-invalid]:border-auth-danger [&.is-invalid]:focus-visible:border-auth-danger [&.is-invalid]:aria-expanded:border-auth-danger';
  $selectValueClass = 'data-placeholder-active:text-auth-text-faint';
  $chevronClass = 'shrink-0 text-auth-text-muted transition-transform duration-150 ease-in-out group-aria-expanded/select:rotate-180';
  // The listbox hangs under the select; `d-none` (JS) hides it, so it carries no display utility.
  $optionsClass = 'absolute inset-x-0 top-full z-5 mt-1 max-h-60 list-none overflow-y-auto rounded-field bg-auth-surface p-[0.25rem] shadow-[0_12px_32px_var(--color-shadow)] [border:1px_solid_var(--color-auth-border)]';
  $optionClass = 'cursor-pointer rounded-lg px-[0.75rem] py-[0.5rem] text-fluid-sm text-auth-text hover:bg-auth-overlay-soft aria-selected:font-semibold aria-selected:text-auth-accent';
  // Indoor / Outdoor / Both on one row; wraps by whole option only if the row truly cannot fit.
  $venueRowClass = 'relative flex flex-wrap items-center gap-x-[1rem] gap-y-[0.5rem]';
  $checkLabelClass = 'flex cursor-pointer items-center gap-2 text-fluid-sm font-medium whitespace-nowrap text-auth-text [margin:0]';
  // The red border comes from the fieldset (group/venue) when the JS marks it invalid.
  $checkClass = 'relative size-4 shrink-0 cursor-pointer appearance-none rounded-[4px] [border:1px_solid_var(--color-auth-border)] bg-auth-field-bg [margin:0] checked:border-brand checked:bg-brand checked:after:absolute checked:after:top-[1px] checked:after:left-[4px] checked:after:h-[8px] checked:after:w-[4px] checked:after:rotate-45 checked:after:border-solid checked:after:border-white checked:after:[border-width:0_2px_2px_0] focus-visible:shadow-[0_0_0_3px_var(--color-auth-focus-soft)] focus-visible:outline-none group-[.is-invalid]/venue:border-auth-danger';
  $eventTypes = ['Baby Shower', 'Bridal Shower', 'Birthday Party', 'Concert', 'Family Reunion', 'Team-Building Event', 'Wedding', 'Others'];
  @endphp
  <div class="{{ $stepClass }}">
    <div class="{{ $cardClass }}">
      <h2 class="{{ $titleClass }}">Event Information</h2>

      <form
        id="event-information-form"
        method="POST"
        action="{{ route('booking.event-information') }}"
        novalidate>
        @csrf

        <div class="{{ $fieldClass }}">
          <label for="ei-event-name" class="{{ $labelClass }}">
            <span>Event Name</span>
            <span class="{{ $requiredClass }}">*</span>
          </label>
          <input type="text" id="ei-event-name" name="event_name" class="{{ $inputClass }}" placeholder="e.g. 60th Birthday of my Grandmother" required>
          <small class="{{ $fieldErrorClass }} d-none" data-field-error="event_name"></small>
        </div>

        <div class="{{ $fieldClass }}" data-select>
          <label id="ei-event-type-label" for="ei-event-type-button" class="{{ $labelClass }}">
            <span>Event Type</span>
            <span class="{{ $requiredClass }}">*</span>
          </label>
          <button
            type="button"
            id="ei-event-type-button"
            class="{{ $selectBtnClass }}"
            aria-haspopup="listbox"
            aria-expanded="false"
            aria-labelledby="ei-event-type-label ei-event-type-button"
            data-select-toggle>
            <span class="{{ $selectValueClass }}" data-select-value data-placeholder="Select event type">Select event type</span>
            <svg class="{{ $chevronClass }}" width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
              <path d="M2.5 4.5L7 9L11.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
            </svg>
          </button>
          <ul class="{{ $optionsClass }} d-none" role="listbox" tabindex="-1" aria-labelledby="ei-event-type-label" data-select-options>
            @foreach ($eventTypes as $eventType)
            <li class="{{ $optionClass }}" role="option" tabindex="-1" data-value="{{ $eventType }}">{{ $eventType }}</li>
            @endforeach
          </ul>
          <input type="hidden" id="ei-event-type" name="event_type" data-select-input>
          <small class="{{ $fieldErrorClass }} d-none" data-field-error="event_type"></small>
        </div>

        <div class="{{ $fieldClass }} d-none" data-event-type-other-field>
          <label for="ei-event-type-other" class="{{ $labelClass }}">
            <span>Please specify</span>
            <span class="{{ $requiredClass }}">*</span>
          </label>
          <input type="text" id="ei-event-type-other" name="event_type_other" class="{{ $inputClass }}" placeholder="e.g. Anniversary Celebration">
          <small class="{{ $fieldErrorClass }} d-none" data-field-error="event_type_other"></small>
        </div>

        <div class="{{ $fieldClass }}">
          <label for="ei-event-location" class="{{ $labelClass }}">
            <span>Event Location</span>
            <span class="{{ $requiredClass }}">*</span>
          </label>
          <input type="text" id="ei-event-location" name="event_location" class="{{ $inputClass }}" autocomplete="street-address" placeholder="Postal code, Barangay, City, Province" required>
          <small class="{{ $fieldErrorClass }} d-none" data-field-error="event_location"></small>
        </div>

        <div class="{{ $fieldClass }}">
          <label for="ei-venue-contact-person" class="{{ $labelClass }}">
            <span>Venue Contact Person</span>
            <span class="{{ $optionalClass }}">(Optional)</span>
          </label>
          <input type="text" id="ei-venue-contact-person" name="venue_contact_person" class="{{ $inputClass }}" autocomplete="tel" placeholder="09XXX-XXX-XXXX">
          <small class="{{ $fieldErrorClass }} d-none" data-field-error="venue_contact_person"></small>
        </div>

        <div class="{{ $fieldClass }}">
          <label for="ei-guest-count" class="{{ $labelClass }}">
            <span>Number of Guests</span>
            <span class="{{ $optionalClass }}">(Optional)</span>
          </label>
          <input type="text" id="ei-guest-count" name="guest_count" class="{{ $inputClass }}" inputmode="numeric" pattern="[0-9]*" placeholder="0">
          <small class="{{ $fieldErrorClass }} d-none" data-field-error="guest_count"></small>
        </div>

        <fieldset class="group/venue {{ $fieldsetClass }}" data-venue-type tabindex="-1" aria-required="true">
          <legend class="{{ $labelClass }}">
            <span>Venue Type</span>
            <span class="{{ $requiredClass }}">*</span>
          </legend>
          <div class="{{ $venueRowClass }}">
            @foreach (['Indoor', 'Outdoor', 'Both'] as $venueType)
            <label class="{{ $checkLabelClass }}">
              <input type="checkbox" name="venue_type" value="{{ $venueType }}" class="{{ $checkClass }}" data-venue-type-option required>
              <span>{{ $venueType }}</span>
            </label>
            @endforeach
          </div>
          <small class="{{ $fieldErrorClass }} d-none" data-field-error="venue_type"></small>
        </fieldset>

        <p class="{{ $errorClass }} d-none" role="alert" data-event-info-error></p>
      </form>
    </div>

    <x-booking.step-actions>
      <x-booking.step-button variant="cancel" data-booking-previous>Previous</x-booking.step-button>
      <x-booking.step-button type="submit" form="event-information-form">Continue</x-booking.step-button>
    </x-booking.step-actions>
  </div>