{{-- One package card.
     "Edit" opens the shared #packageModal (package-modal.blade.php): packages.js reads the
     data-package JSON below. Available / Unavailable are two real forms (PATCH to
     admin.packages.availability); the front end never flips the state itself.
     Features behave like the user landing cards: below 768px only the first `preview_features`
     (config/admin/packages.php) show and the rest sit behind a "See more" toggle (one card open at a
     time, via data-bs-parent="#adminPackagesGrid"); from 768px everything is visible and the toggle is inert. --}}
@props(['package', 'previewCount' => null])

@php
$id = $package['id'];
$available = (bool) $package['available'];
$prefix = config('admin.packages.currency_prefix', 'Php');

$features = array_values($package['features'] ?? []);
$previewCount = $previewCount ?? (int) config('admin.packages.preview_features', 2);
$visibleFeatures = array_slice($features, 0, $previewCount);
$extraFeatures = array_slice($features, $previewCount);
$moreId = 'pkg-' . $id . '-more';

$payload = [
'id' => $id,
'name' => $package['name'],
'price' => $package['price'],
'features' => array_values($package['features'] ?? []),
];

// Route::has() keeps the page rendering before the routes exist.
$availabilityUrl = Route::has('admin.packages.availability')
? route('admin.packages.availability', ['package' => $id])
: '#';
@endphp

<article
  class="admin-package-card{{ $available ? '' : ' is-unavailable' }}"
  aria-labelledby="pkg-{{ $id }}-name"
  data-package-card="{{ $id }}">
  <header class="admin-package-card__head">
    <h3 id="pkg-{{ $id }}-name" class="admin-package-card__name">{{ $package['name'] }}</h3>
    <button
      type="button"
      class="admin-btn admin-btn--outline admin-btn--sm"
      data-bs-toggle="modal"
      data-bs-target="#packageModal"
      data-package="{{ json_encode($payload) }}"
      aria-label="Edit {{ $package['name'] }}">
      <i class="ph ph-pencil-simple" aria-hidden="true"></i>
      <span>Edit</span>
    </button>
  </header>

  <p class="admin-package-card__price">{{ $prefix }} {{ number_format($package['price']) }}</p>

  <div class="admin-package-card__features-block">
    <h4 class="admin-package-card__label">
      @if ($extraFeatures)
      {{-- Below 768px this toggles the extra features; from 768px up it is inert (see _packages.scss). --}}
      <button
        class="admin-package-card__toggle collapsed"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#{{ $moreId }}"
        data-bs-parent="#adminPackagesGrid"
        aria-expanded="false"
        aria-controls="{{ $moreId }}">
        <span id="pkg-{{ $id }}-features">Features</span>
        <span class="admin-package-card__toggle-action">
          <span class="admin-package-card__toggle-more">See more</span>
          <span class="admin-package-card__toggle-less">See less</span>
          <span class="admin-package-card__chevron" aria-hidden="true"></span>
        </span>
      </button>
      @else
      <span id="pkg-{{ $id }}-features">Features</span>
      @endif
    </h4>

    <ul class="admin-package-card__features" aria-labelledby="pkg-{{ $id }}-features">
      @foreach ($visibleFeatures as $feature)
      <li>{{ $feature }}</li>
      @endforeach
    </ul>

    @if ($extraFeatures)
    <div id="{{ $moreId }}" class="admin-package-card__more collapse">
      <ul class="admin-package-card__features admin-package-card__features--extra" aria-label="More features of {{ $package['name'] }}">
        @foreach ($extraFeatures as $feature)
        <li>{{ $feature }}</li>
        @endforeach
      </ul>
    </div>
    @endif
  </div>

  <div class="admin-package-card__availability" role="group" aria-label="Availability of {{ $package['name'] }}">
    @foreach (config('admin.packages.availability') as $key => $state)
    @php
    $active = ($key === 'available') === $available;
    $confirm = $state['confirm'] ? str_replace('{name}', $package['name'], $state['confirm']) : null;
    @endphp
    <form method="POST" action="{{ $availabilityUrl }}" @if ($confirm && ! $active) data-confirm="{{ $confirm }}" @endif>
      @csrf
      @method('PATCH')
      <input type="hidden" name="availability" value="{{ $key }}">
      <button
        type="submit"
        class="admin-pill admin-pill--{{ $state['tone'] }}{{ $active ? ' admin-pill--solid' : '' }}"
        aria-pressed="{{ $active ? 'true' : 'false' }}">{{ $state['label'] }}</button>
    </form>
    @endforeach
  </div>
</article>