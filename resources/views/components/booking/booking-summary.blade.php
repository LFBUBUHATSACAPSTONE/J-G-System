@php($reschedule = $reschedule ?? null)
<div class="booking-summary position-relative z-1">
  <div class="booking-summary__card">
    <div class="booking-summary__grid">

      {{-- Left column: read-only recap of every prior step --}}
      <div class="booking-summary__column">
        <h2 class="booking-summary__title">Booking Summary</h2>

        <div class="booking-summary__receipt">
          <h3 class="booking-summary__section">Personal Information</h3>

          <div class="booking-summary__row">
            <span class="booking-summary__label">Full Name</span>
            <span class="booking-summary__value">{{ $fullName ?? '' }}</span>
          </div>

          <div class="booking-summary__row">
            <span class="booking-summary__label">Email</span>
            <span class="booking-summary__value">{{ $email ?? '' }}</span>
          </div>

          <div class="booking-summary__row">
            <span class="booking-summary__label">Contact Number</span>
            <span class="booking-summary__value">{{ $contactNumber ?? '' }}</span>
          </div>

          <div class="booking-summary__divider" role="separator"></div>

          <h3 class="booking-summary__section">Event Details</h3>

          <div class="booking-summary__row">
            <span class="booking-summary__label">Event Name</span>
            <span class="booking-summary__value">{{ $eventName ?? '' }}</span>
          </div>

          <div class="booking-summary__row">
            <span class="booking-summary__label">Event Type</span>
            <span class="booking-summary__value">{{ $eventType ?? '' }}</span>
          </div>

          <div class="booking-summary__row">
            <span class="booking-summary__label">Event Location</span>
            <span class="booking-summary__value">{{ $eventLocation ?? '' }}</span>
          </div>

          <div class="booking-summary__row">
            <span class="booking-summary__label">Event Contact Person</span>
            <span class="booking-summary__value">{{ $eventContactPerson ?? '' }}</span>
          </div>

          <div class="booking-summary__divider" role="separator"></div>

          <h3 class="booking-summary__section">Schedule</h3>

          <div class="booking-summary__row">
            <span class="booking-summary__label">Event Date</span>
            <span class="booking-summary__value">{{ $eventDate ?? '' }}</span>
          </div>

          <div class="booking-summary__row">
            <span class="booking-summary__label">Start In</span>
            <span class="booking-summary__value">{{ $startTime ?? '' }}</span>
          </div>

          <div class="booking-summary__row">
            <span class="booking-summary__label">End In</span>
            <span class="booking-summary__value">{{ $endTime ?? '' }}</span>
          </div>
        </div>
      </div>

      {{-- Right column: payment option (with down payment amount) and how-to steps --}}
      <div class="booking-summary__column">
        <h2 class="booking-summary__title">Payment</h2>

        <form
          id="payment-form"
          @if ($reschedule) data-reschedule-id="{{ $reschedule['booking_id'] }}" @endif
          data-package-cost="{{ $packageCost ?? 0 }}"
          method="POST"
          action="{{ route('booking.booking-summary') }}"
          novalidate>
          @csrf
          <input type="hidden" name="payment_option" data-payment-option-input>

          <div class="booking-summary__payment-grid">

            {{-- Payment Option (a reschedule carries the original payment over instead) --}}
            @if ($reschedule)
            <div class="booking-summary__payment-panel" data-payment-carried-over>
              <h3 class="booking-summary__payment-heading">Payment carried over from {{ $reschedule['reference'] }}</h3>
              <p class="booking-summary__option-hint">The payment from your cancelled booking applies to this reschedule, so there is nothing to pay again. After you confirm, the booking goes back to pending until the admin approves the new dates.</p>
            </div>
            @else
            <div class="booking-summary__payment-panel">
              <h3 class="booking-summary__payment-heading">Payment Option</h3>

              <div class="booking-summary__option-group" role="radiogroup" aria-label="Payment option" data-payment-option-group>
                <button
                  type="button"
                  class="booking-summary__option-btn"
                  role="radio"
                  aria-checked="false"
                  data-payment-option="full">
                  Full Payment
                </button>
                <p class="booking-summary__option-hint">Pay the full amount now to secure your booking.</p>

                <button
                  type="button"
                  class="booking-summary__option-btn"
                  role="radio"
                  aria-checked="false"
                  data-payment-option="down">
                  Down Payment
                </button>
                <p class="booking-summary__option-hint">Pay at least 30% now to reserve your slot; remaining balance can be paid before or on the event day.</p>
              </div>

              {{-- Shown by booking-summary.js only while Down Payment is selected --}}
              <div class="booking-summary__down-payment d-none" data-down-payment-panel>
                <label class="booking-summary__input-label" for="down-payment-amount">Down payment amount</label>
                <div class="booking-summary__amount">
                  <span class="booking-summary__amount-prefix" aria-hidden="true">Php</span>
                  <input
                    id="down-payment-amount"
                    class="booking-summary__amount-input"
                    type="text"
                    inputmode="decimal"
                    autocomplete="off"
                    placeholder="0.00"
                    name="down_payment_amount"
                    aria-describedby="down-payment-help down-payment-error"
                    data-down-payment-input>
                </div>
                <small id="down-payment-error" class="booking-summary__field-error d-none" role="alert" data-field-error="down_payment_amount"></small>
                <p id="down-payment-help" class="booking-summary__option-hint" data-down-payment-help>Enter at least 30% of your selected package cost. Numbers only.</p>
              </div>

              <small class="booking-summary__field-error d-none" data-field-error="payment_option"></small>

              <ul class="booking-summary__reminder-list">
                <li><strong>Reminder:</strong> Downpayment confirms your booking but doesn't cover the full cost. Remaining balance must be settled before or on the event day.</li>
                <li>Receipts are issued automatically after payment.</li>
                <li>Booking is only secured after payment is processed.</li>
              </ul>
            </div>

            {{-- How to complete this step --}}
            <div class="booking-summary__payment-panel">
              <h3 class="booking-summary__payment-heading">How to pay</h3>
              <ol class="booking-summary__instructions">
                <li><strong>Check your details</strong> - Make sure the booking summary on the left is correct.</li>
                <li><strong>Choose a payment option</strong> - Select Full Payment or Down Payment.</li>
                <li><strong>Enter your down payment</strong> - If you chose Down Payment, type the amount in numbers only. It must be at least 30% of your package cost.</li>
                <li><strong>Continue</strong> - Click Continue to move on to the Confirmation step.</li>
              </ol>
            </div>
            @endif
          </div>

          <p class="booking-summary__error text-danger d-none" role="alert" data-payment-error></p>
        </form>
      </div>
    </div>
  </div>

  <div class="booking-summary__actions">
    <x-button type="button" class="btn-color-gradient--secondary client-info__btn--cancel text-pale--white rounded-2" data-booking-previous>Previous</x-button>
    @if ($reschedule)
    <x-button type="submit" form="payment-form" class="client-info__btn--confirm btn-color-gradient--primary font-button--responsive text-pale--white rounded-2">Confirm Reschedule</x-button>
    @else
    <x-button type="submit" form="payment-form" class="client-info__btn--confirm btn-color-gradient--primary font-button--responsive text-pale--white rounded-2">Continue</x-button>
    @endif
  </div>
</div>