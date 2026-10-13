
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

  <main class="admin-content admin-account" data-admin-account>
    <x-admin.page-header />

    <div class="admin-account__layout">

      {{-- ============ Profile ============ --}}
      <section class="admin-card admin-account__profile" aria-labelledby="admin-account-title">
        <h2 class="admin-account__title" id="admin-account-title">Profile</h2>

        <form id="admin-account-form" class="admin-account__form" method="POST" action="{{ $updateUrl }}"
          enctype="multipart/form-data" novalidate>
          @csrf
          @method('PATCH')

          {{-- Photo --}}
          <div class="admin-account__avatar-block">
            <div class="admin-account__avatar-wrap">
              <div class="admin-account__avatar" data-avatar>
                @if ($avatarUrl)
                <img src="{{ $avatarUrl }}" alt="Your profile photo">
                @else
                {{ $initials }}
                @endif
              </div>
              <button type="button" class="admin-account__avatar-edit" data-avatar-pick aria-label="Change profile photo">
                <i class="ph ph-pencil-simple" aria-hidden="true"></i>
              </button>
              <input type="file" name="avatar" class="d-none" accept="image/jpeg,image/png,image/webp"
                data-avatar-input data-max-mb="2" tabindex="-1" aria-label="Profile photo file">
            </div>
            <small class="admin-account-field__error d-none" data-field-error="avatar" role="alert"></small>
          </div>

          {{-- Name --}}
          <div class="admin-account-row" data-row="name" role="group" aria-labelledby="aa-name-label">
            <span class="admin-account-row__label" id="aa-name-label">Name</span>
            <div class="admin-account-row__view" data-row-view>
              <span class="admin-account-row__value" data-row-value>{{ $fullName }}</span>
              <button type="button" class="admin-account-row__action" data-row-edit aria-expanded="false"
                aria-controls="aa-name-panel" aria-label="Edit name">Edit</button>
            </div>
            <div class="admin-account-row__panel" id="aa-name-panel" data-row-panel hidden>
              <div class="admin-account-row__fields admin-account-row__fields--name">
                <div class="admin-account-field">
                  <label class="admin-account-field__label" for="aa-first-name">First name</label>
                  <input class="admin-account-field__control" type="text" id="aa-first-name" name="first_name"
                    value="{{ $firstName }}" autocomplete="given-name" maxlength="60">
                  <small class="admin-account-field__error d-none" data-field-error="first_name" role="alert"></small>
                </div>
                <div class="admin-account-field">
                  <label class="admin-account-field__label" for="aa-last-name">Last name</label>
                  <input class="admin-account-field__control" type="text" id="aa-last-name" name="last_name"
                    value="{{ $lastName }}" autocomplete="family-name" maxlength="60">
                  <small class="admin-account-field__error d-none" data-field-error="last_name" role="alert"></small>
                </div>
                <div class="admin-account-field">
                  <label class="admin-account-field__label" for="aa-middle-initial">M.I. (optional)</label>
                  <input class="admin-account-field__control" type="text" id="aa-middle-initial" name="middle_initial"
                    value="{{ $middle }}" maxlength="1" autocomplete="off">
                  <small class="admin-account-field__error d-none" data-field-error="middle_initial" role="alert"></small>
                </div>
              </div>
              <div class="admin-account-row__panel-actions">
                <button type="button" class="admin-account-row__action" data-row-cancel>Cancel</button>
              </div>
            </div>
          </div>

          {{-- Email --}}
          <div class="admin-account-row" data-row="email" role="group" aria-labelledby="aa-email-label">
            <span class="admin-account-row__label" id="aa-email-label">Email</span>
            <div class="admin-account-row__view" data-row-view>
              <span class="admin-account-row__value" data-row-value>{{ $email }}</span>
              <button type="button" class="admin-account-row__action" data-row-edit aria-expanded="false"
                aria-controls="aa-email-panel" aria-label="Change email">Change</button>
            </div>
            <div class="admin-account-row__panel" id="aa-email-panel" data-row-panel hidden>
              <div class="admin-account-field">
                <label class="admin-account-field__label" for="aa-email">New email address</label>
                <input class="admin-account-field__control" type="email" id="aa-email" name="email"
                  value="{{ $email }}" autocomplete="email" inputmode="email">
                <small class="admin-account-field__error d-none" data-field-error="email" role="alert"></small>
              </div>
              <div class="admin-account-row__panel-actions">
                <button type="button" class="admin-account-row__action" data-row-cancel>Cancel</button>
              </div>
            </div>
          </div>

          {{-- Contact number --}}
          <div class="admin-account-row" data-row="phone" role="group" aria-labelledby="aa-phone-label">
            <span class="admin-account-row__label" id="aa-phone-label">Contact Number</span>
            <div class="admin-account-row__view" data-row-view>
              <span class="admin-account-row__value" data-row-value>{{ $phoneDisplay }}</span>
              <button type="button" class="admin-account-row__action" data-row-edit aria-expanded="false"
                aria-controls="aa-phone-panel" aria-label="Change contact number">Change</button>
            </div>
            <div class="admin-account-row__panel" id="aa-phone-panel" data-row-panel hidden>
              <div class="admin-account-field">
                <label class="admin-account-field__label" for="aa-phone">Contact number</label>
                <input class="admin-account-field__control" type="tel" id="aa-phone" name="phone"
                  value="{{ $phone }}" autocomplete="tel" inputmode="numeric" maxlength="11">
                <small class="admin-account-field__error d-none" data-field-error="phone" role="alert"></small>
              </div>
              <div class="admin-account-row__panel-actions">
                <button type="button" class="admin-account-row__action" data-row-cancel>Cancel</button>
              </div>
            </div>
          </div>

          {{-- Password --}}
          <div class="admin-account-row" data-row="password" role="group" aria-labelledby="aa-password-label">
            <span class="admin-account-row__label" id="aa-password-label">Password</span>
            <div class="admin-account-row__view" data-row-view>
              <span class="admin-account-row__value">
                <span aria-hidden="true">••••••••••••</span>
                <span class="visually-hidden">Hidden for security</span>
              </span>
              <button type="button" class="admin-account-row__action" data-row-edit aria-expanded="false"
                aria-controls="aa-password-panel" aria-label="Change password">Change</button>
            </div>
            <div class="admin-account-row__panel" id="aa-password-panel" data-row-panel hidden>
              <div class="admin-account-row__fields">
                <div class="admin-account-field">
                  <label class="admin-account-field__label" for="aa-password">New password</label>
                  <div class="admin-account-field__password">
                    <input class="admin-account-field__control" type="password" id="aa-password" name="password"
                      autocomplete="new-password">
                    <button type="button" class="admin-account-field__toggle" data-password-toggle="aa-password"
                      aria-pressed="false" aria-label="Show password"><i class="ph ph-eye" aria-hidden="true"></i></button>
                  </div>
                  <small class="admin-account-field__error d-none" data-field-error="password" role="alert"></small>
                </div>
                <div class="admin-account-field">
                  <label class="admin-account-field__label" for="aa-password-confirm">Confirm new password</label>
                  <div class="admin-account-field__password">
                    <input class="admin-account-field__control" type="password" id="aa-password-confirm"
                      name="password_confirmation" autocomplete="new-password">
                    <button type="button" class="admin-account-field__toggle" data-password-toggle="aa-password-confirm"
                      aria-pressed="false" aria-label="Show password"><i class="ph ph-eye" aria-hidden="true"></i></button>
                  </div>
                  <small class="admin-account-field__error d-none" data-field-error="password_confirmation" role="alert"></small>
                </div>
              </div>
              <ul class="admin-account-rules" aria-label="Password requirements">
                <li data-password-rule="length"><i class="ph ph-circle" aria-hidden="true"></i>At least 8 characters</li>
                <li data-password-rule="lower"><i class="ph ph-circle" aria-hidden="true"></i>A lowercase letter</li>
                <li data-password-rule="upper"><i class="ph ph-circle" aria-hidden="true"></i>An uppercase letter</li>
                <li data-password-rule="number"><i class="ph ph-circle" aria-hidden="true"></i>A number</li>
                <li data-password-rule="special"><i class="ph ph-circle" aria-hidden="true"></i>A special character</li>
              </ul>
              <div class="admin-account-row__panel-actions">
                <button type="button" class="admin-account-row__action" data-row-cancel>Cancel</button>
              </div>
            </div>
          </div>

          {{-- Appears only when the email or password is being changed --}}
          <div class="admin-account__confirm" data-account-confirm hidden>
            <div class="admin-account-field">
              <label class="admin-account-field__label" for="aa-current-password">Current password</label>
              <div class="admin-account-field__password">
                <input class="admin-account-field__control" type="password" id="aa-current-password"
                  name="current_password" autocomplete="current-password" aria-describedby="aa-current-hint">
                <button type="button" class="admin-account-field__toggle" data-password-toggle="aa-current-password"
                  aria-pressed="false" aria-label="Show password"><i class="ph ph-eye" aria-hidden="true"></i></button>
              </div>
              <small class="admin-account-field__hint" id="aa-current-hint">Needed to confirm a change to your email or password.</small>
              <small class="admin-account-field__error d-none" data-field-error="current_password" role="alert"></small>
            </div>
          </div>

          <p class="admin-account__status d-none" data-account-status role="status" aria-live="polite"></p>
        </form>

        <div class="admin-account__actions">
          <form method="POST" action="{{ $logoutUrl }}" class="admin-account__logout">
            @csrf
            <button type="submit" class="admin-btn admin-btn--light" @disabled(! $logoutUrl)>Log out</button>
          </form>
          <button type="submit" form="admin-account-form" class="admin-btn admin-btn--brand" data-account-save disabled>Save</button>
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