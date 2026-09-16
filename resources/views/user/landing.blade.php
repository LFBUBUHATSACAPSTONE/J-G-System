<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <x-site-head></x-site-head>
</head>

<div class="overflow-hidden">
  <img src="{{ asset('images/backgrounds/design/landing_bg.webp') }}" alt="" class="position-absolute z-0 w-100 h-100">

  <header class="position-relative z-1">
    <nav class="navbar navbar-expand-md navbar-dark bg-dark">
      <div class="container">
        <a href="#" class="navbar-brand">
          <img src="{{ asset('images/logo/logo_rectangular.webp') }}" alt="Business Logo" class="position-relative z-1">
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav" aria-controls="nav" aria-expanded="false" aria-label="Expand Navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="nav">
          <ul class="navbar-nav mx-auto">
            <li class="nav-item"><a href="#" class="nav-link active font-family-base" aria-current="page">About</a></li>
            <li class="nav-item"><a href="#" class="nav-link font-family-base">Feature</a></li>
            <li class="nav-item"><a href="#" class="nav-link font-family-base">Package</a></li>
          </ul>
          <div class="d-flex gap-3">
            <button type="button" class="btn btn-outline-light rounded-3 font-family-text">Sign up</button>
            <button type="button" class="btn btn-light rounded-3 font-family-text">Login</button>
          </div>
        </div>
      </div>
    </nav>
  </header>
</div>

</html>