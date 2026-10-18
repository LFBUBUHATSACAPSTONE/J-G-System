{{-- Password requirement checklist; js/account/profile-rules.js ticks it as the user types.
     Mirrors getPasswordError() in js/auth/validation.js (the sign-up rule). --}}
@props(['id' => null])
<ul class="profile-rules" @if ($id) id="{{ $id }}" @endif aria-label="Password requirements">
  <li data-password-rule="length"><i class="ph ph-circle" aria-hidden="true"></i>At least 8 characters</li>
  <li data-password-rule="lower"><i class="ph ph-circle" aria-hidden="true"></i>A lowercase letter</li>
  <li data-password-rule="upper"><i class="ph ph-circle" aria-hidden="true"></i>An uppercase letter</li>
  <li data-password-rule="number"><i class="ph ph-circle" aria-hidden="true"></i>A number</li>
  <li data-password-rule="special"><i class="ph ph-circle" aria-hidden="true"></i>A special character</li>
</ul>