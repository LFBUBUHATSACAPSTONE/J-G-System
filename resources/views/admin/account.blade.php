@php
$firstName = (string) data_get($admin, 'first_name', 'Arjay');
$lastName = (string) data_get($admin, 'last_name', 'Dela Cruz');
$middle = strtoupper((string) data_get($admin, 'middle_initial', ''));
$email = (string) data_get($admin, 'email', 'admin@example.com');
$phone = preg_replace('/\D/', '', (string) data_get($admin, 'phone', '09123456789'));
$avatarUrl = data_get($admin, 'avatar_url');

$fullName = trim(implode(' ', array_filter([$firstName, $middle ? $middle . '.' : null, $lastName])));
$initials = strtoupper(mb_substr($firstName, 0, 1) . mb_substr($lastName, 0, 1)) ?: 'A';
$phoneDisplay = strlen($phone) === 11 ? preg_replace('/^(\d{4})(\d{3})(\d{4})$/', '$1 $2 $3', $phone) : $phone;

$updateUrl = Route::has('admin.account.update') ? route('admin.account.update') : '';
$logoutUrl = Route::has('logout') ? route('logout') : '';

$sessions = $sessions ?? [];
$lastLogin = $lastLogin ?? null;
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
  <x-admin-header />
</head>

<body>
  <x-admin.sidebar />

  <main class="admin-content admin-account" data-admin-account data-profile-root>
    <x-admin.page-header />

    <div class="admin-account__layout">

      {{-- ============ Profile (shared card: components/profile/*, sass/profile, js/account/profile-core.js) ============ --}}
      <section class="profile-card" aria-labelledby="admin-account-title">
        <h2 class="profile-card__title" id="admin-account-title">Profile</h2>

        <form id="admin-account-form" class="profile-card__form" method="POST" action="{{ $updateUrl }}"
          enctype="multipart/form-data" novalidate>
          @csrf
          @method('PATCH')

          <x-profile.avatar :initials="$initials" :url="$avatarUrl" editable :max-mb="2" />

          {{-- Name --}}
          <x-profile.row name="name" label="Name" :value="$fullName" action="Edit">
            <div class="profile-row__fields profile-row__fields--name">
              <x-profile.field id="aa-first-name" name="first_name" label="First name" :value="$firstName"
                autocomplete="given-name" maxlength="60" />
              <x-profile.field id="aa-last-name" name="last_name" label="Last name" :value="$lastName"
                autocomplete="family-name" maxlength="60" />
              <x-profile.field id="aa-middle-initial" name="middle_initial" label="M.I. (optional)" :value="$middle"
                maxlength="1" autocomplete="off" />
            </div>
          </x-profile.row>

          {{-- Email --}}
          <x-profile.row name="email" label="Email" :value="$email" action="Change">
            <x-profile.field id="aa-email" name="email" type="email" label="New email address" :value="$email"
              autocomplete="email" inputmode="email" />
          </x-profile.row>

          {{-- Contact number --}}
          <x-profile.row name="phone" label="Contact Number" :value="$phoneDisplay" action="Change">
            <x-profile.field id="aa-phone" name="phone" type="tel" label="Contact number" :value="$phone"
              autocomplete="tel" inputmode="numeric" placeholder="09XXX-XXX-XXXX" />
          </x-profile.row>

          {{-- Password --}}
          <x-profile.row name="password" label="Password" action="Change" masked>
            <div class="profile-row__fields">
              <x-profile.field id="aa-password" name="password" type="password" label="New password" password
                autocomplete="new-password" describedby="aa-password-rules" />
              <x-profile.field id="aa-password-confirm" name="password_confirmation" type="password"
                label="Confirm new password" password autocomplete="new-password" />
            </div>
            <x-profile.password-rules id="aa-password-rules" />
          </x-profile.row>

          {{-- Appears only when the email or password is being changed --}}
          <div class="profile-confirm" data-profile-confirm hidden>
            <x-profile.field id="aa-current-password" name="current_password" type="password" label="Current password"
              password autocomplete="current-password"
              hint="Needed to confirm a change to your email or password." />
          </div>

          <x-profile.status />
        </form>

        <div class="profile-actions">
          <form method="POST" action="{{ $logoutUrl }}" data-logout data-redirect-first data-redirect-url="{{ Route::has('admin.login') ? route('admin.login') : '/' }}">
            @csrf
            <button type="submit" class="profile-btn profile-btn--secondary" @disabled(! $logoutUrl)>Log out</button>
          </form>
          <button type="submit" form="admin-account-form" class="profile-btn profile-btn--primary" data-profile-save disabled>Save</button>
        </div>
      </section>

      {{-- ============ Sign-in activity (read-only) ============ --}}
      <section class="admin-card admin-account__activity" aria-labelledby="admin-activity-title">
        <h2 class="admin-card__title" id="admin-activity-title">Sign-in activity</h2>

        @if ($lastLogin)
        <div class="admin-account-last">
          <span class="admin-account-last__label">Last sign-in</span>
          <span class="admin-account-last__value">{{ $lastLogin->format('F j, Y') }} at {{ $lastLogin->format('g:i A') }}</span>
        </div>
        @endif

        @if (count($sessions))
        <ul class="admin-account-sessions">
          @foreach ($sessions as $session)
          <li class="admin-account-session">
            <span class="admin-account-session__icon" aria-hidden="true"><i class="ph ph-{{ $session['icon'] ?? 'desktop' }}"></i></span>
            <span class="admin-account-session__text">
              <span class="admin-account-session__device">{{ $session['device'] }}</span>
              <span class="admin-account-session__meta">{{ $session['location'] }}</span>
              <span class="admin-account-session__meta">
                {{ ! empty($session['current']) ? 'Active now' : 'Last active ' . $session['last_active']->diffForHumans() }}
              </span>
            </span>
            @if (! empty($session['current']))
            <span class="admin-account-session__badge">This device</span>
            @endif
          </li>
          @endforeach
        </ul>
        @else
        <p class="admin-account-last__label">No sign-in activity to show yet.</p>
        @endif
      </section>

    </div>
  </main>
</body>

</html>