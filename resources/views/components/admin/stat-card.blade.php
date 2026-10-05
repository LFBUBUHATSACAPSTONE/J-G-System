{{-- Dashboard KPI card. `featured` = the filled purple variant.
     `href` makes the whole card a link (the title is the real <a>, stretched over the card).
     `change` is the difference vs last month as a whole number (+2, 0, -1); null hides the badge.
     `higher` says whether a rise is 'good', 'bad' or 'neutral', which only picks the badge colour.
     The arrow + number is visual; the visually-hidden sentence is what a screen reader hears. --}}
@props([
'label',
'value',
'change' => null,
'higher' => 'neutral',
'href' => null,
'hint' => null,
'caption' => 'vs last month',
'featured' => false,
])

@php
$hasChange = ! is_null($change);
$dir = ! $hasChange ? null : ($change > 0 ? 'up' : ($change < 0 ? 'down' : 'flat' ));

  // good / bad only when the card has an opinion and the number actually moved.
  $tone='neutral' ;
  if ($hasChange && $dir !=='flat' && $higher !=='neutral' ) {
  $tone=(($dir==='up' )===($higher==='good' )) ? 'good' : 'bad' ;
  }

  $icon=['up'=> 'arrow-up', 'down' => 'arrow-down', 'flat' => 'minus'][$dir] ?? null;
  $text = $dir === 'flat' ? 'No change' : ($change > 0 ? '+' : '−') . abs((int) $change);
  $spoken = $dir === 'flat'
  ? 'No change compared with last month'
  : ($dir === 'up' ? 'Up ' : 'Down ') . abs((int) $change) . ' compared with last month';
  @endphp

  <article {{ $attributes->class(['admin-card', 'admin-stat', 'admin-stat--featured' => $featured, 'admin-stat--link' => $href]) }}>
    <h2 class="admin-stat__label">
      @if ($href)
      <a href="{{ $href }}" class="admin-stat__link">
        {{ $label }}<span class="visually-hidden">: {{ $value }}. View in Bookings</span>
        <i class="ph ph-arrow-up-right admin-stat__go" aria-hidden="true"></i>
      </a>
      @else
      {{ $label }}
      @endif
    </h2>

    <div class="admin-stat__body">
      <p class="admin-stat__value">{{ $value }}</p>

      @if ($hasChange)
      <span class="admin-stat__delta admin-stat__delta--{{ $tone }}">
        <i class="ph ph-{{ $icon }}" aria-hidden="true"></i>
        <span aria-hidden="true">{{ $text }}</span>
        <span class="visually-hidden">{{ $spoken }}</span>
      </span>
      @endif
    </div>

    <p class="admin-stat__caption">{{ $hasChange ? $caption : $hint }}</p>
  </article>