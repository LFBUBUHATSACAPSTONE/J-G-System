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
    /* Safari support */
  }
</style>

<body>
  @include('includes.navigation');

  @include('includes.landing-caption', ["header" => "BRING YOUR EVENT TO LIFE", "caption" => "We're dedicated to making every occasion look and sound its best with reliable equipment and professional service."])

  <x-coverflow-carousel :items="[
            ['image' => asset('images/carousel/1.webp'), 'alt' => ''],
            ['image' => asset('images/carousel/2.webp'), 'alt' => ''],
            ['image' => asset('images/carousel/3.webp'), 'alt' => ''],
            ['image' => asset('images/carousel/4.webp'), 'alt' => ''],
            ['image' => asset('images/carousel/5.webp'), 'alt' => ''],
            ['image' => asset('images/carousel/6.webp'), 'alt' => ''],
            ['image' => asset('images/carousel/7.webp'), 'alt' => ''],
        ]" :interval="3000" />

  <x-button class="btn-color-gradient--primary button-white position-relative z-1 rounded-5 px-5 py-3 border-0 font-family-text fw-semibold fs-5">
    Book Now
  </x-button>
</body>

</html>