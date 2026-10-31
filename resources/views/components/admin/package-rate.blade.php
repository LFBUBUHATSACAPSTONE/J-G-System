{{-- Package Rate donut. Pure SVG + Blade, no chart library, no JS.
     Input: [['id' => 'budget-lite', 'label' => 'Budget Lite', 'count' => 12], ...]
     (counts, not percentages; the percentage is worked out here). `id` is optional: with it the
     legend row links to Bookings filtered by that package.
     The largest `top` packages (config/admin/dashboard.php 'chart_top') get a slice each and
     the rest are grouped as "Other", so the chart stays readable when packages are added.
     Colour never identifies a package on its own: the legend always shows name, count and %. --}}
@props(['packages' => [], 'top' => null])

@php
$top = $top ?? config('admin.dashboard.chart_top', 5);

$sorted = collect($packages)->filter(fn ($p) => ($p['count'] ?? 0) > 0)->sortByDesc('count')->values();
$items = $sorted->take($top)->all();
$rest = $sorted->slice($top);
if ($rest->isNotEmpty()) {
$items[] = ['label' => 'Other', 'count' => $rest->sum('count'), 'other' => true, 'more' => $rest->count()];
}
$items = collect($items);

$total = $items->sum('count');
$gap = $items->count() > 1 ? 0.6 : 0; // white separator between segments
$offset = 0;
$slot = fn ($item, $i) => ($item['other'] ?? false) ? 'other' : ($i % 6) + 1;
$slices = [1 => 'text-slice-1', 2 => 'text-slice-2', 3 => 'text-slice-3', 4 => 'text-slice-4', 5 => 'text-slice-5', 6 => 'text-slice-6', 'other' => 'text-ink-subtle'];
$summary = $items->map(fn ($i) => "{$i['label']} {$i['count']}")->join(', ');
@endphp

<section {{ $attributes->class(['rounded-card bg-surface p-4 min-[576px]:p-6']) }} aria-labelledby="package-rate-title">
  <h2 id="package-rate-title" class="mt-0 mb-4 text-fluid-base font-semibold text-brand-text min-[576px]:text-fluid-heading">Package Rate <span class="text-[0.8em] font-normal text-ink-muted max-[575.98px]:block">(approved)</span></h2>

  @if ($total > 0)
  <div class="flex flex-wrap items-center gap-x-8 gap-y-6 max-[575.98px]:justify-center max-[575.98px]:gap-4">
    <div class="relative size-48 flex-none max-[575.98px]:size-40">
      <svg class="block size-full" viewBox="0 0 42 42" role="img"
        aria-label="Bookings per package: {{ $summary }}. The same figures are listed beside the chart.">
        @foreach ($items as $package)
        @php
        $pct = $package['count'] / $total * 100;
        $dash = max($pct - $gap, 0);
        @endphp
        <circle
          class="{{ $slices[$slot($package, $loop->index)] }}"
          cx="21" cy="21" r="15.9155"
          fill="none" stroke="currentColor" stroke-width="5"
          pathLength="100"
          stroke-dasharray="{{ round($dash, 3) }} {{ round(100 - $dash, 3) }}"
          stroke-dashoffset="{{ $offset > 0 ? -round($offset, 3) : 0 }}"
          transform="rotate(-90 21 21)" />
        @php $offset += $pct; @endphp
        @endforeach
      </svg>
      {{-- Decorative: the total is already in the chart's text alternative. --}}
      <div class="pointer-events-none absolute inset-0 flex flex-col items-center justify-center" aria-hidden="true">
        <strong class="text-fluid-display leading-none font-bold text-brand-text">{{ $total }}</strong>
        <span class="text-[0.8rem] text-ink-muted">{{ \Illuminate\Support\Str::plural('booking', $total) }}</span>
      </div>
    </div>

    <ul class="m-0 grid flex-[1_1_18rem] list-none grid-cols-[repeat(auto-fit,minmax(13rem,1fr))] gap-x-6 gap-y-4 p-0 max-[575.98px]:basis-full max-[575.98px]:grid-cols-2 max-[575.98px]:gap-3">
      @foreach ($items as $package)
      @php
      $isLink = ! empty($package['id']) && Route::has('admin.bookings');
      $href = $isLink ? route('admin.bookings', ['package' => $package['id']]) : null;
      $pct = number_format($package['count'] / $total * 100, 1);
      @endphp
      <li class="grid grid-cols-[auto_1fr] items-center gap-x-2">
        <span class="row-span-2 size-5 rounded-full bg-current {{ $slices[$slot($package, $loop->index)] }}" aria-hidden="true"></span>
        @if ($href)
        <a href="{{ $href }}" class="text-fluid-sm font-medium text-ink no-underline hover:text-brand-text hover:underline focus-visible:rounded-field focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text"
          aria-label="{{ $package['label'] }}, {{ $package['count'] }} bookings, {{ $pct }} percent. View in Bookings">{{ $package['label'] }}</a>
        @else
        <span class="text-fluid-sm font-medium text-ink">{{ $package['label'] }}@isset($package['more']) <span class="text-[0.8rem] font-normal text-ink-muted">({{ $package['more'] }} {{ \Illuminate\Support\Str::plural('package', $package['more']) }})</span>@endisset</span>
        @endif
        <span class="text-[0.8rem] text-ink-muted"><span class="max-[575.98px]:block">{{ $package['count'] }} {{ \Illuminate\Support\Str::plural('booking', $package['count']) }}</span><span class="max-[575.98px]:hidden"> · </span><span class="max-[575.98px]:block">{{ $pct }}%</span></span>
      </li>
      @endforeach
    </ul>
  </div>
  @else
  <p class="m-0 text-fluid-sm text-ink-muted">No bookings yet, so there is nothing to chart.</p>
  @endif
</section>