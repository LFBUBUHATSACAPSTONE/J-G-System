@props([
'name',
'price',
'features' => [],
'featured' => false,
'href' => null,
])

<div
  class="package-card{{ $featured ? ' package-card--featured' : '' }}"
  data-package-card
  data-package-name="{{ $name }}"
  data-package-cost="{{ $price }}">

  <h3 class="package-card__name">{{ $name }}</h3>
  <p class="package-card__price">Php {{ number_format((float) $price) }}</p>

  @if ($href)
  <a href="{{ $href }}" class="package-card__cta btn-color-white" data-package-select>
    Book Now
  </a>
  @else
  <button type="button" class="package-card__cta btn-color-white" data-package-select>
    Book Now
  </button>
  @endif

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