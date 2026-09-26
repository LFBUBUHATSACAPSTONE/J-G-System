<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <x-site-head></x-site-head>
</head>

<body>
  <img src="{{ asset('images/backgrounds/design/landing_bg.webp') }}" alt="" class="position-absolute z-0 w-100 h-100">

  <!-- @include('components/booking/client-information'); -->
  <!-- @include('components/booking/event-information'); -->
  @include('components/booking/event-schedule');
</body>


</html>