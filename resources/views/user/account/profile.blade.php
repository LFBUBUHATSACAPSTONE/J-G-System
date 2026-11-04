@php
// Front-end stub: reads the logged-in user when there is one, sample values when there is not
// (so the page always renders). `address` is not a users column yet (see docs/backend-impact.md);
// data_get() returns the default instead of failing when a value is missing.
$firstName = data_get($user, 'first_name', 'Juan');
$middleName = (string) data_get($user, 'middle_name', '');
$lastName = data_get($user, 'last_name', 'Dela Cruz');
$email = data_get($user, 'email', 'juan@example.com');
$phone = (string) data_get($user, 'phone', '');
$address = data_get($user, 'address', '') ?? '';
$fullName = trim(implode(' ', array_filter([$firstName, $middleName, $lastName])));
$phoneDigits = preg_replace('/\D/', '', $phone);
$phoneDisplay = strlen($phoneDigits) === 11 ? preg_replace('/^(\d{4})(\d{3})(\d{4})$/', '$1 $2 $3', $phoneDigits) : $phone;
$initials = strtoupper(mb_substr($firstName, 0, 1) . mb_substr($lastName, 0, 1)) ?: 'U';
$isVerified = ! is_null(data_get($user, 'email_verified_at'));
$memberSince = data_get($user, 'created_at')?->format('F Y');
$cardClass = 'w-full max-w-xl rounded-card bg-surface p-4 font-sans text-ink min-[576px]:p-8';
$cardTitleClass = 'mb-6 text-center text-fluid-icon font-bold text-ink';
$actionsClass = 'mt-6 grid grid-cols-2 gap-3 [&_form]:contents';
$primaryBtnClass = 'w-full cursor-pointer rounded-field border-2 border-transparent bg-brand px-4 py-2 leading-normal font-semibold text-on-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text enabled:hover:bg-brand-hover disabled:cursor-not-allowed disabled:opacity-50';
$secondaryBtnClass = 'w-full cursor-pointer rounded-field border-2 border-transparent bg-brand-soft px-4 py-2 leading-normal font-semibold text-brand-text focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text enabled:hover:bg-brand-soft-hover disabled:cursor-not-allowed disabled:opacity-50';
@endphp

<x-account.layout>
  {{-- The same profile card as the admin account page (components/profile/*, sass/profile,
       js/account/profile-core.js). Rows open inline; Save sends the same PATCH as before. --}}
  <div class="flex flex-col gap-6" data-account-profile data-profile-root>
    <section class="{{ $cardClass }}" aria-labelledby="account-personal-title">
      <h2 class="{{ $cardTitleClass }}" id="account-personal-title">Personal information</h2>

      <form
        id="account-profile-form"
        class="grid gap-4"
        method="POST"
        action="{{ route('user.account.profile.update') }}"
        novalidate>
        @csrf
        @method('PATCH')

        <x-profile.avatar :initials="$initials" />

        <div class="mb-2 flex flex-wrap items-center justify-center gap-x-3 gap-y-2 text-[0.8125rem] text-ink-muted">
          @if ($isVerified)
          <x-account.badge tone="success"><i class="ph ph-seal-check" aria-hidden="true"></i>Email verified</x-account.badge>
          @else
          <x-account.badge tone="warning"><i class="ph ph-warning-circle" aria-hidden="true"></i>Email not verified</x-account.badge>
          @endif
          @if ($memberSince)
          <span>Member since {{ $memberSince }}</span>
          @endif
        </div>

        {{-- Name --}}
        <x-profile.row name="name" label="Name" :value="$fullName" empty="Your name" action="Edit">
          <div class="grid grid-cols-[minmax(0,1fr)] gap-3 min-[576px]:grid-cols-3">
            <x-profile.field id="ap-first-name" name="first_name" label="First name" :value="$firstName"
              autocomplete="given-name" placeholder="Juan" maxlength="100" />
            <x-profile.field id="ap-middle-name" name="middle_name" label="Middle name (optional)" :value="$middleName"
              autocomplete="additional-name" placeholder="Santos" maxlength="100" />
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
            autocomplete="tel" inputmode="numeric" placeholder="09XXX-XXX-XXXX" />
        </x-profile.row>

        {{-- Address (optional, one line) --}}
        <x-profile.row name="address" label="Address" :value="$address" empty="Not set" action="Change"
          hint="Used to pre-fill the Client Information step when you book.">
          <x-profile.field id="ap-address" name="address" label="Address" :value="$address"
            autocomplete="street-address" placeholder="Barangay, City, Province" />
        </x-profile.row>

        <x-profile.status />

        <div class="{{ $actionsClass }}">
          <button type="button" class="{{ $secondaryBtnClass }}" data-profile-reset disabled>Discard changes</button>
          <button type="submit" class="{{ $primaryBtnClass }}" data-profile-save disabled>Save changes</button>
        </div>
      </form>
    </section>
  </div>
</x-account.layout>