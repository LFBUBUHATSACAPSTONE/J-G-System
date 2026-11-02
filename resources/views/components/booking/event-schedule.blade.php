{{--
  Event Schedule step (booking flow). Phase 9D: styles are utilities, class strings in the PHP block below.
  The day buttons and the sliding AM / PM pill are built by js/booking/event-schedule.js, which holds their class
  strings (DAY_CLASS, THUMB_CLASS): a change to the look of a day is made there, not here.
  State is read, not renamed: `d-none` (error lines, the reschedule empty card) and `is-invalid` (the time boxes) are
  toggled by the JS as before; `is-invisible` (Previous arrow at the first month), `is-weekend`, `is-selected`,
  `is-full` and `has-value` are read by variants; `aria-pressed` drives the AM / PM text colour.
  Hooks kept for the JS: ids, `name`s, `data-*`, and `event-schedule__field` / `event-schedule__time-field`, which
  `event-schedule.js`, `booking-flow.js` and `booking-summary.js` select with closest(). They carry no style.
  One PHP block only: mixing it with the one-line PHP directives corrupts the compiled view.
--}}
@php
$reschedule = $reschedule ?? null;
$rescheduleMonthLabel = $reschedule ? \Carbon\Carbon::createFromFormat('!Y-m', $reschedule['month'])->format('F Y') : null;
$minGap = \App\Support\Scheduling::minGapLabel();
$stepClass = 'relative z-1 flex flex-col items-start gap-6';
$cardClass = 'booking-card flex-1 px-[1rem] py-6 min-[576px]:p-6';
$titleClass = 'text-fluid-booking-title font-bold [margin:0_0_1.5rem] min-[992px]:[margin:0_0_2rem]';
$noticeClass = 'text-[0.85rem] text-auth-text-muted [margin:0_0_1rem] [&_strong]:text-auth-text';
// Calendar above the times on phones, side by side from 640px.
$bodyClass = 'flex flex-col gap-6 min-[640px]:flex-row min-[640px]:gap-8';
$calendarClass = 'relative w-full rounded-panel bg-auth-field-bg p-[1rem] min-[576px]:p-6 min-[640px]:max-w-[320px]';
// Fixed-width side columns keep both arrows in place whatever the month label says, and while Previous is hidden.
$calHeaderClass = 'mb-[1rem] grid grid-cols-[1.5rem_1fr_1.5rem] items-center gap-[0.5rem]';
$monthClass = 'text-center text-fluid-month font-bold whitespace-nowrap';
// is-invisible (set by the JS at the earliest month) hides the arrow but keeps its grid column.
$navClass = 'inline-flex cursor-pointer items-center justify-center bg-transparent p-[0.25rem] text-auth-accent [border:0] focus-visible:rounded-md focus-visible:shadow-[0_0_0_3px_var(--color-auth-focus-soft)] focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-35 [&.is-invisible]:pointer-events-none [&.is-invisible]:[visibility:hidden]';
$weekdaysClass = 'mb-2 grid grid-cols-7';
$weekdayClass = 'pb-[0.25rem] text-center text-[0.8rem] font-semibold text-auth-text';
$weekendClass = 'pb-[0.25rem] text-center text-[0.8rem] font-semibold text-auth-danger';
$daysClass = 'grid grid-cols-7 gap-y-[0.25rem]';
$fieldErrorClass = 'mt-1 text-[0.78rem] text-auth-danger [&:not(.d-none)]:block';
$legendClass = 'flex items-center gap-2 text-[0.8rem] text-auth-text-muted [margin:1rem_0_0]';
$swatchClass = 'size-[0.9rem] rounded-[0.25rem] shadow-[inset_0_0_0_1px_var(--color-auth-danger)]';
$timesClass = 'flex flex-1 flex-col gap-6';
$rangeClass = 'text-fluid-sm font-semibold text-auth-text-faint [margin:0] [&.has-value]:text-auth-text';
$labelClass = 'mb-2 block text-fluid-sm font-semibold text-auth-text';
$requiredClass = 'text-auth-danger';
// The wrapper shows the ring while its input has focus; invalid wins over it.
$timeFieldClass = 'event-schedule__time-field flex items-stretch overflow-hidden rounded-field border-[1px] border-transparent bg-auth-field-bg focus-within:border-auth-accent focus-within:shadow-[0_0_0_3px_var(--color-auth-focus-soft)] [&.is-invalid]:border-auth-danger [&.is-invalid]:focus-within:border-auth-danger';
$timeInputClass = 'autofill-dark min-w-0 flex-1 bg-transparent px-[1rem] py-[0.75rem] text-fluid-sm text-auth-text [border:0] placeholder:text-auth-text-faint focus:outline-none';
$ampmClass = 'relative flex gap-[0.25rem] [margin:0.25rem]';
$ampmBtnClass = 'relative z-1 cursor-pointer rounded-md bg-transparent text-[0.78rem] font-bold text-auth-text-faint transition-colors duration-200 ease-[ease] [border:0] [padding:0_0.75rem] aria-pressed:text-auth-text focus-visible:shadow-[0_0_0_3px_var(--color-auth-focus-soft)] focus-visible:outline-none';
$hintClass = 'text-[0.8rem] leading-[1.5] text-auth-text-muted [margin:0]';
// Both <strong> in the reminder are blocks, as the Sass had them.
  $reminderClass = 'text-[0.8rem] leading-[1.5] text-auth-text-muted [margin:0] [&_strong]:mb-1 [&_strong]:block [&_strong]:text-auth-text';
  // The reschedule card carries d-none (JS) and no display utility.
  $emptyClass = 'px-[1rem] py-[2rem] text-center text-auth-text-muted [&_strong]:mb-2 [&_strong]:block [&_strong]:text-fluid-name [&_strong]:text-auth-text [&_p]:text-[0.85rem] [&_p]:leading-[1.5] [&_p]:[margin:0]';
  $errorClass = 'mt-[1rem] text-[0.85rem] text-auth-banner-error';
  @endphp
  <div class="{{ $stepClass }}">
    <div class="{{ $cardClass }}">
      <h2 class="{{ $titleClass }}">Event Schedule</h2>

      @if ($reschedule)
      <p class="{{ $noticeClass }}" data-reschedule-notice>
        Choose a new date in any available month. You can reschedule until {{ $rescheduleMonthLabel }} has passed.
      </p>
      @endif

      <form
        id="event-schedule-form"
        method="POST"
        action="{{ route('booking.event-schedule') }}"
        novalidate>
        @csrf

        <div class="{{ $bodyClass }}">
          <div class="{{ $calendarClass }}" data-calendar data-availability-url="{{ route('booking.availability') }}"
            @if ($reschedule) data-initial-month="{{ $reschedule['month'] }}" @endif>
            <div class="{{ $calHeaderClass }}">
              <button type="button" class="{{ $navClass }} is-invisible" data-calendar-prev aria-label="Previous month">
                <svg width="10" height="16" viewBox="0 0 10 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M8.5 1.5L1.5 8L8.5 14.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </button>
              <span class="{{ $monthClass }}" data-calendar-label aria-live="polite"></span>
              <button type="button" class="{{ $navClass }}" data-calendar-next aria-label="Next month">
                <svg width="10" height="16" viewBox="0 0 10 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                  <path d="M1.5 1.5L8.5 8L1.5 14.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
              </button>
            </div>

            <div class="{{ $weekdaysClass }}" aria-hidden="true">
              @foreach (['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'] as $weekday)
              <span class="{{ $loop->first || $loop->last ? $weekendClass : $weekdayClass }}">{{ $weekday }}</span>
              @endforeach
            </div>

            <div class="{{ $daysClass }}" role="grid" aria-labelledby="event-schedule-month-label" data-calendar-days></div>

            <input type="hidden" name="event_start_date" data-start-date-input>
            <input type="hidden" name="event_end_date" data-end-date-input>
            <small class="{{ $fieldErrorClass }} d-none" data-field-error="event_start_date"></small>

            <p class="{{ $legendClass }}">
              <span class="{{ $swatchClass }}" aria-hidden="true"></span>
              Fully booked
            </p>
          </div>

          <div class="{{ $timesClass }}">
            <p class="{{ $rangeClass }}" data-selected-range aria-live="polite">Select a date on the calendar.</p>

            <div class="event-schedule__field">
              <label for="es-start-time" class="{{ $labelClass }}">
                <span>Start In</span>
                <span class="{{ $requiredClass }}">*</span>
              </label>
              <div class="{{ $timeFieldClass }}">
                <input type="text" id="es-start-time" name="start_time" class="{{ $timeInputClass }}" placeholder="HH:MM" inputmode="decimal" autocomplete="off" maxlength="5" data-time-input>
                <div class="{{ $ampmClass }}" role="group" aria-label="Start time period" data-ampm-group>
                  <button type="button" class="{{ $ampmBtnClass }}" data-ampm="AM" aria-pressed="true">AM</button>
                  <button type="button" class="{{ $ampmBtnClass }}" data-ampm="PM" aria-pressed="false">PM</button>
                </div>
              </div>
              <small class="{{ $fieldErrorClass }} d-none" data-field-error="start_time"></small>
            </div>

            <div class="event-schedule__field">
              <label for="es-end-time" class="{{ $labelClass }}">
                <span>End In</span>
                <span class="{{ $requiredClass }}">*</span>
              </label>
              <div class="{{ $timeFieldClass }}">
                <input type="text" id="es-end-time" name="end_time" class="{{ $timeInputClass }}" placeholder="HH:MM" inputmode="decimal" autocomplete="off" maxlength="5" data-time-input>
                <div class="{{ $ampmClass }}" role="group" aria-label="End time period" data-ampm-group>
                  <button type="button" class="{{ $ampmBtnClass }}" data-ampm="AM" aria-pressed="true">AM</button>
                  <button type="button" class="{{ $ampmBtnClass }}" data-ampm="PM" aria-pressed="false">PM</button>
                </div>
              </div>
              <small class="{{ $fieldErrorClass }} d-none" data-field-error="end_time"></small>
            </div>

            <p class="{{ $hintClass }}">Type the time as 1:00, or as 1.00 on a phone keypad.</p>

            <p class="{{ $reminderClass }}">
              <strong>Reminder:</strong>
              Double-check your selected schedule. Changes may not be allowed once you proceed.
              @if ($minGap)
              Bookings on the same day need at least <strong>{{ $minGap }}</strong> between them, and a day closes once it is fully booked.
              @endif
            </p>
          </div>
        </div>

        @if ($reschedule)
        <div class="{{ $emptyClass }} d-none" role="status" data-reschedule-empty>
          <strong>No available dates in this month</strong>
          <p data-reschedule-empty-message>Try another month.</p>
        </div>
        @endif

        <p class="{{ $errorClass }} d-none" role="alert" data-event-schedule-error></p>
      </form>
    </div>

    <x-booking.step-actions>
      <x-booking.step-button variant="cancel" data-booking-previous>Previous</x-booking.step-button>
      <x-booking.step-button type="submit" form="event-schedule-form">Continue</x-booking.step-button>
    </x-booking.step-actions>
  </div>