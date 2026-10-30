{{--
  Reminder: any future CTA that redirects to login should use the same two attributes,
     e.g. data-bs-toggle="modal" data-bs-target="#authModal" data-auth-view="login"
--}}

@php
$navLinkClass = 'nav-link relative inline-block whitespace-nowrap px-0 py-2 min-[992px]:px-2 font-normal no-underline text-on-brand/60 transition-colors duration-200 ease-in-out motion-reduce:transition-none not-aria-[current=location]:hover:text-on-brand/90 not-aria-[current=location]:focus-visible:text-on-brand/90 aria-[current=location]:font-semibold aria-[current=location]:text-on-brand before:content-[\'\'] before:absolute before:-inset-x-6 before:-inset-y-[0.375rem] min-[992px]:before:hidden after:content-[\'\'] after:absolute after:bottom-[0.15rem] after:inset-x-0 after:h-0.5 after:origin-left after:scale-x-0 after:rounded-[2px] after:bg-(image:--gradient-button) after:transition-transform after:duration-250 after:ease-in-out motion-reduce:after:transition-none aria-[current=location]:after:scale-x-100';

$navBadgeClass = 'absolute -top-1.5 -right-1.5 grid min-w-5 h-5 place-items-center rounded-full bg-nav-badge px-[0.3rem] text-[0.6875rem] leading-none font-bold text-on-brand [&[hidden]]:hidden';
@endphp

<header class="sticky top-0 z-1020 border-b border-on-brand/25 bg-on-brand/10 backdrop-blur-md">
  <nav class="navbar navbar-expand-lg navbar-dark relative py-4">
    <div class="d-flex align-items-center container px-4 min-[992px]:relative min-[992px]:px-12">
      <a href="{{ route('user.landing') }}#" class="navbar-brand hidden min-[992px]:block">
        <img src="{{ asset('images/logo/J&G_official_logo.webp') }}" alt=" Business Logo" class="relative z-1 w-20">
      </a>

      <button class="inline-flex min-h-11 min-w-11 items-center justify-center rounded-md border border-on-brand/10 bg-transparent px-3 py-1 text-xl leading-none text-on-brand/55 transition-shadow duration-150 ease-in-out outline-none focus-visible:ring-4 min-[992px]:hidden" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Expand Navigation">
        <svg class="size-[1.5em]" viewBox="0 0 30 30" fill="none" aria-hidden="true" focusable="false">
          <path d="M4 7h22M4 15h22M4 23h22" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-miterlimit="10" />
        </svg>
      </button>

      <div class="collapse navbar-collapse below-lg:absolute below-lg:inset-x-0 below-lg:top-full below-lg:bg-nav-menu below-lg:backdrop-blur-md" id="nav">
        {{-- Menu padding and bottom line live here, not on #nav: Bootstrap animates only #nav's height, so padding on it stayed visible as a strip while closing. From lg up this wrapper disappears (contents). --}}
        <div class="px-6 pt-2 pb-[1rem] border-b border-on-brand/25 min-[992px]:contents">
          <ul class="navbar-nav gap-[0.25rem] min-[992px]:absolute min-[992px]:left-1/2 min-[992px]:-translate-x-1/2 min-[992px]:flex-row min-[992px]:gap-4 min-[1200px]:gap-12">
            <li class="nav-item"><a href="{{ route('user.landing') }}#" class="{{ $navLinkClass }}" data-nav-target="home" aria-current="location">Home</a></li>
            <li class="nav-item"><a href="{{ route('user.landing') }}#features" class="{{ $navLinkClass }}" data-nav-target="features">Features</a></li>
            <li class="nav-item"><a href="{{ route('user.landing') }}#packages" class="{{ $navLinkClass }}" data-nav-target="packages">Packages</a></li>
            <li class="nav-item"><a href="{{ route('user.landing') }}#inclusions" class="{{ $navLinkClass }}" data-nav-target="inclusions">Inclusions</a></li>
            <li class="nav-item"><a href="{{ route('user.landing') }}#about" class="{{ $navLinkClass }}" data-nav-target="about">About</a></li>
            @auth
            <li class="nav-item min-[992px]:hidden">
              <a href="{{ route('user.account') }}" class="{{ $navLinkClass }}">
                Account
                @if (($chatUnread ?? 0) > 0)
                <span class="{{ $navBadgeClass }}" data-chat-unread-badge>{{ $chatUnread > 9 ? '9+' : $chatUnread }}</span>
                @endif
              </a>
            </li>
            @endauth
          </ul>
        </div>

        <div class="hidden items-center justify-center gap-4 min-[992px]:ml-auto min-[992px]:flex">
          @auth
          <a href="{{ route('user.account') }}"
            aria-label="My account ({{ ($chatUnread ?? 0) > 0 ? ($chatUnread > 9 ? '9+' : $chatUnread).' unread messages' : 'no unread messages' }})"
            title="My account"
            class="relative hidden size-11 place-items-center rounded-full border border-on-brand/35 bg-on-brand/10 text-xl text-on-brand no-underline transition-colors duration-200 ease-in-out motion-reduce:transition-none hover:border-on-brand/75 hover:bg-on-brand/20 focus-visible:border-on-brand/75 focus-visible:bg-on-brand/20 focus-visible:outline-2 focus-visible:outline-offset-3 focus-visible:outline-on-brand min-[992px]:grid">
            <x-chat.icon name="user" />
            @if (($chatUnread ?? 0) > 0)
            <span class="{{ $navBadgeClass }}" data-chat-unread-badge>{{ $chatUnread > 9 ? '9+' : $chatUnread }}</span>
            @endif
          </a>
          @else
          <x-button type="button"
            data-bs-toggle="modal" data-bs-target="#authModal"
            data-auth-view="signup"
            class="rounded-lg btn-color-gradient--primary px-4 py-2">
            Sign up
          </x-button>

          <x-button type="button"
            data-bs-toggle="modal"
            data-bs-target="#authModal"
            data-auth-view="login"
            class="rounded-lg btn-color-gradient--secondary px-4 py-2">
            Login
          </x-button>
          @endauth
        </div>
      </div>
    </div>
  </nav>
</header>