<h2 class="auth-modal__title">Forgot Password</h2>
<p class="auth-modal__subtext">Enter Email or Phone Number</p>

<form method="POST" action="{{ route('password.email') }}" novalidate>
  @csrf

  <div class="auth-modal__field">
    <label for="forgot-identifier">
      <span>Email or Phone</span>
      <span class="field-required__identifier">*</span>
    </label>
    <input type="text" id="forgot-identifier" name="identifier" autocomplete="username" placeholder="juanDelaCruz@gmail.com / 09XX-XXX-XXX" required>
    <small class="auth-modal__field-error d-none" data-field-error="identifier"></small>
  </div>

  <p class="auth-modal__error text-danger d-none" role="alert" data-forgot-error></p>

  <a href="#" class="auth-modal__standalone-link" data-auth-view="login">Back to Log in</a>

  <x-button type="submit" class="auth-modal__submit btn-color-gradient--primary">Confirm</x-button>
</form>