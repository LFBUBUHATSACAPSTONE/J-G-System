@php($reschedule = $reschedule ?? null)
<div class="booking-summary position-relative z-1">
  <div class="booking-summary__card">
    <div class="booking-summary__grid">

      {{-- First column: read-only recap of every prior step --}}
      <div class="booking-summary__column">
        <h2 class="booking-summary__title">Booking Summary</h2>

        <div class="booking-summary__receipt">
          <h3 class="booking-summary__section">Personal Information</h3>

          <div class="booking-summary__row">
            <span class="booking-summary__label">Full Name</span>
            <span class="booking-summary__value" data-booking-summary="fullName">{{ $fullName ?? '' }}</span>
          </div>

          <div class="booking-summary__row">
            <span class="booking-summary__label">Email</span>
            <span class="booking-summary__value" data-booking-summary="email">{{ $email ?? '' }}</span>
          </div>

          <div class="booking-summary__row">
            <span class="booking-summary__label">Contact Number</span>
            <span class="booking-summary__value" data-booking-summary="contactNumber">{{ $contactNumber ?? '' }}</span>
          </div>

          <div class="booking-summary__divider" role="separator"></div>

          <h3 class="booking-summary__section">Selected Package</h3>

          <div class="booking-summary__row">
            <span class="booking-summary__label">Package</span>
            <span class="booking-summary__value" data-booking-summary="packageName">{{ $packageName ?? '' }}</span>
          </div>

          <div class="booking-summary__row">
            <span class="booking-summary__label">Package Price</span>
            <span class="booking-summary__value" data-booking-summary="packagePrice">Php {{ number_format((float) ($packageCost ?? 0), 2) }}</span>
          </div>

          <div class="booking-summary__divider" role="separator"></div>

          <h3 class="booking-summary__section">Event Details</h3>

          <div class="booking-summary__row">
            <span class="booking-summary__label">Event Name</span>
            <span class="booking-summary__value" data-booking-summary="eventName">{{ $eventName ?? '' }}</span>
          </div>

          <div class="booking-summary__row">
            <span class="booking-summary__label">Event Type</span>
            <span class="booking-summary__value" data-booking-summary="eventType">{{ $eventType ?? '' }}</span>
          </div>

          <div class="booking-summary__row">
            <span class="booking-summary__label">Event Location</span>
            <span class="booking-summary__value" data-booking-summary="eventLocation">{{ $eventLocation ?? '' }}</span>
          </div>

          <div class="booking-summary__row">
            <span class="booking-summary__label">Event Contact Person</span>
            <span class="booking-summary__value" data-booking-summary="eventContactPerson">{{ $eventContactPerson ?? '' }}</span>
          </div>

          <div class="booking-summary__divider" role="separator"></div>

          <h3 class="booking-summary__section">Schedule</h3>

          <div class="booking-summary__row">
            <span class="booking-summary__label">Event Date</span>
            <span class="booking-summary__value" data-booking-summary="eventDate">{{ $eventDate ?? '' }}</span>
          </div>

          <div class="booking-summary__row">
            <span class="booking-summary__label">Start In</span>
            <span class="booking-summary__value" data-booking-summary="startTime">{{ $startTime ?? '' }}</span>
          </div>

          <div class="booking-summary__row">
            <span class="booking-summary__label">End In</span>
            <span class="booking-summary__value" data-booking-summary="endTime">{{ $endTime ?? '' }}</span>
          </div>
        </div>
      </div>

      {{-- Second column: payment type (option + down payment amount) --}}
      <div class="booking-summary__column">
        <h2 class="booking-summary__title">Payment Type</h2>

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
                <p class="booking-summary__option-hint">Choose this if you have paid the full package price. The booking remains pending until reviewed.</p>

                <button
                  type="button"
                  class="booking-summary__option-btn"
                  role="radio"
                  aria-checked="false"
                  data-payment-option="down">
                  Down Payment
                </button>
                <p class="booking-summary__option-hint">Choose this if you have paid at least 30% of the package price; the remaining balance can be settled before or on the event day.</p>
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
                <small class="booking-summary__percent d-none" aria-live="polite" data-down-payment-percent></small>
                <small id="down-payment-error" class="booking-summary__field-error d-none" role="alert" data-field-error="down_payment_amount"></small>
                <p id="down-payment-help" class="booking-summary__option-hint" data-down-payment-help>Enter at least 30% of your selected package cost. Numbers only.</p>
              </div>

              <small class="booking-summary__field-error d-none" data-field-error="payment_option"></small>

              <ul class="booking-summary__reminder-list">
                <li><strong>Reminder:</strong> A down payment does not cover the full cost. The remaining balance must be settled before or on the event day.</li>
                <li>Your booking and payment amount are recorded for admin review.</li>
              </ul>
            </div>
            @endif
          </div>

          <p class="booking-summary__error text-danger d-none" role="alert" data-payment-error></p>
        </form>
      </div>

      {{-- Third column: payment details form link + proof upload (not shown when rescheduling).
           The file input lives here but belongs to #payment-form through its form="payment-form" attribute. --}}
      @unless ($reschedule)
      <div class="booking-summary__column booking-summary__column--form">
        <h2 class="booking-summary__title">Payment Form</h2>

        <div class="booking-summary__payment-grid">
          {{-- Dummy link: replace the href with the real payment details form. --}}
          <div class="booking-summary__payment-panel">
            <h3 class="booking-summary__payment-heading">Payment Details Form</h3>
            <p class="booking-summary__option-hint">Open the form in a new tab, complete it, then come back to upload your proof.</p>
            <a
              class="booking-summary__link-btn"
              href="https://example.com/jg-audio-payment-details-form"
              target="_blank"
              rel="noopener noreferrer">
              Open payment details form
              <span class="visually-hidden">(opens in a new tab)</span>
            </a>
          </div>

          {{-- Proof that the payment details form was submitted (screenshot of the confirmation).
               Sent with the form as `payment_proof`; shown only for a new payment, not a reschedule. --}}
          <div class="booking-summary__payment-panel">
            <h3 class="booking-summary__payment-heading">Upload Proof of Submission</h3>
            <p class="booking-summary__option-hint">After you submit the payment details form, upload a screenshot of its confirmation page. JPG, PNG or WebP, up to 5 MB.</p>

            <div class="booking-summary__upload" data-proof-upload>
              <input
                id="payment-proof"
                form="payment-form"
                class="booking-summary__file-input"
                type="file"
                name="payment_proof"
                accept="image/jpeg,image/png,image/webp"
                aria-describedby="payment-proof-error"
                data-proof-input>

              <label class="booking-summary__dropzone" for="payment-proof" data-proof-dropzone>
                <span class="booking-summary__dropzone-title">Choose an image</span>
                <span class="booking-summary__dropzone-hint">or drag and drop it here</span>
              </label>

              <div class="booking-summary__preview d-none" data-proof-preview>
                <img class="booking-summary__preview-img" alt="Preview of your uploaded proof of submission" data-proof-image>
                <div class="booking-summary__preview-meta">
                  <span class="booking-summary__preview-name" data-proof-name></span>
                  <button type="button" class="booking-summary__preview-remove" data-proof-remove>Remove</button>
                </div>
              </div>
            </div>

            <small id="payment-proof-error" class="booking-summary__field-error d-none" role="alert" data-field-error="payment_proof"></small>
          </div>
        </div>
      </div>
      @endunless

      {{-- Fourth column: how to complete this step (not shown when rescheduling, the payment is carried over) --}}
      @unless ($reschedule)
      <div class="booking-summary__column booking-summary__column--guide">
        <h2 class="booking-summary__title">Instructions</h2>

        <div class="booking-summary__payment-grid">
          <div class="booking-summary__payment-panel">
            <h3 class="booking-summary__payment-heading">How to pay</h3>
            <ol class="booking-summary__instructions">
              <li><strong>Check your details</strong> - Make sure the booking summary on the left is correct.</li>
              <li><strong>Choose a payment option</strong> - Select Full Payment or Down Payment.</li>
              <li><strong>Enter your down payment</strong> - If you chose Down Payment, type the amount in numbers only. It must be at least 30% of your package cost.</li>
              <li><strong>Fill in the payment details form</strong> - Open the payment details form, enter your payment details and submit it.</li>
              <li><strong>Upload your proof</strong> - Upload a screenshot of the form's confirmation page.</li>
              <li><strong>Continue</strong> - Click Continue to move on to the Confirmation step.</li>
            </ol>
          </div>
        </div>
      </div>
      @endunless
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