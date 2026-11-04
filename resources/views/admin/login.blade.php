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

// Class strings (Tailwind, Phase 10A), written whole so Tailwind can find them.
$cardClass = 'relative isolate w-full max-w-md overflow-hidden rounded-card bg-auth-surface px-6 pt-6 pb-8 shadow-[0_1rem_3rem_var(--color-shadow)] md:px-11 md:py-8 before:pointer-events-none before:absolute before:-top-[20%] before:-right-[20%] before:-z-1 before:h-[60%] before:w-[70%] before:bg-[image:radial-gradient(circle_at_top_right,var(--color-auth-glow-violet)_0%,var(--color-auth-glow-rose)_45%,transparent_75%)] before:opacity-35 before:blur-[60px]';
$badgeClass = 'rounded-full bg-brand px-3 py-1 text-sm font-semibold tracking-[0.08em] text-on-brand uppercase';
$titleClass = 'mx-0 mt-0 mb-[0.4rem] text-[1.5rem] leading-[1.2] font-bold md:text-[1.75rem]';
$subtitleClass = 'mx-0 mt-0 mb-5 text-[0.85rem] text-auth-text-muted';
$labelClass = 'mb-[0.35rem] block text-[0.8rem] font-normal text-auth-text-muted';
$controlClass = 'autofill-dark motion-safe:transition-[border-color,box-shadow] motion-safe:duration-150 motion-safe:ease-[ease] w-full rounded-field border border-transparent bg-auth-field py-[0.7rem] text-[0.9rem] leading-normal text-auth-text placeholder:text-auth-placeholder focus:border-auth-accent focus:outline-3 focus:outline-offset-0 focus:outline-auth-focus-soft [&.is-invalid]:border-auth-danger [&.is-invalid]:focus:border-auth-danger';
$toggleClass = 'absolute top-10 right-[0.9rem] cursor-pointer border-0 bg-transparent p-0 leading-none focus-visible:rounded-sm focus-visible:shadow-[0_0_0_3px_var(--color-auth-focus)] focus-visible:outline-none [&_i]:block [&_i]:size-5 [&_i]:text-[1.25rem] [&_i]:text-auth-text [&_i]:opacity-60 [&:hover_i]:opacity-100 [&:focus-visible_i]:opacity-100';
$fieldErrorClass = 'mt-[0.35rem] text-[0.78rem] text-auth-danger not-[.d-none]:block';
$alertClass = 'mx-0 mt-0 mb-4 text-[0.85rem] text-auth-banner-error';
$standaloneLinkClass = 'mb-5 block text-[0.85rem] font-semibold text-auth-accent hover:underline';
$rowClass = 'mb-6 flex flex-wrap items-center justify-between gap-2 text-[0.8rem] text-auth-text-muted';
$rememberClass = 'm-0 flex cursor-pointer items-center gap-2';
$checkboxClass = 'relative m-0 size-4 shrink-0 cursor-pointer appearance-none rounded-sm border border-auth-border bg-auth-field checked:border-brand checked:bg-brand checked:after:absolute checked:after:top-px checked:after:left-1 checked:after:h-2 checked:after:w-1 checked:after:rotate-45 checked:after:border-r-2 checked:after:border-b-2 checked:after:border-on-brand focus-visible:shadow-[0_0_0_3px_var(--color-auth-focus-soft)] focus-visible:outline-none';
$linkClass = 'font-semibold text-auth-accent hover:underline focus-visible:rounded-sm focus-visible:shadow-[0_0_0_3px_var(--color-auth-focus)] focus-visible:outline-none motion-safe:transition-shadow motion-safe:duration-150 motion-safe:ease-[ease]';
$submitClass = 'w-full cursor-pointer rounded-field border-0 p-3 text-[0.95rem] font-semibold focus-visible:shadow-[0_0_0_3px_var(--color-auth-focus)] focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-60';
$codeInputsClass = 'mb-5 flex gap-[0.6rem]';
$codeBoxClass = 'autofill-dark aspect-square w-full min-w-0 rounded-field border border-auth-border bg-auth-field px-[2px] py-px text-center text-[1.1rem] text-auth-text focus:border-auth-accent focus:outline-3 focus:outline-offset-0 focus:outline-auth-focus-soft [&.is-invalid]:border-auth-danger [&.is-invalid]:focus:border-auth-danger';
$maskedClass = 'font-semibold [overflow-wrap:anywhere] text-auth-accent';
$resendClass = 'mb-6 inline-block cursor-pointer border-0 bg-transparent p-0 text-[0.85rem] font-semibold text-auth-accent focus-visible:rounded-sm focus-visible:shadow-[0_0_0_3px_var(--color-auth-focus)] focus-visible:outline-none enabled:hover:underline disabled:cursor-not-allowed disabled:text-auth-placeholder';
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

  @vite(['resources/js/admin-login.js', 'resources/css/tailwind.css'])

  <title>Sign in - {{ $appName }}</title>
