@props([
'name',
'price',
'features' => [],
'featured' => false,
])

<div
  class="package-card{{ $featured ? ' package-card--featured' : '' }}"
  data-package-card
  data-package-name="{{ $name }}"
  data-package-cost="{{ $price }}">

  <h3 class="package-card__name">{{ $name }}</h3>
  <p class="package-card__price">Php {{ number_format((float) $price) }}</p>

  {{-- Opens the shared #authModal on its Login view (same attributes every
       other login CTA uses). data-package-select still fires
       booking:package-saved with the chosen package (see package.js). --}}
  <x-button type="button"
    class="package-card__cta"
    data-package-select
    data-bs-toggle="modal"
    data-bs-target="#authModal"
    data-auth-view="login">
    Book Now
  </x-button>

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