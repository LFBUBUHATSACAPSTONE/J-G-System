{{--
  Auth modal verification form (Phase 7D): styles are utilities, the class strings are the PHP variables set just below.
  Hooks kept: auth-modal__code-box (verification-code.js), d-none, is-invalid, data-verification-destination, data-verification-error, #resend-code-btn.
--}}
@php
$titleClass = 'mb-[0.4rem] text-[1.5rem] font-bold min-[768px]:text-[1.75rem]';
$subtextClass = 'mb-[1.25rem] text-[0.85rem] text-auth-text-muted';
$maskedClass = 'font-semibold text-auth-accent';
$codeInputsClass = 'mb-[1.25rem] flex gap-[0.6rem]';
$codeBoxClass = 'auth-modal__code-box autofill-dark aspect-square w-full min-w-0 rounded-field [border:1px_solid_var(--color-auth-border)] bg-auth-field text-center text-[1.1rem] text-auth-text focus:border-auth-accent focus:shadow-[0_0_0_3px_var(--color-auth-ring-soft)] focus:outline-none [&.is-invalid]:border-auth-danger [&.is-invalid]:focus:border-auth-danger';
$errorClass = 'mb-[1rem] text-[0.85rem] text-auth-banner-error';
$resendClass = 'mb-[1.5rem] text-[0.85rem] text-auth-accent [background:none] [border:none] [padding:0] disabled:text-auth-placeholder [&_span]:font-semibold [&_span]:no-underline hover:[&_span]:underline';
$submitClass = 'w-full rounded-field text-[0.95rem] font-semibold [padding:0.75rem]';
@endphp

<h2 class="{{ $titleClass }}">Verification</h2>

<p class="{{ $subtextClass }}">
  Enter the Verification code below.<br>
  We sent to <span class="{{ $maskedClass }}" data-verification-destination></span>
</p>

<form method="POST" action="{{ route('verification.confirm') }}" novalidate>
  @csrf
  <input type="hidden" name="verification_token" value="">

  <div class="{{ $codeInputsClass }}">
    <input type="text" maxlength="1" inputmode="numeric" name="code[]" class="{{ $codeBoxClass }}" required>
    <input type="text" maxlength="1" inputmode="numeric" name="code[]" class="{{ $codeBoxClass }}" required>
    <input type="text" maxlength="1" inputmode="numeric" name="code[]" class="{{ $codeBoxClass }}" required>
    <input type="text" maxlength="1" inputmode="numeric" name="code[]" class="{{ $codeBoxClass }}" required>
    <input type="text" maxlength="1" inputmode="numeric" name="code[]" class="{{ $codeBoxClass }}" required>
    <input type="text" maxlength="1" inputmode="numeric" name="code[]" class="{{ $codeBoxClass }}" required>
  </div>

  <p class="d-none {{ $errorClass }}" role="alert" data-verification-error></p>

  <x-button type="button" id="resend-code-btn" class="{{ $resendClass }}" data-resend-url="{{ route('verification.resend') }}">Resend Code</x-button>

  <x-button type="submit" class="btn-color-gradient--primary {{ $submitClass }}">Confirm</x-button>
</form>