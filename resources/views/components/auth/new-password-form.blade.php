{{--
  Auth modal form (Phase 7C): styles are utilities, the class strings are the PHP variables set just below.
  The same strings are in the other auth forms: a change to one is a change to all of them.
  Hooks kept: d-none, is-invalid, data-field-error, data-auth-view, auth-modal__toggle-password, field-required__identifier.
--}}
@php
$titleClass = 'mb-[0.4rem] text-[1.5rem] font-bold min-[768px]:text-[1.75rem]';
$fieldClass = 'relative mb-[0.85rem]';
$labelClass = 'mb-[0.35rem] block text-[0.8rem] font-normal text-auth-text-muted';
$requiredClass = 'text-auth-danger';
$inputClass = 'autofill-dark w-full rounded-field [border:1px_solid_transparent] bg-auth-field px-[1rem] py-[0.7rem] text-[0.9rem] text-auth-text placeholder:text-auth-placeholder focus:border-auth-accent focus:shadow-[0_0_0_3px_var(--color-auth-ring-soft)] focus:outline-none [&.is-invalid]:border-auth-danger [&.is-invalid]:focus:border-auth-danger';
$toggleClass = 'absolute top-[2.5rem] right-[0.9rem] [background:none] [border:none] [padding:0] leading-none [&_i]:block [&_i]:text-[1.25rem] [&_i]:text-auth-text [&_i]:opacity-60 hover:[&_i]:opacity-100 focus-visible:[&_i]:opacity-100';
$fieldErrorClass = 'mt-[0.35rem] text-[0.78rem] text-auth-danger [&:not(.d-none)]:block';
$errorClass = 'mb-[1rem] text-[0.85rem] text-auth-banner-error';
$standaloneClass = 'mb-[1.25rem] block text-[0.85rem] font-semibold text-auth-accent no-underline hover:underline';
@endphp

<h2 class="{{ $titleClass }}">New Password</h2>

<form method="POST" action="{{ route('password.update') }}" novalidate>
  @csrf

  <div class="{{ $fieldClass }}">
    <label class="{{ $labelClass }}" for="new-password">
      <span>Enter New Password</span>
      <span class="field-required__identifier {{ $requiredClass }}">*</span>
    </label>
    <input class="{{ $inputClass }}" type="password" id="new-password" name="password" placeholder="••••••••" required>

    <x-button type="button" class="auth-modal__toggle-password {{ $toggleClass }}" data-target="new-password" aria-label="Show password" aria-pressed="false">
      <i class="ph ph-eye" aria-hidden="true"></i>
    </x-button>
    <small class="d-none {{ $fieldErrorClass }}" data-field-error="password"></small>
  </div>

  <div class="{{ $fieldClass }}">
    <label class="{{ $labelClass }}" for="new-password-confirm">
      <span>Confirm Password</span>
      <span class="field-required__identifier {{ $requiredClass }}">*</span>
    </label>
    <input class="{{ $inputClass }}" type="password" id="new-password-confirm" name="password_confirmation" placeholder="••••••••" required>

    <x-button type="button" class="auth-modal__toggle-password {{ $toggleClass }}" data-target="new-password-confirm" aria-label="Show password" aria-pressed="false">
      <i class="ph ph-eye" aria-hidden="true"></i>
    </x-button>
    <small class="d-none {{ $fieldErrorClass }}" data-field-error="password_confirmation"></small>
  </div>

  <p class="d-none {{ $errorClass }}" role="alert" data-new-password-error></p>

  <a href="#" class="{{ $standaloneClass }}" data-auth-view="login">Back to Log in</a>

  <x-button type="submit" class="auth-modal__submit btn-color-gradient--primary">Confirm</x-button>
</form>