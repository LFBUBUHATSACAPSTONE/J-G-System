{{--
  Reminder: any future CTA that redirects to login should use the same two attributes,
     e.g. data-bs-toggle="modal" data-bs-target="#authModal" data-auth-view="login"
--}}

<header class="position-relative z-1 border-bottom border-white border-opacity-25 glass-header">
  <nav class="navbar navbar-expand-md navbar-dark py-3">
    <div class="d-flex align-items-center container px-3 px-md-5">
      <a href="#" class="navbar-brand d-none d-md-block">
        <img src="{{ asset('images/logo/logo_rectangular.webp') }}" alt="Business Logo" class="position-relative z-1 max-h-10">
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Expand Navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav mx-auto gap-md-5 gap-2 mt-3 mt-md-0">
          <li class="nav-item"><a href="#" class="nav-link active font-family-text" aria-current="page">About</a></li>
          <li class="nav-item"><a href="#" class="nav-link font-family-text">Feature</a></li>
          <li class="nav-item"><a href="#" class="nav-link font-family-text">Package</a></li>
        </ul>

        <div class="d-flex gap-3 align-items-center justify-content-center mt-3 mt-md-0">

          <button type="button"
            data-bs-toggle="modal" data-bs-target="#authModal"
            data-auth-view="signup"
            class=" rounded-3 font-family-text btn-color-gradient--primary px-3 py-2 d-none d-md-block">Sign up</button>

          <button type="button"
            data-bs-toggle="modal"
            data-bs-target="#authModal"
            data-auth-view="login"
            class="rounded-3 font-family-text btn-color-gradient--secondary px-3 py-2 d-none d-md-block">Login</button>

        </div>
      </div>
    </div>
  </nav>
</header>