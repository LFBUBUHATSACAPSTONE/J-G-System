@php
// Utility strings written whole so Tailwind can find them. The account sidebar
// (components/account/sidebar.blade.php) repeats the toggle, close, link and footer strings: a change to one is a change to both.
$toggleClass = 'sticky top-0 z-[1030] flex h-14 w-full items-center justify-start border-0 bg-sidebar px-4 py-0 text-fluid-icon text-surface focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sidebar-ring min-[992px]:hidden';
$closeClass = 'inline-flex size-9 flex-none items-center justify-center rounded-field border-0 bg-sidebar text-fluid-xl text-surface focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sidebar-ring min-[992px]:hidden';
$asideClass = 'overflow-y-auto font-sans text-surface [-ms-overflow-style:none] [scrollbar-width:none] [--bs-offcanvas-bg:var(--color-sidebar)] [--bs-offcanvas-color:var(--color-surface)] [--bs-offcanvas-width:min(16rem,80vw)] [&::-webkit-scrollbar]:hidden min-[992px]:fixed min-[992px]:inset-y-0 min-[992px]:left-0 min-[992px]:flex min-[992px]:w-78 min-[992px]:flex-col';
$linkBase = 'flex items-center gap-3 rounded-field border-2 border-transparent px-4 py-2 text-[0.9rem] font-medium no-underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-sidebar-ring [&_i]:text-fluid-heading [&_i]:leading-none min-[992px]:px-8 min-[992px]:[&_i]:text-fluid-xl';
$linkOn = 'text-surface hover:bg-sidebar-hover aria-[current=page]:border-brand';
$buttonReset = 'w-full cursor-pointer bg-transparent text-left';
@endphp
<button
  type="button"
  class="{{ $toggleClass }}"
  data-bs-toggle="offcanvas"
  data-bs-target="#adminSidebar"
  aria-controls="adminSidebar"
  aria-label="Open navigation">
  <i class="ph ph-list" aria-hidden="true"></i>
</button>

<aside id="adminSidebar" class="{{ $asideClass }} offcanvas-lg offcanvas-start" tabindex="-1" aria-label="Admin">
  <div class="mx-4 flex items-center justify-between border-b border-sidebar-divider py-4 min-[992px]:mx-8 min-[992px]:justify-center min-[992px]:py-6">
    <img
      src="{{ asset('images/logo/J&G_official_logo.webp') }}"
      alt="J&G Audio Lights and Sounds"
      class="h-auto w-full max-w-32 min-[992px]:max-w-40">

    <button
      type="button"
      class="{{ $closeClass }}"
      data-bs-dismiss="offcanvas"
      data-bs-target="#adminSidebar"
      aria-label="Close navigation">
      <i class="ph ph-x" aria-hidden="true"></i>
    </button>
  </div>

  <nav class="px-4 py-6 min-[992px]:px-8" aria-label="Admin navigation">
    <ul class="m-0 flex list-none flex-col gap-1 p-0 min-[992px]:gap-3">
      @foreach (config('admin.nav') as $item)
      @php
      $isActive = request()->routeIs($item['active'] ?? $item['route']);
      // Route::has() keeps the sidebar rendering before every page exists.
      $href = Route::has($item['route']) ? route($item['route']) : '#';
      @endphp
      <li>
        <a
          href="{{ $href }}"
          class="{{ $linkBase }} {{ $linkOn }}"
          @if ($isActive) aria-current="page" @endif>
          <i class="ph ph-{{ $item['icon'] }}" aria-hidden="true"></i>
          <span>{{ $item['label'] }}</span>
          @if ($item['route'] === 'admin.messages' && ! empty($adminChatUnread))
          <span class="ml-auto min-w-5.5 flex-none rounded-full bg-brand px-2 py-0 text-center text-xs leading-[1.4] font-semibold text-on-brand" data-chat-unread-badge>{{ $adminChatUnread > 9 ? '9+' : $adminChatUnread }}<span class="sr-only"> unread messages</span></span>
          @endif
        </a>
      </li>
      @endforeach
    </ul>
  </nav>

  <div class="mt-auto border-t border-sidebar-divider p-4 min-[992px]:mx-8 min-[992px]:px-0 min-[992px]:py-6">
    <form method="POST" action="{{ Route::has('admin.logout') ? route('admin.logout') : '#' }}">
      @csrf
      <button type="submit" class="{{ $linkBase }} {{ $linkOn }} {{ $buttonReset }}">
        <i class="ph ph-sign-out" aria-hidden="true"></i>
        <span>Log out</span>
      </button>
    </form>
  </div>
</aside>