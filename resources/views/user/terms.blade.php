{{--
  Standalone Terms and Conditions page (route `terms`, GET /terms). The text is config/terms.php, shared with the
  "terms" view of the landing auth modal through <x-terms.content>. Public: no sign-in needed, so the sign-up link, the
  footer and a booking confirmation can all point here.
--}}
@php
$terms = config('terms');
$backClass = 'inline-flex items-center gap-[0.5rem] text-[0.95rem] font-semibold text-auth-accent no-underline hover:underline';
$titleClass = 'text-[1.75rem] font-bold leading-[1.2] text-auth-text [margin:1.5rem_0_0.5rem] min-[768px]:text-[2.25rem]';
$subtitleClass = 'text-[0.95rem] text-auth-text-muted [margin:0_0_2rem]';
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <x-site-head></x-site-head>
</head>

<body class="flex min-h-svh flex-col bg-landing-base text-auth-text">
  <x-toast.region feed="user" />
  <header class="border-b border-glass-border px-[1.25rem] py-[1rem] min-[768px]:px-[2rem]">
    <div class="mx-auto flex max-w-[52rem] items-center justify-between gap-[1rem]">
      <a href="{{ route('user.landing') }}">
        <img src="{{ asset('images/logo/J&G_official_logo.webp') }}" alt="J&amp;G Audio Lights and Sounds" class="w-[8rem]">
      </a>
      <a href="{{ route('user.landing') }}" class="{{ $backClass }}">
        <i class="ph ph-arrow-left" aria-hidden="true"></i>
        <span>Back to home</span>
      </a>
    </div>
  </header>

  <main class="mx-auto w-full max-w-[52rem] flex-1 px-[1.25rem] py-[2rem] min-[768px]:px-[2rem] min-[768px]:py-[3rem]">
    <h1 class="{{ $titleClass }}">{{ $terms['title'] }}</h1>
    <p class="{{ $subtitleClass }}">{{ $terms['subtitle'] }}</p>

    <x-terms.content heading-tag="h2" />
  </main>

  @include('components.landing.footer')
</body>

</html>