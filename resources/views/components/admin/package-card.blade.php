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

// Equipment (row_key => text) for the modal's Equipment fields. The values come from the
// package_equipment table (what the admin typed in Admin > Packages); the config defaults are only
// the seed of that table, so they are used when a package has no saved equipment at all.
$equipmentDefaults = config('package-equipment')['defaults'] ?? [];

$payload = [
'id' => $id,
'name' => $package['name'],
'price' => $package['price'],
'features' => array_values($package['features'] ?? []),
'equipment' => $package['equipment'] ?? ($equipmentDefaults[$id] ?? []),
];

// Route::has() keeps the page rendering before the routes exist.
$availabilityUrl = Route::has('admin.packages.availability')
? route('admin.packages.availability', ['package' => $id])
: '#';
@endphp

<article
  class="admin-package-card flex flex-col rounded-card bg-surface px-6 pt-6 pb-3 text-ink [&.is-unavailable]:bg-danger-soft{{ $available ? '' : ' is-unavailable' }}"
  aria-labelledby="pkg-{{ $id }}-name"
  data-package-card="{{ $id }}">
  <header class="flex items-baseline justify-between gap-4">
    <h3 id="pkg-{{ $id }}-name" class="m-0 text-fluid-title font-semibold text-brand-text">{{ $package['name'] }}</h3>
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

  <p class="mt-1 mb-4 text-fluid-xl font-semibold">{{ $prefix }} {{ number_format($package['price']) }}</p>

  <div class="mb-6 flex-1">
    <h4 class="mt-0 mb-1 text-fluid-sm font-semibold text-brand-text">
      @if ($extraFeatures)
      {{-- Below 768px this toggles the extra features; from 768px up it is inert (see _packages.scss). --}}
      <button
        class="group/toggle flex min-h-10 w-full cursor-pointer items-center justify-between gap-3 border-0 bg-transparent px-0 py-1 text-left text-inherit focus-visible:rounded-sm focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text min-[768px]:pointer-events-none min-[768px]:min-h-0 min-[768px]:cursor-default min-[768px]:p-0 collapsed"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#{{ $moreId }}"
        data-bs-parent="#adminPackagesGrid"
        aria-expanded="false"
        aria-controls="{{ $moreId }}">
        <span id="pkg-{{ $id }}-features">Features</span>
        <span class="inline-flex items-center gap-2 text-[0.85rem] font-medium text-ink min-[768px]:hidden">
          <span class="group-[:not(.collapsed)]/toggle:hidden">See more</span>
          <span class="hidden group-[:not(.collapsed)]/toggle:inline">See less</span>
          <span class="size-2 -translate-y-0.5 rotate-45 border-r-2 border-b-2 transition-transform duration-250 group-[:not(.collapsed)]/toggle:translate-y-px group-[:not(.collapsed)]/toggle:-rotate-135 motion-reduce:transition-none" aria-hidden="true"></span>
        </span>
      </button>
      @else
      <span id="pkg-{{ $id }}-features">Features</span>
      @endif
    </h4>

    <ul class="m-0 list-disc pl-4 text-fluid-md [&_li+li]:mt-1" aria-labelledby="pkg-{{ $id }}-features">
      @foreach ($visibleFeatures as $feature)
      <li>{{ $feature }}</li>
      @endforeach
    </ul>

    @if ($extraFeatures)
    <div id="{{ $moreId }}" class="collapse min-[768px]:block! min-[768px]:h-auto! min-[768px]:visible!">
      <ul class="mt-1 mb-0 list-disc pl-4 text-fluid-md [&_li+li]:mt-1" aria-label="More features of {{ $package['name'] }}">
        @foreach ($extraFeatures as $feature)
        <li>{{ $feature }}</li>
        @endforeach
      </ul>
    </div>
    @endif
  </div>

  <div class="-mx-1 mb-2 grid gap-2" role="group" aria-label="Availability of {{ $package['name'] }}">
    @foreach (config('admin.packages.availability') as $key => $state)
    @php
    $active = ($key === 'available') === $available;
    // The active state is a button too (the server treats it as a no-op): it keeps its fill on hover.
    $pressedHover = ['success' => 'aria-pressed:hover:bg-success', 'danger' => 'aria-pressed:hover:bg-danger'][$state['tone']] ?? '';
    $confirm = $state['confirm'] ? str_replace('{name}', $package['name'], $state['confirm']) : null;
    @endphp
    <form class="contents" method="POST" action="{{ $availabilityUrl }}" @if ($confirm && ! $active) data-confirm="{{ $confirm }}" @endif>
      @csrf
      @method('PATCH')
      <input type="hidden" name="availability" value="{{ $key }}">
      <button
        type="submit"
        class="admin-pill admin-pill--{{ $state['tone'] }}{{ $active ? ' admin-pill--solid' : '' }} aria-pressed:cursor-default {{ $pressedHover }}"
        aria-pressed="{{ $active ? 'true' : 'false' }}">{{ $state['label'] }}</button>
    </form>
    @endforeach
  </div>
</article>