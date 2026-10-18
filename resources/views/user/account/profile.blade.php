@php
// Front-end stub: reads the logged-in user when there is one, sample values when there is not
// (so the page always renders). `address` is not a users column yet (see docs/backend-impact.md);
// data_get() returns the default instead of failing when a value is missing.
$firstName = data_get($user, 'first_name', 'Juan');
$lastName = data_get($user, 'last_name', 'Dela Cruz');
$email = data_get($user, 'email', 'juan@example.com');
$phone = (string) data_get($user, 'phone', '');
$address = data_get($user, 'address', '') ?? '';
$fullName = trim("{$firstName} {$lastName}");
$phoneDigits = preg_replace('/\D/', '', $phone);
$phoneDisplay = strlen($phoneDigits) === 11 ? preg_replace('/^(\d{4})(\d{3})(\d{4})$/', '$1 $2 $3', $phoneDigits) : $phone;
$initials = strtoupper(mb_substr($firstName, 0, 1) . mb_substr($lastName, 0, 1)) ?: 'U';
$isVerified = ! is_null(data_get($user, 'email_verified_at'));
$memberSince = data_get($user, 'created_at')?->format('F Y');
@endphp

<x-account.layout>
  {{-- The same profile card as the admin account page (components/profile/*, sass/profile,
       js/account/profile-core.js). Rows open inline; Save sends the same PATCH as before. --}}
  <div class="account-profile" data-account-profile data-profile-root>
    <section class="profile-card" aria-labelledby="account-personal-title">
      <h2 class="profile-card__title" id="account-personal-title">Personal information</h2>

      <form
        id="account-profile-form"
        class="profile-card__form"
        method="POST"
        action="{{ route('user.account.profile.update') }}"
        novalidate>
        @csrf
        @method('PATCH')

        <x-profile.avatar :initials="$initials" />

        <div class="profile-meta">
          @if ($isVerified)
          <span class="account-badge account-badge--success"><i class="ph ph-seal-check" aria-hidden="true"></i>Email verified</span>
          @else
          <span class="account-badge account-badge--warning"><i class="ph ph-warning-circle" aria-hidden="true"></i>Email not verified</span>
          @endif
          @if ($memberSince)
          <span>Member since {{ $memberSince }}</span>
          @endif
        </div>

        {{-- Name --}}
        <x-profile.row name="name" label="Name" :value="$fullName" empty="Your name" action="Edit">
          <div class="profile-row__fields profile-row__fields--name has-two">
            <x-profile.field id="ap-first-name" name="first_name" label="First name" :value="$firstName"
              autocomplete="given-name" placeholder="Juan" maxlength="100" />
            <x-profile.field id="ap-last-name" name="last_name" label="Last name" :value="$lastName"
              autocomplete="family-name" placeholder="Dela Cruz" maxlength="100" />
          </div>
        </x-profile.row>

        {{-- Email: the login. Read-only here, so it has no Change button and is never sent. --}}
        <x-profile.row name="email" label="Email" :value="$email"
          hint="This is your login email and can't be changed here." />

        {{-- Contact number (optional) --}}
        <x-profile.row name="phone" label="Contact Number" :value="$phoneDisplay" empty="Not set" action="Change">
          <x-profile.field id="ap-phone" name="phone" type="tel" label="Contact number" :value="$phone"
            autocomplete="tel" inputmode="numeric" maxlength="11" placeholder="09XXXXXXXXX" />
        </x-profile.row>

        {{-- Address (optional, one line) --}}
        <x-profile.row name="address" label="Address" :value="$address" empty="Not set" action="Change"
          hint="Used to pre-fill the Client Information step when you book.">
          <x-profile.field id="ap-address" name="address" label="Address" :value="$address"
            autocomplete="street-address" placeholder="Barangay, City, Province" />
        </x-profile.row>

        <x-profile.status />

        <div class="profile-actions">
          <button type="button" class="profile-btn profile-btn--secondary" data-profile-reset disabled>Discard changes</button>
          <button type="submit" class="profile-btn profile-btn--primary" data-profile-save disabled>Save changes</button>
        </div>
      </form>
    </section>
  </div>
</x-account.layout>