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

$bgClass = 'relative w-full bg-landing-base [clip-path:inset(-10rem_0_0_0)]';
$bgImageClass = 'pointer-events-none absolute -top-40 left-0 h-[calc(100%+10rem)] w-full object-cover';
$bgStarClass = 'pointer-events-none absolute top-[80%] left-1/2 w-full -translate-x-1/2 -translate-y-1/2';
$sectionClass = 'relative z-1 flex min-h-[calc(100svh-var(--landing-header-h,6.5rem)+1.5rem)] flex-col justify-center pt-(--section-pad-top) pr-(--section-pad-x) pb-(--section-pad-bottom) pl-(--section-pad-x)';
$titleClass = 'mt-0 mb-10 text-center font-landing-heading text-fluid-features-title font-extrabold tracking-[0.15em] text-features-title uppercase [text-shadow:0_2px_12px_var(--color-features-title-shadow)]';
$gridClass = 'mx-auto grid w-full max-w-320 grid-cols-1 gap-[0.75rem] md:grid-cols-2 md:gap-6 min-[75rem]:grid-cols-4';
$cardClass = '@container flex flex-col rounded-card [border:1px_solid_var(--color-glass-border)] bg-glass text-on-brand backdrop-blur-[10px] backdrop-saturate-140 [box-shadow:inset_0_1px_0_var(--color-glass-highlight),0_8px_32px_var(--color-glass-shadow)] transition-[background-color,translate] duration-250 ease-[ease] hover:-translate-y-0.5 hover:bg-glass-hover md:p-6 min-[75rem]:p-5';
$nameClass = '[margin:0] text-fluid-feature-name leading-tight font-bold text-on-brand md:mb-3 min-[75rem]:text-[min(1.05rem,6cqw)] min-[75rem]:whitespace-nowrap';
$toggleClass = 'group/toggle flex w-full cursor-pointer items-center gap-[0.75rem] rounded-[inherit] border-0 bg-transparent px-[1.25rem] py-[1.1rem] text-left text-inherit [font:inherit] focus-visible:-outline-offset-2 focus-visible:outline-2 focus-visible:outline-on-brand md:pointer-events-none md:cursor-default md:p-0';
$labelClass = 'min-w-0 flex-1';
$chevronClass = 'size-[0.6rem] flex-none rotate-45 border-r-2 border-b-2 border-current transition-transform duration-250 ease-[ease] group-aria-expanded/toggle:-rotate-135 md:hidden';
$bodyClass = 'collapse md:block md:h-auto md:flex-1 md:[visibility:visible]';
$descriptionClass = '[margin:0] px-[1.25rem] pb-[1.25rem] text-fluid-feature-desc leading-[1.55] font-light text-glass-body md:p-0 min-[75rem]:text-[calc(min(1.05rem,6cqw)*0.8)]';
$iconInlineClass = 'flex flex-none text-on-brand md:hidden [&_svg]:size-5.5';
$iconBottomClass = 'hidden text-on-brand md:mt-auto md:flex md:self-center md:pt-8 [&_svg]:size-6.5';
@endphp

<div class="{{ $bgClass }}">
  <img src="{{ asset('images/backgrounds/design/features_red_gradient.webp') }}" alt="" aria-hidden="true" class="{{ $bgImageClass }}">
  <img src="{{ asset('images/backgrounds/design/star_full.webp') }}" alt="" aria-hidden="true" class="{{ $bgStarClass }}">
  <section id="features" class="{{ $sectionClass }}">
    <h2 class="{{ $titleClass }}">
      {{ $title }}
    </h2>

    <div class="{{ $gridClass }}" id="features-grid">
      @foreach ($features as $feature)
      @php $icon = $featuresSectionIcons[$feature['icon']] ?? $feature['icon']; @endphp
      <div class="{{ $cardClass }}">
        <h3 class="{{ $nameClass }}">
          {{-- Below 768px this is an accordion toggle (Bootstrap collapse); from 768px up it is inert and the body is always open. --}}
          <button
            class="{{ $toggleClass }} collapsed"
            type="button"
            data-bs-toggle="collapse"
            data-bs-target="#feature-{{ $loop->index }}"
            aria-expanded="false"
            aria-controls="feature-{{ $loop->index }}">
            <span class="{{ $iconInlineClass }}" aria-hidden="true">{!! $icon !!}</span>
            <span class="{{ $labelClass }}">{{ $feature['name'] }}</span>
            <span class="{{ $chevronClass }}" aria-hidden="true"></span>
          </button>
        </h3>
        <div id="feature-{{ $loop->index }}" class="{{ $bodyClass }}" data-bs-parent="#features-grid">
          <p class="{{ $descriptionClass }}">{{ $feature['description'] }}</p>
        </div>
        <span class="{{ $iconBottomClass }}" aria-hidden="true">{!! $icon !!}</span>
      </div>
      @endforeach
    </div>
  </section>
</div>