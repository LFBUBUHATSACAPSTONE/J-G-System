{{--
  Reminder: any future CTA that redirects to login should use the same two attributes,
     e.g. data-bs-toggle="modal" data-bs-target="#authModal" data-auth-view="login"
--}}

@php
$navLinkClass = 'nav-link relative inline-block whitespace-nowrap font-normal text-on-brand/60 transition-colors duration-200 ease-in-out motion-reduce:transition-none not-aria-[current=location]:hover:text-on-brand/90 not-aria-[current=location]:focus-visible:text-on-brand/90 aria-[current=location]:font-semibold aria-[current=location]:text-on-brand after:content-[\'\'] after:absolute after:bottom-[0.15rem] after:left-[var(--bs-nav-link-padding-x,0.5rem)] after:right-[var(--bs-nav-link-padding-x,0.5rem)] after:h-0.5 after:origin-left after:scale-x-0 after:rounded-[2px] after:bg-(image:--gradient-button) after:transition-transform after:duration-250 after:ease-in-out motion-reduce:after:transition-none aria-[current=location]:after:scale-x-100';
@endphp

<header class="glass-header sticky top-0 z-1020 border-b border-on-brand/25 bg-on-brand/10 backdrop-blur-md">
  <nav class="navbar navbar-expand-lg navbar-dark py-4">
    <div class="d-flex align-items-center container px-4 min-[992px]:px-12">
      <a href="{{ route('user.landing') }}#" class="navbar-brand d-none d-lg-block">
        <img src="{{ asset('images/logo/J&G_official_logo.webp') }}" alt=" Business Logo" class="relative z-1 w-20">
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Expand Navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="nav">
        <ul class="navbar-nav mx-auto gap-6 min-[992px]:gap-4 min-[1200px]:gap-12 min-[992px]:mt-0">
          <li class="nav-item"><a href="{{ route('user.landing') }}#" class="{{ $navLinkClass }}" data-nav-target="home" aria-current="location">Home</a></li>
          <li class="nav-item"><a href="{{ route('user.landing') }}#features" class="{{ $navLinkClass }}" data-nav-target="features">Features</a></li>
          <li class="nav-item"><a href="{{ route('user.landing') }}#packages" class="{{ $navLinkClass }}" data-nav-target="packages">Packages</a></li>
          <li class="nav-item"><a href="{{ route('user.landing') }}#inclusions" class="{{ $navLinkClass }}" data-nav-target="inclusions">Inclusions</a></li>
          <li class="nav-item"><a href="{{ route('user.landing') }}#about" class="{{ $navLinkClass }}" data-nav-target="about">About</a></li>
          @auth
          <li class="nav-item d-lg-none">
            <a href="{{ route('user.account') }}" class="{{ $navLinkClass }}">
              Account
              @if (($chatUnread ?? 0) > 0)
              <span class="landing-nav__badge" data-chat-unread-badge>{{ $chatUnread > 9 ? '9+' : $chatUnread }}</span>
              @endif
            </a>
          </li>
          @endauth
        </ul>

        <div class="d-flex gap-4 align-items-center justify-content-center min-[992px]:mt-0">
          @auth
          <a href="{{ route('user.account') }}"
            aria-label="My account ({{ ($chatUnread ?? 0) > 0 ? ($chatUnread > 9 ? '9+' : $chatUnread).' unread messages' : 'no unread messages' }})"
            title="My account"
            class="landing-nav__icon-button d-none d-lg-grid">
            <x-chat.icon name="user" />
            @if (($chatUnread ?? 0) > 0)
            <span class="landing-nav__badge" data-chat-unread-badge>{{ $chatUnread > 9 ? '9+' : $chatUnread }}</span>
            @endif
          </a>
          @else
          <x-button type="button"
            data-bs-toggle="modal" data-bs-target="#authModal"
            data-auth-view="signup"
            class="rounded-3 btn-color-gradient--primary px-4 py-2">
            Sign up
          </x-button>

          <x-button type="button"
            data-bs-toggle="modal"
            data-bs-target="#authModal"
            data-auth-view="login"
            class="rounded-3 btn-color-gradient--secondary px-4 py-2">
            Login
          </x-button>
          @endauth
        </div>
      </div>
    </div>
  </nav>
</header>