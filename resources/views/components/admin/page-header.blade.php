{{-- Gradient page title + subtitle, used by every admin page (Dashboard included).
     Copy comes from config/admin.php -> pages.<route name>.heading / .subheading, so changing
     a page's wording is one config edit. Pass `title` / `subtitle` to override on a single page.
     If a page has no config entry, the title falls back to its sidebar label and the subtitle
     is left out. --}}
@props([
'title' => null,
'subtitle' => null,
])

@php
// Index the array directly: route names contain a dot, so config('admin.pages.admin.bookings')
// would be read as nested keys and never match the flat 'admin.bookings' key.
$page = (config('admin.pages', []))[Route::currentRouteName() ?? ''] ?? [];

$navLabel = collect(config('admin.nav', []))
->first(fn ($item) => request()->routeIs($item['active'] ?? $item['route']))['label'] ?? null;

$title = $title ?? $page['heading'] ?? $navLabel ?? config('admin.name', 'J&G Admin');
$subtitle = $subtitle ?? $page['subheading'] ?? null;
@endphp

<header {{ $attributes->class(['admin-page-header']) }}>
  <h1 class="admin-page-header__title">{{ $title }}</h1>
  @if ($subtitle)
  <p class="admin-page-header__subtitle">{{ $subtitle }}</p>
  @endif
</header>