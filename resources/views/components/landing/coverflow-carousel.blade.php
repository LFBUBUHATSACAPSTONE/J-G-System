@props([
'items' => [
['image' => asset('images/carousel/poster1.webp'), 'alt' => ''],
['image' => asset('images/carousel/poster2.webp'), 'alt' => ''],
['image' => asset('images/carousel/poster3.webp'), 'alt' => ''],
['image' => asset('images/carousel/poster4.webp'), 'alt' => ''],
['image' => asset('images/carousel/poster5.webp'), 'alt' => '']
],
'interval' => 2000,
// true inside the landing hero: the carousel then takes the height the hero has left over (the hero sets
// --hero-carousel-min) instead of its own fluid height.
'fill' => false,
])

@php
// Whole class strings (never built from pieces) so Tailwind can read them. Spacing and sizes are arbitrary
// values on purpose: Bootstrap's `h-auto`, `my-auto`, `z-*` and friends are `!important` utilities too.
//
// The slides use the posters' own aspect ratio (2048x1717) so object-cover has nothing to crop. Their width
// follows the carousel's height (cqh), capped by the --coverflow-*-cap values; the height comes from the ratio.
$rootSize = $fill
? 'h-auto min-h-(--hero-carousel-min) max-h-[min(65vw,580px)] flex-[1_1_0] [margin-block:auto] max-[768.01px]:max-h-[300px]'
: 'h-[clamp(260px,45vw,580px)] short-landscape:h-[clamp(180px,40vh,260px)]';
$rootClass = 'group/cf relative z-1 w-full overflow-hidden select-none [container-type:size] [--coverflow-ratio:2048_/_1717] [--coverflow-center-cap:760px] [--coverflow-side-cap:420px] max-[992.01px]:[--coverflow-center-cap:460px] max-[992.01px]:[--coverflow-side-cap:230px] max-[768.01px]:[--coverflow-center-cap:270px] max-[768.01px]:[--coverflow-side-cap:130px] ' . $rootSize;
$trackClass = 'relative h-full w-full [--coverflow-center-width:min(var(--coverflow-center-cap),109cqh)] [--coverflow-side-width:min(var(--coverflow-side-cap),55cqh)]';
// Position is the `data-pos` attribute that coverflow-carousel.js sets on each slide: center, left-1, left-2,
// right-1, right-2 or hidden. Without it a slide rests at the hidden look (small, blurred, transparent). The
// transition is switched on by the root's `data-ready`, so the first paint snaps into place.
$slideClass = 'absolute top-1/2 left-1/2 z-1 aspect-(--coverflow-ratio) w-(--coverflow-center-width) [translate:-50%_-50%] [scale:0.8] [filter:blur(2px)_brightness(0.6)] opacity-0 group-data-ready/cf:[transition:translate_0.6s_ease,scale_0.6s_ease,opacity_0.6s_ease,filter_0.6s_ease] data-[pos=center]:z-5 data-[pos=center]:[scale:1] data-[pos=center]:[filter:none] data-[pos=center]:opacity-100 data-[pos=left-1]:z-4 data-[pos=left-1]:[translate:calc(-50%_-_var(--coverflow-side-width))_-50%] data-[pos=left-1]:[scale:0.85] data-[pos=left-1]:[filter:blur(1px)_brightness(0.75)] data-[pos=left-1]:opacity-85 data-[pos=right-1]:z-4 data-[pos=right-1]:[translate:calc(-50%_+_var(--coverflow-side-width))_-50%] data-[pos=right-1]:[scale:0.85] data-[pos=right-1]:[filter:blur(1px)_brightness(0.75)] data-[pos=right-1]:opacity-85 data-[pos=left-2]:z-3 data-[pos=left-2]:[translate:calc(-50%_-_(var(--coverflow-side-width)*1.8))_-50%] data-[pos=left-2]:[scale:0.7] data-[pos=left-2]:[filter:blur(2px)_brightness(0.55)] data-[pos=left-2]:opacity-50 data-[pos=right-2]:z-3 data-[pos=right-2]:[translate:calc(-50%_+_(var(--coverflow-side-width)*1.8))_-50%] data-[pos=right-2]:[scale:0.7] data-[pos=right-2]:[filter:blur(2px)_brightness(0.55)] data-[pos=right-2]:opacity-50 data-[pos=hidden]:pointer-events-none';
$clipClass = 'relative h-full w-full overflow-hidden';
// Pinned to all four edges of the clip so the photo always fills the slide exactly, whatever the photo's own
// aspect ratio says.
$imgClass = 'pointer-events-none absolute inset-0 block size-full max-w-none object-cover [-webkit-user-drag:none]';
@endphp

<div
  class="{{ $rootClass }}"
  data-coverflow
  data-interval="{{ $interval }}">
  <div class="{{ $trackClass }}">
    @foreach ($items as $index => $item)
    <div class="{{ $slideClass }}" data-index="{{ $index }}">
      <div class="{{ $clipClass }}">
        <img
          src="{{ $item['image'] }}"
          alt="{{ $item['alt'] ?? '' }}"
          class="{{ $imgClass }}"
          draggable="false">
      </div>
    </div>
    @endforeach
  </div>
</div>