{{-- Account > Security. FRONT END ONLY. Variables from the controller:
       $user        => the logged-in user (sample values are used when there is none)
       $hasPassword => bool, optional, defaults to true. false = the account has no password of its own
                       (signed up with Google only), so the form is replaced by a note. Only the backend
                       can tell: `users.password` is NOT NULL, so google_id alone does not prove it.
     The password form PATCHes `user.account.password.update` (contract: js/account/security.js). If that
     route does not exist yet the action is empty and the page only shows a "not connected" notice. --}}
@php
$hasPassword = (bool) ($hasPassword ?? true);
$hasGoogle = filled(data_get($user, 'google_id'));
$passwordAction = Route::has('user.account.password.update') ? route('user.account.password.update') : '';

// Mirrors getPasswordError() in js/auth/validation.js (the sign-up rule): one line per requirement.
$rules = [
'length' => 'At least 8 characters',
'lower' => 'A lowercase letter',
'upper' => 'An uppercase letter',
'number' => 'A number',
'special' => 'A special character',
];

// Utility strings for the form (the old .account-form* Sass went with the profile card in Phase 4).
$gridClass = 'grid grid-cols-1 gap-4 @min-[34rem]:grid-cols-2';
$fieldClass = 'grid min-w-0 gap-1';
$fieldWideClass = 'grid min-w-0 gap-1 @min-[34rem]:col-span-2';
$labelClass = 'text-fluid-md text-brand-text';
$requiredClass = 'text-danger-text';
$wrapClass = 'relative';
$inputClass = 'min-h-11 w-full rounded-field border border-field-border bg-surface py-2 pr-12 pl-3 text-fluid-base text-ink placeholder:text-ink-subtle focus-visible:ring-4 focus-visible:ring-brand-halo focus-visible:outline-2 focus-visible:outline-offset-0 focus-visible:outline-brand-text [&.is-invalid]:border-danger-border';
$toggleClass = 'absolute top-1/2 right-1 grid size-10 -translate-y-1/2 cursor-pointer place-items-center rounded-field border-0 bg-transparent p-0 text-[1.25rem] text-ink-muted hover:text-brand-text focus-visible:outline-2 focus-visible:outline-brand-text';
$errorClass = 'text-sm text-danger-text d-none';
$statusClass = 'm-0 rounded-panel border border-success-border bg-success-soft px-4 py-3 text-fluid-md font-medium text-success-text [&.is-error]:border-danger-border [&.is-error]:bg-danger-soft [&.is-error]:text-danger-text d-none';
@endphp

<x-account.layout>
  <div class="account-security">

    <section class="account-card" aria-labelledby="account-password-title">
      <h2 class="account-card__title" id="account-password-title">Change password</h2>

      @if ($hasPassword)
      <form
        id="account-password-form"
        class="grid gap-4"
        method="POST"
        action="{{ $passwordAction }}"
        autocomplete="on"
        novalidate>
        @csrf
        @method('PATCH')

        <div class="{{ $gridClass }}">
          <div class="{{ $fieldWideClass }}">
            <label class="{{ $labelClass }}" for="sp-current">Current password <span class="{{ $requiredClass }}" aria-hidden="true">*</span></label>
            <div class="{{ $wrapClass }}">
              <input class="{{ $inputClass }}" type="password" id="sp-current" name="current_password" autocomplete="current-password" required>
              <button type="button" class="{{ $toggleClass }}" data-password-toggle="sp-current" aria-label="Show password" aria-pressed="false">
                <i class="ph ph-eye" aria-hidden="true"></i>
              </button>
            </div>
            <small class="{{ $errorClass }}" data-field-error="current_password"></small>
          </div>

          <div class="{{ $fieldClass }}">
            <label class="{{ $labelClass }}" for="sp-new">New password <span class="{{ $requiredClass }}" aria-hidden="true">*</span></label>
            <div class="{{ $wrapClass }}">
              <input class="{{ $inputClass }}" type="password" id="sp-new" name="password" autocomplete="new-password" aria-describedby="sp-rules" required>
              <button type="button" class="{{ $toggleClass }}" data-password-toggle="sp-new" aria-label="Show password" aria-pressed="false">
                <i class="ph ph-eye" aria-hidden="true"></i>
              </button>
            </div>
            <small class="{{ $errorClass }}" data-field-error="password"></small>
          </div>

          <div class="{{ $fieldClass }}">
            <label class="{{ $labelClass }}" for="sp-confirm">Confirm new password <span class="{{ $requiredClass }}" aria-hidden="true">*</span></label>
            <div class="{{ $wrapClass }}">
              <input class="{{ $inputClass }}" type="password" id="sp-confirm" name="password_confirmation" autocomplete="new-password" required>
              <button type="button" class="{{ $toggleClass }}" data-password-toggle="sp-confirm" aria-label="Show password" aria-pressed="false">
                <i class="ph ph-eye" aria-hidden="true"></i>
              </button>
            </div>
            <small class="{{ $errorClass }}" data-field-error="password_confirmation"></small>
          </div>
        </div>

        <ul class="account-security__rules" id="sp-rules" aria-label="Password requirements">
          @foreach ($rules as $key => $text)
          <li data-password-rule="{{ $key }}">
            <i class="ph ph-circle" aria-hidden="true"></i>
            <span>{{ $text }}</span>
          </li>
          @endforeach
        </ul>

        <p class="{{ $statusClass }}" role="status" aria-live="polite" data-password-status></p>

        <div class="flex">
          <button type="submit" class="account-btn account-btn--primary" data-password-save disabled>Save new password</button>
        </div>
      </form>
      @else
      <p class="account-security__note">
        <i class="ph ph-info" aria-hidden="true"></i>
        <span>You sign in with Google, so this account has no password to change.</span>
      </p>
      @endif
    </section>

    <section class="account-card" aria-labelledby="account-signin-title">
      <h2 class="account-card__title" id="account-signin-title">Sign-in methods</h2>

      <ul class="account-security__methods">
        <li class="account-security__method">
          <i class="ph ph-envelope-simple" aria-hidden="true"></i>
          <div class="account-security__method-text">
            <p class="account-security__method-name">Email and password</p>
            <p class="account-security__method-hint">{{ data_get($user, 'email', 'juan@example.com') }}</p>
          </div>
          @if ($hasPassword)
          <span class="account-badge account-badge--success">Connected</span>
          @else
          <span class="account-badge account-badge--neutral">Not set</span>
          @endif
        </li>

        <li class="account-security__method">
          <i class="ph ph-google-logo" aria-hidden="true"></i>
          <div class="account-security__method-text">
            <p class="account-security__method-name">Google</p>
            <p class="account-security__method-hint">Sign in with your Google account</p>
          </div>
          @if ($hasGoogle)
          <span class="account-badge account-badge--success">Connected</span>
          @else
          <span class="account-badge account-badge--neutral">Not connected</span>
          @endif
        </li>
      </ul>
    </section>

  </div>
</x-account.layout>