@php
// Front-end stub: reads the logged-in user when there is one, sample values when there is not
// (so the page always renders). `address` is not a users column yet (see docs/backend-impact.md);
// data_get() returns the default instead of failing when a value is missing.
$firstName = data_get($user, 'first_name', 'Juan');
$lastName = data_get($user, 'last_name', 'Dela Cruz');
$email = data_get($user, 'email', 'juan@example.com');
$phone = data_get($user, 'phone', '');
$address = data_get($user, 'address', '') ?? '';
$fullName = trim("{$firstName} {$lastName}");
$initials = strtoupper(mb_substr($firstName, 0, 1) . mb_substr($lastName, 0, 1)) ?: 'U';
$isVerified = ! is_null(data_get($user, 'email_verified_at'));
$memberSince = data_get($user, 'created_at')?->format('F Y');
@endphp

<x-account.layout>
  <div class="account-profile" data-account-profile>

    <section class="account-card account-summary" aria-label="Account summary">
      <div class="account-summary__avatar" aria-hidden="true" data-profile-initials>{{ $initials }}</div>
      <div class="account-summary__text">
        <p class="account-summary__name" data-profile-name>{{ $fullName ?: 'Your name' }}</p>
        <p class="account-summary__email">{{ $email }}</p>
        <div class="account-summary__meta">
          @if ($isVerified)
          <span class="account-badge account-badge--success"><i class="ph ph-seal-check" aria-hidden="true"></i>Email verified</span>
          @else
          <span class="account-badge account-badge--warning"><i class="ph ph-warning-circle" aria-hidden="true"></i>Email not verified</span>
          @endif
          @if ($memberSince)
          <span class="account-summary__since">Member since {{ $memberSince }}</span>
          @endif
        </div>
      </div>
    </section>

    <form
      id="account-profile-form"
      class="account-form"
      method="POST"
      action="{{ route('user.account.profile.update') }}"
      novalidate>
      @csrf
      @method('PATCH')

      <section class="account-card" aria-labelledby="account-personal-title">
        <h2 class="account-card__title" id="account-personal-title">Personal information</h2>

        <div class="account-form__grid">
          <div class="account-form__field">
            <label for="ap-first-name">First Name <span class="account-form__required" aria-hidden="true">*</span></label>
            <input type="text" id="ap-first-name" name="first_name" value="{{ $firstName }}" autocomplete="given-name" placeholder="Juan" required>
            <small class="account-form__error d-none" data-field-error="first_name"></small>
          </div>

          <div class="account-form__field">
            <label for="ap-last-name">Last Name <span class="account-form__required" aria-hidden="true">*</span></label>
            <input type="text" id="ap-last-name" name="last_name" value="{{ $lastName }}" autocomplete="family-name" placeholder="Dela Cruz" required>
            <small class="account-form__error d-none" data-field-error="last_name"></small>
          </div>

          <div class="account-form__field">
            <label for="ap-email">Email</label>
            <input type="email" id="ap-email" value="{{ $email }}" autocomplete="email" readonly aria-describedby="ap-email-hint">
            <small class="account-form__hint" id="ap-email-hint">This is your login email and can't be changed here.</small>
          </div>

          <div class="account-form__field">
            <label for="ap-phone">Contact Number</label>
            <input type="text" id="ap-phone" name="phone" value="{{ $phone }}" autocomplete="tel" inputmode="numeric" placeholder="09XXXXXXXXX">
            <small class="account-form__error d-none" data-field-error="phone"></small>
          </div>
        </div>
      </section>

      <section class="account-card" aria-labelledby="account-address-title">
        <h2 class="account-card__title" id="account-address-title">Address</h2>

        <div class="account-form__grid">
          <div class="account-form__field account-form__field--wide">
            <label for="ap-address">Address</label>
            <input type="text" id="ap-address" name="address" value="{{ $address }}" autocomplete="street-address" placeholder="Barangay, City, Province">
            <small class="account-form__hint" id="ap-address-hint">Used to pre-fill the Client Information step when you book.</small>
            <small class="account-form__error d-none" data-field-error="address"></small>
          </div>
        </div>
      </section>

      <p class="account-form__status d-none" role="status" aria-live="polite" data-profile-status></p>

      <div class="account-form__actions">
        <button type="button" class="account-btn account-btn--secondary" data-profile-reset disabled>Discard changes</button>
        <button type="submit" class="account-btn account-btn--primary" data-profile-save disabled>Save changes</button>
      </div>
    </form>
  </div>
</x-account.layout>