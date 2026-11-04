@props([
'name',
'price',
'packageId' => null,
'features' => [],
'featured' => false,
'previewCount' => 2, // features always visible on phones; the rest sit behind "See more"
])

@php
$featureId = 'pkg-features-' . \Illuminate\Support\Str::slug($name);
$packageId = $packageId ?? \Illuminate\Support\Str::slug($name);
$visibleFeatures = array_slice($features, 0, $previewCount);
$extraFeatures = array_slice($features, $previewCount);

// Whole class strings (never built from pieces) so Tailwind can read them. The featured card swaps the border
// colour and adds a soft accent ring to the shadow.
$cardClass = $featured
? 'relative flex flex-col rounded-panel border-[1px] border-auth-accent bg-glass px-[1.25rem] pt-[1.1rem] pb-[1.25rem] text-on-brand shadow-[inset_0_1px_0_var(--color-glass-highlight),0_0_0_3px_var(--color-auth-focus-soft),0_8px_32px_var(--color-glass-shadow)] backdrop-blur-[10px] backdrop-saturate-140 transition-[background-color,translate] duration-250 ease-[ease] hover:-translate-y-0.5 hover:bg-glass-hover min-[576px]:px-6 min-[576px]:pt-6 min-[576px]:pb-7 min-[992px]:rounded-card'
: 'relative flex flex-col rounded-panel border-[1px] border-glass-border bg-glass px-[1.25rem] pt-[1.1rem] pb-[1.25rem] text-on-brand shadow-[inset_0_1px_0_var(--color-glass-highlight),0_8px_32px_var(--color-glass-shadow)] backdrop-blur-[10px] backdrop-saturate-140 transition-[background-color,translate] duration-250 ease-[ease] hover:-translate-y-0.5 hover:bg-glass-hover min-[576px]:px-6 min-[576px]:pt-6 min-[576px]:pb-7 min-[992px]:rounded-card';

// "Book Now". Its hover gradient lives on ::before so its opacity and angle can animate
// (--gradient-angle is registered with @property in _gradient-button.scss).
$ctaClass = 'relative isolate block min-h-11 w-full overflow-hidden rounded-field border-0 bg-[linear-gradient(180deg,var(--color-button-white)_0%,oklch(85%_0_0)_100%)] px-[1rem] py-[0.7rem] mb-[1.1rem] text-center text-[0.95rem] font-semibold text-button-black no-underline [--gradient-angle:90deg] [transition:color_0.3s_ease-in-out,opacity_0.15s_ease-in-out] before:absolute before:inset-0 before:-z-1 before:bg-[linear-gradient(var(--gradient-angle),oklch(0.545_0.236_307.3),oklch(0.595_0.234_22.6))] before:opacity-0 before:[transition:opacity_0.3s_ease-in-out,--gradient-angle_0.3s_ease-in-out] hover:text-pale-white hover:before:opacity-100 hover:before:[--gradient-angle:270deg] focus-visible:text-pale-white focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-auth-accent focus-visible:before:opacity-100 focus-visible:before:[--gradient-angle:270deg] active:text-pale-white active:opacity-80 active:before:opacity-100 active:before:[--gradient-angle:270deg] motion-reduce:[transition:none] motion-reduce:before:[transition:none] min-[768px]:mb-6';

$listClass = 'm-[0] flex list-none flex-col gap-[0.65rem] p-[0] text-[0.85rem] leading-[1.45] font-light text-glass-body min-[768px]:gap-3 min-[768px]:text-[0.875rem]';
$itemClass = 'relative pl-[1rem] before:absolute before:top-[0.6em] before:left-[0.1rem] before:size-[3px] before:rounded-full before:bg-glass-body';
@endphp

{{--Individual Package Cards Contents--}}
<div
  class="{{ $cardClass }}"
  data-package-card
  data-package-name="{{ $name }}"
  data-package-cost="{{ $price }}">

  <h3 class="mt-0 mb-[0.6rem] text-[0.95rem] leading-[1.3] font-semibold text-on-brand min-[768px]:mb-7 min-[768px]:text-[1rem]">{{ $name }}</h3>
  <p class="mt-0 mb-[0.9rem] text-[length:clamp(1.6rem,1.3rem_+_1vw,2.1rem)] leading-[1.1] font-bold text-on-brand min-[768px]:mb-5">Php {{ number_format((float) $price) }}</p>

  @auth
  <a class="{{ $ctaClass }}" data-package-select href="{{ route('user.booking', ['package' => $packageId]) }}">
    Book Now
  </a>
  @else
  <button type="button" class="{{ $ctaClass }}" data-package-select data-bs-toggle="modal" data-bs-target="#authModal" data-auth-view="login"><span>Book Now</span></button>
  @endauth

  <div class="mb-[0.9rem] h-px bg-glass-border min-[768px]:mb-5"></div>

  <div class="flex-1">
    <h4 class="mt-0 mb-[0.75rem] text-[0.9rem] leading-[1.2] font-semibold text-on-brand">
      @if ($extraFeatures)
      {{-- Below 768px this toggles the extra features (one card open at a time via data-bs-parent);
           from 768px up it is inert and everything is visible (min-[768px]:pointer-events-none). `collapsed` is toggled by
           Bootstrap's collapse plugin and read by the label and chevron through `group`. --}}
      <button
        class="group collapsed flex w-full cursor-pointer items-center justify-between gap-[0.75rem] border-0 bg-transparent px-0 py-[0.25rem] text-left text-inherit [font:inherit] focus-visible:rounded-[4px] focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-auth-accent min-[768px]:pointer-events-none min-[768px]:cursor-default min-[768px]:py-0"
        type="button"
        data-bs-toggle="collapse"
        data-bs-target="#{{ $featureId }}"
        aria-expanded="false"
        aria-controls="{{ $featureId }}">
        <span>Features</span>
        <span class="inline-flex items-center gap-[0.4rem] text-[0.8rem] font-normal text-glass-body min-[768px]:hidden">
          <span class="group-[:not(.collapsed)]:hidden">See more</span>
          <span class="hidden group-[:not(.collapsed)]:inline">See less</span>
          <span class="size-2 -translate-y-[2px] rotate-45 border-r-2 border-b-2 border-current transition-transform duration-250 ease-[ease] group-[:not(.collapsed)]:translate-y-[1px] group-[:not(.collapsed)]:-rotate-135" aria-hidden="true"></span>
        </span>
      </button>
      @else
      Features
      @endif
    </h4>

    <ul class="{{ $listClass }}">
      @foreach ($visibleFeatures as $feature)
      <li class="{{ $itemClass }}">{{ $feature }}</li>
      @endforeach
    </ul>

    @if ($extraFeatures)
    <div id="{{ $featureId }}" class="collapse min-[768px]:visible min-[768px]:block min-[768px]:h-auto" data-bs-parent="#package-grid">
      <ul class="{{ $listClass }} mt-[0.65rem] min-[768px]:mt-3">
        @foreach ($extraFeatures as $feature)
        <li class="{{ $itemClass }}">{{ $feature }}</li>
        @endforeach
      </ul>
    </div>
    @endif
  </div>
</div>