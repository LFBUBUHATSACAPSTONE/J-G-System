<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <x-site-head></x-site-head>
</head>

<body>
  <img src="{{ asset('images/backgrounds/design/landing_bg.webp') }}" alt="" class="position-absolute z-0 w-100 h-100">

  {{--
    Booking flow container — booking-flow.js reads/writes
    data-current-view on this element and toggles d-none on each
    .booking-flow__view child, the same relationship auth-modal.js has
    to #authModal's .auth-modal__view children.

    data-cancel-url / data-continue-url: where booking-flow.js sends the
    person when they Cancel out of the flow, or hit Continue on the
    final Confirmation step. Both point at the landing page for now —
    swap either independently once there's somewhere more specific for
    it to go (e.g. a user dashboard for "continue").
  --}}
  {{-- Reschedule mode: the controller passes $reschedule = ['booking_id', 'reference', 'month' => 'YYYY-MM'].
       Absent on a normal booking. See docs/reschedule-within-original-month.md. --}}
  @php($reschedule = $reschedule ?? null)

  @include('components.progress-tracker')

  <div
    id="bookingFlow"
    class="booking-flow"
    data-current-view="{{ $reschedule ? 'event-schedule' : 'client-information' }}"
    @if ($reschedule)
    data-reschedule-id="{{ $reschedule['booking_id'] }}"
    data-reschedule-month="{{ $reschedule['month'] }}"
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
    <div class="booking-flow__view d-none" data-view="booking-confirmation">
      @include('components.booking.booking-confirmation')
    </div>
  </div>
</body>


</html>