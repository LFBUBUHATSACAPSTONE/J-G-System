<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <x-site-head></x-site-head>
</head>

<x-chat.widget :conversation="$chatConversation ?? null" :authenticated="auth()->check()" />

<body>
  @include('components.landing.navigation')

  <section class="landing-hero">
    <img src="{{ asset('images/backgrounds/design/landing_bg.webp') }}" alt="" class="landing-hero__bg">
    @if (session('auth_error'))
    <div class="alert alert-danger position-relative z-1 mx-auto mt-3" role="alert" style="max-width: 36rem;">
      {{ session('auth_error') }}
    </div>
    @endif

    @include('components.landing.landing-caption', ["header" => "Bring Your Event to Life", "caption" => "We're dedicated to making every occasion look and sound its best with reliable equipment and professional service."])

    <x-landing.coverflow-carousel />

    <div class="d-flex justify-content-center">
      @auth
      <a id="landing-book-now" href="{{ route('user.booking') }}" class="btn-color-gradient--primary font-button--responsive text-pale--white position-relative z-1 rounded-5 px-5 py-3 border-0 fw-semibold text-decoration-none">
        <span>Book Now</span>
      </a>
      @else
      <button type="button" id="landing-book-now" data-bs-toggle="modal" data-bs-target="#authModal" data-auth-view="login" class="btn-color-gradient--primary font-button--responsive text-pale--white position-relative z-1 rounded-5 px-5 py-3 border-0 fw-semibold"><span>Book Now</span></button>
      @endauth
    </div>
  </section>

  <x-auth-modal></x-auth-modal>
  @include('components.landing.features')
  @include('components.booking.package')
  @include('components.landing.package-comparison')
  @include('components.landing.footer')
</body>

</html>