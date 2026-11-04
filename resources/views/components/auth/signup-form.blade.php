{{--
  Auth modal form (Phase 7C): styles are utilities, the class strings are the PHP variables set just below.
  The same strings are in the other auth forms: a change to one is a change to all of them.
  Hooks kept: d-none, is-invalid, data-field-error, data-auth-view, auth-modal__toggle-password, field-required__identifier.
--}}
@php
$titleClass = 'mb-[0.4rem] text-[1.5rem] font-bold min-[768px]:text-[1.75rem]';
$subtextClass = 'mb-[1.25rem] text-[0.85rem] text-auth-text-muted';
$linkClass = 'font-semibold text-auth-accent no-underline hover:underline';
$fieldClass = 'relative mb-[0.85rem]';
$fieldItemClass = 'relative mb-[0.85rem] min-[480px]:flex-1';
$fieldRowClass = 'flex flex-col min-[480px]:flex-row min-[480px]:gap-[0.75rem]';
$labelClass = 'mb-[0.35rem] block text-[0.8rem] font-normal text-auth-text-muted';
$requiredClass = 'text-auth-danger';
$inputClass = 'autofill-dark w-full rounded-field [border:1px_solid_transparent] bg-auth-field px-[1rem] py-[0.7rem] text-[0.9rem] text-auth-text placeholder:text-auth-placeholder focus:border-auth-accent focus:shadow-[0_0_0_3px_var(--color-auth-ring-soft)] focus:outline-none [&.is-invalid]:border-auth-danger [&.is-invalid]:focus:border-auth-danger';
$toggleClass = 'absolute top-[2.5rem] right-[0.9rem] [background:none] [border:none] [padding:0] leading-none [&_i]:block [&_i]:text-[1.25rem] [&_i]:text-auth-text [&_i]:opacity-60 hover:[&_i]:opacity-100 focus-visible:[&_i]:opacity-100';
$fieldErrorClass = 'mt-[0.35rem] text-[0.78rem] text-auth-danger [&:not(.d-none)]:block';
$errorClass = 'mb-[1rem] text-[0.85rem] text-auth-banner-error';
$rowClass = 'mb-[1.5rem] flex flex-wrap items-center justify-between gap-[0.5rem] text-[0.8rem] text-auth-text-muted';
$rowLabelClass = 'flex items-center gap-[0.5rem] [margin:0]';
$checkClass = 'relative size-4 shrink-0 cursor-pointer appearance-none rounded-[4px] [border:1px_solid_var(--color-auth-border)] bg-auth-field [margin:0] checked:border-brand checked:bg-brand checked:after:absolute checked:after:top-[1px] checked:after:left-[4px] checked:after:h-[8px] checked:after:w-[4px] checked:after:rotate-45 checked:after:border-solid checked:after:border-white checked:after:[border-width:0_2px_2px_0] focus-visible:shadow-[0_0_0_3px_var(--color-auth-ring-soft)] focus-visible:outline-none';
$submitClass = 'w-full rounded-field text-[0.95rem] font-semibold [padding:0.75rem]';
$dividerClass = 'mt-[2.5rem] mb-[0.85rem] flex items-center gap-[0.75rem] text-[0.78rem] text-auth-text-muted before:h-px before:flex-1 before:bg-auth-border after:h-px after:flex-1 after:bg-auth-border';
$ssoClass = 'mb-[0.5rem] flex gap-[0.75rem]';
@endphp

<h2 class="{{ $titleClass }}">Create an account</h2>

<p class="{{ $subtextClass }}">
  Already have an account?
  <a href="#" class="{{ $linkClass }}" data-auth-view="login">Log in</a>
</p>

<form method="POST" action="{{ route('register') }}" novalidate>
  @csrf

  <div class="{{ $fieldRowClass }}">
    <div class="{{ $fieldItemClass }}">
      <label class="{{ $labelClass }}" for="signup-first-name">
        <span>First Name</span>
        <span class="field-required__identifier {{ $requiredClass }}">*</span>
      </label>
      <input class="{{ $inputClass }}" type="text" id="signup-first-name" name="first_name" placeholder="Juan" required>
      <small class="d-none {{ $fieldErrorClass }}" data-field-error="first_name"></small>
    </div>
    <div class="{{ $fieldItemClass }}">
      <label class="{{ $labelClass }}" for="signup-middle-name">
        <span>Middle Name (optional)</span>
      </label>
      <input class="{{ $inputClass }}" type="text" id="signup-middle-name" name="middle_name" placeholder="Santos">
      <small class="d-none {{ $fieldErrorClass }}" data-field-error="middle_name"></small>
    </div>
  </div>

  <div class="{{ $fieldClass }}">
    <label class="{{ $labelClass }}" for="signup-last-name">
      <span>Last Name</span>
      <span class="field-required__identifier {{ $requiredClass }}">*</span>
    </label>
    <input class="{{ $inputClass }}" type="text" id="signup-last-name" name="last_name" placeholder="Dela Cruz" required>
    <small class="d-none {{ $fieldErrorClass }}" data-field-error="last_name"></small>
  </div>

  <div class="{{ $fieldClass }}">
    <label class="{{ $labelClass }}" for="signup-identifier">
      <span>Email</span>
      <span class="field-required__identifier {{ $requiredClass }}">*</span>
    </label>
    <input class="{{ $inputClass }}" type="text" id="signup-identifier" name="identifier" inputmode="email" placeholder="juanDelaCruz@gmail.com" required>
    <small class="d-none {{ $fieldErrorClass }}" data-field-error="identifier"></small>
  </div>

  <div class="{{ $fieldClass }}">
    <label class="{{ $labelClass }}" for="signup-password">
      <span>Password</span>
      <span class="field-required__identifier {{ $requiredClass }}">*</span>
    </label>
    <input class="{{ $inputClass }}" type="password" id="signup-password" name="password" placeholder="••••••••" required>

    <x-button type="button" class="auth-modal__toggle-password {{ $toggleClass }}" data-target="signup-password" aria-label="Show password" aria-pressed="false">
      <i class="ph ph-eye" aria-hidden="true"></i>
    </x-button>
    <small class="d-none {{ $fieldErrorClass }}" data-field-error="password"></small>
  </div>

  <p class="d-none {{ $errorClass }}" role="alert" data-signup-error></p>

  <div class="{{ $rowClass }}">
    <label class="{{ $rowLabelClass }}">
      <input class="{{ $checkClass }}" type="checkbox" name="terms" required>
      I agree to the <a href="#" class="{{ $linkClass }}" target="_blank">Terms & Condition</a>
    </label>
  </div>
  <small class="d-none {{ $fieldErrorClass }}" data-field-error="terms"></small>

  <x-button type="submit" class="btn-color-gradient--primary {{ $submitClass }}">
    Create account
  </x-button>
</form>

<div class="{{ $dividerClass }}">Or register with</div>

<div class="{{ $ssoClass }}">
  <x-auth.link-button
    id="google-login-btn"
    :href="route('auth.google.redirect')"
    :aria-label="'Google Login'"
    icon="images/icons/auth/google.svg"
    icon-alt="Google Icon">
    Google
  </x-auth.link-button>
</div>