{{-- Dashboard KPI card. `featured` = the filled purple variant (Total Bookings).
     delta is optional (Cancelled Events has none); direction is 'up' or 'down'.
     The arrow + % is decorative-visual, the visually-hidden sentence is what a screen reader hears. --}}
@props([
'label',
'value',
'delta' => null,
'direction' => 'up',
'caption' => 'This month vs last',
'featured' => false,
])

<article {{ $attributes->class(['admin-card', 'admin-stat', 'admin-stat--featured' => $featured]) }}>
  <h2 class="admin-stat__label">{{ $label }}</h2>

  <div class="admin-stat__body">
    <p class="admin-stat__value">{{ $value }}</p>

    @if (! is_null($delta))
    <span class="admin-stat__delta admin-stat__delta--{{ $direction }}">
      <i class="ph ph-arrow-{{ $direction }}" aria-hidden="true"></i>
      <span aria-hidden="true">{{ $delta }}%</span>
      <span class="visually-hidden">{{ $direction === 'down' ? 'Down' : 'Up' }} {{ $delta }} percent compared with last month</span>
    </span>
    @endif
  </div>

  <p class="admin-stat__caption">{{ $caption }}</p>
</article>