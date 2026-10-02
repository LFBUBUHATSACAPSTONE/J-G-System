<div class="client-info position-relative z-1">
  <div class="client-info__card">
    <h2 class="client-info__title">Personal Information</h2>

    <form
      id="personal-information-form"
      method="POST"
      action="{{ route('booking.client-information') }}"
      novalidate>
      @csrf

      <div class="client-info__field-row">
        <div class="client-info__field">
          <label for="pi-first-name">
            <span>First Name</span>
            <span class="field-required__identifier">*</span>
          </label>
          <input type="text" id="pi-first-name" name="first_name" autocomplete="given-name" placeholder="Juan" required>
          <small class="client-info__field-error d-none" data-field-error="first_name"></small>
        </div>
        <div class="client-info__field">
          <label for="pi-last-name">
            <span>Last Name</span>
            <span class="field-required__identifier">*</span>
          </label>
          <input type="text" id="pi-last-name" name="last_name" autocomplete="family-name" placeholder="Dela Cruz" required>
          <small class="client-info__field-error d-none" data-field-error="last_name"></small>
        </div>
      </div>

      <div class="client-info__field">
        <label for="pi-email">
          <span>Email</span>
          <span class="field-required__identifier">*</span>
        </label>
        <input type="text" id="pi-email" name="email" autocomplete="email" placeholder="juanDelaCruz@gmail.com" required>
        <small class="client-info__field-error d-none" data-field-error="email"></small>
      </div>

      <div class="client-info__field">
        <label for="pi-contact-number">
          <span>Contact Number</span>
          <span class="field-required__identifier">*</span>
        </label>
        <input type="text" id="pi-contact-number" name="contact_number" autocomplete="tel" placeholder="09XXX-XXX-XXXX" required>
        <small class="client-info__field-error d-none" data-field-error="contact_number"></small>
      </div>

      <div class="client-info__field">
        <label for="pi-address">
          <span>Address</span>
          <span class="field-required__identifier">*</span>
        </label>
        <input type="text" id="pi-address" name="address" autocomplete="street-address" placeholder="Barangay, City, Province" required>
        <small class="client-info__field-error d-none" data-field-error="address"></small>
      </div>

      <p class="client-info__error text-danger d-none" role="alert" data-personal-info-error></p>
    </form>
  </div>

  <div class="client-info__actions">
    <x-button type="button" class="btn-color-gradient--secondary client-info__btn--cancel text-pale--white rounded-2" data-booking-cancel>Cancel</x-button>
    <x-button type="submit" form="personal-information-form" class="client-info__btn--confirm
    btn-color-gradient--primary font-button--responsive text-pale--white rounded-2">Continue</x-button>
  </div>
</div>