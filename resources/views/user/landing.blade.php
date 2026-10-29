<!DOCTYPE html>
<html lang="en" class="scroll-pt-[calc(var(--landing-header-h,6.5rem)-1.5rem)]">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <x-site-head></x-site-head>
</head>

<body>
  @include('components.landing.navigation')

  @php
  $bookNowClass = 'btn-color-gradient--primary relative z-1 rounded-4xl border-0 px-12 py-3 text-fluid-button font-semibold text-pale-white';
  @endphp

  {{-- landing-hero stays as a hook: _landing-hero.scss still sizes the carousel inside it (until 8H). --}}
  <section class="landing-hero relative isolate flex min-h-[calc(100svh-var(--landing-header-h,6.5rem))] flex-col gap-[clamp(0.75rem,2.5vh,2rem)] bg-landing-base px-(--section-pad-x) pt-[clamp(1.5rem,5vh,2.5rem)] pb-[clamp(2rem,6.5vh,3.25rem)] [--hero-carousel-min:220px] short-landscape:gap-0 short-landscape:pt-3 short-landscape:pb-3 short-landscape:[--hero-carousel-min:80px]">
    <img src="{{ asset('images/backgrounds/design/landing_bg.webp') }}" alt="" class="pointer-events-none absolute top-[calc(-1*var(--landing-header-h,5rem))] left-0 -z-1 h-[calc(100%+var(--landing-header-h,5rem))] w-full object-cover [-webkit-mask-image:linear-gradient(to_bottom,#000_calc(100%-12rem),transparent_100%)] mask-[linear-gradient(to_bottom,#000_calc(100%-12rem),transparent_100%)]">
    @if (session('auth_error'))
    <div class="relative z-1 mx-auto mt-4 mb-4 max-w-xl rounded-md border border-[#f1aeb5] bg-[#f8d7da] p-4 text-[#58151c]" role="alert">
      {{ session('auth_error') }}
    </div>
    @endif

    @include('components.landing.landing-caption', ["header" => "Bring Your Event to Life", "caption" => "We're dedicated to making every occasion look and sound its best with reliable equipment and professional service."])

    <x-landing.coverflow-carousel />

    <div class="flex justify-center">
      @auth
      <a id="landing-book-now" href="#packages" class="{{ $bookNowClass }} no-underline">
        <span>Book Now</span>
      </a>
      @else
      <button type="button" id="landing-book-now" data-bs-toggle="modal" data-bs-target="#authModal" data-auth-view="login" class="{{ $bookNowClass }}"><span>Book Now</span></button>
      @endauth
    </div>
  </section>

  <x-auth-modal></x-auth-modal>
  @include('components.landing.features')
  <x-booking.package :packages="$packages" />
  @include('components.landing.package-comparison')
  @include('components.landing.footer')

  <x-chat.widget :conversation="$chatConversation ?? null" :authenticated="auth()->check()" />
</body>

</html>