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

// Table cells. Package header cells and value cells share $cellClass; the left-hand label cells are
// their own recipe so a utility never has to override another one.
$cellClass = 'px-2 py-[0.85rem] align-middle font-light leading-[1.4] text-glass-body';
$labelClass = 'w-[18%] px-2 py-[0.85rem] text-left align-middle leading-[1.4] font-semibold text-on-brand';
@endphp

<div id="inclusions" class="relative isolate z-1 w-full bg-landing-base [clip-path:inset(-100vh_0_0_0)]">
  <img src="{{ asset('images/backgrounds/design/sides_star.webp') }}" alt="" aria-hidden="true" class="pointer-events-none absolute top-[-8%] left-0 w-full -translate-y-[40%] opacity-60">
  <img src="{{ asset('images/backgrounds/design/star_tall.webp') }}" class="pointer-events-none absolute bottom-0 left-1/2 w-full -translate-x-1/2 translate-y-[13%] opacity-60" alt="" aria-hidden="true">
  <section class="relative z-1 mx-auto max-w-[1100px] px-(--section-pad-x) pt-(--section-pad-top) pb-(--section-pad-bottom) text-on-brand" aria-labelledby="pcmp-title">
    <h2 class="mt-0 mb-7 text-center font-heading text-[length:clamp(1.4rem,1rem_+_2vw,2.25rem)] leading-[1.2] font-extrabold tracking-[0.12em] text-features-title uppercase [text-shadow:0_2px_12px_oklch(0%_0_0/0.45)] md:mb-9" id="pcmp-title">{{ $title }}</h2>

    @foreach ($sections as $section)
    <div class="not-last:mb-8 md:not-last:mb-11">
      <h3 class="mt-0 mb-[0.85rem] font-heading text-[0.85rem] leading-[1.2] font-bold tracking-[0.15em] text-features-title uppercase md:text-[0.95rem]">{{ $section['title'] }}</h3>

      <div class="overflow-x-auto rounded-panel border-[1px] border-glass-border bg-glass px-[0.75rem] pt-1 pb-2 shadow-[inset_0_1px_0_var(--color-glass-highlight),0_8px_32px_var(--color-glass-shadow)] backdrop-blur-[10px] backdrop-saturate-140 min-[992px]:rounded-card min-[992px]:px-6 min-[992px]:pt-2 min-[992px]:pb-3">
        {{-- --pcmp-cols widens the table (min-width) for every package column beyond the original six. --}}
        <table class="w-full min-w-[max(820px,calc(9rem_+_var(--pcmp-cols,6)*7rem))] border-collapse text-center text-[0.75rem] min-[992px]:text-[0.8rem]" style="--pcmp-cols: {{ count($packages) }}">
          <thead>
            <tr class="border-b border-glass-border">
              <th scope="col" class="{{ $labelClass }}">Features / Equipment</th>
              @foreach ($packages as $package)
              <th scope="col" class="{{ $cellClass }}">
                <span class="block leading-[1.3] font-semibold text-on-brand">{{ $package['name'] }}</span>
                <span class="block bg-(image:--gradient-price) bg-clip-text leading-[1.3] font-bold text-transparent [-webkit-text-fill-color:transparent]">Php {{ number_format((float) $package['price']) }}</span>
              </th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @foreach ($section['rows'] as $rowKey => $label)
            <tr class="transition-[background-color] duration-250 ease-[ease] not-first:border-t not-first:border-glass-divider hover:bg-glass">
              <th scope="row" class="{{ $labelClass }}">{{ $label }}</th>
              @foreach ($packages as $package)
              @php
              $value = trim((string) ($equipmentById[$package['id']][$rowKey] ?? ''));
              @endphp
              <td class="{{ $cellClass }}">
                @if ($value === '')
                <span aria-hidden="true">{{ $emptyMark }}</span>
                <span class="sr-only">Not included</span>
                @elseif (in_array(mb_strtolower($value), $yesValues, true))
                <svg class="inline-block size-4 align-middle text-auth-accent" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" role="img" aria-label="Included">
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