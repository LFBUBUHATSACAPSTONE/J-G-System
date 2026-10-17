{{--
  Admin sign-in gate (the landing an admin sees first at /admin).

  Views (same swap pattern as the user auth modal, ONE card, no page reloads): login, forgot-password,
  verification, new-password. `[data-auth-view]` links switch the view; js/admin-login.js owns the logic.

  FRONT END ONLY. Own layout and own Vite entry (admin-login), so it never loads the admin shell or
  the public landing / auth modal. No sign-up and no Google button: admin accounts are created by the owner.

  Names and data the back end wires later
    route('admin.login.submit')  POST JSON {email, password, remember}
    route('admin.dashboard')     where a signed-in admin goes
    route('admin.password.email')        POST JSON {email}                      forgot password: sends the code
    route('admin.verification.confirm')  POST JSON {code, context, verification_token}
    route('admin.verification.resend')   POST JSON {context, verification_token}
    route('admin.password.update')       POST JSON {token, email, password, password_confirmation}
    $noAccess (optional bool)    true for a signed-in non-admin: shows the "no access" message
--}}
@php
$submitUrl = Route::has('admin.login.submit') ? route('admin.login.submit') : '';
$dashboardUrl = Route::has('admin.dashboard') ? route('admin.dashboard') : '';
$forgotUrl = Route::has('admin.password.email') ? route('admin.password.email') : '';
$confirmUrl = Route::has('admin.verification.confirm') ? route('admin.verification.confirm') : '';
$resendUrl = Route::has('admin.verification.resend') ? route('admin.verification.resend') : '';
$updateUrl = Route::has('admin.password.update') ? route('admin.password.update') : '';
$noAccess = (bool) ($noAccess ?? false);
$appName = config('admin.name', 'J&G Admin');
$imageUrl = asset(config('admin.logo', 'images/logo/J&G_official_logo.webp'));
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="robots" content="noindex, nofollow, noarchive">
  <meta name="referrer" content="no-referrer">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="description" content="Sign in to the J&G Audio admin panel.">
  <meta name="author" content="{{ config('admin.author') }}">

  <link rel="icon" type="image/webp" href="{{ $imageUrl }}">
  <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">

  @vite(['resources/sass/admin-login.scss', 'resources/js/admin-login.js'])

  <title>Sign in - {{ $appName }}</title>
</head>

