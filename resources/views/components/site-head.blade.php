@props([
'title' => null,
'ogTitle' => null,
'description' => null,
'ogDescription' => null,
'author' => null,
'image' => null,
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
@endphp

<!-- Primary Meta Tags -->
<meta name="title" content="{{ $title }}">
<meta name="description" content="{{ $description }}">
<meta name="author" content="{{ $author }}">

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

<!-- Google Font : Poppins-->
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet">

<title>{{ $title }}</title>