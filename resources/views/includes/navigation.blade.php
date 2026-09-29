{{--
  Reminder: any future CTA that redirects to login should use the same two attributes,
     e.g. data-bs-toggle="modal" data-bs-target="#authModal" data-auth-view="login"
--}}

<header class="sticky-top border-bottom border-white border-opacity-25 glass-header">
  <nav class="navbar navbar-expand-md navbar-dark py-3">
    <div class="d-flex align-items-center container px-3 px-md-5">
      <a href="{{ route('user.landing') }}#" class="navbar-brand d-none d-md-block">
        <img src="{{ asset('images/logo/J&G_official_logo.webp') }}" alt=" Business Logo" class="position-relative z-1 max-h-10">
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Expand Navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav mx-auto gap-md-5 gap-2 mt-3 mt-md-0">
          <li class="nav-item"><a href="{{ route('user.landing') }}#" class="nav-link active" data-nav-target="home" aria-current="location">Home</a></li>
          <li class="nav-item"><a href="{{ route('user.landing') }}#features" class="nav-link" data-nav-target="features">Features</a></li>
          <li class="nav-item"><a href="{{ route('user.landing') }}#packages" class="nav-link" data-nav-target="packages">Packages</a></li>
          <li class="nav-item"><a href="{{ route('user.landing') }}#inclusions" class="nav-link" data-nav-target="inclusions">Inclusions</a></li>
          <li class="nav-item"><a href="{{ route('user.landing') }}#about" class="nav-link" data-nav-target="about">About</a></li>
        </ul>

        <div class="d-flex gap-3 align-items-center justify-content-center mt-3 mt-md-0">

          <x-button type="button"
            data-bs-toggle="modal" data-bs-target="#authModal"
            data-auth-view="signup"
            class=" rounded-3 btn-color-gradient--primary px-3 py-2 d-none d-md-block">
            Sign up
          </x-button>

          <x-button type="button"
            data-bs-toggle="modal"
            data-bs-target="#authModal"
            data-auth-view="login"
            class="rounded-3 btn-color-gradient--secondary px-3 py-2 d-none d-md-block">
            Login
          </x-button>

        </div>
      </div>
    </div>
  </nav>
</header>