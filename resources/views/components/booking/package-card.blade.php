@props([
'name',
'price',
'features' => [],
'featured' => false,
'previewCount' => 2, // features always visible on phones; the rest sit behind "See more"
])

@php
$featureId = 'pkg-features-' . \Illuminate\Support\Str::slug($name);
$visibleFeatures = array_slice($features, 0, $previewCount);
$extraFeatures = array_slice($features, $previewCount);
@endphp

{{--Individual Package Cards Contents--}}
<div
  class="package-card{{ $featured ? ' package-card--featured' : '' }}"
  data-package-card
  data-package-name="{{ $name }}"
  data-package-cost="{{ $price }}">

  <h3 class="package-card__name">{{ $name }}</h3>
  <p class="package-card__price">Php {{ number_format((float) $price) }}</p>

  @auth
  <a class="package-card__cta" data-package-select href="{{ route('user.booking') }}">
    Book Now
  </a>
  @else
  <button type="button" class="package-card__cta" data-package-select data-bs-toggle="modal" data-bs-target="#authModal" data-auth-view="login"><span>Book Now</span></button>
  @endauth

  <div class="package-card__divider"></div>

  <div class="package-card__features">
    <h4 class="package-card__features-title">
      @if ($extraFeatures)
      {{-- Below 768px this toggles the extra features (one card open at a time via data-bs-parent);
           from 768px up it is inert and everything is visible (see _package.scss). --}}
      <button
        class="package-card__toggle collapsed"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#{{ $featureId }}"
        aria-expanded="false"
        aria-controls="{{ $featureId }}">
        <span class="package-card__toggle-title">Features</span>
        <span class="package-card__toggle-action">
          <span class="package-card__toggle-more">See more</span>
          <span class="package-card__toggle-less">See less</span>
          <span class="package-card__chevron" aria-hidden="true"></span>
        </span>
      </button>
      @else
      Features
      @endif
    </h4>

    <ul class="package-card__features-list">
      @foreach ($visibleFeatures as $feature)
      <li>{{ $feature }}</li>
      @endforeach
    </ul>

    @if ($extraFeatures)
    <div id="{{ $featureId }}" class="package-card__more collapse" data-bs-parent="#package-grid">
      <ul class="package-card__features-list package-card__features-list--extra">
        @foreach ($extraFeatures as $feature)
        <li>{{ $feature }}</li>
        @endforeach
      </ul>
    </div>
    @endif
  </div>
</div>