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
  <img src="{{ asset('images/backgrounds/design/landing_bg.webp') }}" alt="" class="position-absolute z-0 w-100 h-100">

  @include('includes.navigation');

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
      class="btn-color-gradient--primary font-button--responsive text-pale--white position-relative z-1 rounded-5 px-5 py-3 border-0  fw-semibold">
      Book Now
    </x-button>
  </div>

  <x-auth-modal></x-auth-modal>
  <x-package-card>
@php
$packages = [
    [
        'name' => 'Budget Lite',
        'price' => 5000,
        'features' => [
            'Ideal for small and intimate events',
            'Basic yet clear sound setup',
            'Simple lighting for ambience',
            'Good for meetings and mini gatherings',
            'Easy and quick installation',
        ],
    ],
    [
        'name' => 'Budget Party',
        'price' => 10000,
        'features' => [
            'Perfect for birthdays and school programs',
            'Brighter party lighting',
            'Improved sound coverage',
            'Great for corporate events',
            'Fun and lively atmosphere',
        ],
    ],
    [
        'name' => 'Budget Wedding',
        'price' => 18000,
        'features' => [
            'Best for simple weddings',
            'LED wall with live feed',
            'Clean and elegant audio',
            'For church or reception setups',
            'Balanced sound and lighting',
        ],
    ],
    [
        'name' => 'Luxe Lite',
        'price' => 25000,
        'features' => [
            'For formal programs and receptions',
            'Enhanced lighting setup',
            'Clear sound for speeches and music',
            'Supports basic band needs',
            'Professional presentation finish',
        ],
    ],
    [
        'name' => 'Modern Glam',
        'price' => 35000,
        'features' => [
            'Ideal for debuts and luxury weddings',
            'Upgraded visual lighting',
            'Strong event audio',
            'Works well for indoor venues',
            'Grand ambience setting',
        ],
    ],
    [
        'name' => 'Elite Symphony',
        'price' => 45000,
        'features' => [
            'Best for concerts and grand events',
            'Full premium audio and lighting',
            'Concert-level production',
            'Complete event stage setup',
            'Maximum visual and sound impact',
        ],
    ],
];
@endphp

<div class="d-flex flex-wrap gap-4 justify-content-center p-4">
    @foreach($packages as $package)
        <x-package-card
            :name="$package['name']"
            :price="$package['price']"
            :features="$package['features']"
        />
    @endforeach
</div>
</x-package-card>
</body>

</html>