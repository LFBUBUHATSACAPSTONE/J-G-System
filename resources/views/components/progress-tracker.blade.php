{{--
  Booking progress tracker — sits above #bookingFlow in booking.blade.php.
  Purely presentational on the server side: every step renders "locked"
  except its data-number, and resources/js/booking/progress-tracker.js
  does all the state switching by watching #bookingFlow's
  data-current-view attribute (the same one booking-flow.js already
  writes to on every step change) and toggling classes here — same
  read-only-root pattern progress-tracker.js has to booking-flow.js.

  Every key below must also exist in STEP_ORDER in booking-flow.js.

  Phase 9A: styles are utilities (class strings in the PHP block below). The JS toggles `is-locked`, `is-open` and
  `is-numbered` on each step and the utilities read them through `group-[...]/step:`. Hook classes that
  progress-tracker.js selects: `progress-tracker`, `progress-tracker__step`, `progress-tracker__circle`.
  The two sizes that the track line depends on are variables on the inner box (`--tracker-pad` = top padding,
  `--tracker-circle` = circle size): change them there and the line follows, as the Sass variables did.
  The fill's width is set by the JS (inline style), so it carries no width utility.
--}}
@php
$trackerSteps = [
['key' => 'package', 'label' => 'Package', 'number' => 1],
['key' => 'client-information', 'label' => 'Personal Information', 'number' => 2],
['key' => 'event-information', 'label' => 'Event Information', 'number' => 3],
['key' => 'event-schedule', 'label' => 'Event Schedule', 'number' => 4],
['key' => 'booking-summary', 'label' => 'Payment', 'number' => 5],
['key' => 'booking-confirmation', 'label' => 'Submitted', 'number' => 6],
];

// Pinned to the top while the step scrolls; frosted glass (80% page colour + blur), opaque where blur is unsupported.
$navClass = 'progress-tracker sticky top-0 z-10 w-full border-b border-auth-border bg-auth-bg/80 backdrop-blur-md not-supports-[backdrop-filter:blur(1px)]:bg-auth-bg';
// The 960px box that holds the steps; the track line is positioned against it. Same side padding as the flow below.
$innerClass = 'relative mx-auto max-w-240 px-[1rem] pt-(--tracker-pad) pb-[0.75rem] [--tracker-circle:1.75rem] [--tracker-pad:0.75rem] min-[576px]:px-6 min-[576px]:[--tracker-circle:2.25rem] min-[576px]:[--tracker-pad:1rem] min-[992px]:px-8';
// Half a step column (6 steps) in from each side: starts and ends on the circle centres.
$trackClass = 'absolute top-[calc(var(--tracker-pad)_+_var(--tracker-circle)_/_2_-_1px)] right-[calc(100%_/_12)] left-[calc(100%_/_12)] h-0.5 overflow-hidden rounded-full bg-auth-border';
$fillClass = 'absolute inset-y-0 left-0 bg-auth-accent transition-[width] duration-450 ease-[ease]';
$listClass = 'relative m-[0] flex list-none justify-between p-[0]';
// min-w-0 lets the column shrink below its label's width so labels wrap inside their own column.
$stepClass = 'progress-tracker__step group/step flex min-w-0 max-w-full flex-1 flex-col items-center gap-1 min-[576px]:gap-2';
// Locked: grey, not clickable. Open (behind the active step) and numbered (current or completed): filled.
$circleClass = 'progress-tracker__circle flex size-(--tracker-circle) shrink-0 cursor-not-allowed items-center justify-center rounded-full border-2 border-auth-border bg-auth-surface p-[0] text-xs font-bold text-auth-text-faint transition-[background-color,border-color,color,translate] duration-300 ease-[ease] min-[576px]:text-[0.85rem] enabled:cursor-pointer enabled:hover:-translate-y-px group-[:is(.is-open,.is-numbered)]/step:border-auth-accent group-[:is(.is-open,.is-numbered)]/step:bg-auth-accent group-[:is(.is-open,.is-numbered)]/step:text-white';
$lockClass = 'hidden group-[.is-locked]/step:block';
$numberClass = 'hidden group-[.is-numbered]/step:block';
// Phones: the label is not shown (it breaks letter by letter in a 1/6 column); it stays for screen readers.
$labelClass = 'w-full text-center text-[0.8rem] leading-[1.3] wrap-break-word text-auth-text-muted transition-[color,font-weight] duration-300 ease-[ease] max-[575.98px]:sr-only group-[.is-numbered]/step:font-bold group-[.is-numbered]/step:text-auth-text';
@endphp

<nav class="{{ $navClass }}" aria-label="Booking progress">
  <div class="{{ $innerClass }}">
    <div class="{{ $trackClass }}">
      <div class="{{ $fillClass }}" style="width: 0%" data-progress-fill></div>
    </div>

    <ol class="{{ $listClass }}">
      @foreach ($trackerSteps as $step)
      <li class="{{ $stepClass }} is-locked" data-step="{{ $step['key'] }}">
        <button type="button" class="{{ $circleClass }}" @disabled(true)>
          <svg class="{{ $lockClass }}" viewBox="0 0 24 24" width="14" height="14" fill="none" aria-hidden="true">
            <rect x="5" y="10" width="14" height="10" rx="2" stroke="currentColor" stroke-width="2" />
            <path d="M8 10V7a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
          </svg>
          <span class="{{ $numberClass }}" aria-hidden="true">{{ $step['number'] }}</span>
        </button>
        <span class="{{ $labelClass }}">{{ $step['label'] }}</span>
      </li>
      @endforeach
    </ol>
  </div>
</nav>