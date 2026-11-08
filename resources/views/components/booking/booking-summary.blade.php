{{--
  Payment step (booking flow): Booking Summary | Payment Type | Payment Form | Instructions.
  Phase 9E: styles are utilities, class strings in the PHP block below.

  Columns follow the CARD's width (container query named `summary`), not the screen's:
    < 560px      one column, stacked: Instructions first, then Summary, Payment Type, Payment Form
                 (`order-first` only; the HTML stays Summary | Payment Type | Payment Form | Instructions)
    560-1079px   two columns, Instructions first (Instructions | Summary, then Payment Type | Payment Form)
    >= 1080px    four columns in one row (Summary | Payment Type | Payment Form | Instructions)
  A reschedule renders only Summary and Payment Type, so it stays at two columns from 560px.
  The rules between the columns are written per column and per range (the PHP picks the strings by $reschedule),
  so no `:has()` and no two rules fight for the same side.

  State is read, not renamed: the JS (booking-summary.js, auth/validation.js) toggles `d-none` on the error lines, the
  down payment panel, the percent line and the proof preview; `is-filled`, `is-invalid` and `is-dragover` on the upload
  wrapper and the dropzone; `is-invalid` on the amount box; `is-low` and `is-ok` on the percent line. The utilities read
  them (`[&:not(.d-none)]:block`, `group-[.is-filled]/upload:`, `[&.is-invalid]:`), so the JS did not change.
  Payment option state is `aria-checked`, which the JS already sets.
  Hooks kept for the JS: ids, `name`s, `form`, `data-*`, `booking-summary__amount` (booking-summary.js selects it).
  The Previous / Continue row is components/booking/step-actions + step-button, shared by every step.
--}}
@php
$reschedule = $reschedule ?? null;

$stepClass = 'relative z-1 flex flex-col items-stretch gap-6';
$cardClass = 'booking-card @container/summary px-[1rem] py-6 min-[576px]:p-6';
$titleClass = 'text-fluid-booking-title font-bold [margin:0_0_1.5rem] min-[992px]:[margin:0_0_2rem]';

// Grid and column rules (see the comment above). Each column is its own container so the recap can switch layout on
// the column's own width (`@max-[360px]:`). Rules are `border-t` / `border-l` (colour on the column) with 24px of space.
$gridClass = $reschedule
? 'grid grid-cols-1 gap-6 @min-[560px]/summary:grid-cols-2'
: 'grid grid-cols-1 gap-6 @min-[560px]/summary:grid-cols-2 @min-[1080px]/summary:grid-cols-4';
$columnClass = '@container min-w-0 border-auth-border';
$summaryColClass = $columnClass.($reschedule
? ''
: ' @max-[560px]/summary:border-t @max-[560px]/summary:pt-6 @min-[560px]/summary:@max-[1080px]/summary:border-l @min-[560px]/summary:@max-[1080px]/summary:pl-6');
$paymentColClass = $columnClass.($reschedule
? ' @max-[560px]/summary:border-t @max-[560px]/summary:pt-6 @min-[560px]/summary:border-l @min-[560px]/summary:pl-6'
: ' @max-[1080px]/summary:border-t @max-[1080px]/summary:pt-6 @min-[1080px]/summary:border-l @min-[1080px]/summary:pl-6');
$formColClass = $columnClass.' @max-[1080px]/summary:border-t @max-[1080px]/summary:pt-6 @min-[560px]/summary:border-l @min-[560px]/summary:pl-6';
$guideColClass = $columnClass.' @max-[1080px]/summary:order-first @min-[1080px]/summary:border-l @min-[1080px]/summary:pl-6';

// Recap: a grid whose rows are `contents`, so every value lines up at the same x across all rows and dividers.
// In a narrow column (under 360px) the label sits above its value. The colon after the label is the ::after.
$receiptClass = 'grid grid-cols-[max-content_minmax(0,1fr)] items-baseline gap-x-6 gap-y-3 @max-[360px]:grid-cols-[minmax(0,1fr)]';
$rowClass = 'contents @max-[360px]:flex @max-[360px]:flex-col @max-[360px]:gap-1';
$labelClass = "[grid-column:1] text-[0.85rem] font-semibold whitespace-nowrap text-auth-text-muted after:content-[':'] @max-[360px]:[grid-column:auto] @max-[360px]:whitespace-normal";
$valueClass = '[grid-column:2] text-left text-fluid-sm font-semibold wrap-anywhere text-auth-text @max-[360px]:[grid-column:auto]';
$sectionClass = 'col-span-full text-fluid-base font-bold text-auth-accent [margin:0]';
$dividerClass = 'col-span-full h-px bg-auth-border [margin:0.25rem_0]';

