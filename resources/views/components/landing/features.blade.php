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

<div class="features-bg">
  <img src="{{ asset('images/backgrounds/design/features_red_gradient.webp') }}" alt="" aria-hidden="true" class="features-bg__image">
  <img src="{{ asset('images/backgrounds/design/star_full.webp') }}" alt="" aria-hidden="true" class="features-bg__star">
  <section id="features" class="features-section">
    <h2 class="features-section__title">
      {{ $title }}
    </h2>

    <div class="features-section__grid">
      @foreach ($features as $feature)
      <div class="feature-card">
        <div class="feature-card__body">
          <h3 class="feature-card__name">{{ $feature['name'] }}</h3>
          <p class="feature-card__description">{{ $feature['description'] }}</p>
        </div>
        <span class="feature-card__icon" aria-hidden="true">
          {!! $featuresSectionIcons[$feature['icon']] ?? $feature['icon'] !!}
        </span>
      </div>
      @endforeach
    </div>
  </section>
</div>