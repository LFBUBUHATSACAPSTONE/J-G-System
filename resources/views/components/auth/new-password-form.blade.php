<h2 class="auth-modal__title">New Password</h2>

<form method="POST" action="{{ route('password.update') }}">
  @csrf

  <div class="auth-modal__field">
    <label for="new-password">Enter New Password</label>
    <input type="password" id="new-password" name="password" required>

    <x-button type="button" class="auth-modal__toggle-password" data-target="new-password">
      <span>
        <img src="{{ asset('images/icons/auth/show_password.svg')}}" alt="Show Password">
      </span>
    </x-button>

  </div>

  <div class="auth-modal__field">
    <label for="new-password-confirm">Confirm Password</label>
    <input type="password" id="new-password-confirm" name="password_confirmation" required>

    <x-button type="button" class="auth-modal__toggle-password" data-target="new-password-confirm">
      <span>
        <img src="{{ asset('images/icons/auth/show_password.svg')}}" alt="Show Password">
      </span>
    </x-button>

  </div>

  <a href="#" data-auth-view="login">Back to Log in</a>

  <button type="submit" class="auth-modal__submit">Confirm</button>
</form>