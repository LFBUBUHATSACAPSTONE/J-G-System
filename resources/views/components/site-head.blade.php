@props([
'title' => null,
'ogTitle' => null,
'description' => null,
'ogDescription' => null,
'author' => null,
'image' => null,
'noindex' => null,
'noTracking' => null,
])

@php
$pages = config('site.pages', []);
$page = $pages[Route::currentRouteName()] ?? [];

$title = $title ?? $page['title'] ?? config('site.default_title');
$description = $description ?? $page['description'] ?? config('site.default_description');
$ogTitle = $ogTitle ?? $page['og_title'] ?? $title;
$ogDescription = $ogDescription ?? $page['og_description'] ?? $description;
$author = $author ?? config('site.author');
$imageUrl = asset($image ?? $page['logo'] ?? config('site.logo'));
$noindex = $noindex ?? $page['noindex'] ?? false;

// Booking carries a client's personal/event details through a multi-step flow, so it's opted out of tracking by default — explicit prop or a page's 'no_tracking' config entry can still override this for any route, booking or otherwise.

$noTracking = $noTracking
?? $page['no_tracking']
?? \Illuminate\Support\Str::startsWith(Route::currentRouteName() ?? '', ['user.booking', 'booking.']);
@endphp

@if($noindex)
<meta name="robots" content="noindex, nofollow">
@endif

@if($noTracking)
<meta name="referrer" content="no-referrer">
<meta http-equiv="Permissions-Policy" content="browsing-topics=(), interest-cohort=()">
@endif

<!-- Primary Meta Tags -->
<meta name="title" content="{{ $title }}">
<meta name="description" content="{{ $description }}">
<meta name="author" content="{{ $author }}">
<meta name="csrf-token" content="{{ csrf_token() }}">

<!-- Open Graph / Facebook -->
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ $ogDescription }}">
<meta property="og:image" content="{{ $imageUrl }}">

<!-- Twitter -->
<meta property="twitter:card" content="summary_large_image">
<meta property="twitter:title" content="{{ $ogTitle }}">
<meta property="twitter:description" content="{{ $ogDescription }}">
<meta property="twitter:image" content="{{ $imageUrl }}">

<!-- Favicon -->
<link rel="icon" type="image/webp" href="{{ $imageUrl }}">
<link rel="icon" type="image/x-icon" href="{{ asset('favicon.ico') }}">

@vite(['resources/js/app.js'])

<title>{{ $title }}</title>