<body>
  <img src="{{ asset('images/backgrounds/design/landing_bg.webp') }}" alt="" class="admin-login__bg">
  <main class="admin-login">
    <section class="admin-login__card" aria-label="Admin sign in" data-admin-auth data-current-view="login">

      <div class="admin-login__brand">
        <img src="{{ $imageUrl }}" alt="J&G Audio Lights and Sounds" class="admin-login__logo">
        <span class="admin-login__badge">Admin</span>
      </div>

      @if ($noAccess)
      <p class="admin-login__alert" role="alert">This account doesn't have admin access. Sign in with an admin account to continue.</p>
      @endif

      {{-- View: Login --}}
      <div class="admin-login__view" data-view="login">
        <h1 class="admin-login__title" id="admin-login-title">Welcome Back, Admin!</h1>
        <p class="admin-login__subtitle">Sign in to manage bookings, packages and messages.</p>

        <form class="admin-login__form" method="POST" action="{{ $submitUrl }}" data-admin-login
          data-redirect="{{ $dashboardUrl }}" novalidate>
          @csrf

          <div class="admin-login__field">
            <label for="admin-login-email">Email <span class="admin-login__required" aria-hidden="true">*</span></label>
            <input type="text" id="admin-login-email" name="email" class="admin-login__control" inputmode="email"
              autocomplete="username" placeholder="admin@example.com" required>
            <small class="admin-login__field-error d-none" data-field-error="email"></small>
          </div>

          <div class="admin-login__field">
            <label for="admin-login-password">Password <span class="admin-login__required" aria-hidden="true">*</span></label>
            <div class="admin-login__password">
              <input type="password" id="admin-login-password" name="password" class="admin-login__control"
                autocomplete="current-password" placeholder="••••••••" required>
              <button type="button" class="admin-login__toggle" data-target="admin-login-password"
                aria-label="Show password" aria-pressed="false">
                <i class="ph ph-eye" aria-hidden="true"></i>
              </button>
            </div>
            <small class="admin-login__field-error d-none" data-field-error="password"></small>
          </div>

          <p class="admin-login__alert d-none" role="alert" data-login-error></p>

          <div class="admin-login__row">
            <label class="admin-login__remember">
              <input type="checkbox" name="remember">
              Remember Me
            </label>
            <a href="#" class="admin-login__link" data-auth-view="forgot-password">Forgot Password?</a>
          </div>

          <button type="submit" class="admin-login__submit btn-color-gradient--primary">Sign in</button>
        </form>
      </div>

      {{-- View: Forgot Password --}}
      <div class="admin-login__view d-none" data-view="forgot-password">
        <h1 class="admin-login__title">Forgot Password</h1>
        <p class="admin-login__subtitle">Enter your email address</p>

        <form class="admin-login__form" method="POST" action="{{ $forgotUrl }}" data-admin-forgot novalidate>
          @csrf

          <div class="admin-login__field">
            <label for="admin-forgot-email">Email <span class="admin-login__required" aria-hidden="true">*</span></label>
            <input type="text" id="admin-forgot-email" name="email" class="admin-login__control" inputmode="email"
              autocomplete="username" placeholder="admin@example.com" required>
            <small class="admin-login__field-error d-none" data-field-error="email"></small>
          </div>

          <p class="admin-login__alert d-none" role="alert" data-forgot-error></p>

          <a href="#" class="admin-login__standalone-link" data-auth-view="login">Back to Log in</a>

          <button type="submit" class="admin-login__submit btn-color-gradient--primary">Confirm</button>
        </form>
      </div>

      {{-- View: Verification --}}
      <div class="admin-login__view d-none" data-view="verification">
        <h1 class="admin-login__title">Verification</h1>
        <p class="admin-login__subtitle">
          Enter the Verification code below.<br>
          We sent to <span class="admin-login__masked" data-verification-destination></span>
        </p>

        <form class="admin-login__form" method="POST" action="{{ $confirmUrl }}" data-admin-verification novalidate>
          @csrf

          <div class="admin-login__code-inputs" role="group" aria-label="6-digit verification code">
            @for ($i = 1; $i <= 6; $i++)
              <input type="text" maxlength="1" inputmode="numeric" autocomplete="one-time-code" name="code[]"
              class="admin-login__code-box" aria-label="Digit {{ $i }}" required>
              @endfor
          </div>

          <p class="admin-login__alert d-none" role="alert" data-verification-error></p>

          <button type="button" class="admin-login__resend" id="admin-resend-code-btn"
            data-resend-url="{{ $resendUrl }}">Resend Code</button>

          <button type="submit" class="admin-login__submit btn-color-gradient--primary">Confirm</button>
        </form>
      </div>

      {{-- View: New Password --}}
      <div class="admin-login__view d-none" data-view="new-password">
        <h1 class="admin-login__title">New Password</h1>
        <p class="admin-login__subtitle">Choose a new password for your admin account.</p>

        <form class="admin-login__form" method="POST" action="{{ $updateUrl }}" data-admin-new-password novalidate>
          @csrf

          <div class="admin-login__field">
            <label for="admin-new-password">Enter New Password <span class="admin-login__required" aria-hidden="true">*</span></label>
            <div class="admin-login__password">
              <input type="password" id="admin-new-password" name="password" class="admin-login__control"
                autocomplete="new-password" placeholder="••••••••" required>
              <button type="button" class="admin-login__toggle" data-target="admin-new-password"
                aria-label="Show password" aria-pressed="false">
                <i class="ph ph-eye" aria-hidden="true"></i>
              </button>
            </div>
            <small class="admin-login__field-error d-none" data-field-error="password"></small>
          </div>

          <div class="admin-login__field">
            <label for="admin-new-password-confirm">Confirm Password <span class="admin-login__required" aria-hidden="true">*</span></label>
            <div class="admin-login__password">
              <input type="password" id="admin-new-password-confirm" name="password_confirmation"
                class="admin-login__control" autocomplete="new-password" placeholder="••••••••" required>
              <button type="button" class="admin-login__toggle" data-target="admin-new-password-confirm"
                aria-label="Show password" aria-pressed="false">
                <i class="ph ph-eye" aria-hidden="true"></i>
              </button>
            </div>
            <small class="admin-login__field-error d-none" data-field-error="password_confirmation"></small>
          </div>

          <p class="admin-login__alert d-none" role="alert" data-new-password-error></p>

          <a href="#" class="admin-login__standalone-link" data-auth-view="login">Back to Log in</a>

          <button type="submit" class="admin-login__submit btn-color-gradient--primary">Confirm</button>
        </form>
      </div>

    </section>
  </main>
</body>

</html>