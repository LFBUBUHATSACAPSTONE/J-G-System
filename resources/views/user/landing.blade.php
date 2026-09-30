<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <x-site-head></x-site-head>
</head>

<style>
  .glass-header {
    background-color: rgba(255, 255, 255, 0.1);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
  }
</style>

<body>
  <img src="{{ asset('images/backgrounds/design/landing_bg.webp') }}" alt="" class="position-absolute z-0 w-100 h-100">

  @include('includes.navigation');

  @if (session('auth_error'))
    <div class="alert alert-danger position-relative z-1 mx-auto mt-3" role="alert" style="max-width: 36rem;">
      {{ session('auth_error') }}
    </div>
  @endif

  @include('includes.landing-caption', ["header" => "Bring Your Event to Life", "caption" => "We're dedicated to making every occasion look and sound its best with reliable equipment and professional service."])

  <x-coverflow-carousel :items="[
            ['image' => asset('images/carousel/1.webp'), 'alt' => ''],
            ['image' => asset('images/carousel/2.webp'), 'alt' => ''],
            ['image' => asset('images/carousel/3.webp'), 'alt' => ''],
            ['image' => asset('images/carousel/4.webp'), 'alt' => ''],
            ['image' => asset('images/carousel/5.webp'), 'alt' => ''],
            ['image' => asset('images/carousel/6.webp'), 'alt' => ''],
            ['image' => asset('images/carousel/7.webp'), 'alt' => ''],
        ]" :interval="3000" />

  <div class="d-flex justify-content-center">
    <x-button
      data-bs-toggle="modal"
      data-bs-target="#authModal"
      data-auth-view="login"
      class="btn-color-gradient--primary font-button--responsive text-pale--white position-relative z-1 rounded-5 px-5 py-3 border-0 fw-semibold">
      Book Now
    </x-button>
  </div>

  <x-auth-modal></x-auth-modal>
  @include('components.features')
  @include('components.booking.package')
  @include('components.booking.package-comparison')
  @include('components.footer')
</body>

</html>