// Panels inside the Payment, Payment Form and Instructions columns.
$panelGridClass = 'flex flex-col gap-6';
$panelClass = 'min-w-0 rounded-panel bg-auth-field-bg p-[1rem] [border:1px_solid_var(--color-auth-border)]';
$panelHeadingClass = 'text-fluid-base font-bold text-auth-accent [margin:0_0_1rem]';
// Each hint stays close to its button (4px above), 16px below; the last one in its box has no bottom margin.
$hintClass = 'text-[0.78rem] leading-normal text-auth-text-faint [margin:0.25rem_0_1rem] last:mb-[0]';
$optionGroupClass = 'flex flex-col gap-1';
$optionBtnClass = 'w-full cursor-pointer rounded-field bg-auth-surface px-[1rem] py-[0.75rem] text-center font-bold text-auth-text transition-[border-color,background-color] duration-150 ease-in-out [border:1px_solid_var(--color-auth-border)] hover:border-auth-accent aria-checked:border-brand aria-checked:bg-brand focus-visible:shadow-[0_0_0_3px_var(--color-auth-focus-soft)] focus-visible:outline-none';
$reminderClass = 'list-disc pl-[1rem] text-[0.78rem] leading-[1.6] text-auth-text-muted [margin:1.5rem_0_0] [&_li+li]:mt-1 [&_strong]:text-auth-accent';

// Down payment amount (numbers only). The box is the border; the prefix and the input sit inside it, so the focus ring
// and the invalid border belong to the box. `booking-summary__amount` stays as a hook (the JS adds `is-invalid` to it).
$downPaymentClass = 'mt-[1rem]';
$inputLabelClass = 'mb-2 block text-[0.85rem] font-semibold text-auth-text-muted';
$amountClass = 'flex items-stretch overflow-hidden rounded-field bg-auth-surface transition-[border-color] duration-150 ease-in-out [border:1px_solid_var(--color-auth-border)] focus-within:border-auth-accent focus-within:shadow-[0_0_0_3px_var(--color-auth-focus-soft)] [&.is-invalid]:border-auth-danger [&.is-invalid]:focus-within:border-auth-danger';
$amountPrefixClass = 'flex items-center px-[0.75rem] font-bold text-auth-text-muted [border-right:1px_solid_var(--color-auth-border)]';
$amountInputClass = 'autofill-dark min-w-0 flex-1 bg-transparent px-[1rem] py-[0.75rem] font-semibold text-auth-text [border:0] [--autofill-bg:var(--color-auth-surface)] placeholder:text-auth-text-faint focus:outline-none';
$percentClass = 'mt-1 text-[0.8rem] font-semibold text-auth-text-muted [&:not(.d-none)]:block [&.is-low]:text-auth-danger [&.is-ok]:text-auth-accent';

// How-to-pay steps: the browser's own numbers ("1. ") in the accent colour, inside the list so a wrapped line goes
// back under the number, as the old counter did.
$instructionsClass = 'list-inside list-decimal p-[0] text-[0.78rem] leading-[1.6] text-auth-text-muted [margin:0] marker:font-bold marker:text-auth-accent [&>li+li]:mt-3 [&_strong]:font-bold [&_strong]:text-auth-accent';

// Link to the payment details form: 44px tall, 8px by 16px padding, the brand fill.
$linkBtnClass = 'inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-field bg-brand px-[1rem] py-2 text-center font-bold text-auth-text no-underline transition-[filter] duration-150 ease-in-out [border:1px_solid_var(--color-brand)] hover:brightness-110 focus-visible:shadow-[0_0_0_3px_var(--color-auth-focus-soft)] focus-visible:outline-none';

