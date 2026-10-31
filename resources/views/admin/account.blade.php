@php
$firstName = (string) data_get($admin, 'first_name', 'Arjay');
$lastName = (string) data_get($admin, 'last_name', 'Dela Cruz');
$middleName = (string) data_get($admin, 'middle_name', '');
$email = (string) data_get($admin, 'email', 'admin@example.com');
$phone = preg_replace('/\D/', '', (string) data_get($admin, 'phone', '09123456789'));
$avatarUrl = data_get($admin, 'avatar_url');

$fullName = trim(implode(' ', array_filter([$firstName, $middleName, $lastName])));
$initials = strtoupper(mb_substr($firstName, 0, 1) . mb_substr($lastName, 0, 1)) ?: 'A';
$phoneDisplay = strlen($phone) === 11 ? preg_replace('/^(\d{4})(\d{3})(\d{4})$/', '$1 $2 $3', $phone) : $phone;

$updateUrl = Route::has('admin.account.update') ? route('admin.account.update') : '';
$logoutUrl = Route::has('logout') ? route('logout') : '';

$sessions = $sessions ?? [];
$lastLogin = $lastLogin ?? null;
$cardClass = 'w-full max-w-xl rounded-card bg-surface p-4 font-sans text-ink min-[576px]:p-8';
$cardTitleClass = 'mb-6 text-center text-fluid-icon font-bold text-ink';
$actionsClass = 'mt-6 grid grid-cols-2 gap-3 [&_form]:contents';
$primaryBtnClass = 'w-full cursor-pointer rounded-field border-2 border-transparent bg-brand px-4 py-2 leading-normal font-semibold text-on-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text enabled:hover:bg-brand-hover disabled:cursor-not-allowed disabled:opacity-50';
$secondaryBtnClass = 'w-full cursor-pointer rounded-field border-2 border-transparent bg-brand-soft px-4 py-2 leading-normal font-semibold text-brand-text focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text enabled:hover:bg-brand-soft-hover disabled:cursor-not-allowed disabled:opacity-50';
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
  <x-admin-header />
</head>

<body class="bg-sidebar font-sans">
  <x-admin.sidebar />

  <main class="admin-content font-sans" data-admin-account data-profile-root>
    <x-admin.page-header />

    <div class="grid grid-cols-[minmax(0,1fr)] items-start gap-6 min-[1200px]:grid-cols-[minmax(0,36rem)_minmax(0,24rem)]">

      {{-- ============ Profile (shared card: components/profile/*, sass/profile, js/account/profile-core.js) ============ --}}
      <section class="{{ $cardClass }}" aria-labelledby="admin-account-title">
        <h2 class="{{ $cardTitleClass }}" id="admin-account-title">Profile</h2>

        <form id="admin-account-form" class="grid gap-4" method="POST" action="{{ $updateUrl }}"
          enctype="multipart/form-data" novalidate>
          @csrf
          @method('PATCH')

          <x-profile.avatar :initials="$initials" :url="$avatarUrl" editable :max-mb="2" />

          {{-- Name --}}
          <x-profile.row name="name" label="Name" :value="$fullName" action="Edit">
            <div class="grid grid-cols-[minmax(0,1fr)] gap-3 min-[576px]:grid-cols-3">
              <x-profile.field id="aa-first-name" name="first_name" label="First name" :value="$firstName"
                autocomplete="given-name" maxlength="60" />
              <x-profile.field id="aa-middle-name" name="middle_name" label="Middle name (optional)" :value="$middleName"
                autocomplete="additional-name" maxlength="60" />
              <x-profile.field id="aa-last-name" name="last_name" label="Last name" :value="$lastName"
                autocomplete="family-name" maxlength="60" />
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
            <div class="grid grid-cols-[minmax(0,1fr)] gap-3">
              <x-profile.field id="aa-password" name="password" type="password" label="New password" password
                autocomplete="new-password" describedby="aa-password-rules" />
              <x-profile.field id="aa-password-confirm" name="password_confirmation" type="password"
                label="Confirm new password" password autocomplete="new-password" />
            </div>
            <x-profile.password-rules id="aa-password-rules" />
          </x-profile.row>

          {{-- Appears only when the email or password is being changed --}}
          <div class="rounded-panel bg-brand-soft p-4" data-profile-confirm hidden>
            <x-profile.field id="aa-current-password" name="current_password" type="password" label="Current password"
              password autocomplete="current-password"
              hint="Needed to confirm a change to your email or password." />
          </div>

          <x-profile.status />
        </form>

        <div class="{{ $actionsClass }}">
          <form method="POST" action="{{ $logoutUrl }}" data-logout data-redirect-first data-redirect-url="{{ Route::has('admin.login') ? route('admin.login') : '/' }}">
            @csrf
            <button type="submit" class="{{ $secondaryBtnClass }}" @disabled(! $logoutUrl)>Log out</button>
          </form>
          <button type="submit" form="admin-account-form" class="{{ $primaryBtnClass }}" data-profile-save disabled>Save</button>
        </div>
      </section>

      {{-- ============ Sign-in activity (read-only) ============ --}}
      <section class="rounded-card bg-surface p-4 min-[576px]:p-6" aria-labelledby="admin-activity-title">
        <h2 class="mb-4 text-fluid-heading font-semibold text-brand-text" id="admin-activity-title">Sign-in activity</h2>

        @if ($lastLogin)
        <div class="mb-4 grid gap-0.5">
          <span class="text-sm text-ink-muted">Last sign-in</span>
          <span class="font-semibold text-ink">{{ $lastLogin->format('F j, Y') }} at {{ $lastLogin->format('g:i A') }}</span>
        </div>
        @endif

        @if (count($sessions))
        <ul class="m-0 grid list-none gap-3 p-0">
          @foreach ($sessions as $session)
          <li class="flex items-center gap-3 rounded-panel bg-panel p-3">
            <span class="grid size-10 flex-none place-items-center rounded-full bg-brand-soft text-[1.25rem] text-brand-text" aria-hidden="true"><i class="ph ph-{{ $session['icon'] ?? 'desktop' }}"></i></span>
            <span class="grid min-w-0 flex-1">
              <span class="font-semibold text-ink wrap-anywhere">{{ $session['device'] }}</span>
              <span class="text-sm text-ink-muted">{{ $session['location'] }}</span>
              <span class="text-sm text-ink-muted">
                {{ ! empty($session['current']) ? 'Active now' : 'Last active ' . $session['last_active']->diffForHumans() }}
              </span>
            </span>
            @if (! empty($session['current']))
            <span class="flex-none rounded-full bg-success-soft px-3 py-0.5 text-[0.8125rem] font-semibold text-success-text">This device</span>
            @endif
          </li>
          @endforeach
        </ul>
        @else
        <p class="m-0 text-sm text-ink-muted">No sign-in activity to show yet.</p>
        @endif
      </section>

    </div>
  </main>
</body>

</html>