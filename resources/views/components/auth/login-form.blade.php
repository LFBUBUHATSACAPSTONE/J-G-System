<h2 class="auth-modal__title">Welcome Back!</h2>

<p class="auth-modal__subtext">
  Don't have an account?
  <a href="#" data-auth-view="signup">Sign up</a>
</p>

<form method="POST" action="{{ route('login') }}">
  @csrf

  <div class="auth-modal__field">
    <label for="login-identifier">Email or Phone</label>
    <input type="text" id="login-identifier" name="identifier" required>
  </div>

  <div class="auth-modal__field">
    <label for="login-password">Password</label>
    <input type="password" id="login-password" name="password" required>
    <button type="button" class="auth-modal__toggle-password" data-target="login-password">Show</button>
  </div>

  <div class="auth-modal__row">
    <label>
      <input type="checkbox" name="remember">
      Remember Me
    </label>
    <a href="#" data-auth-view="forgot-password">Forgot Password?</a>
  </div>

  <button type="submit" class="auth-modal__submit">Login</button>
</form>

<div class="auth-modal__divider">Or register with</div>

<div class="auth-modal__sso">
  <button type="button" id="google-login-btn">Google</button>
  <button type="button" id="apple-login-btn">Apple</button>
</div>