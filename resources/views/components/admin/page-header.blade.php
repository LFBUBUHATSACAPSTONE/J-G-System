{{-- Gradient page title + subtitle, used by every admin page (Dashboard included).
     Copy comes from config/admin.php -> pages.<route name>.heading / .subheading, so changing
     a page's wording is one config edit. Pass `title` / `subtitle` to override on a single page.
     If a page has no config entry, the title falls back to its sidebar label and the subtitle
     is left out. --}}
@props([
'title' => null,
'subtitle' => null,
'compact' => false,
])

@php
// Index the array directly: route names contain a dot, so config('admin.pages.admin.bookings')
// would be read as nested keys and never match the flat 'admin.bookings' key.
$page = (config('admin.pages', []))[Route::currentRouteName() ?? ''] ?? [];

$navLabel = collect(config('admin.nav', []))
->first(fn ($item) => request()->routeIs($item['active'] ?? $item['route']))['label'] ?? null;

$title = $title ?? $page['heading'] ?? $navLabel ?? config('admin.name', 'J&G Admin');
$subtitle = $subtitle ?? $page['subheading'] ?? null;

// `compact` (Dashboard): a slightly smaller title and subtitle on phones.
$titleClass = $compact ? 'max-[575.98px]:text-[clamp(1.5rem,1.2rem_+_2vw,2.5rem)]' : '';
$subtitleClass = $compact ? 'max-[575.98px]:text-sm' : '';
@endphp

<header {{ $attributes->class(['admin-page-header', 'mb-6 font-sans min-[992px]:mb-8']) }}>
  <h1 class="inline-block m-0 bg-[linear-gradient(90deg,var(--color-brand-text),var(--color-brand-end))] bg-clip-text text-[clamp(1.75rem,1.2rem+2vw,2.5rem)] font-bold text-transparent [-webkit-text-fill-color:transparent] forced-colors:bg-none forced-colors:text-[CanvasText] forced-colors:[-webkit-text-fill-color:currentColor] {{ $titleClass }}">{{ $title }}</h1>
  @if ($subtitle)
  <p class="mt-1 mb-0 text-[clamp(0.9347rem,0.9291rem+0.0279vw,0.95rem)] text-ink-muted {{ $subtitleClass }}">{{ $subtitle }}</p>
  @endif
</header>