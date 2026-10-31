{{--
  Auth modal form (Phase 7C): styles are utilities, the class strings are the PHP variables set just below.
  The same strings are in the other auth forms: a change to one is a change to all of them.
  Hooks kept: d-none, is-invalid, data-field-error, data-auth-view, auth-modal__toggle-password, field-required__identifier.
--}}
@php
$titleClass = 'mb-[0.4rem] text-[1.5rem] font-bold min-[768px]:text-[1.75rem]';
$subtextClass = 'mb-[1.25rem] text-[0.85rem] text-auth-text-muted';
$fieldClass = 'relative mb-[0.85rem]';
$labelClass = 'mb-[0.35rem] block text-[0.8rem] font-normal text-auth-text-muted';
$requiredClass = 'text-auth-danger';
$inputClass = 'autofill-dark w-full rounded-field [border:1px_solid_transparent] bg-auth-field px-[1rem] py-[0.7rem] text-[0.9rem] text-auth-text placeholder:text-auth-placeholder focus:border-auth-accent focus:shadow-[0_0_0_3px_var(--color-auth-ring-soft)] focus:outline-none [&.is-invalid]:border-auth-danger [&.is-invalid]:focus:border-auth-danger';
$fieldErrorClass = 'mt-[0.35rem] text-[0.78rem] text-auth-danger [&:not(.d-none)]:block';
$errorClass = 'mb-[1rem] text-[0.85rem] text-auth-banner-error';
$standaloneClass = 'mb-[1.25rem] block text-[0.85rem] font-semibold text-auth-accent no-underline hover:underline';
@endphp

<h2 class="{{ $titleClass }}">Forgot Password</h2>
<p class="{{ $subtextClass }}">Enter your email address</p>

<form method="POST" action="{{ route('password.email') }}" novalidate>
  @csrf

  <div class="{{ $fieldClass }}">
    <label class="{{ $labelClass }}" for="forgot-identifier">
      <span>Email</span>
      <span class="field-required__identifier {{ $requiredClass }}">*</span>
    </label>
    <input class="{{ $inputClass }}" type="text" id="forgot-identifier" name="identifier" inputmode="email" autocomplete="username" placeholder="juanDelaCruz@gmail.com" required>
    <small class="d-none {{ $fieldErrorClass }}" data-field-error="identifier"></small>
  </div>

  <p class="d-none {{ $errorClass }}" role="alert" data-forgot-error></p>

  <a href="#" class="{{ $standaloneClass }}" data-auth-view="login">Back to Log in</a>

  <x-button type="submit" class="auth-modal__submit btn-color-gradient--primary">Confirm</x-button>
</form>