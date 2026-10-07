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

      {{-- Right column: payment option, payment summary, and GCash QR --}}
      <div class="booking-summary__column">
        <h2 class="booking-summary__title">Payment</h2>

        <form
          id="payment-form"
          @if ($reschedule) data-reschedule-id="{{ $reschedule['booking_id'] }}" @endif
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
                <p class="booking-summary__option-hint">Pay 30% now to reserve your slot; remaining balance can be paid before or on the event day.</p>
              </div>

              <small class="booking-summary__field-error d-none" data-field-error="payment_option"></small>

              <ul class="booking-summary__reminder-list">
                <li><strong>Reminder:</strong> Downpayment confirms your booking but doesn't cover the full cost. Remaining balance must be settled before or on the event day.</li>
                <li>Receipts are issued automatically after payment.</li>
                <li>Booking is only secured after payment is processed.</li>
              </ul>
            </div>

            @endif

            {{-- Payment Summary --}}
            <div class="booking-summary__payment-panel">
              <h3 class="booking-summary__payment-heading">
                Payment Summary <span class="booking-summary__ref">- #{{ $referenceNumber ?? 'JG00000' }}</span>
              </h3>

              <dl class="booking-summary__summary-list">
                <div class="booking-summary__summary-row">
                  <dt>Date</dt>
                  <dd>{{ $eventDate ?? '' }}</dd>
                </div>
                <div class="booking-summary__summary-row">
                  <dt>Time</dt>
                  <dd>{{ $bookedAt ?? '' }}</dd>
                </div>
                <div class="booking-summary__summary-row">
                  <dt>Package</dt>
                  <dd>{{ $packageName ?? '' }}</dd>
                </div>
                <div class="booking-summary__summary-row">
                  <dt>Package Cost</dt>
                  <dd>Php {{ number_format($packageCost ?? 0, 2) }}</dd>
                </div>
                <div class="booking-summary__summary-row">
                  <dt>Transportation fee</dt>
                  <dd>Php {{ number_format($transportationFee ?? 0, 2) }}</dd>
                </div>
                <div class="booking-summary__summary-row">
                  <dt>Payment Method</dt>
                  <dd>{{ $paymentMethod ?? 'Gcash' }}</dd>
                </div>
                <div class="booking-summary__summary-row booking-summary__summary-row--total">
                  <dt>Total</dt>
                  <dd>Php {{ number_format($totalCost ?? 0, 2) }}</dd>
                </div>
              </dl>
            </div>

            {{-- GCash QR (not shown when rescheduling) --}}
            @unless ($reschedule)
            <div class="booking-summary__payment-panel booking-summary__payment-panel--qr">
              <div class="booking-summary__qr-code" role="img" aria-label="GCash payment QR code">
                @if(!empty($qrCodeUrl))
                <img src="{{ $qrCodeUrl }}" alt="GCash payment QR code">
                @else
                <span>QR Code</span>
                @endif
              </div>

              <ol class="booking-summary__qr-steps">
                <li><strong>Confirm Details</strong> - Ensure all event information is correct before paying.</li>
                <li><strong>Select Payment Type</strong> - Choose Full Payment or Downpayment. Amount due will be displayed.</li>
                <li><strong>Scan &amp; Pay</strong> - Scan the GCash QR code to complete your transaction.</li>
                <li><strong>Final &amp; Verified</strong> - Payment is final and non-reversible once scanned.</li>
                <li><strong>Confirmation</strong> - You will be directed to the Confirmation page and notified once verified.</li>
              </ol>
            </div>
            @endunless
          </div>

          <p class="booking-summary__error text-danger d-none" role="alert" data-payment-error></p>
        </form>
      </div>
    </div>
  </div>

  <div class="booking-summary__actions">
    <x-button type="button" class="btn-color-gradient--secondary client-info__btn--cancel text-pale--white rounded-2" data-booking-cancel>Cancel</x-button>
    <x-button type="button" class="btn-color-gradient--secondary client-info__btn--cancel text-pale--white rounded-2" data-booking-previous>Previous</x-button>
    @if ($reschedule)
    <x-button type="submit" form="payment-form" class="client-info__btn--confirm btn-color-gradient--primary font-button--responsive text-pale--white rounded-2">Confirm Reschedule</x-button>
    @endif
  </div>
</div>