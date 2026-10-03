<button
  type="button"
  class="admin-sidebar-toggle d-lg-none"
  data-bs-toggle="offcanvas"
  data-bs-target="#adminSidebar"
  aria-controls="adminSidebar"
  aria-label="Open navigation">
  <i class="ph ph-list" aria-hidden="true"></i>
</button>

<aside id="adminSidebar" class="admin-sidebar offcanvas-lg offcanvas-start" tabindex="-1" aria-label="Admin">
  <div class="admin-sidebar__header">
    <img
      src="{{ asset('images/logo/J&G_official_logo.webp') }}"
      alt="J&G Audio Lights and Sounds"
      class="admin-sidebar__logo">

    <button
      type="button"
      class="admin-sidebar__close d-lg-none"
      data-bs-dismiss="offcanvas"
      data-bs-target="#adminSidebar"
      aria-label="Close navigation">
      <i class="ph ph-x" aria-hidden="true"></i>
    </button>
  </div>

  <nav class="admin-sidebar__nav" aria-label="Admin navigation">
    <ul class="admin-sidebar__list">
      @foreach (config('admin.nav') as $item)
      @php
      $isActive = request()->routeIs($item['active'] ?? $item['route']);
      // Route::has() keeps the sidebar rendering before every page exists.
      $href = Route::has($item['route']) ? route($item['route']) : '#';
      @endphp
      <li>
        <a
          href="{{ $href }}"
          class="admin-sidebar__link{{ $isActive ? ' is-active' : '' }}"
          @if ($isActive) aria-current="page" @endif>
          <i class="ph ph-{{ $item['icon'] }}" aria-hidden="true"></i>
          <span>{{ $item['label'] }}</span>
        </a>
      </li>
      @endforeach
    </ul>
  </nav>
</aside>