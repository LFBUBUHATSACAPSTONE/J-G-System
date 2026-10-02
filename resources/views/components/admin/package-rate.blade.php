{{-- Package Rate donut. Pure SVG + Blade, no chart library, no JS.
     Input: [['label' => 'Budget Lite', 'count' => 12], ...]  (counts, not percentages;
     the percentage is worked out here so the backend only sends booking counts).
     Packages are admin-managed, so the number of packages is not fixed: colours cycle
     through the 6 chart tokens. Colour never identifies a package on its own: the
     legend always shows the name and the percentage. --}}


@props(['packages' => []])

@php
$items = collect($packages);
$total = $items->sum('count');
$gap = $items->count() > 1 ? 0.6 : 0; // white separator between segments
$offset = 0;
@endphp

<section {{ $attributes->class(['admin-card', 'admin-package-rate']) }} aria-labelledby="package-rate-title">
  <h2 id="package-rate-title" class="admin-card__title">Package Rate</h2>

  @if ($total > 0)
  <div class="admin-package-rate__body">
    <svg class="admin-package-rate__chart" viewBox="0 0 42 42" role="img"
      aria-label="Share of bookings per package. The same figures are listed beside the chart.">
      @foreach ($items as $package)
      @php
      $pct = $package['count'] / $total * 100;
      $dash = max($pct - $gap, 0);
      $slot = ($loop->index % 6) + 1;
      @endphp
      <circle
        class="admin-chart--{{ $slot }}"
        cx="21" cy="21" r="15.9155"
        fill="none" stroke="currentColor" stroke-width="5"
        pathLength="100"
        stroke-dasharray="{{ round($dash, 3) }} {{ round(100 - $dash, 3) }}"
        stroke-dashoffset="{{ $offset > 0 ? -round($offset, 3) : 0 }}"
        transform="rotate(-90 21 21)" />
      @php $offset += $pct; @endphp
      @endforeach
    </svg>

    <ul class="admin-package-rate__legend">
      @foreach ($items as $package)
      <li>
        <span class="admin-package-rate__swatch admin-chart--{{ ($loop->index % 6) + 1 }}" aria-hidden="true"></span>
        <span class="admin-package-rate__name">{{ $package['label'] }}</span>
        <span class="admin-package-rate__pct">{{ number_format($package['count'] / $total * 100, 1) }}%</span>
      </li>
      @endforeach
    </ul>
  </div>
  @else
  <p class="admin-empty">No bookings yet, so there is nothing to chart.</p>
  @endif
</section>