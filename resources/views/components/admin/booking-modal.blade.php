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
// Tailwind class strings shared by the fields below (kept whole so Tailwind can find them).
// `d-none` and `is-invalid` stay as the state classes: auth/validation.js (shared with the user side) toggles them,
// and the observer in bookings.js mirrors is-invalid to aria-invalid, which is what the red border listens to.
$fieldClass = 'grid min-w-0 gap-1';
$labelClass = 'text-fluid-md text-brand-text';
$controlClass = 'min-h-10 w-full appearance-none rounded-field border border-field-border bg-surface px-3 py-2 text-fluid-md text-ink placeholder:text-ink-subtle placeholder:opacity-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text aria-invalid:border-danger-border';
$editableClass = 'group-[.is-editing]/modal:not-aria-invalid:not-[.is-locked]:border-brand-text'; // unlocked fields while editing
$errorClass = 'd-none text-sm text-danger-text';
$lockDays = (int) config('scheduling.location_lock_days', 1);

// Only used after a failed save (the backend redirects back with errors + old input and flashes
// edit_booking_id): bookings.js reopens that booking in edit mode and shows the messages.
$serverErrors = isset($errors) ? $errors->toArray() : [];
@endphp

<div
  class="modal fade admin-booking-modal group/modal [[hidden]]:hidden!"
  id="bookingModal"
  tabindex="-1"
  aria-labelledby="bookingModalTitle"
  aria-hidden="true"
  data-location-lock-days="{{ $lockDays }}"
  data-server-errors="{{ json_encode($serverErrors) }}"
  data-server-old="{{ json_encode(session()->getOldInput()) }}"
  data-server-booking="{{ session('edit_booking_id') }}"
  data-update-url-template="{{ Route::has('admin.bookings.update') ? route('admin.bookings.update', ['booking' => '__ID__']) : '#' }}"
  data-review-url-template="{{ Route::has('admin.bookings.cancellation.review') ? route('admin.bookings.cancellation.review', ['booking' => '__ID__']) : '#' }}">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
    <form class="modal-content rounded-card border-0 bg-surface font-sans text-ink" method="POST" action="#" novalidate>
      @csrf
      @method('PATCH')

      <div class="modal-body grid gap-6 p-6 min-[992px]:grid-cols-[minmax(0,1fr)_minmax(0,2fr)] min-[992px]:px-8 short-wide:gap-4 short-wide:px-6 short-wide:py-4">
        <h2 id="bookingModalTitle" class="visually-hidden col-span-full">
          Booking details <span data-booking-field="reference"></span>
        </h2>

        {{-- Client Information --}}
        <section aria-labelledby="bm-client-title">
          <h3 id="bm-client-title" class="mb-4 text-fluid-xl leading-9 font-bold text-ink short-wide:mb-2">Client Information</h3>

          <div class="grid gap-4 rounded-card bg-panel p-6 short-wide:gap-3 short-wide:p-4">
            @foreach ($clientFields as $field)
            <div class="{{ $fieldClass }}">
              <label for="bm-{{ $field['id'] }}" class="{{ $labelClass }}">{{ $field['label'] }}</label>
              <input type="text" id="bm-{{ $field['id'] }}" name="{{ $field['name'] }}" class="{{ $controlClass }} {{ $editableClass }}" readonly
                placeholder="--" aria-describedby="bm-err-{{ $field['id'] }}"
                data-booking-field="{{ $field['path'] }}" data-editable>
              <small class="{{ $errorClass }}" id="bm-err-{{ $field['id'] }}" data-field-error="{{ $field['name'] }}"></small>
              <x-admin.edited-note :path="$field['path']" />
            </div>
            @endforeach
          </div>
        </section>

        {{-- Event Information --}}
        <section aria-labelledby="bm-event-title">
          <div class="mb-4 flex items-center justify-between gap-4 short-wide:mb-2">
            <h3 id="bm-event-title" class="m-0 text-fluid-xl leading-9 font-bold text-ink">Event Information</h3>
            <button type="button" class="inline-flex min-h-9 cursor-pointer items-center justify-center gap-2 rounded-full border border-brand-text bg-transparent px-4 py-1 text-fluid-sm font-semibold whitespace-nowrap text-brand-text hover:bg-brand-soft [&_.ph]:text-fluid-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text" data-booking-edit>
              <i class="ph ph-pencil-simple" aria-hidden="true" data-booking-edit-icon></i>
              <span data-booking-edit-label>Edit</span>
            </button>
          </div>

          <div class="grid gap-4 rounded-card bg-panel p-6 min-[768px]:grid-cols-2 min-[768px]:gap-6 short-wide:gap-3 short-wide:p-4">
            <div class="grid content-start gap-4">
              <div class="{{ $fieldClass }}">
                <label for="bm-event-name" class="{{ $labelClass }}">Event Name</label>
                <input type="text" id="bm-event-name" name="event_name" class="{{ $controlClass }} {{ $editableClass }}" readonly
                  placeholder="--" aria-describedby="bm-err-event-name" data-booking-field="event.name" data-editable>
                <small class="{{ $errorClass }}" id="bm-err-event-name" data-field-error="event_name"></small>
                <x-admin.edited-note path="event.name" />
              </div>

              {{-- Event type: view mode shows the text; Edit swaps in the dropdown. Choosing "Others"
                   swaps the dropdown for a text field IN THE SAME SPOT (no extra field) and the label
                   gets an "Others" tag. The arrow button beside it goes back to the list.
                   Posts event_type and, for Others, event_type_other (same names as the user side). --}}
              <div class="{{ $fieldClass }}" data-type-field>
                <label for="bm-event-type" class="{{ $labelClass }}" data-type-label>
                  Event type
                  <span class="ms-2 inline-block rounded-field border border-field-border px-2 py-1 text-xs leading-[1.2] font-semibold text-brand-text" data-others-tag hidden>Others</span>
                </label>
                <input type="text" id="bm-event-type" class="{{ $controlClass }}" readonly
                  placeholder="--" data-booking-field="event.type" data-type-display>

                <div data-type-select-wrap hidden>
                  <select id="bm-event-type-select" name="event_type" class="admin-field__control peer" disabled
                    aria-describedby="bm-err-event-type" data-booking-field="event.type_value" data-editable>
                    <option value="">Select event type</option>
                    @foreach ($eventTypes as $eventType)
                    <option value="{{ $eventType }}">{{ $eventType }}</option>
                    @endforeach
                  </select>
                </div>

                <div class="grid grid-cols-[minmax(0,1fr)_auto] items-center gap-3" data-type-other-wrap hidden>
                  <input type="text" id="bm-event-type-other" name="event_type_other" class="{{ $controlClass }} {{ $editableClass }}" readonly
                    placeholder="Type the event type" aria-describedby="bm-err-event-type-other"
                    data-booking-field="event.type_other" data-editable>
                  <button type="button" class="inline-flex min-h-9 cursor-pointer items-center justify-center gap-2 rounded-full border border-brand-text bg-transparent px-4 py-1 text-fluid-sm font-semibold whitespace-nowrap text-brand-text hover:bg-brand-soft [&_.ph]:text-fluid-lg focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text" data-type-change
                    aria-label="Choose the event type from the list">
                    <i class="ph ph-caret-down" aria-hidden="true"></i>
                  </button>
                </div>

                <small class="{{ $errorClass }}" id="bm-err-event-type" data-field-error="event_type"></small>
                <small class="{{ $errorClass }}" id="bm-err-event-type-other" data-field-error="event_type_other"></small>
                <x-admin.edited-note path="event.type" />
              </div>

              <div class="{{ $fieldClass }}">
                <label for="bm-event-location" class="{{ $labelClass }}">Event Location</label>
                <input type="text" id="bm-event-location" name="event_location" class="{{ $controlClass }} {{ $editableClass }}" readonly
                  placeholder="--" aria-describedby="bm-err-event-location bm-location-lock"
                  data-booking-field="event.location" data-editable>
                <small class="{{ $errorClass }}" id="bm-err-event-location" data-field-error="event_location"></small>
                <p class="m-0 flex items-center gap-2 text-sm text-ink-muted" id="bm-location-lock" data-location-lock hidden>
                  <i class="ph ph-lock-simple" aria-hidden="true"></i>
                  Locked: the location can't be changed within {{ $lockDays }} {{ Str::plural('day', $lockDays) }} of the event.
                </p>
                <x-admin.edited-note path="event.location" />
              </div>

              <div class="{{ $fieldClass }}">
                <label for="bm-contact-person" class="{{ $labelClass }}">Event Contact Person</label>
                <input type="text" id="bm-contact-person" name="venue_contact_person" class="{{ $controlClass }} {{ $editableClass }}" readonly
                  inputmode="numeric" placeholder="--" aria-describedby="bm-err-contact-person"
                  data-booking-field="event.contact_person" data-editable>
                <small class="{{ $errorClass }}" id="bm-err-contact-person" data-field-error="venue_contact_person"></small>
                <x-admin.edited-note path="event.contact_person" />
              </div>

              <div class="{{ $fieldClass }}">
                <label for="bm-guests" class="{{ $labelClass }}">Number of Guests</label>
                <div class="grid grid-cols-2 gap-3">
                  <input type="text" id="bm-guests" name="guest_count" class="{{ $controlClass }} {{ $editableClass }}" readonly
                    inputmode="numeric" pattern="[0-9]*" placeholder="--"
                    data-booking-field="event.guests" data-editable>
                  <select name="venue_type" class="admin-field__control peer" aria-label="Venue type" disabled
                    data-booking-field="event.venue_type" data-editable>
                    <option value="">--</option>
                    @foreach ($venueTypes as $venueType)
                    <option value="{{ $venueType }}">{{ $venueType }}</option>
                    @endforeach
                  </select>
                </div>
                <small class="{{ $errorClass }}" data-field-error="guest_count"></small>
                <small class="{{ $errorClass }}" data-field-error="venue_type"></small>
                <x-admin.edited-note path="event.guests" />
                <x-admin.edited-note path="event.venue_type" />
              </div>
            </div>

            <div class="grid content-start gap-4">
              <div class="{{ $fieldClass }}">
                <label for="bm-event-date" class="{{ $labelClass }}">Event Date</label>
                <input type="text" id="bm-event-date" class="{{ $controlClass }}" readonly
                  placeholder="--" data-booking-field="event.schedule">
              </div>

              <div class="grid grid-cols-2 gap-3">
                <div class="{{ $fieldClass }}">
                  <label for="bm-start-time" class="{{ $labelClass }}">Start in</label>
                  <input type="text" id="bm-start-time" class="{{ $controlClass }}" readonly
                    placeholder="--" data-booking-field="event.start_time">
                </div>
                <div class="{{ $fieldClass }}">
                  <label for="bm-end-time" class="{{ $labelClass }}">End in</label>
                  <input type="text" id="bm-end-time" class="{{ $controlClass }}" readonly
                    placeholder="--" data-booking-field="event.end_time">
                </div>
              </div>

              <div class="{{ $fieldClass }}">
                <label for="bm-payment" class="{{ $labelClass }}">Payment</label>
                <input type="text" id="bm-payment" class="{{ $controlClass }}" readonly
                  placeholder="--" data-booking-field="payment.label">
              </div>

              <div class="{{ $fieldClass }}">
                <label for="bm-down-payment" class="{{ $labelClass }}">Down Payment Value</label>
                <input type="text" id="bm-down-payment" class="{{ $controlClass }}" readonly
                  placeholder="--" data-booking-field="payment.down_payment_label">
              </div>

              {{-- Proof of submission: the screenshot the client uploaded on the Payment step of the
                   booking flow (payment.proof_url). View-only: no name, never posted, not data-editable.
                   bookings.js shows the thumbnail (click to open it full size in a new tab) or the
                   empty text. Same field on Bookings and Booking History (this modal is shared). --}}
              <div class="{{ $fieldClass }}">
                <span id="bm-proof-label" class="{{ $labelClass }}">Proof of Submission</span>
                <div class="rounded-field border border-field-border bg-surface px-4 py-3 text-center" role="group" aria-labelledby="bm-proof-label" data-booking-proof>
                  <a class="block rounded-field focus-visible:outline-2 focus-visible:outline-offset-4 focus-visible:outline-ink" href="#" target="_blank" rel="noopener noreferrer" hidden data-proof-link>
                    <img class="block max-h-40 w-full rounded-field object-contain" alt="Proof of submission uploaded by the client" loading="lazy" data-proof-img>
                    <span class="visually-hidden">Open the proof of submission in a new tab</span>
                  </a>
                  <p class="m-0 text-fluid-md text-ink-muted" data-proof-empty>No proof uploaded</p>
                </div>
              </div>
            </div>

            {{-- Package strip: full width under both columns, next to Payment and Down Payment. --}}
            <div class="{{ $fieldClass }} min-[768px]:col-span-full">
              <span id="bm-package-label" class="{{ $labelClass }}">Package</span>
              <div class="rounded-field border border-field-border bg-surface px-4 py-3 text-center [&_p]:m-0" role="group" aria-labelledby="bm-package-label">
                <p class="text-fluid-lg" data-booking-field="package.name"></p>
                <p class="text-fluid-3xl leading-[1.2] font-medium" data-booking-field="package.price_label"></p>
                <p class="text-fluid-md font-medium text-ink-muted data-[state=paid]:text-success-text data-[state=partial]:text-warning-text data-[state=pending]:text-warning-text"
                  data-booking-field="payment.status" data-booking-state="payment.state"></p>
              </div>
            </div>
          </div>
        </section>

        {{-- The client's cancellation request and the admin's decision. Filled by
             js/admin/cancellation.js from booking.cancellation. The two buttons only appear while
             the request is pending, so the client's reason and the decision stay in one place. --}}
        <section class="reason-note" aria-labelledby="bm-cancellation-title" data-cancellation-section hidden>
          <h3 class="reason-note__label" id="bm-cancellation-title" data-cancellation-title></h3>
          <p class="reason-note__by" data-cancellation-meta></p>

          <div data-cancellation-user hidden>
            <p class="reason-note__by" data-cancellation-user-label>Client's reason</p>
            <p class="reason-note__text" data-cancellation-user-reason></p>
            <p class="reason-note__by" data-cancellation-user-date></p>
          </div>

          <div data-cancellation-admin hidden>
            <p class="reason-note__by" data-cancellation-admin-label>J&amp;G's reason</p>
            <p class="reason-note__text" data-cancellation-admin-reason></p>
            <p class="reason-note__by" data-cancellation-admin-date></p>
          </div>

          <div class="d-flex flex-wrap gap-2 mt-3" data-cancellation-actions hidden>
            <button type="button" class="admin-btn admin-btn--danger" data-approve-cancellation>
              <i class="ph ph-check" aria-hidden="true"></i>
              <span>Approve Cancellation</span>
            </button>
            <button type="button" class="admin-btn admin-btn--outline" data-decline-cancellation>
              <i class="ph ph-x" aria-hidden="true"></i>
              <span>Decline Cancellation</span>
            </button>
          </div>
        </section>

        {{-- Why it was cancelled or declined (payload.reason, from booking.status_reason). Hidden otherwise. --}}
        <section class="m-0 flex flex-col gap-1 rounded-lg border border-danger-border bg-danger-soft px-4 py-3" aria-labelledby="bm-reason-label" data-booking-reason hidden>
          <h3 class="mb-2 text-sm leading-[1.2] font-semibold text-danger-text" id="bm-reason-label" data-reason-label></h3>
          <p class="mb-4 text-sm text-ink-muted" data-reason-by></p>
          <p class="m-0 text-sm wrap:anywhere whitespace-pre-line text-ink" data-reason-text></p>
        </section>

        <p class="m-0 rounded-field border border-danger-border bg-danger-soft px-3 py-2 text-sm text-danger-text" role="alert" data-booking-error hidden></p>
      </div>

      <div class="modal-footer justify-end gap-3 border-0 px-6 pt-3 pb-6 min-[992px]:px-8 short-wide:px-6 short-wide:pb-4 max-[575.98px]:flex-col max-[575.98px]:items-stretch *:m-0 max-[575.98px]:*:w-full">
        <button type="submit" class="inline-flex min-h-12 cursor-pointer items-center justify-center rounded-panel border-0 bg-brand px-12 py-2 text-fluid-base font-semibold text-on-brand hover:bg-brand-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text" data-booking-save hidden>Save changes</button>
        <button type="button" class="inline-flex min-h-12 cursor-pointer items-center justify-center rounded-panel border border-brand-text bg-transparent px-12 py-2 text-fluid-base font-semibold text-brand-text hover:bg-brand-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text" data-bs-dismiss="modal">Back</button>
      </div>
    </form>
  </div>
</div>