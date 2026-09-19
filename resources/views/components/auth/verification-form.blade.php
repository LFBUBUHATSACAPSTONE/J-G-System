<h2 class="auth-modal__title">Verification</h2>

<p class="auth-modal__subtext">
  Enter the Verification code below.<br>
  We sent to <span class="auth-modal__masked-destination" data-verification-destination></span>
</p>

<form method="POST" action="{{ route('verification.confirm') }}">
  @csrf

  <div class="auth-modal__code-inputs">
    <input type="text" maxlength="1" inputmode="numeric" name="code[]" class="auth-modal__code-box" required>
    <input type="text" maxlength="1" inputmode="numeric" name="code[]" class="auth-modal__code-box" required>
    <input type="text" maxlength="1" inputmode="numeric" name="code[]" class="auth-modal__code-box" required>
    <input type="text" maxlength="1" inputmode="numeric" name="code[]" class="auth-modal__code-box" required>
    <input type="text" maxlength="1" inputmode="numeric" name="code[]" class="auth-modal__code-box" required>
    <input type="text" maxlength="1" inputmode="numeric" name="code[]" class="auth-modal__code-box" required>
  </div>

  <button type="button" id="resend-code-btn">Resend Code</button>

  <x-button type="submit" class="auth-modal__submit">Confirm</x-button>
</form>