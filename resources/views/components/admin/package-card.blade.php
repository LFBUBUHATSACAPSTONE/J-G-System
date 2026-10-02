{{-- One package card.
     "Edit" opens the shared #packageModal (package-modal.blade.php): 
        packages.js reads the data-package JSON below. Available / Unavailable are two real forms (PATCH to admin.packages.availability); --}}

@props(['package'])

@php
$id = $package['id'];
$available = (bool) $package['available'];
$prefix = config('admin-packages.currency_prefix', 'Php');

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
      class="admin-link"
      data-bs-toggle="modal"
      data-bs-target="#packageModal"
      data-package="{{ json_encode($payload) }}"
      aria-label="Edit {{ $package['name'] }}">Edit</button>
  </header>

  <p class="admin-package-card__price">{{ $prefix }} {{ number_format($package['price']) }}</p>

  <p class="admin-package-card__label" id="pkg-{{ $id }}-features">Features</p>
  <ul class="admin-package-card__features" aria-labelledby="pkg-{{ $id }}-features">
    @foreach ($package['features'] ?? [] as $feature)
    <li>{{ $feature }}</li>
    @endforeach
  </ul>

  <div class="admin-package-card__availability" role="group" aria-label="Availability of {{ $package['name'] }}">
    @foreach (config('admin-packages.availability') as $key => $state)
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