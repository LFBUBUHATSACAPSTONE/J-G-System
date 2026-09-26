@php
$trackerSteps = [
['key' => 'client-information', 'label' => 'Client Information', 'number' => 1],
['key' => 'event-information', 'label' => 'Event Information', 'number' => 2],
['key' => 'event-schedule', 'label' => 'Event Schedule', 'number' => 3],
['key' => 'booking-summary', 'label' => 'Payment', 'number' => 4],
['key' => 'booking-confirmation', 'label' => 'Confirmation', 'number' => 5],
];
@endphp

<nav class="progress-tracker" aria-label="Booking progress">
  <div class="progress-tracker__track">
    <div class="progress-tracker__track-fill" data-progress-fill></div>
  </div>

  <ol class="progress-tracker__list">
    @foreach ($trackerSteps as $step)
    <li class="progress-tracker__step is-locked" data-step="{{ $step['key'] }}">
      <button type="button" class="progress-tracker__circle" @disabled(true)>
        <svg class="progress-tracker__icon-lock" viewBox="0 0 24 24" width="14" height="14" fill="none" aria-hidden="true">
          <rect x="5" y="10" width="14" height="10" rx="2" stroke="currentColor" stroke-width="2" />
          <path d="M8 10V7a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
        </svg>
        <span class="progress-tracker__number" aria-hidden="true">{{ $step['number'] }}</span>
      </button>
      <span class="progress-tracker__label">{{ $step['label'] }}</span>
    </li>
    @endforeach
  </ol>
</nav>