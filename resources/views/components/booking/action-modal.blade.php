{{-- One confirmation modal for booking actions, shared by the admin Bookings page (Approve, Decline,
     Verify Payment, Cancel) and the client's My Bookings (Cancel booking). The look is the same on both.

     The markup is generic: resources/js/shared/action-modal.js (openActionModal) fills the title,
     message, button label and colour each time it opens, so one modal serves every action.

       id      DOM id of the modal (one per page)
       reason  true = also show the required "reason" box (Decline / Cancel). Min and max length come
               from config/cancellation.php, the same numbers the controllers validate with.

     Nothing here posts anywhere: the caller's onConfirm does the request, and the modal stays open
     with the server's message if it fails. --}}
@props(['id' => 'actionModal', 'reason' => false])

@php
$min = (int) config('cancellation.min', 10);
$max = (int) config('cancellation.max', 500);
@endphp

<div
  class="modal fade action-modal"
  id="{{ $id }}"
  tabindex="-1"
  aria-labelledby="{{ $id }}Title"
  aria-hidden="true"
  data-action-modal
  data-min="{{ $min }}"
  data-max="{{ $max }}">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content action-modal__card">
      <div class="modal-header action-modal__header">
        <h2 class="action-modal__title" id="{{ $id }}Title" data-action-title>Are you sure?</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body action-modal__body">
        <p class="action-modal__message" data-action-message></p>

        @if ($reason)
        <div class="action-modal__field" data-action-reason-field>
          <label for="{{ $id }}Reason" class="action-modal__label">
            <span data-action-reason-label>Reason</span>
            <span class="action-modal__required" aria-hidden="true">*</span>
          </label>
          <textarea
            id="{{ $id }}Reason"
            class="action-modal__control"
            rows="4"
            maxlength="{{ $max }}"
            required
            aria-describedby="{{ $id }}ReasonHint {{ $id }}ReasonError"
            data-action-reason></textarea>
          <div class="action-modal__meta">
            <small id="{{ $id }}ReasonHint" class="action-modal__hint" data-action-reason-hint>At least {{ $min }} characters.</small>
            <small class="action-modal__count" aria-hidden="true" data-action-count>0/{{ $max }}</small>
          </div>
          <small id="{{ $id }}ReasonError" class="action-modal__error d-none" role="alert" data-action-reason-error></small>
        </div>
        @endif

        <p class="action-modal__error action-modal__error--block d-none" role="alert" data-action-error></p>
      </div>

      <div class="modal-footer action-modal__footer">
        <button type="button" class="action-modal__btn action-modal__btn--secondary" data-bs-dismiss="modal" data-action-back>Back</button>
        <button type="button" class="action-modal__btn action-modal__btn--brand" data-action-confirm>Confirm</button>
      </div>
    </div>
  </div>
</div>