{{-- One modal shared by every row. bookings.js fills it from the clicked row's
     data-booking JSON, so there is no per-row modal markup.
     The Edit toggle unlocks every field marked data-editable (client details + event name, type,
     location, contact person, guests, venue type) and shows "Save changes" (PATCH to
     admin.bookings.update). Schedule, payment, proof of submission and package stay read-only.
     Event Location locks `location_lock_days` before the event (config/scheduling.php).
     On the History page the payload has editable = false, so Edit is hidden and the modal only
     shows the data plus the "Edited" labels. Validation lives in bookings.js (same rules as the
     user-side booking form). --}}
@php
$clientFields = [
['id' => 'client-name', 'name' => 'client_name', 'label' => 'Client Name', 'path' => 'client.name'],
['id' => 'client-email', 'name' => 'client_email', 'label' => 'Email', 'path' => 'client.email'],
['id' => 'client-phone', 'name' => 'client_phone', 'label' => 'Contact Number', 'path' => 'client.phone'],
['id' => 'client-address', 'name' => 'client_address', 'label' => 'Address', 'path' => 'client.address'],
];
$venueTypes = config('admin.bookings.venue_types', ['Indoor', 'Outdoor', 'Both']);
$eventTypes = config('admin.bookings.event_types', ['Baby Shower', 'Bridal Shower', 'Birthday Party', 'Concert', 'Family Reunion', 'Team-Building Event', 'Wedding', 'Others']);
$lockDays = (int) config('scheduling.location_lock_days', 1);

// Only used after a failed save (the backend redirects back with errors + old input and flashes
// edit_booking_id): bookings.js reopens that booking in edit mode and shows the messages.
$serverErrors = isset($errors) ? $errors->toArray() : [];
@endphp

