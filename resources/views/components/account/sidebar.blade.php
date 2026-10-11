{{-- Account sidebar. Same look as the admin sidebar (it reuses the .admin-sidebar styles), but with
     no logo: the top slot is a "Back to Home" link, and Log out sits at the bottom.
     Tabs come from config/account.php -> nav. A tab whose route does not exist yet is drawn
     disabled with a "Soon" tag, so the whole menu shows while pages are built one by one. --}}
<button
  type="button"
  class="admin-sidebar-toggle d-lg-none"
  data-bs-toggle="offcanvas"
  data-bs-target="#accountSidebar"
  aria-controls="accountSidebar"
  aria-label="Open navigation">
  <i class="ph ph-list" aria-hidden="true"></i>
</button>

<aside id="accountSidebar" class="admin-sidebar account-sidebar offcanvas-lg offcanvas-start" tabindex="-1" aria-label="Account">
  <div class="admin-sidebar__header">
    <a href="{{ route('user.landing') }}" class="account-sidebar__back">
      <i class="ph ph-arrow-left" aria-hidden="true"></i>
      <span>Back to Home</span>
    </a>

    <button
      type="button"
      class="admin-sidebar__close d-lg-none"
      data-bs-dismiss="offcanvas"
      data-bs-target="#accountSidebar"
      aria-label="Close navigation">
      <i class="ph ph-x" aria-hidden="true"></i>
    </button>
  </div>

  <nav class="admin-sidebar__nav" aria-label="Account navigation">
    <ul class="admin-sidebar__list">
      @foreach (config('account.nav') as $item)
      @php
      $isActive = request()->routeIs($item['active'] ?? $item['route']);
      $isReady = Route::has($item['route']);
      $linkClass = 'admin-sidebar__link'
      . ($isActive ? ' is-active' : '')
      . (! $isReady ? ' is-disabled' : '')
      . (! empty($item['danger']) ? ' is-danger' : '');
      @endphp
      <li>
        @if ($isReady)
        <a
          href="{{ route($item['route']) }}"
          class="{{ $linkClass }}"
          @if ($isActive) aria-current="page" @endif>
          <i class="ph ph-{{ $item['icon'] }}" aria-hidden="true"></i>
          <span>{{ $item['label'] }}</span>
        </a>
        @else
        <span class="{{ $linkClass }}" aria-disabled="true">
          <i class="ph ph-{{ $item['icon'] }}" aria-hidden="true"></i>
          <span>{{ $item['label'] }}</span>
          <span class="account-sidebar__soon">Soon</span>
        </span>
        @endif
      </li>
      @endforeach
    </ul>
  </nav>

  <div class="account-sidebar__footer">
    <form method="POST" action="{{ Route::has('logout') ? route('logout') : '#' }}">
      @csrf
      <button type="submit" class="admin-sidebar__link account-sidebar__logout">
        <i class="ph ph-sign-out" aria-hidden="true"></i>
        <span>Log out</span>
      </button>
    </form>
  </div>
</aside>