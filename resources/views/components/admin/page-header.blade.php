{{--

Gradient page title + subtitle. The mockups show the same "Welcome Admin!" header on the Bookings page, so the copy is a default, not a rule: pass `title` / `subtitle` per page. 

--}}
@props([
'title' => 'Welcome Admin!',
'subtitle' => 'Here is the overview of your business',
])

<header {{ $attributes->class(['admin-page-header']) }}>
  <h1 class="admin-page-header__title">{{ $title }}</h1>
  <p class="admin-page-header__subtitle">{{ $subtitle }}</p>
</header>