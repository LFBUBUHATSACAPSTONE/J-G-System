<h2 class="auth-modal__title">Forgot Password</h2>
<p class="auth-modal__subtext">Enter Email or Phone Number</p>

<form method="POST" action="{{ route('password.email') }}">
  @csrf

  <div class="auth-modal__field">
    <label for="forgot-identifier">Email or Phone</label>
    <input type="text" id="forgot-identifier" name="identifier" required>
  </div>

  <p class="auth-modal__error text-danger d-none" role="alert" data-forgot-error></p>

  <a href="#" data-auth-view="login">Back to Log in</a>

  <x-button type="submit" class="auth-modal__submit">Confirm</x-button>
</form>