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
  class="modal fade"
  id="{{ $id }}"
  tabindex="-1"
  aria-labelledby="{{ $id }}Title"
  aria-hidden="true"
  data-action-modal
  data-min="{{ $min }}"
  data-max="{{ $max }}">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-2xl border-0 bg-surface font-sans text-ink">
      <div class="modal-header items-start gap-4 border-0 px-6 pt-6 pb-0">
        <h2 class="m-0 text-[clamp(1.149rem,1.1123rem+0.1836vw,1.25rem)] leading-[1.3] font-semibold text-ink" id="{{ $id }}Title" data-action-title>Are you sure?</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body flex flex-col gap-4 px-6 py-4">
        <p class="m-0 text-sm wrap-anywhere text-ink-muted" data-action-message></p>

        @if ($reason)
        <div class="flex flex-col gap-2" data-action-reason-field>
          <label for="{{ $id }}Reason" class="text-sm font-semibold text-ink">
            <span data-action-reason-label>Reason</span>
            <span class="ml-1 text-danger-text" aria-hidden="true">*</span>
          </label>
          <textarea
            id="{{ $id }}Reason"
            class="min-h-24 w-full resize-y rounded-lg border border-field-border bg-panel px-4 py-3 text-sm text-ink placeholder:text-ink-subtle focus-visible:ring-4 focus-visible:ring-brand-halo focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text aria-invalid:border-danger-border aria-invalid:bg-danger-soft"
            rows="4"
            maxlength="{{ $max }}"
            required
            aria-describedby="{{ $id }}ReasonHint {{ $id }}ReasonError"
            data-action-reason></textarea>
          <div class="flex justify-between gap-4">
            <small id="{{ $id }}ReasonHint" class="text-sm text-ink-subtle" data-action-reason-hint>At least {{ $min }} characters.</small>
            <small class="flex-none text-sm text-ink-subtle" aria-hidden="true" data-action-count>0/{{ $max }}</small>
          </div>
          <small id="{{ $id }}ReasonError" class="hidden text-sm text-danger-text" role="alert" data-action-reason-error></small>
        </div>
        @endif

        <p class="m-0 hidden rounded-lg border border-danger-border bg-danger-soft px-3 py-2 text-sm text-danger-text" role="alert" data-action-error></p>
      </div>

      <div class="modal-footer flex-wrap gap-2 border-0 px-6 pt-0 pb-6 max-[575.98px]:flex-col-reverse max-[575.98px]:items-stretch">
        <button type="button" class="m-0 cursor-pointer rounded-lg border border-field-border bg-transparent px-4 py-2 text-sm font-semibold text-ink transition-colors duration-150 ease-in focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text enabled:hover:bg-panel disabled:cursor-not-allowed disabled:opacity-60" data-bs-dismiss="modal" data-action-back>Back</button>
        <button type="button" class="m-0 cursor-pointer rounded-lg border border-transparent bg-brand px-4 py-2 text-sm font-semibold text-white transition-colors duration-150 ease-in focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text enabled:hover:bg-brand-hover disabled:cursor-not-allowed disabled:opacity-60" data-action-confirm>Confirm</button>
      </div>
    </div>
  </div>
</div>