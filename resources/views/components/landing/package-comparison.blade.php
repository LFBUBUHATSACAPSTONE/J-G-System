{{-- Landing "Package Comparison Table".
     Columns come from $packages (the landing route passes the available packages; this file is @included, so it
     shares that variable). Rows come from config/package-equipment.php. Each cell is looked up by the package's
     equipment[row_key], so a new package always lines up under its own header; no value = the empty mark.
     Values per package: $package['equipment'] (row_key => text) — the values the admin typed in Admin > Packages,
     which are stored in the package_equipment table. The config defaults are only the seed of that table (see the
     migration), so a package that has no saved value prints the empty mark. --}}
@props([
'title' => 'Package Comparison Table',
'packages' => [],
])

@php
$equipmentConfig = config('package-equipment');
$sections = $equipmentConfig['sections'] ?? [];
$yesValues = array_map('mb_strtolower', $equipmentConfig['yes_values'] ?? []);
$emptyMark = $equipmentConfig['empty_mark'] ?? '—';
$defaults = $equipmentConfig['defaults'] ?? [];

// package id => its row_key => text map
$equipmentById = [];
foreach ($packages as $package) {
$equipmentById[$package['id']] = $package['equipment'] ?? ($defaults[$package['id']] ?? []);
}
@endphp

<div id="inclusions" class="pcmp-bg position-relative z-1">
  <img src="{{ asset('images/backgrounds/design/sides_star.webp') }}" alt="" aria-hidden="true" class="pcmp-bg__side-image">
  <img src="{{ asset('images/backgrounds/design/star_tall.webp') }}" class="pcmp-bg__image" alt="" aria-hidden="true">
  <section class="pcmp" aria-labelledby="pcmp-title">
    <h2 class="pcmp__title" id="pcmp-title">{{ $title }}</h2>

    @foreach ($sections as $section)
    <div class="pcmp__section">
      <h3 class="pcmp__section-title">{{ $section['title'] }}</h3>

      <div class="pcmp__scroll">
        {{-- --pcmp-cols widens the table (min-width) for every package column beyond the original six. --}}
        <table class="pcmp__table" style="--pcmp-cols: {{ count($packages) }}">
          <thead>
            <tr>
              <th scope="col" class="pcmp__label">Features / Equipment</th>
              @foreach ($packages as $package)
              <th scope="col">
                <span class="pcmp__pkg">{{ $package['name'] }}</span>
                <span class="pcmp__price">Php {{ number_format((float) $package['price']) }}</span>
              </th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @foreach ($section['rows'] as $rowKey => $label)
            <tr>
              <th scope="row" class="pcmp__label">{{ $label }}</th>
              @foreach ($packages as $package)
              @php
              $value = trim((string) ($equipmentById[$package['id']][$rowKey] ?? ''));
              @endphp
              <td>
                @if ($value === '')
                <span aria-hidden="true">{{ $emptyMark }}</span>
                <span class="visually-hidden">Not included</span>
                @elseif (in_array(mb_strtolower($value), $yesValues, true))
                <svg class="pcmp__check" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="Included">
                  <path d="M5 12.5l4.5 4.5L19 7.5" />
                </svg>
                @else
                {{ $value }}
                @endif
              </td>
              @endforeach
            </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
    @endforeach
  </section>
</div>