{{--
  Reminder: any future CTA that redirects to login should use the same two attributes,
     e.g. data-bs-toggle="modal" data-bs-target="#authModal" data-auth-view="login"
--}}

<header class="sticky-top border-bottom border-white border-opacity-25 glass-header">
  <nav class="navbar navbar-expand-lg navbar-dark py-3">
    <div class="d-flex align-items-center container px-3 px-lg-5">
      <a href="{{ route('user.landing') }}#" class="navbar-brand d-none d-lg-block">
        <img src="{{ asset('images/logo/J&G_official_logo.webp') }}" alt=" Business Logo" class="position-relative z-1 max-h-10">
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Expand Navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav mx-auto gap-2 gap-lg-3 gap-xl-5 mt-3 mt-lg-0">
          <li class="nav-item"><a href="{{ route('user.landing') }}#" class="nav-link active" data-nav-target="home" aria-current="location">Home</a></li>
          <li class="nav-item"><a href="{{ route('user.landing') }}#features" class="nav-link" data-nav-target="features">Features</a></li>
          <li class="nav-item"><a href="{{ route('user.landing') }}#packages" class="nav-link" data-nav-target="packages">Packages</a></li>
          <li class="nav-item"><a href="{{ route('user.landing') }}#inclusions" class="nav-link" data-nav-target="inclusions">Inclusions</a></li>
          <li class="nav-item"><a href="{{ route('user.landing') }}#about" class="nav-link" data-nav-target="about">About</a></li>
        </ul>

        <div class="d-flex gap-3 align-items-center justify-content-center mt-3 mt-lg-0">
          @auth
          <x-button type="button"
            data-chat-open
            aria-label="Messages"
            aria-expanded="false"
            aria-controls="chatWidgetPanel"
            title="Messages"
            class="landing-nav__icon-button">
            <x-chat.icon name="chat" />
          </x-button>

          <div class="dropdown">
            <button type="button"
              id="landingUserMenu"
              data-bs-toggle="dropdown"
              data-bs-display="static"
              aria-expanded="false"
              aria-label="Profile"
              title="Profile"
              class="landing-nav__icon-button dropdown-toggle dropdown-toggle--no-caret border-0">
              <x-chat.icon name="user" />
            </button>
            <ul class="dropdown-menu dropdown-menu-end landing-nav__dropdown-menu shadow-lg py-2 mt-2" aria-labelledby="landingUserMenu">
              <li class="px-3 py-2 border-bottom border-white border-opacity-10">
                <span class="d-block fw-semibold text-white small text-truncate">
                  {{ auth()->user()->first_name ?? '' }} {{ auth()->user()->last_name ?? '' }}
                </span>
                <span class="d-block text-white text-opacity-50 text-truncate" style="font-size: 0.75rem;">
                  {{ auth()->user()->email ?? '' }}
                </span>
              </li>
              <li>
                <a class="dropdown-item d-flex align-items-center gap-2 py-2" href="{{ route('home') }}">
                  <x-chat.icon name="user" />
                  <span>Profile</span>
                </a>
              </li>
              <li><hr class="dropdown-divider border-white border-opacity-10 my-1"></li>
              <li>
                <form method="POST" action="{{ route('logout') }}" class="m-0">
                  @csrf
                  <button type="submit" class="dropdown-item d-flex align-items-center gap-2 py-2 text-danger">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" width="16" height="16" aria-hidden="true">
                      <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                      <polyline points="16 17 21 12 16 7"></polyline>
                      <line x1="21" y1="12" x2="9" y2="12"></line>
                    </svg>
                    <span>Logout</span>
                  </button>
                </form>
              </li>
            </ul>
          </div>
          @else
          <x-button type="button"
            data-bs-toggle="modal" data-bs-target="#authModal"
            data-auth-view="signup"
            class="rounded-3 btn-color-gradient--primary px-3 py-2">
            Sign up
          </x-button>

          <x-button type="button"
            data-bs-toggle="modal"
            data-bs-target="#authModal"
            data-auth-view="login"
            class="rounded-3 btn-color-gradient--secondary px-3 py-2">
            Login
          </x-button>
          @endauth
        </div>
      </div>
    </div>
  </nav>
</header>