</head>

<body class="admin-login-page m-0 bg-auth-bg font-sans leading-normal text-auth-text">
  <img src="{{ asset('images/backgrounds/design/landing_bg.webp') }}" alt="" class="pointer-events-none fixed inset-0 -z-1 size-full object-cover">
  <main class="grid min-h-dvh place-items-center p-4 min-[576px]:p-6">
    <section class="{{ $cardClass }}" aria-label="Admin sign in" data-admin-auth data-current-view="login">

      <div class="mb-6 grid justify-items-start gap-3">
        <img src="{{ $imageUrl }}" alt="J&G Audio Lights and Sounds" class="h-auto w-full max-w-32">
        <span class="{{ $badgeClass }}">Admin</span>
      </div>

      @if ($noAccess)
      <p class="{{ $alertClass }}" role="alert">This account doesn't have admin access. Sign in with an admin account to continue.</p>
      @endif

      {{-- View: Login --}}
      <div class="admin-login__view" data-view="login">
        <h1 class="{{ $titleClass }}" id="admin-login-title">Welcome Back, Admin!</h1>
        <p class="{{ $subtitleClass }}">Sign in to manage bookings, packages and messages.</p>

        <form class="m-0" method="POST" action="{{ $submitUrl }}" data-admin-login
          data-redirect="{{ $dashboardUrl }}" novalidate>
          @csrf

          <div class="relative mb-[0.85rem]">
            <label for="admin-login-email" class="{{ $labelClass }}">Email <span class="text-auth-danger" aria-hidden="true">*</span></label>
            <input type="text" id="admin-login-email" name="email" class="admin-login__control {{ $controlClass }} px-4" inputmode="email"
              autocomplete="username" placeholder="admin@example.com" required>
            <small class="{{ $fieldErrorClass }} d-none" data-field-error="email"></small>
          </div>

          <div class="relative mb-[0.85rem]">
            <label for="admin-login-password" class="{{ $labelClass }}">Password <span class="text-auth-danger" aria-hidden="true">*</span></label>
            <div>
              <input type="password" id="admin-login-password" name="password" class="admin-login__control {{ $controlClass }} pr-12 pl-4"
                autocomplete="current-password" placeholder="••••••••" required>
              <button type="button" class="admin-login__toggle {{ $toggleClass }}" data-target="admin-login-password"
                aria-label="Show password" aria-pressed="false">
                <i class="ph ph-eye" aria-hidden="true"></i>
              </button>
            </div>
            <small class="{{ $fieldErrorClass }} d-none" data-field-error="password"></small>
          </div>

          <p class="{{ $alertClass }} d-none" role="alert" data-login-error></p>

          <div class="{{ $rowClass }}">
            <label class="{{ $rememberClass }}">
              <input type="checkbox" name="remember" class="{{ $checkboxClass }}">
              Remember Me
            </label>
            <a href="#" class="{{ $linkClass }}" data-auth-view="forgot-password">Forgot Password?</a>
          </div>

          <button type="submit" class="{{ $submitClass }} btn-color-gradient--primary">Sign in</button>
        </form>
      </div>

      {{-- View: Forgot Password --}}
      <div class="admin-login__view d-none" data-view="forgot-password">
        <h1 class="{{ $titleClass }}">Forgot Password</h1>
        <p class="{{ $subtitleClass }}">Enter your email address</p>

        <form class="m-0" method="POST" action="{{ $forgotUrl }}" data-admin-forgot novalidate>
          @csrf

          <div class="relative mb-[0.85rem]">
            <label for="admin-forgot-email" class="{{ $labelClass }}">Email <span class="text-auth-danger" aria-hidden="true">*</span></label>
            <input type="text" id="admin-forgot-email" name="email" class="admin-login__control {{ $controlClass }} px-4" inputmode="email"
              autocomplete="username" placeholder="admin@example.com" required>
            <small class="{{ $fieldErrorClass }} d-none" data-field-error="email"></small>
          </div>

          <p class="{{ $alertClass }} d-none" role="alert" data-forgot-error></p>

          <a href="#" class="{{ $standaloneLinkClass }}" data-auth-view="login">Back to Log in</a>

          <button type="submit" class="{{ $submitClass }} btn-color-gradient--primary">Confirm</button>
        </form>
      </div>

      {{-- View: Verification --}}
      <div class="admin-login__view d-none" data-view="verification">
        <h1 class="{{ $titleClass }}">Verification</h1>
        <p class="{{ $subtitleClass }}">
          Enter the Verification code below.<br>
          We sent to <span class="{{ $maskedClass }}" data-verification-destination></span>
        </p>

        <form class="m-0" method="POST" action="{{ $confirmUrl }}" data-admin-verification novalidate>
          @csrf

          <div class="{{ $codeInputsClass }}" role="group" aria-label="6-digit verification code">
            @for ($i = 1; $i <= 6; $i++)
              <input type="text" maxlength="1" inputmode="numeric" autocomplete="one-time-code" name="code[]"
              class="admin-login__code-box {{ $codeBoxClass }}" aria-label="Digit {{ $i }}" required>
              @endfor
          </div>

          <p class="{{ $alertClass }} d-none" role="alert" data-verification-error></p>

          <button type="button" class="{{ $resendClass }}" id="admin-resend-code-btn"
            data-resend-url="{{ $resendUrl }}">Resend Code</button>

          <button type="submit" class="{{ $submitClass }} btn-color-gradient--primary">Confirm</button>
        </form>
      </div>

      {{-- View: New Password --}}
      <div class="admin-login__view d-none" data-view="new-password">
        <h1 class="{{ $titleClass }}">New Password</h1>
        <p class="{{ $subtitleClass }}">Choose a new password for your admin account.</p>

        <form class="m-0" method="POST" action="{{ $updateUrl }}" data-admin-new-password novalidate>
          @csrf

          <div class="relative mb-[0.85rem]">
            <label for="admin-new-password" class="{{ $labelClass }}">Enter New Password <span class="text-auth-danger" aria-hidden="true">*</span></label>
            <div>
              <input type="password" id="admin-new-password" name="password" class="admin-login__control {{ $controlClass }} pr-12 pl-4"
                autocomplete="new-password" placeholder="••••••••" required>
              <button type="button" class="admin-login__toggle {{ $toggleClass }}" data-target="admin-new-password"
                aria-label="Show password" aria-pressed="false">
                <i class="ph ph-eye" aria-hidden="true"></i>
              </button>
            </div>
            <small class="{{ $fieldErrorClass }} d-none" data-field-error="password"></small>
          </div>

          <div class="relative mb-[0.85rem]">
            <label for="admin-new-password-confirm" class="{{ $labelClass }}">Confirm Password <span class="text-auth-danger" aria-hidden="true">*</span></label>
            <div>
              <input type="password" id="admin-new-password-confirm" name="password_confirmation"
                class="admin-login__control {{ $controlClass }} pr-12 pl-4" autocomplete="new-password" placeholder="••••••••" required>
              <button type="button" class="admin-login__toggle {{ $toggleClass }}" data-target="admin-new-password-confirm"
                aria-label="Show password" aria-pressed="false">
                <i class="ph ph-eye" aria-hidden="true"></i>
              </button>
            </div>
            <small class="{{ $fieldErrorClass }} d-none" data-field-error="password_confirmation"></small>
          </div>

          <p class="{{ $alertClass }} d-none" role="alert" data-new-password-error></p>

          <a href="#" class="{{ $standaloneLinkClass }}" data-auth-view="login">Back to Log in</a>

          <button type="submit" class="{{ $submitClass }} btn-color-gradient--primary">Confirm</button>
        </form>
      </div>

    </section>
  </main>
</body>

</html>