// Proof upload. The real file input is transparent and covers the dropzone (it stays focusable); the dropzone is its
// label. The wrapper (group/upload) carries `is-filled` (a file is chosen: preview replaces the dropzone) and
// `is-invalid`; the input is the peer (peer/proof) for the keyboard focus ring on the dropzone.
// Invalid beats hover and dragover (twin variants); keyboard focus beats invalid.
$uploadClass = 'group/upload relative';
$fileInputClass = 'peer/proof absolute inset-0 size-full cursor-pointer opacity-0 group-[.is-filled]/upload:hidden';
$dropzoneClass = 'flex cursor-pointer flex-col items-center gap-1 rounded-field bg-auth-surface px-[1rem] py-6 text-center transition-[border-color,background-color] duration-150 ease-in-out [border:1px_dashed_var(--color-auth-border)] hover:border-auth-accent [&.is-dragover]:border-auth-accent peer-focus-visible/proof:border-auth-accent peer-focus-visible/proof:shadow-[0_0_0_3px_var(--color-auth-focus-soft)] group-[.is-invalid]/upload:border-auth-danger group-[.is-invalid]/upload:hover:border-auth-danger group-[.is-invalid]/upload:[&.is-dragover]:border-auth-danger group-[.is-invalid]/upload:peer-focus-visible/proof:border-auth-accent group-[.is-filled]/upload:hidden';
$dropzoneTitleClass = 'font-bold text-auth-text';
$dropzoneHintClass = 'text-[0.78rem] text-auth-text-faint';
// The preview is shown by the JS removing `d-none`, so it carries no plain display utility.
$previewClass = 'flex-col gap-[0.75rem] rounded-field bg-auth-surface p-[0.75rem] [border:1px_solid_var(--color-auth-border)] [&:not(.d-none)]:flex';
$previewImgClass = 'block max-h-60 w-full rounded-field object-contain';
$previewMetaClass = 'flex min-w-0 items-center justify-between gap-[0.75rem]';
$previewNameClass = 'min-w-0 truncate text-[0.85rem] font-semibold text-auth-text-muted';
$previewRemoveClass = 'flex-none cursor-pointer rounded-field bg-transparent px-[0.75rem] py-1 text-[0.85rem] font-semibold text-auth-text [border:1px_solid_var(--color-auth-border)] hover:border-auth-danger hover:text-auth-danger focus-visible:shadow-[0_0_0_3px_var(--color-auth-focus-soft)] focus-visible:outline-none';

