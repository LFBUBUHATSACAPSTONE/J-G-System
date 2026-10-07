{{-- One modal shared by every row. bookings.js fills it from the clicked row's
     data-booking JSON, so there is no per-row modal markup.
     Client Information is always read-only. Event Information has an Edit toggle that
     unlocks the fields marked data-editable and shows "Save changes" (PATCH to
     admin.bookings.update). Schedule, payment and package stay read-only. --}}
@php
$clientFields = [
['id' => 'client-name', 'label' => 'Client Name', 'path' => 'client.name'],
['id' => 'client-email', 'label' => 'Email', 'path' => 'client.email'],
['id' => 'client-phone', 'label' => 'Contact Number', 'path' => 'client.phone'],
['id' => 'client-address', 'label' => 'Address', 'path' => 'client.address'],
];
$venueTypes = config('admin.bookings.venue_types', ['Indoor', 'Outdoor', 'Both']);
@endphp

<div
  class="modal fade admin-booking-modal"
  id="bookingModal"
  tabindex="-1"
  aria-labelledby="bookingModalTitle"
  aria-hidden="true"
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
              <input type="text" id="bm-{{ $field['id'] }}" class="admin-field__control" readonly
                placeholder="--" data-booking-field="{{ $field['path'] }}">
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
                  placeholder="--" data-booking-field="event.name" data-editable>
              </div>

              <div class="admin-field">
                <label for="bm-event-type" class="admin-field__label">Event type</label>
                <input type="text" id="bm-event-type" class="admin-field__control" readonly
                  placeholder="--" data-booking-field="event.type">
              </div>

              <div class="admin-field">
                <label for="bm-event-location" class="admin-field__label">Event Location</label>
                <input type="text" id="bm-event-location" name="event_location" class="admin-field__control" readonly
                  placeholder="--" data-booking-field="event.location" data-editable>
              </div>

              <div class="admin-field">
                <label for="bm-contact-person" class="admin-field__label">Event Contact Person</label>
                <input type="text" id="bm-contact-person" class="admin-field__control" readonly
                  placeholder="--" data-booking-field="event.contact_person">
              </div>

              <div class="admin-field">
                <label for="bm-guests" class="admin-field__label">Number of Guests</label>
                <div class="admin-field__row">
                  <input type="text" id="bm-guests" class="admin-field__control" readonly
                    inputmode="numeric" pattern="[0-9]*" placeholder="--"
                    data-booking-field="event.guests">
                  <select class="admin-field__control" aria-label="Venue type" disabled
                    data-booking-field="event.venue_type">
                    <option value="">--</option>
                    @foreach ($venueTypes as $venueType)
                    <option value="{{ $venueType }}">{{ $venueType }}</option>
                    @endforeach
                  </select>
                </div>
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
                <span id="bm-package-label" class="admin-field__label">Package</span>
                <div class="admin-booking-modal__package" role="group" aria-labelledby="bm-package-label">
                  <p class="admin-booking-modal__package-name" data-booking-field="package.name"></p>
                  <p class="admin-booking-modal__package-price" data-booking-field="package.price_label"></p>
                  <p class="admin-booking-modal__package-status"
                    data-booking-field="payment.status" data-booking-state="payment.state"></p>
                </div>
              </div>
            </div>
          </div>
        </section>
      </div>

      <div class="modal-footer admin-booking-modal__footer">
        <button type="submit" class="admin-btn admin-btn--brand" data-booking-save hidden>Save changes</button>
        <button type="button" class="admin-btn admin-btn--outline" data-bs-dismiss="modal">Back</button>
      </div>
    </form>
  </div>
</div>