{{-- Dashboard KPI card. `featured` = the filled purple variant.
     `href` makes the whole card a link (the title is the real <a>, stretched over the card).
     `change` is the difference vs last month as a whole number (+2, 0, -1); null hides the badge.
     `higher` says whether a rise is 'good', 'bad' or 'neutral', which only picks the badge colour.
     The arrow + number is visual; the screen-reader-only sentence is what a screen reader hears. --}}
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
  // Whole class strings, picked from maps (never built from pieces). Plain and featured differ only in colour.
  $palette = $featured
  ? ['card' => 'bg-brand text-on-brand', 'label' => 'text-on-brand', 'value' => 'text-on-brand', 'caption' => 'text-on-brand-muted', 'ring' => 'focus-visible:after:outline-on-brand']
  : ['card' => 'bg-surface', 'label' => 'text-brand-text', 'value' => 'text-brand-text', 'caption' => 'text-ink-muted', 'ring' => 'focus-visible:after:outline-brand-text'];
  $deltaClass = ['good' => 'bg-success text-ink', 'bad' => 'bg-danger-soft text-danger-text', 'neutral' => 'bg-brand-soft text-brand-text'][$tone];
  $hoverClass = $href ? 'hover:shadow-[0_0.25rem_1rem_oklch(0%_0_0/0.12)]' : '';
  @endphp

  <article {{ $attributes->class(['group/stat relative flex min-h-38 flex-col justify-between gap-3 rounded-card p-6 transition-shadow duration-150 ease-[ease] max-[575.98px]:min-h-0 max-[575.98px]:gap-2 max-[575.98px]:p-4 max-[399.98px]:grid max-[399.98px]:grid-cols-[minmax(0,1fr)_auto] max-[399.98px]:items-center max-[399.98px]:gap-x-3', $palette['card'], $hoverClass]) }}>
    <h2 class="m-0 text-fluid-label font-semibold max-[575.98px]:text-fluid-sm max-[399.98px]:col-start-1 max-[399.98px]:row-start-1 {{ $palette['label'] }}">
      @if ($href)
      <a href="{{ $href }}" class="flex items-center justify-between gap-2 text-inherit no-underline after:absolute after:inset-0 after:rounded-[inherit] after:content-[''] focus-visible:outline-none focus-visible:after:outline-2 focus-visible:after:outline-offset-2 forced-colors:focus-visible:after:outline-[Highlight] {{ $palette['ring'] }}">
        {{ $label }}<span class="sr-only">: {{ $value }}. View in Bookings</span>
        <i class="ph ph-arrow-up-right flex-none text-fluid-heading transition-transform duration-150 ease-[ease] group-hover/stat:translate-x-0.5 group-hover/stat:-translate-y-0.5" aria-hidden="true"></i>
      </a>
      @else
      {{ $label }}
      @endif
    </h2>

    <div class="flex flex-wrap items-center gap-x-3 gap-y-2 max-[575.98px]:flex-nowrap max-[575.98px]:gap-2 max-[399.98px]:col-start-2 max-[399.98px]:row-span-2 max-[399.98px]:row-start-1">
      <p class="m-0 text-fluid-stat font-bold leading-none max-[575.98px]:text-fluid-3xl {{ $palette['value'] }}">{{ $value }}</p>

      @if ($hasChange)
      <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-[0.8rem] font-semibold max-[575.98px]:whitespace-nowrap {{ $deltaClass }}">
        <i class="ph ph-{{ $icon }}" aria-hidden="true"></i>
        <span aria-hidden="true">{{ $text }}</span>
        <span class="sr-only">{{ $spoken }}</span>
      </span>
      @endif
    </div>

    <p class="m-0 text-fluid-sm max-[575.98px]:text-[0.8rem] max-[575.98px]:whitespace-nowrap max-[399.98px]:col-start-1 max-[399.98px]:row-start-2 {{ $palette['caption'] }}">{{ $hasChange ? $caption : $hint }}</p>
  </article>