// <small> is inline: shown with block, hidden by the JS's d-none (a plain `block` would beat d-none).
  $fieldErrorClass = 'mt-1 text-[0.78rem] text-auth-danger [&:not(.d-none)]:block';
  // The banner under the form keeps the 16px above and the 16px below (the reboot's paragraph margin) it always had.
  $bannerErrorClass = 'text-[0.85rem] text-auth-banner-error [margin:1rem_0]';
  @endphp
  <div class="{{ $stepClass }}">
    <div class="{{ $cardClass }}">
      <div class="{{ $gridClass }}">

        {{-- First column: read-only recap of every prior step --}}
        <div class="{{ $summaryColClass }}">
          <h2 class="{{ $titleClass }}">Booking Summary</h2>

          <div class="{{ $receiptClass }}">
            @unless ($reschedule)
            <div class="{{ $rowClass }}">
              <span class="{{ $labelClass }}">Booking Reference</span>
              <span class="{{ $valueClass }}">#{{ $bookingReference ?? '' }}</span>
            </div>
            @endunless

            <h3 class="{{ $sectionClass }}">Personal Information</h3>

            <div class="{{ $rowClass }}">
              <span class="{{ $labelClass }}">Full Name</span>
              <span class="{{ $valueClass }}" data-booking-summary="fullName">{{ $fullName ?? '' }}</span>
            </div>

            <div class="{{ $rowClass }}">
              <span class="{{ $labelClass }}">Email</span>
              <span class="{{ $valueClass }}" data-booking-summary="email">{{ $email ?? '' }}</span>
            </div>

            <div class="{{ $rowClass }}">
              <span class="{{ $labelClass }}">Contact Number</span>
              <span class="{{ $valueClass }}" data-booking-summary="contactNumber">{{ $contactNumber ?? '' }}</span>
            </div>

            <div class="{{ $dividerClass }}" role="separator"></div>

            <h3 class="{{ $sectionClass }}">Selected Package</h3>

            <div class="{{ $rowClass }}">
              <span class="{{ $labelClass }}">Package</span>
              <span class="{{ $valueClass }}" data-booking-summary="packageName">{{ $packageName ?? '' }}</span>
            </div>

            <div class="{{ $rowClass }}">
              <span class="{{ $labelClass }}">Package Price</span>
              <span class="{{ $valueClass }}" data-booking-summary="packagePrice">Php {{ number_format((float) ($packageCost ?? 0), 2) }}</span>
            </div>

            <div class="{{ $dividerClass }}" role="separator"></div>

            <h3 class="{{ $sectionClass }}">Event Details</h3>

            <div class="{{ $rowClass }}">
              <span class="{{ $labelClass }}">Event Name</span>
              <span class="{{ $valueClass }}" data-booking-summary="eventName">{{ $eventName ?? '' }}</span>
            </div>

            <div class="{{ $rowClass }}">
              <span class="{{ $labelClass }}">Event Type</span>
              <span class="{{ $valueClass }}" data-booking-summary="eventType">{{ $eventType ?? '' }}</span>
            </div>

            <div class="{{ $rowClass }}">
              <span class="{{ $labelClass }}">Event Location</span>
              <span class="{{ $valueClass }}" data-booking-summary="eventLocation">{{ $eventLocation ?? '' }}</span>
            </div>

            <div class="{{ $rowClass }}">
              <span class="{{ $labelClass }}">Event Contact Person</span>
              <span class="{{ $valueClass }}" data-booking-summary="eventContactPerson">{{ $eventContactPerson ?? '' }}</span>
            </div>

            <div class="{{ $dividerClass }}" role="separator"></div>

            <h3 class="{{ $sectionClass }}">Schedule</h3>

            <div class="{{ $rowClass }}">
              <span class="{{ $labelClass }}">Event Date</span>
              <span class="{{ $valueClass }}" data-booking-summary="eventDate">{{ $eventDate ?? '' }}</span>
            </div>

            <div class="{{ $rowClass }}">
              <span class="{{ $labelClass }}">Start In</span>
              <span class="{{ $valueClass }}" data-booking-summary="startTime">{{ $startTime ?? '' }}</span>
            </div>

            <div class="{{ $rowClass }}">
              <span class="{{ $labelClass }}">End In</span>
              <span class="{{ $valueClass }}" data-booking-summary="endTime">{{ $endTime ?? '' }}</span>
            </div>
          </div>
        </div>

        {{-- Second column: payment type (option + down payment amount) --}}
        <div class="{{ $paymentColClass }}">
          <h2 class="{{ $titleClass }}">Payment Type</h2>

          <form
            id="payment-form"
            @if ($reschedule) data-reschedule-id="{{ $reschedule['booking_id'] }}" @endif
            data-package-cost="{{ $packageCost ?? 0 }}"
            method="POST"
            action="{{ route('booking.booking-summary') }}"
            novalidate>
            @csrf
            <input type="hidden" name="payment_option" data-payment-option-input>

            <div class="{{ $panelGridClass }}">

              {{-- Payment Option (a reschedule carries the original payment over instead) --}}
              @if ($reschedule)
              <div class="{{ $panelClass }}" data-payment-carried-over>
                <h3 class="{{ $panelHeadingClass }}">Payment carried over from {{ $reschedule['reference'] }}</h3>
                <p class="{{ $hintClass }}">The payment from your cancelled booking applies to this reschedule, so there is nothing to pay again. After you confirm, the booking goes back to pending until the admin approves the new dates.</p>
              </div>
              @else
              <div class="{{ $panelClass }}">
                <h3 class="{{ $panelHeadingClass }}">Payment Option</h3>

                <div class="{{ $optionGroupClass }}" role="radiogroup" aria-label="Payment option" data-payment-option-group>
                  <button
                    type="button"
                    class="{{ $optionBtnClass }}"
                    role="radio"
                    aria-checked="false"
                    data-payment-option="full">
                    Full Payment
                  </button>
                  <p class="{{ $hintClass }}">Choose this if you have paid the full package price. The booking remains pending until reviewed.</p>

                  <button
                    type="button"
                    class="{{ $optionBtnClass }}"
                    role="radio"
                    aria-checked="false"
                    data-payment-option="down">
                    Down Payment
                  </button>
                  <p class="{{ $hintClass }}">Choose this if you have paid at least 30% of the package price; the remaining balance can be settled before or on the event day.</p>
                </div>

                {{-- Shown by booking-summary.js only while Down Payment is selected --}}
                <div class="{{ $downPaymentClass }} d-none" data-down-payment-panel>
                  <label class="{{ $inputLabelClass }}" for="down-payment-amount">Down payment amount</label>
                  <div class="booking-summary__amount {{ $amountClass }}">
                    <span class="{{ $amountPrefixClass }}" aria-hidden="true">Php</span>
                    <input
                      id="down-payment-amount"
                      class="{{ $amountInputClass }}"
                      type="text"
                      inputmode="decimal"
                      autocomplete="off"
                      placeholder="0.00"
                      name="down_payment_amount"
                      aria-describedby="down-payment-help down-payment-error"
                      data-down-payment-input>
                  </div>
                  <small class="{{ $percentClass }} d-none" aria-live="polite" data-down-payment-percent></small>
                  <small id="down-payment-error" class="{{ $fieldErrorClass }} d-none" role="alert" data-field-error="down_payment_amount"></small>
                  <p id="down-payment-help" class="{{ $hintClass }}" data-down-payment-help>Enter at least 30% of your selected package cost. Numbers only.</p>
                </div>

                <small class="{{ $fieldErrorClass }} d-none" data-field-error="payment_option"></small>

                <ul class="{{ $reminderClass }}">
                  <li><strong>Reminder:</strong> A down payment does not cover the full cost. The remaining balance must be settled before or on the event day.</li>
                  <li>Your booking and payment amount are recorded for admin review.</li>
                </ul>
              </div>
              @endif
            </div>

            <p class="{{ $bannerErrorClass }} d-none" role="alert" data-payment-error></p>
          </form>
        </div>

        {{-- Third column: payment details form link + proof upload (not shown when rescheduling).
           The file input lives here but belongs to #payment-form through its form="payment-form" attribute. --}}
        @unless ($reschedule)
        <div class="{{ $formColClass }}">
          <h2 class="{{ $titleClass }}">Payment Form</h2>

          <div class="{{ $panelGridClass }}">
            {{-- Link to the payment details form (Google Form). The user opens it in a new tab, fills it out and submits it,
               then comes back to upload a screenshot of the confirmation page. --}}
            <div class="{{ $panelClass }}">
              <h3 class="{{ $panelHeadingClass }}">Payment Details Form</h3>
              <p class="{{ $hintClass }}">Open the form in a new tab, complete it, then come back to upload your proof.</p>
              <a
                class="{{ $linkBtnClass }}"
                href="https://docs.google.com/forms/d/e/1FAIpQLSe8usIS2x0O5BrqwKlnwIZknFrNpmkSBuut9KA5a9Gn2IYvBw/viewform"
                target="_blank"
                rel="noopener noreferrer">
                Open payment details form
                <span class="sr-only">(opens in a new tab)</span>
              </a>
            </div>

            {{-- Proof that the payment details form was submitted (screenshot of the confirmation).
               Sent with the form as `payment_proof`; shown only for a new payment, not a reschedule. --}}
            <div class="{{ $panelClass }}">
              <h3 class="{{ $panelHeadingClass }}">Upload Proof of Submission</h3>
              <p class="{{ $hintClass }}">After you submit the payment details form, upload a screenshot of its confirmation page. JPG, PNG or WebP, up to 5 MB.</p>

              <div class="{{ $uploadClass }}" data-proof-upload>
                <input
                  id="payment-proof"
                  form="payment-form"
                  class="{{ $fileInputClass }}"
                  type="file"
                  name="payment_proof"
                  accept="image/jpeg,image/png,image/webp"
                  aria-describedby="payment-proof-error"
                  data-proof-input>

                <label class="{{ $dropzoneClass }}" for="payment-proof" data-proof-dropzone>
                  <span class="{{ $dropzoneTitleClass }}">Choose an image</span>
                  <span class="{{ $dropzoneHintClass }}">or drag and drop it here</span>
                </label>

                <div class="{{ $previewClass }} d-none" data-proof-preview>
                  <img class="{{ $previewImgClass }}" alt="Preview of your uploaded proof of submission" data-proof-image>
                  <div class="{{ $previewMetaClass }}">
                    <span class="{{ $previewNameClass }}" data-proof-name></span>
                    <button type="button" class="{{ $previewRemoveClass }}" data-proof-remove>Remove</button>
                  </div>
                </div>
              </div>

              <small id="payment-proof-error" class="{{ $fieldErrorClass }} d-none" role="alert" data-field-error="payment_proof"></small>
            </div>
          </div>
        </div>
        @endunless

        {{-- Fourth column: how to complete this step (not shown when rescheduling, the payment is carried over) --}}
        @unless ($reschedule)
        <div class="{{ $guideColClass }}">
          <h2 class="{{ $titleClass }}">Instructions</h2>

          <div class="{{ $panelGridClass }}">
            <div class="{{ $panelClass }}">
              <h3 class="{{ $panelHeadingClass }}">How to pay</h3>
              <ol class="{{ $instructionsClass }}">
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

    <x-booking.step-actions>
      <x-booking.step-button variant="cancel" data-booking-previous>Previous</x-booking.step-button>
      @if ($reschedule)
      <x-booking.step-button type="submit" form="payment-form">Confirm Reschedule</x-booking.step-button>
      @else
      <x-booking.step-button type="submit" form="payment-form">Continue</x-booking.step-button>
      @endif
    </x-booking.step-actions>
  </div>
