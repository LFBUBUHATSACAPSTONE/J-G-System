@props([
'title' => 'Features',
'features' => [
[
'icon' => 'audio',
'name' => 'Premium Audio Equipment',
'description' => 'We provide professional-grade mixers, speakers, and wireless microphones for crystal-clear sound. Our audio systems deliver powerful performance suitable for any event size.',
],
[
'icon' => 'lighting',
'name' => 'Dynamic Lighting Effects',
'description' => 'Our intelligent lighting creates vibrant colors, dramatic effects, and an immersive atmosphere. From parled lights to smoke machines, we enhance every moment on stage.',
],
[
'icon' => 'video',
'name' => 'High-Quality Video Systems',
'description' => 'We offer LED walls, processors, and video switchers for smooth and vivid visuals. Your presentations, concerts, and programs will look sharp and professional.',
],
[
'icon' => 'setup',
'name' => 'Complete Event Setup',
'description' => 'Our team handles installation, setup, and on-site technical assistance. We make sure your event runs smoothly from start to finish.',
],
],
])

@php
$featuresSectionIcons = [
'audio' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
  <path d="M9 18V5l12-2v13" />
  <circle cx="6" cy="18" r="3" />
  <circle cx="18" cy="16" r="3" />
</svg>',
'lighting' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
  <path d="M9 18h6" />
  <path d="M10 21h4" />
  <path d="M12 3a6 6 0 0 0-4 10.5c.6.6 1 1.4 1 2.5h6c0-1.1.4-1.9 1-2.5A6 6 0 0 0 12 3Z" />
</svg>',
'video' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
  <rect x="2.5" y="6" width="13" height="12" rx="2" />
  <path d="m21.5 8-6 4 6 4Z" />
</svg>',
'setup' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round">
  <circle cx="12" cy="12" r="3" />
  <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09a1.65 1.65 0 0 0-1-1.51 1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09a1.65 1.65 0 0 0 1.51-1 1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c.2.43.57.76 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1Z" />
</svg>',
];
@endphp

<div class="relative w-full bg-landing-base [clip-path:inset(calc(-1*var(--features-bleed))_0_0_0)]">
  <img src="{{ asset('images/backgrounds/design/features_red_gradient.webp') }}" alt="" aria-hidden="true" class="pointer-events-none absolute top-[calc(-1*var(--features-bleed))] left-0 h-[calc(100%_+_var(--features-bleed))] w-full object-cover">
  <img src="{{ asset('images/backgrounds/design/star_full.webp') }}" alt="" aria-hidden="true" class="pointer-events-none absolute top-[80%] left-1/2 w-full -translate-x-1/2 -translate-y-1/2">
  <section id="features" class="relative z-1 flex min-h-[calc(100svh_-_var(--landing-header-h,6.5rem)_+_1.5rem)] flex-col justify-center px-(--section-pad-x) pt-(--section-pad-top) pb-(--section-pad-bottom)">
    <h2 class="mt-0 mb-10 text-center font-heading text-fluid-section-title leading-[1.2] font-extrabold tracking-[0.15em] text-features-title uppercase [text-shadow:0_2px_12px_oklch(0%_0_0/0.45)]">
      {{ $title }}
    </h2>

    <div class="mx-auto grid w-full max-w-[1280px] grid-cols-1 gap-[0.75rem] min-[768px]:grid-cols-2 min-[768px]:gap-6 min-[1200px]:grid-cols-4" id="features-grid">
      @foreach ($features as $feature)
      @php $icon = $featuresSectionIcons[$feature['icon']] ?? $feature['icon']; @endphp
      <div class="[container-type:inline-size] flex flex-col rounded-card border-[1px] border-glass-border bg-glass p-[0] text-on-brand shadow-[inset_0_1px_0_var(--color-glass-highlight),0_8px_32px_var(--color-glass-shadow)] backdrop-blur-[10px] backdrop-saturate-140 transition-[background-color,translate] duration-250 ease-[ease] hover:-translate-y-0.5 hover:bg-glass-hover min-[768px]:p-6 min-[1200px]:p-5 [--feature-name:clamp(1.05rem,0.98rem_+_0.3vw,1.2rem)] min-[1200px]:[--feature-name:min(1.05rem,6cqw)]">
        <h3 class="m-[0] text-(length:--feature-name) leading-[1.25] font-bold text-on-brand min-[768px]:mb-3 min-[1200px]:whitespace-nowrap">
          {{-- Below 768px this is an accordion toggle; from 768px up it is inert (min-[768px]:pointer-events-none). `collapsed` is toggled by Bootstrap's collapse plugin and read by the chevron through `group`. --}}
          <button
            class="group collapsed flex w-full cursor-pointer items-center gap-[0.75rem] rounded-[inherit] border-0 bg-transparent px-[1.25rem] py-[1.1rem] text-left text-inherit [font:inherit] focus-visible:-outline-offset-2 focus-visible:outline-2 focus-visible:outline-on-brand min-[768px]:pointer-events-none min-[768px]:cursor-default min-[768px]:p-0"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#feature-{{ $loop->index }}"
            aria-expanded="false"
            aria-controls="feature-{{ $loop->index }}">
            <span class="flex shrink-0 text-on-brand min-[768px]:hidden [&_svg]:size-[22px]" aria-hidden="true">{!! $icon !!}</span>
            <span class="min-w-0 flex-1">{{ $feature['name'] }}</span>
            <span class="size-[0.6rem] shrink-0 rotate-45 border-r-2 border-b-2 border-current transition-transform duration-250 ease-[ease] group-[:not(.collapsed)]:-rotate-135 min-[768px]:hidden" aria-hidden="true"></span>
          </button>
        </h3>
        <div id="feature-{{ $loop->index }}" class="collapse min-[768px]:visible min-[768px]:block min-[768px]:h-auto min-[768px]:flex-1" data-bs-parent="#features-grid">
          <p class="m-[0] px-[1.25rem] pb-[1.25rem] text-[length:calc(var(--feature-name)*0.8)] leading-[1.55] font-light text-glass-body min-[768px]:p-0">{{ $feature['description'] }}</p>
        </div>
        <span class="hidden text-on-brand min-[768px]:mt-auto min-[768px]:flex min-[768px]:self-center min-[768px]:pt-8 [&_svg]:size-[26px]" aria-hidden="true">{!! $icon !!}</span>
      </div>
      @endforeach
    </div>
  </section>
</div>