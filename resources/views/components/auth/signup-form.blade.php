<h2 class="auth-modal__title">Create an account</h2>

<p class="auth-modal__subtext">
  Already have an account?
  <a href="#" data-auth-view="login">Log in</a>
</p>

<form method="POST" action="{{ route('register') }}">
  @csrf

  <div class="auth-modal__field-row">
    <div class="auth-modal__field">
      <label for="signup-first-name">First Name</label>
      <input type="text" id="signup-first-name" name="first_name" required>
    </div>
    <div class="auth-modal__field">
      <label for="signup-last-name">Last Name</label>
      <input type="text" id="signup-last-name" name="last_name" required>
    </div>
  </div>

  <div class="auth-modal__field">
    <label for="signup-identifier">Email or Phone</label>
    <input type="text" id="signup-identifier" name="identifier" required>
  </div>

  <div class="auth-modal__field">
    <label for="signup-password">Password</label>
    <input type="password" id="signup-password" name="password" required>
    <button type="button" class="auth-modal__toggle-password" data-target="signup-password">Show</button>
  </div>

  <div class="auth-modal__row">
    <label>
      <input type="checkbox" name="terms" required>
      I agree to the <a href="#" target="_blank">Terms & Condition</a>
    </label>
  </div>

  <button type="submit" class="auth-modal__submit">Create account</button>
</form>

<div class="auth-modal__divider">Or register with</div>

<div class="auth-modal__sso">
  <button type="button" id="google-signup-btn">Google</button>
  <button type="button" id="apple-signup-btn">Apple</button>
</div>