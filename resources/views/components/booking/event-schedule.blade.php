<div class="event-schedule position-relative z-1">
  <div class="event-schedule__card">
    <h2 class="event-schedule__title">Event Schedule</h2>

    <form
      id="event-schedule-form"
      method="POST"
      action="{{ route('booking.event-schedule') }}"
      novalidate>
      @csrf

      <div class="event-schedule__body">
        <div class="event-schedule__calendar" data-calendar>
          <div class="event-schedule__calendar-header">
            <button type="button" class="event-schedule__nav-btn is-invisible" data-calendar-prev aria-label="Previous month">
              <svg width="10" height="16" viewBox="0 0 10 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M8.5 1.5L1.5 8L8.5 14.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </button>
            <span class="event-schedule__month-label" data-calendar-label aria-live="polite"></span>
            <button type="button" class="event-schedule__nav-btn" data-calendar-next aria-label="Next month">
              <svg width="10" height="16" viewBox="0 0 10 16" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
                <path d="M1.5 1.5L8.5 8L1.5 14.5" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
              </svg>
            </button>
          </div>

          <div class="event-schedule__weekdays" aria-hidden="true">
            <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
          </div>

          <div class="event-schedule__days" role="grid" aria-labelledby="event-schedule-month-label" data-calendar-days></div>

          <input type="hidden" name="event_start_date" data-start-date-input>
          <input type="hidden" name="event_end_date" data-end-date-input>
          <small class="event-schedule__field-error d-none" data-field-error="event_start_date"></small>
        </div>

        <div class="event-schedule__times">
          <p class="event-schedule__selected-range" data-selected-range aria-live="polite">Select a date on the calendar.</p>

          <div class="event-schedule__field">
            <label for="es-start-time">
              <span>Start In</span>
              <span class="field-required__identifier">*</span>
            </label>
            <div class="event-schedule__time-field">
              <input type="text" id="es-start-time" name="start_time" placeholder="HH:MM" inputmode="numeric" autocomplete="off" maxlength="5" data-time-input>
              <div class="event-schedule__ampm" role="group" aria-label="Start time period" data-ampm-group>
                <button type="button" data-ampm="AM" aria-pressed="true">AM</button>
                <button type="button" data-ampm="PM" aria-pressed="false">PM</button>
              </div>
            </div>
            <small class="event-schedule__field-error d-none" data-field-error="start_time"></small>
          </div>

          <div class="event-schedule__field">
            <label for="es-end-time">
              <span>End In</span>
              <span class="field-required__identifier">*</span>
            </label>
            <div class="event-schedule__time-field">
              <input type="text" id="es-end-time" name="end_time" placeholder="HH:MM" inputmode="numeric" autocomplete="off" maxlength="5" data-time-input>
              <div class="event-schedule__ampm" role="group" aria-label="End time period" data-ampm-group>
                <button type="button" data-ampm="AM" aria-pressed="true">AM</button>
                <button type="button" data-ampm="PM" aria-pressed="false">PM</button>
              </div>
            </div>
            <small class="event-schedule__field-error d-none" data-field-error="end_time"></small>
          </div>

          <p class="event-schedule__reminder">
            <strong>Reminder:</strong>
            Double-check your selected schedule. Changes may not be allowed once you proceed.
          </p>
        </div>
      </div>

      <p class="event-schedule__error text-danger d-none" role="alert" data-event-schedule-error></p>
    </form>
  </div>

  <div class="client-info__actions">
    <x-button type="submit" form="event-schedule-form" class="client-info__btn--confirm
    btn-color-gradient--primary font-button--responsive text-pale--white rounded-2">Continue</x-button>
    <x-button type="button" class="btn-color-gradient--secondary client-info__btn--cancel text-pale--white rounded-2" data-booking-previous>Previous</x-button>
  </div>
</div>