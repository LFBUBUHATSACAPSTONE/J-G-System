<h2 class="auth-modal__title">Welcome Back!</h2>

<p class="auth-modal__subtext">
  Don't have an account?
  <a href="#" data-auth-view="signup">Sign up</a>
</p>

<form method="POST" action="{{ route('login') }}" novalidate>
  @csrf

  <div class="auth-modal__field">
    <label for="login-identifier">
      <span>Email</span>
      <span class="field-required__identifier">*</span>
    </label>
    <input type="text" id="login-identifier" name="identifier" inputmode="email" autocomplete="username" placeholder="juanDelaCruz@gmail.com" required>
    <small class="auth-modal__field-error d-none" data-field-error="identifier"></small>
  </div>

  <div class="auth-modal__field">
    <label for="login-password">
      <span>Password</span>
      <span class="field-required__identifier">*</span>
    </label>
    <input type="password" id="login-password" name="password" autocomplete="current-password" placeholder="••••••••" required>

    <x-button type="button" class="auth-modal__toggle-password" data-target="login-password">
      <span>
        <img src="{{ asset('images/icons/auth/show_password.svg')}}" alt="Show Password">
      </span>
    </x-button>
    <small class="auth-modal__field-error d-none" data-field-error="password"></small>
  </div>

  <p class="auth-modal__error text-danger d-none" role="alert" data-login-error></p>

  <div class="auth-modal__row">
    <label>
      <input type="checkbox" name="remember">
      Remember Me
    </label>
    <a href="#" data-auth-view="forgot-password">Forgot Password?</a>
  </div>

  <x-button type="submit" class="auth-modal__submit btn-color-gradient--primary">
    Login
  </x-button>
</form>

<div class="auth-modal__divider">Or sign in with</div>

<div class="auth-modal__sso">
  <x-auth.link-button
    id="google-login-btn"
    :href="route('auth.google.redirect')"
    :aria-label="'Google Login'"
    icon="images/icons/auth/google.svg"
    icon-alt="Google Icon">
    Google
  </x-auth.link-button>
</div>