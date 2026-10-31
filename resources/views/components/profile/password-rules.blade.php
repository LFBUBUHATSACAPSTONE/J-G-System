{{-- Password requirement checklist; js/account/profile-rules.js ticks it as the user types.
     Mirrors getPasswordError() in js/auth/validation.js (the sign-up rule). --}}
@props(['id' => null])
@php
$ruleClass = 'flex items-center gap-2 [&.is-met]:text-success-text [&_i]:flex-none [&_i]:text-[1.125rem] [&_i]:leading-none';
@endphp
<ul class="m-0 grid list-none grid-cols-[minmax(0,1fr)] gap-x-6 gap-y-2 p-0 text-sm text-ink-muted min-[576px]:grid-flow-col min-[576px]:grid-cols-2 min-[576px]:grid-rows-[repeat(3,auto)]" @if ($id) id="{{ $id }}" @endif aria-label="Password requirements">
  <li class="{{ $ruleClass }}" data-password-rule="length"><i class="ph ph-circle" aria-hidden="true"></i>At least 8 characters</li>
  <li class="{{ $ruleClass }}" data-password-rule="lower"><i class="ph ph-circle" aria-hidden="true"></i>A lowercase letter</li>
  <li class="{{ $ruleClass }}" data-password-rule="upper"><i class="ph ph-circle" aria-hidden="true"></i>An uppercase letter</li>
  <li class="{{ $ruleClass }}" data-password-rule="number"><i class="ph ph-circle" aria-hidden="true"></i>A number</li>
  <li class="{{ $ruleClass }}" data-password-rule="special"><i class="ph ph-circle" aria-hidden="true"></i>A special character</li>
</ul>