<h2 class="auth-modal__title">Create an account</h2>

<p class="auth-modal__subtext">
  Already have an account?
  <a href="#" data-auth-view="login">Log in</a>
</p>

<form method="POST" action="{{ route('register') }}" novalidate>
  @csrf

  <div class="auth-modal__field-row">
    <div class="auth-modal__field">
      <label for="signup-first-name">
        <span>First Name</span>
        <span class="field-required__identifier">*</span>
      </label>
      <input type="text" id="signup-first-name" name="first_name" placeholder="Juan" required>
      <small class="auth-modal__field-error d-none" data-field-error="first_name"></small>
    </div>
    <div class="auth-modal__field">
      <label for="signup-last-name">
        <span>Last Name</span>
        <span class="field-required__identifier">*</span>
      </label>
      <input type="text" id="signup-last-name" name="last_name" placeholder="Dela Cruz" required>
      <small class="auth-modal__field-error d-none" data-field-error="last_name"></small>
    </div>
  </div>

  <div class="auth-modal__field">
    <label for="signup-identifier">
      <span>Email or Phone</span>
      <span class="field-required__identifier">*</span>
    </label>
    <input type="text" id="signup-identifier" name="identifier" placeholder="juanDelaCruz@gmail.com / 09XX-XXX-XXX" required>
    <small class="auth-modal__field-error d-none" data-field-error="identifier"></small>
  </div>

  <div class="auth-modal__field">
    <label for="signup-password">
      <span>Password</span>
      <span class="field-required__identifier">*</span>
    </label>
    <input type="password" id="signup-password" name="password" placeholder="••••••••" required>

    <x-button type="button" class="auth-modal__toggle-password" data-target="signup-password">
      <span>
        <img src="{{ asset('images/icons/auth/show_password.svg')}}" alt="Show Password">
      </span>
    </x-button>
    <small class="auth-modal__field-error d-none" data-field-error="password"></small>
  </div>

  <p class="auth-modal__error text-danger d-none" role="alert" data-signup-error></p>

  <div class="auth-modal__row">
    <label>
      <input type="checkbox" name="terms" required>
      I agree to the <a href="#" target="_blank">Terms & Condition</a>
    </label>
  </div>

  <x-button type="submit" class="auth-modal__submit btn-color-gradient--primary">
    Create account
  </x-button>
</form>

<div class="auth-modal__divider">Or register with</div>

<div class="auth-modal__sso">
  <x-auth.link-button
    id="google-login-btn"
    :aria-label="'Google Login'"
    icon="images/icons/auth/google.svg"
    icon-alt="Google Icon">
    Google
  </x-auth.link-button>

  <x-auth.link-button
    id="apple-login-btn"
    :aria-label="'Apple Login'"
    icon="images/icons/auth/apple.svg"
    icon-alt="Apple Icon">
    Apple
  </x-auth.link-button>
</div>