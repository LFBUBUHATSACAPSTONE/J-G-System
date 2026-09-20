<h2 class="auth-modal__title">New Password</h2>

<form method="POST" action="{{ route('password.update') }}" novalidate>
  @csrf

  <div class="auth-modal__field">
    <label for="new-password">Enter New Password</label>
    <input type="password" id="new-password" name="password" autocomplete="new-password" required>

    <x-button type="button" class="auth-modal__toggle-password" data-target="new-password">
      <img src="{{ asset('images/icons/auth/show_password.svg')}}" alt="Show Password">
    </x-button>
    <small class="auth-modal__field-error d-none" data-field-error="password"></small>
  </div>

  <div class="auth-modal__field">
    <label for="new-password-confirm">Confirm Password</label>
    <input type="password" id="new-password-confirm" name="password_confirmation" autocomplete="new-password" required>

    <x-button type="button" class="auth-modal__toggle-password" data-target="new-password-confirm">
      <img src="{{ asset('images/icons/auth/show_password.svg')}}" alt="Show Password">
    </x-button>
    <small class="auth-modal__field-error d-none" data-field-error="password_confirmation"></small>
  </div>

  <p class="auth-modal__error text-danger d-none" role="alert" data-new-password-error></p>

  <a href="#" data-auth-view="login">Back to Log in</a>

  <x-button type="submit" class="auth-modal__submit">Confirm</x-button>
</form>