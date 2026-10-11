{{-- Shell for every account page: head, sidebar, page header, content slot.
     Copy comes from config/account.php (pages.<route name>); a page only supplies its content.
     Pass `title` / `subtitle` to override the heading on a single page. --}}
@props([
'title' => null,
'subtitle' => null,
])

@php
// Index the array directly: route names contain a dot, so config('account.pages.<name>') would be
  // read as nested keys and never match.
  $page = (config('account.pages', []))[Route::currentRouteName() ?? ''] ?? [];

  $navLabel = collect(config('account.nav', []))
  ->first(fn ($item) => request()->routeIs($item['active'] ?? $item['route']))['label'] ?? null;

  $appName = config('account.name', 'J&G Account');
  $label = $page['title'] ?? $navLabel;
  $tabTitle = $label ? "{$label} - {$appName}" : $appName;
  $description = $page['description'] ?? config('account.default_description');
  $imageUrl = asset(config('account.logo', 'images/logo/J&G_official_logo.webp'));

  $heading = $title ?? $page['heading'] ?? $navLabel ?? $appName;
  $subheading = $subtitle ?? $page['subheading'] ?? null;
  @endphp
  <!DOCTYPE html>
  <html lang="en">

  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    {{-- Private pages: never index, never leak the URL as a referrer, opt out of ad-topic tracking. --}}
    @if (config('account.noindex', true))
    <meta name="robots" content="noindex, nofollow, noarchive">
    @endif
    <meta name="referrer" content="no-referrer">
    <meta http-equiv="Permissions-Policy" content="browsing-topics=(), interest-cohort=()">

    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="description" content="{{ $description }}">
    <meta name="author" content="{{ config('account.author') }}">

    <link rel="icon" type="image/webp" href="{{ $imageUrl }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">

    @vite(['resources/sass/account.scss', 'resources/js/account.js'])

    <title>{{ $tabTitle }}</title>
  </head>

  <body>
    <x-account.sidebar />

    <main class="admin-content account-content">
      <header class="admin-page-header">
        <h1 class="admin-page-header__title">{{ $heading }}</h1>
        @if ($subheading)
        <p class="admin-page-header__subtitle">{{ $subheading }}</p>
        @endif
      </header>

      {{ $slot }}
    </main>
  </body>

  </html>