{{--
  Auth modal view "terms" (data-view="terms"): the Terms and Conditions inside the modal, so the person does not leave
  the sign-up form (what they typed stays: switching views only hides the sign-up panel). Opened by the "Terms &
  Conditions" link of the sign-up checkbox (data-auth-view="terms"). The text is the shared <x-terms.content>.
  Hooks (auth-modal.js): data-view-heading (focused when the view opens), data-terms-scroll (reset to the top),
  data-terms-accept ("I agree": ticks the sign-up checkbox and goes back; DISABLED until the text has been scrolled to the
  end, auth-modal.js), data-terms-hint (the line under it, its text switches), data-auth-view="signup" (Back).
  Esc goes back to sign-up instead of closing the modal (auth-modal.js).
--}}
@php
$backClass = 'mb-[0.75rem] inline-flex items-center gap-[0.4rem] self-start [background:none] [border:none] [padding:0] text-[0.85rem] font-semibold text-auth-accent hover:underline';
$titleClass = 'mb-[0.75rem] text-[1.5rem] font-bold outline-none min-[768px]:text-[1.75rem]';
$scrollClass = 'min-h-0 flex-1 overflow-y-auto pr-[0.75rem] [scrollbar-width:thin] [scrollbar-color:var(--color-auth-border)_transparent]';
$actionsClass = 'mt-[1rem] shrink-0';
$acceptClass = 'w-full rounded-field text-[0.95rem] font-semibold [padding:0.75rem] disabled:cursor-not-allowed disabled:opacity-60';
$hintClass = 'mt-[0.5rem] text-center text-[0.78rem] text-auth-text-muted [margin-bottom:0]';
@endphp

<div class="flex h-[min(34rem,calc(100svh-9rem))] flex-col">
  <button type="button" data-auth-view="signup" class="{{ $backClass }}">
    <i class="ph ph-arrow-left" aria-hidden="true"></i>
    <span>Back to sign up</span>
  </button>

  <h2 class="{{ $titleClass }}" tabindex="-1" data-view-heading>{{ config('terms.title') }}</h2>

  <div class="{{ $scrollClass }}" data-terms-scroll tabindex="0" role="region" aria-label="{{ config('terms.title') }}">
    <x-terms.content :compact="true" heading-tag="h3" />
  </div>

  <div class="{{ $actionsClass }}">
    <x-button type="button" :disabled="true" class="btn-color-gradient--primary {{ $acceptClass }}" data-terms-accept>
      I have read and agree
    </x-button>
    <p class="{{ $hintClass }}" aria-live="polite" data-terms-hint
      data-locked-text="Scroll to the end of the terms to enable this button."
      data-unlocked-text="You have reached the end. You can now agree.">Scroll to the end of the terms to enable this button.</p>
  </div>
</div>