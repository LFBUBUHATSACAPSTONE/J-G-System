@props([
'title' => null,
'description' => null,
'noindex' => null,
])

@php
$routeName = Route::currentRouteName() ?? '';
// Index the array directly: route names contain a dot, so config("admin.pages.{$routeName}")
// would be read as nested keys and never match.
$page = (config('admin.pages', []))[$routeName] ?? [];

// Fallback label from the nav registry: first entry whose active pattern matches.
$navLabel = collect(config('admin.nav', []))
->first(fn ($item) => request()->routeIs($item['active'] ?? $item['route']))['label'] ?? null;

$appName = config('admin.name', 'J&G Admin');
$label = $page['title'] ?? $navLabel;

$title = $title ?? ($label ? "{$label} | {$appName}" : $appName);
$description = $description ?? $page['description'] ?? config('admin.default_description');
$noindex = $noindex ?? $page['noindex'] ?? config('admin.noindex', true);
$imageUrl = asset(config('admin.logo', 'images/logo/J&G_official_logo.webp'));
@endphp

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

{{-- Admin is private: never index, never leak the URL as a referrer, opt out of ad-topic tracking. --}}
@if($noindex)
<meta name="robots" content="noindex, nofollow, noarchive">
@endif
<meta name="referrer" content="no-referrer">
<meta http-equiv="Permissions-Policy" content="browsing-topics=(), interest-cohort=()">

<meta name="csrf-token" content="{{ csrf_token() }}">
<meta name="description" content="{{ $description }}">
<meta name="author" content="{{ config('admin.author') }}">

<!-- Favicon -->
<link rel="icon" type="image/webp" href="{{ $imageUrl }}">
<link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

{{-- Poppins is the admin font ($admin-font-family). Loaded here so the admin does not depend on app.scss. --}}
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap">

@vite(['resources/sass/admin.scss', 'resources/js/admin.js'])

<title>{{ $title }}</title>