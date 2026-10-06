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
$summary = $items->map(fn ($i) => "{$i['label']} {$i['count']}")->join(', ');
@endphp

<section {{ $attributes->class(['admin-card', 'admin-package-rate']) }} aria-labelledby="package-rate-title">
  <h2 id="package-rate-title" class="admin-card__title">Package Rate (approved)</h2>

  @if ($total > 0)
  <div class="admin-package-rate__body">
    <div class="admin-package-rate__figure">
      <svg class="admin-package-rate__chart" viewBox="0 0 42 42" role="img"
        aria-label="Bookings per package: {{ $summary }}. The same figures are listed beside the chart.">
        @foreach ($items as $package)
        @php
        $pct = $package['count'] / $total * 100;
        $dash = max($pct - $gap, 0);
        @endphp
        <circle
          class="admin-chart--{{ $slot($package, $loop->index) }}"
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
      <div class="admin-package-rate__center" aria-hidden="true">
        <strong>{{ $total }}</strong>
        <span>{{ \Illuminate\Support\Str::plural('booking', $total) }}</span>
      </div>
    </div>

    <ul class="admin-package-rate__legend">
      @foreach ($items as $package)
      @php
      $isLink = ! empty($package['id']) && Route::has('admin.bookings');
      $href = $isLink ? route('admin.bookings', ['package' => $package['id']]) : null;
      $pct = number_format($package['count'] / $total * 100, 1);
      @endphp
      <li>
        <span class="admin-package-rate__swatch admin-chart--{{ $slot($package, $loop->index) }}" aria-hidden="true"></span>
        @if ($href)
        <a href="{{ $href }}" class="admin-package-rate__name admin-package-rate__name--link"
          aria-label="{{ $package['label'] }}, {{ $package['count'] }} bookings, {{ $pct }} percent. View in Bookings">{{ $package['label'] }}</a>
        @else
        <span class="admin-package-rate__name">{{ $package['label'] }}@isset($package['more']) <span class="admin-package-rate__more">({{ $package['more'] }} {{ \Illuminate\Support\Str::plural('package', $package['more']) }})</span>@endisset</span>
        @endif
        <span class="admin-package-rate__pct">{{ $package['count'] }} {{ \Illuminate\Support\Str::plural('booking', $package['count']) }} · {{ $pct }}%</span>
      </li>
      @endforeach
    </ul>
  </div>
  @else
  <p class="admin-empty">No bookings yet, so there is nothing to chart.</p>
  @endif
</section>