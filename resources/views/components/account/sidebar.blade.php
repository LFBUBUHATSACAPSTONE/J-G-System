{{-- Account sidebar. Same look as the admin sidebar (components/admin/sidebar.blade.php: the toggle, close,
     link and footer strings are the same and must match), but with no logo: the top slot is a "Back to Home"
     link, and Log out sits at the bottom.
     Tabs come from config/account.php -> nav. A tab whose route does not exist yet is drawn
     disabled with a "Soon" tag, so the whole menu shows while pages are built one by one. --}}
@php
$toggleClass = 'sticky top-0 z-[1030] flex h-14 w-full items-center justify-start border-0 bg-sidebar px-4 py-0 text-fluid-icon text-surface focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sidebar-ring min-[992px]:hidden';
$closeClass = 'inline-flex size-9 flex-none items-center justify-center rounded-field border-0 bg-sidebar text-fluid-xl text-surface focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sidebar-ring min-[992px]:hidden';
$asideClass = 'overflow-y-auto font-sans text-surface [-ms-overflow-style:none] [scrollbar-width:none] [--bs-offcanvas-bg:var(--color-sidebar)] [--bs-offcanvas-color:var(--color-surface)] [--bs-offcanvas-width:min(16rem,80vw)] [&::-webkit-scrollbar]:hidden min-[992px]:fixed min-[992px]:inset-y-0 min-[992px]:left-0 min-[992px]:flex min-[992px]:w-78 min-[992px]:flex-col';
$linkBase = 'flex items-center gap-3 rounded-field border-2 border-transparent px-4 py-2 text-[0.9rem] font-medium no-underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sidebar-ring [&_i]:text-fluid-heading [&_i]:leading-none min-[992px]:px-8 min-[992px]:[&_i]:text-fluid-xl';
$linkOn = 'text-surface hover:bg-sidebar-hover aria-[current=page]:border-brand';
$linkOff = 'cursor-not-allowed text-sidebar-muted';
$linkDanger = 'text-sidebar-danger hover:bg-sidebar-hover';
$buttonReset = 'w-full bg-transparent text-left';
$backClass = 'inline-flex items-center gap-2 rounded-field px-3 py-2 text-[0.9rem] font-medium text-surface no-underline hover:bg-sidebar-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sidebar-ring [&_i]:text-fluid-heading [&_i]:leading-none';
$badgeClass = 'ml-auto min-w-[1.375rem] flex-none rounded-full bg-brand px-2 py-0 text-center text-xs leading-[1.4] font-semibold text-on-brand';
$soonClass = 'ml-auto rounded-full border border-sidebar-divider px-2 py-1 text-xs leading-[1.2] text-sidebar-soon';
@endphp
<button
  type="button"
  class="{{ $toggleClass }}"
  data-bs-toggle="offcanvas"
  data-bs-target="#accountSidebar"
  aria-controls="accountSidebar"
  aria-label="Open navigation">
  <i class="ph ph-list" aria-hidden="true"></i>
</button>

<aside id="accountSidebar" class="{{ $asideClass }} offcanvas-lg offcanvas-start" tabindex="-1" aria-label="Account">
  <div class="mx-4 flex items-center justify-between border-b border-sidebar-divider py-4 min-[992px]:mx-8 min-[992px]:justify-start">
    <a href="{{ route('user.landing') }}" class="{{ $backClass }}">
      <i class="ph ph-arrow-left" aria-hidden="true"></i>
      <span>Back to Home</span>
    </a>

    <button
      type="button"
      class="{{ $closeClass }}"
      data-bs-dismiss="offcanvas"
      data-bs-target="#accountSidebar"
      aria-label="Close navigation">
      <i class="ph ph-x" aria-hidden="true"></i>
    </button>
  </div>

  <nav class="px-4 py-6 min-[992px]:px-8" aria-label="Account navigation">
    <ul class="m-0 flex list-none flex-col gap-1 p-0 min-[992px]:gap-3">
      @foreach (config('account.nav') as $item)
      @if (! empty($item['modal']))
      {{-- Modal tab (Delete Account): a button that opens the modal; there is no page or route behind it. --}}
      <li>
        <button
          type="button"
          class="{{ $linkBase }} {{ $buttonReset }} {{ ! empty($item['danger']) ? $linkDanger : $linkOn }}"
          data-bs-toggle="modal"
          data-bs-target="#{{ $item['modal'] }}">
          <i class="ph ph-{{ $item['icon'] }}" aria-hidden="true"></i>
          <span>{{ $item['label'] }}</span>
        </button>
      </li>
      @continue
      @endif
      @php
      $isActive = request()->routeIs($item['active'] ?? $item['route']);
      $isReady = Route::has($item['route']);
      $linkClass = $linkBase . ' ' . (! $isReady ? $linkOff : (! empty($item['danger']) ? $linkDanger : $linkOn));
      @endphp
      <li>
        @if ($isReady)
        <a
          href="{{ route($item['route']) }}"
          class="{{ $linkClass }}"
          @if ($isActive) aria-current="page" @endif>
          <i class="ph ph-{{ $item['icon'] }}" aria-hidden="true"></i>
          <span>{{ $item['label'] }}</span>
          @if (($item['route'] ?? null) === 'user.account.messages' && ! empty($chatUnread))
          <span class="{{ $badgeClass }}" data-chat-unread-badge>{{ $chatUnread > 9 ? '9+' : $chatUnread }}<span class="sr-only"> unread messages</span></span>
          @endif
        </a>
        @else
        <span class="{{ $linkClass }}" aria-disabled="true">
          <i class="ph ph-{{ $item['icon'] }}" aria-hidden="true"></i>
          <span>{{ $item['label'] }}</span>
          <span class="{{ $soonClass }}">Soon</span>
        </span>
        @endif
      </li>
      @endforeach
    </ul>
  </nav>

  <div class="mt-auto border-t border-sidebar-divider p-4 min-[992px]:mx-8 min-[992px]:px-0 min-[992px]:py-6">
    {{-- auth/logout.js asks "Log out?" in a modal, sends this as a fetch (the logout route answers JSON) and then goes to the landing page. --}}
    <form
      method="POST"
      action="{{ Route::has('logout') ? route('logout') : '#' }}"
      data-logout
      data-redirect-url="{{ Route::has('user.landing') ? route('user.landing') : '/' }}">
      @csrf
      <button type="submit" class="{{ $linkBase }} {{ $buttonReset }} {{ $linkOn }}">
        <i class="ph ph-sign-out" aria-hidden="true"></i>
        <span>Log out</span>
      </button>
    </form>
  </div>
</aside>