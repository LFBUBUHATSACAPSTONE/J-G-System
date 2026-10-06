@props([
'name',
'price',
'features' => [],
'featured' => false,
])

{{--Individual Package Cards Contents--}} 
<div
  class="package-card{{ $featured ? ' package-card--featured' : '' }}"
  data-package-card
  data-package-name="{{ $name }}"
  data-package-cost="{{ $price }}">

  <h3 class="package-card__name">{{ $name }}</h3>
  <p class="package-card__price">Php {{ number_format((float) $price) }}</p>

  @auth
  <a
    class="package-card__cta"
    data-package-select
    href="{{ route('user.booking') }}">
    Book Now
  </a>
  @else
  <x-button type="button"
    class="package-card__cta"
    data-package-select
    data-bs-toggle="modal"
    data-bs-target="#authModal"
    data-auth-view="login">
    Book Now
  </x-button>
  @endauth

  <div class="package-card__divider"></div>

  <div class="package-card__features">
    <h4 class="package-card__features-title">Features</h4>
    <ul class="package-card__features-list">
      @foreach ($features as $feature)
      <li>{{ $feature }}</li>
      @endforeach
    </ul>
  </div>
</div>