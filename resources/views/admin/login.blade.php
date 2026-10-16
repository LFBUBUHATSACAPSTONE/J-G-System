{{--
  Admin sign-in gate (the landing an admin sees first at /admin).

  FRONT END ONLY. Own layout and own Vite entry (admin-login), so it never loads the admin shell or
  the public landing / auth modal. No sign-up and no Google button: admin accounts are created by the owner.

  Names and data the back end wires later
    route('admin.login.submit')  POST JSON {email, password, remember}
    route('admin.dashboard')     where a signed-in admin goes
    route('admin.password.request')  forgot-password page (link falls back to # until it exists)
    $noAccess (optional bool)    true for a signed-in non-admin: shows the "no access" message
--}}
@php
$submitUrl = Route::has('admin.login.submit') ? route('admin.login.submit') : '';
$dashboardUrl = Route::has('admin.dashboard') ? route('admin.dashboard') : '';
$forgotUrl = Route::has('admin.password.request') ? route('admin.password.request') : '#';
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
  <main class="admin-login">
    <section class="admin-login__card" aria-labelledby="admin-login-title">

      <div class="admin-login__brand">
        <img src="{{ $imageUrl }}" alt="J&G Audio Lights and Sounds" class="admin-login__logo">
        <span class="admin-login__badge">Admin</span>
      </div>

      <h1 class="admin-login__title" id="admin-login-title">Admin Sign In</h1>
      <p class="admin-login__subtitle">Sign in to manage bookings, packages and messages.</p>

      @if ($noAccess)
      <p class="admin-login__alert" role="alert">This account doesn't have admin access. Sign in with an admin account to continue.</p>
      @endif

      <form class="admin-login__form" method="POST" action="{{ $submitUrl }}" data-admin-login
        data-redirect="{{ $dashboardUrl }}" novalidate>
        @csrf

        <p class="admin-login__alert d-none" role="alert" data-login-error></p>

        <div class="admin-login__field">
          <label for="admin-login-email">Email</label>
          <input type="text" id="admin-login-email" name="email" class="admin-login__control" inputmode="email"
            autocomplete="username" placeholder="admin@example.com" required>
          <small class="admin-login__field-error d-none" data-field-error="email"></small>
        </div>

        <div class="admin-login__field">
          <label for="admin-login-password">Password</label>
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

        <div class="admin-login__row">
          <label class="admin-login__remember">
            <input type="checkbox" name="remember">
            Remember me
          </label>
          <a href="{{ $forgotUrl }}" class="admin-login__link">Forgot password?</a>
        </div>

        <button type="submit" class="admin-login__submit">Sign in</button>
      </form>

    </section>
  </main>
</body>

</html>