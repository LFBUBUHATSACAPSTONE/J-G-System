<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <x-site-head></x-site-head>
</head>

<body class="flex min-h-screen flex-col bg-auth-bg">
  <x-toast.region />
  {{-- Page background: fixed to the viewport so it never ends where the first screen ends. --}}
  <img src="{{ asset('images/backgrounds/design/landing_bg.webp') }}" alt="" class="pointer-events-none fixed inset-0 z-0 size-full object-cover">

  {{--
    Booking flow container — booking-flow.js reads/writes
    data-current-view on this element and toggles d-none on each
    .booking-flow__view child, the same relationship auth-modal.js has
    to #authModal's .auth-modal__view children.

    data-cancel-url / data-continue-url: where booking-flow.js sends the
    person when they Cancel out of the flow, or hit Continue on the
    final Confirmation step. Both point at the landing page for now —
    swap either independently once there's somewhere more specific for
    it to go (e.g. a user dashboard for "continue"). --}}

  {{-- Reschedule mode: the controller passes $reschedule = ['booking_id', 'reference', 'month' => 'YYYY-MM'].
       Absent on a normal booking.  --}}
  {{-- One PHP block on purpose: Blade pairs a block with the inline one-line form before it and swallowed it, which left $rescheduleBlocked undefined.
       Never write the directive names inside a comment: Blade reads them there too.
       Phase 9A: class strings. `booking-card` is the shared card surface (@utility in tailwind.css); padding is set here. --}}
  @php
  $reschedule = $reschedule ?? null;
  $rescheduleBlocked = $rescheduleBlocked ?? null;

  $blockedClass = 'relative z-1 flex flex-[1_0_auto] items-center justify-center px-[1rem] py-6 min-[576px]:px-6 min-[576px]:py-8';
  $blockedCardClass = 'booking-card flex max-w-md flex-col items-center border-[1px] border-auth-border px-[1rem] py-8 text-center shadow-[0_1rem_3rem_oklch(0%_0_0/0.45)] min-[576px]:px-8 min-[576px]:py-12';
  $blockedIconClass = 'mb-6 inline-flex size-16 items-center justify-center rounded-full border-[1px] border-auth-border bg-auth-field-bg text-auth-danger';
  $blockedTitleClass = 'text-fluid-icon leading-tight font-bold [margin:0_0_0.75rem]';
  $blockedMessageClass = 'text-base leading-normal text-auth-text-muted text-balance [margin:0_0_2rem]';
  // The same look as the step's Continue button (components/booking/step-button), as a link.
  $blockedBtnClass = 'btn-color-gradient--primary block w-full rounded-md px-6 py-[0.75rem] text-center text-fluid-button font-bold text-pale-white no-underline min-[576px]:w-auto min-[576px]:min-w-48';
  // Side padding 16 / 24 / 32px; the top gap under the tracker is 24px. The Payment step (booking-summary) has four
  // columns, so while it is the visible view the page is as wide as 1480px (the other steps stay at 1040px).
  $flowClass = 'relative z-1 mx-auto flex w-full max-w-260 flex-[1_0_auto] flex-col px-[1rem] pt-6 pb-8 has-[[data-view=booking-summary]:not(.d-none)]:max-w-370 min-[576px]:px-6 min-[992px]:px-8 min-[992px]:pb-6';
  // Confirmation is the only step that centres its card in the space under the tracker. `[&:not(.d-none)]:flex`
  // (not a plain `flex`) so booking-flow.js can still hide it with d-none.
  $confirmViewClass = 'flex-[1_0_auto] flex-col justify-center [&:not(.d-none)]:flex';
  @endphp

  @if ($rescheduleBlocked)
  {{-- Reschedule refused (not user-cancelled, already rescheduled, or the month has passed). The page
       still renders (HTTP 403) so the client sees why, instead of a bare error page. --}}
  <div class="{{ $blockedClass }}" data-reschedule-blocked>
    <div class="{{ $blockedCardClass }}">
      <span class="{{ $blockedIconClass }}" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="32" height="32" fill="none" stroke="currentColor" stroke-width="1.75" stroke-linecap="round" stroke-linejoin="round">
          <rect x="3" y="4.5" width="18" height="16" rx="3"></rect>
          <path d="M3 9.5h18M8 2.5v4M16 2.5v4M9.5 13.5l5 5M14.5 13.5l-5 5"></path>
        </svg>
      </span>
      <h1 class="{{ $blockedTitleClass }}">Reschedule unavailable</h1>
      <p class="{{ $blockedMessageClass }}" role="alert">{{ $rescheduleBlocked }}</p>
      <a href="{{ route('user.landing') }}" class="{{ $blockedBtnClass }}">Back to home</a>
    </div>
  </div>
  @else
  @include('components.progress-tracker')

  <div
    id="bookingFlow"
    class="{{ $flowClass }}"
    data-current-view="{{ $reschedule ? 'event-schedule' : 'client-information' }}"
    @if ($reschedule)
    data-reschedule-id="{{ $reschedule['booking_id'] }}"
    @endif
    data-package-url="{{ route('user.landing') }}#packages"
    data-cancel-url="{{ route('user.landing') }}"
    data-continue-url="{{ route('user.landing') }}">

    {{-- Package is chosen on the landing page (its "Book Now" links here),
         so the flow starts at Client Information. The tracker still lists
         Package as step 1; clicking it goes back to data-package-url. --}}

    {{-- Step: Client Information (entry point) --}}
    <div class="booking-flow__view" data-view="client-information">
      @include('components.booking.client-information')
    </div>

    {{-- Step: Event Information --}}
    <div class="booking-flow__view d-none" data-view="event-information">
      @include('components.booking.event-information')
    </div>

    {{-- Step: Event Schedule --}}
    <div class="booking-flow__view d-none" data-view="event-schedule">
      @include('components.booking.event-schedule')
    </div>

    {{-- Step: Booking Summary + Payment --}}
    <div class="booking-flow__view d-none" data-view="booking-summary">
      @include('components.booking.booking-summary')
    </div>

    {{-- Step: Booking Confirmation (final) --}}
    <div class="booking-flow__view d-none {{ $confirmViewClass }}" data-view="booking-confirmation">
      @include('components.booking.booking-confirmation')
    </div>
  </div>
  @endif
</body>


</html>