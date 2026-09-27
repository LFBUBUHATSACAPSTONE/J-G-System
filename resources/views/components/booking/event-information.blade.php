<div class="event-info position-absolute z-1">
  <div class="event-info__card">
    <h2 class="event-info__title">Event Information</h2>

    <form
      id="event-information-form"
      method="POST"
      action="{{ route('booking.event-information') }}"
      novalidate>
      @csrf

      <div class="event-info__field">
        <label for="ei-event-name">
          <span>Event Name</span>
          <span class="field-required__identifier">*</span>
        </label>
        <input type="text" id="ei-event-name" name="event_name" placeholder="e.g. 60th Birthday of my Grandmother" required>
        <small class="event-info__field-error d-none" data-field-error="event_name"></small>
      </div>

      <div class="event-info__field event-info__field--select" data-select>
        <label id="ei-event-type-label" for="ei-event-type-button">
          <span>Event Type</span>
          <span class="field-required__identifier">*</span>
        </label>
        <button
          type="button"
          id="ei-event-type-button"
          class="event-info__select-btn"
          aria-haspopup="listbox"
          aria-expanded="false"
          aria-labelledby="ei-event-type-label ei-event-type-button"
          data-select-toggle>
          <span class="event-info__select-value" data-select-value data-placeholder="Select event type">Select event type</span>
          <svg class="event-info__select-chevron" width="14" height="14" viewBox="0 0 14 14" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
            <path d="M2.5 4.5L7 9L11.5 4.5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" />
          </svg>
        </button>
        <ul class="event-info__select-options d-none" role="listbox" tabindex="-1" aria-labelledby="ei-event-type-label" data-select-options>
          <li role="option" tabindex="-1" data-value="Baby Shower">Baby Shower</li>
          <li role="option" tabindex="-1" data-value="Bridal Shower">Bridal Shower</li>
          <li role="option" tabindex="-1" data-value="Birthday Party">Birthday Party</li>
          <li role="option" tabindex="-1" data-value="Concert">Concert</li>
          <li role="option" tabindex="-1" data-value="Family Reunion">Family Reunion</li>
          <li role="option" tabindex="-1" data-value="Team-Building Event">Team-Building Event</li>
          <li role="option" tabindex="-1" data-value="Wedding">Wedding</li>
          <li role="option" tabindex="-1" data-value="Others">Others</li>
        </ul>
        <input type="hidden" id="ei-event-type" name="event_type" data-select-input>
        <small class="event-info__field-error d-none" data-field-error="event_type"></small>
      </div>

      <div class="event-info__field">
        <label for="ei-event-location">
          <span>Event Location</span>
          <span class="field-required__identifier">*</span>
        </label>
        <input type="text" id="ei-event-location" name="event_location" autocomplete="street-address" placeholder="Postal code, Barangay, City, Province" required>
        <small class="event-info__field-error d-none" data-field-error="event_location"></small>
      </div>

      <div class="event-info__field">
        <label for="ei-venue-contact-person">
          <span>Venue Contact Person</span>
          <span class="event-info__optional-tag">(Optional)</span>
        </label>
        <input type="text" id="ei-venue-contact-person" name="venue_contact_person" autocomplete="tel" placeholder="(+63) XXX XXX XXXX">
        <small class="event-info__field-error d-none" data-field-error="venue_contact_person"></small>
      </div>

      <div class="event-info__field-row">
        <div class="event-info__field event-info__field--guests">
          <label for="ei-guest-count">
            <span>Number of Guests</span>
            <span class="event-info__optional-tag">(Optional)</span>
          </label>
          <input type="text" id="ei-guest-count" name="guest_count" inputmode="numeric" pattern="[0-9]*" placeholder="0">
          <small class="event-info__field-error d-none" data-field-error="guest_count"></small>
        </div>

        <fieldset class="event-info__venue-type" data-venue-type tabindex="-1" aria-required="true">
          <legend class="visually-hidden">Venue type<span class="field-required__identifier">*</span></legend>
          <label class="event-info__checkbox">
            <input type="checkbox" name="venue_type" value="Indoor" data-venue-type-option required>
            <span>Indoor</span>
          </label>
          <label class="event-info__checkbox">
            <input type="checkbox" name="venue_type" value="Outdoor" data-venue-type-option required>
            <span>Outdoor</span>
          </label>
          <label class="event-info__checkbox">
            <input type="checkbox" name="venue_type" value="Both" data-venue-type-option required>
            <span>Both</span>
          </label>
          <small class="event-info__field-error d-none" data-field-error="venue_type"></small>
        </fieldset>
      </div>

      <p class="event-info__error text-danger d-none" role="alert" data-event-info-error></p>
    </form>
  </div>

  <div class="client-info__actions">
    <x-button type="submit" form="event-information-form" class="client-info__btn--confirm
    btn-color-gradient--primary font-button--responsive text-pale--white rounded-2">Continue</x-button>
    <x-button type="button" class="btn-color-gradient--secondary client-info__btn--cancel text-pale--white rounded-2" data-booking-previous>Previous</x-button>
  </div>
</div>