<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <x-site-head></x-site-head>
</head>

<body>
  <img src="{{ asset('images/backgrounds/design/landing_bg.webp') }}" alt="" class="position-absolute z-0 w-100 h-100">

  @include('components.progress-tracker');

  <div
    id="bookingFlow"
    class="booking-flow"
    data-current-view="client-information"
    data-cancel-url="{{ route('user.landing') }}"
    data-continue-url="{{ route('user.landing') }}">

    {{--
      Step: Package — NOT YET BUILT.

        <div class="booking-flow__view" data-view="package">
          @include('components.booking.package')
        </div>

        -}}

    {{-- Step: Client Information (currently the flow's entry point,
         until Package exists above) --}}
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