<div
  class="modal fade admin-booking-modal"
  id="bookingModal"
  tabindex="-1"
  aria-labelledby="bookingModalTitle"
  aria-hidden="true"
  data-location-lock-days="{{ $lockDays }}"
  data-server-errors="{{ json_encode($serverErrors) }}"
  data-server-old="{{ json_encode(session()->getOldInput()) }}"
  data-server-booking="{{ session('edit_booking_id') }}"
  data-update-url-template="{{ Route::has('admin.bookings.update') ? route('admin.bookings.update', ['booking' => '__ID__']) : '#' }}">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <form class="modal-content admin-booking-modal__card" method="POST" action="#" novalidate>
      @csrf
      @method('PATCH')

      <div class="modal-body admin-booking-modal__body">
        <h2 id="bookingModalTitle" class="visually-hidden">
          Booking details <span data-booking-field="reference"></span>
        </h2>

        {{-- Client Information --}}
        <section class="admin-booking-modal__section admin-booking-modal__section--client" aria-labelledby="bm-client-title">
          <h3 id="bm-client-title" class="admin-booking-modal__heading">Client Information</h3>

          <div class="admin-booking-modal__panel">
            @foreach ($clientFields as $field)
            <div class="admin-field">
              <label for="bm-{{ $field['id'] }}" class="admin-field__label">{{ $field['label'] }}</label>
              <input type="text" id="bm-{{ $field['id'] }}" name="{{ $field['name'] }}" class="admin-field__control" readonly
                placeholder="--" aria-describedby="bm-err-{{ $field['id'] }}"
                data-booking-field="{{ $field['path'] }}" data-editable>
              <small class="admin-field__error d-none" id="bm-err-{{ $field['id'] }}" data-field-error="{{ $field['name'] }}"></small>
              <x-admin.edited-note :path="$field['path']" />
            </div>
            @endforeach
          </div>
        </section>

        {{-- Event Information --}}
        <section class="admin-booking-modal__section admin-booking-modal__section--event" aria-labelledby="bm-event-title">
          <div class="admin-booking-modal__section-head">
            <h3 id="bm-event-title" class="admin-booking-modal__heading">Event Information</h3>
            <button type="button" class="admin-btn admin-btn--outline admin-btn--sm" data-booking-edit>
              <i class="ph ph-pencil-simple" aria-hidden="true" data-booking-edit-icon></i>
              <span data-booking-edit-label>Edit</span>
            </button>
          </div>

          <div class="admin-booking-modal__panel admin-booking-modal__panel--event">
            <div class="admin-booking-modal__col">
              <div class="admin-field">
                <label for="bm-event-name" class="admin-field__label">Event Name</label>
                <input type="text" id="bm-event-name" name="event_name" class="admin-field__control" readonly
                  placeholder="--" aria-describedby="bm-err-event-name" data-booking-field="event.name" data-editable>
                <small class="admin-field__error d-none" id="bm-err-event-name" data-field-error="event_name"></small>
                <x-admin.edited-note path="event.name" />
              </div>

              {{-- Event type: view mode shows the text; Edit swaps in the dropdown. Choosing "Others"
                   swaps the dropdown for a text field IN THE SAME SPOT (no extra field) and the label
                   gets an "Others" tag. The arrow button beside it goes back to the list.
                   Posts event_type and, for Others, event_type_other (same names as the user side). --}}
              <div class="admin-field" data-type-field>
                <label for="bm-event-type" class="admin-field__label" data-type-label>
                  Event type
                  <span class="admin-field__tag admin-field__tag--others" data-others-tag hidden>Others</span>
                </label>
                <input type="text" id="bm-event-type" class="admin-field__control" readonly
                  placeholder="--" data-booking-field="event.type" data-type-display>

                <div data-type-select-wrap hidden>
                  <select id="bm-event-type-select" name="event_type" class="admin-field__control" disabled
                    aria-describedby="bm-err-event-type" data-booking-field="event.type_value" data-editable>
                    <option value="">Select event type</option>
                    @foreach ($eventTypes as $eventType)
                    <option value="{{ $eventType }}">{{ $eventType }}</option>
                    @endforeach
                  </select>
                </div>

                <div class="admin-field__row admin-field__row--action" data-type-other-wrap hidden>
                  <input type="text" id="bm-event-type-other" name="event_type_other" class="admin-field__control" readonly
                    placeholder="Type the event type" aria-describedby="bm-err-event-type-other"
                    data-booking-field="event.type_other" data-editable>
                  <button type="button" class="admin-btn admin-btn--outline admin-btn--sm" data-type-change
                    aria-label="Choose the event type from the list">
                    <i class="ph ph-caret-down" aria-hidden="true"></i>
                  </button>
                </div>

                <small class="admin-field__error d-none" id="bm-err-event-type" data-field-error="event_type"></small>
                <small class="admin-field__error d-none" id="bm-err-event-type-other" data-field-error="event_type_other"></small>
                <x-admin.edited-note path="event.type" />
              </div>

              <div class="admin-field">
                <label for="bm-event-location" class="admin-field__label">Event Location</label>
                <input type="text" id="bm-event-location" name="event_location" class="admin-field__control" readonly
                  placeholder="--" aria-describedby="bm-err-event-location bm-location-lock"
                  data-booking-field="event.location" data-editable>
                <small class="admin-field__error d-none" id="bm-err-event-location" data-field-error="event_location"></small>
                <p class="admin-field__note" id="bm-location-lock" data-location-lock hidden>
                  <i class="ph ph-lock-simple" aria-hidden="true"></i>
                  Locked: the location can't be changed within {{ $lockDays }} {{ Str::plural('day', $lockDays) }} of the event.
                </p>
                <x-admin.edited-note path="event.location" />
              </div>

              <div class="admin-field">
                <label for="bm-contact-person" class="admin-field__label">Event Contact Person</label>
                <input type="text" id="bm-contact-person" name="venue_contact_person" class="admin-field__control" readonly
                  inputmode="numeric" placeholder="--" aria-describedby="bm-err-contact-person"
                  data-booking-field="event.contact_person" data-editable>
                <small class="admin-field__error d-none" id="bm-err-contact-person" data-field-error="venue_contact_person"></small>
                <x-admin.edited-note path="event.contact_person" />
              </div>

              <div class="admin-field">
                <label for="bm-guests" class="admin-field__label">Number of Guests</label>
                <div class="admin-field__row">
                  <input type="text" id="bm-guests" name="guest_count" class="admin-field__control" readonly
                    inputmode="numeric" pattern="[0-9]*" placeholder="--"
                    data-booking-field="event.guests" data-editable>
                  <select name="venue_type" class="admin-field__control" aria-label="Venue type" disabled
                    data-booking-field="event.venue_type" data-editable>
                    <option value="">--</option>
                    @foreach ($venueTypes as $venueType)
                    <option value="{{ $venueType }}">{{ $venueType }}</option>
                    @endforeach
                  </select>
                </div>
                <small class="admin-field__error d-none" data-field-error="guest_count"></small>
                <small class="admin-field__error d-none" data-field-error="venue_type"></small>
                <x-admin.edited-note path="event.guests" />
                <x-admin.edited-note path="event.venue_type" />
              </div>
            </div>

            <div class="admin-booking-modal__col">
              <div class="admin-field">
                <label for="bm-event-date" class="admin-field__label">Event Date</label>
                <input type="text" id="bm-event-date" class="admin-field__control" readonly
                  placeholder="--" data-booking-field="event.schedule">
              </div>

              <div class="admin-field__row">
                <div class="admin-field">
                  <label for="bm-start-time" class="admin-field__label">Start in</label>
                  <input type="text" id="bm-start-time" class="admin-field__control" readonly
                    placeholder="--" data-booking-field="event.start_time">
                </div>
                <div class="admin-field">
                  <label for="bm-end-time" class="admin-field__label">End in</label>
                  <input type="text" id="bm-end-time" class="admin-field__control" readonly
                    placeholder="--" data-booking-field="event.end_time">
                </div>
              </div>

              <div class="admin-field">
                <label for="bm-payment" class="admin-field__label">Payment</label>
                <input type="text" id="bm-payment" class="admin-field__control" readonly
                  placeholder="--" data-booking-field="payment.label">
              </div>

              <div class="admin-field">
                <label for="bm-down-payment" class="admin-field__label">Down Payment Value</label>
                <input type="text" id="bm-down-payment" class="admin-field__control" readonly
                  placeholder="--" data-booking-field="payment.down_payment_label">
              </div>

              {{-- Proof of submission: the screenshot the client uploaded on the Payment step of the
                   booking flow (payment.proof_url). View-only: no name, never posted, not data-editable.
                   bookings.js shows the thumbnail (click to open it full size in a new tab) or the
                   empty text. Same field on Bookings and Booking History (this modal is shared). --}}
              <div class="admin-field admin-booking-modal__proof-field">
                <span id="bm-proof-label" class="admin-field__label">Proof of Submission</span>
                <div class="admin-booking-modal__proof" role="group" aria-labelledby="bm-proof-label" data-booking-proof>
                  <a class="admin-booking-modal__proof-link" href="#" target="_blank" rel="noopener noreferrer" hidden data-proof-link>
                    <img class="admin-booking-modal__proof-img" alt="Proof of submission uploaded by the client" loading="lazy" data-proof-img>
                    <span class="visually-hidden">Open the proof of submission in a new tab</span>
                  </a>
                  <p class="admin-booking-modal__proof-empty" data-proof-empty>No proof uploaded</p>
                </div>
              </div>
            </div>

            {{-- Package strip: full width under both columns, next to Payment and Down Payment. --}}
            <div class="admin-field admin-booking-modal__package-field">
              <span id="bm-package-label" class="admin-field__label">Package</span>
              <div class="admin-booking-modal__package" role="group" aria-labelledby="bm-package-label">
                <p class="admin-booking-modal__package-name" data-booking-field="package.name"></p>
                <p class="admin-booking-modal__package-price" data-booking-field="package.price_label"></p>
                <p class="admin-booking-modal__package-status"
                  data-booking-field="payment.status" data-booking-state="payment.state"></p>
              </div>
            </div>
          </div>
        </section>

        <p class="admin-booking-modal__error" role="alert" data-booking-error hidden></p>
      </div>

      <div class="modal-footer admin-booking-modal__footer">
        <button type="submit" class="admin-btn admin-btn--brand" data-booking-save hidden>Save changes</button>
        <button type="button" class="admin-btn admin-btn--outline" data-bs-dismiss="modal">Back</button>
      </div>
    </form>
  </div>
</div>