{{-- Delete Account confirmation. Opened by the sidebar's Delete Account tab (config/account.php -> nav).
     Copy comes from config/account.php -> delete. delete.js unlocks the button once the user types the
     confirm word, then sends DELETE to `user.account.destroy`. That route does not exist yet, so the URL
     below is empty and the page only shows a "not connected" notice: no request is sent. --}}
@php
$delete = config('account.delete', []);
$confirmWord = $delete['confirm_word'] ?? 'DELETE';
$statusClass = 'm-0 rounded-panel border border-success-border bg-success-soft px-4 py-3 text-fluid-md font-medium text-success-text [&.is-error]:border-danger-border [&.is-error]:bg-danger-soft [&.is-error]:text-danger-text d-none';
@endphp

<div
  class="modal fade font-sans"
  id="deleteAccountModal"
  tabindex="-1"
  aria-labelledby="deleteAccountTitle"
  aria-hidden="true"
  data-delete-account
  data-delete-url="{{ Route::has('user.account.destroy') ? route('user.account.destroy') : '' }}"
  data-redirect-url="{{ Route::has('user.landing') ? route('user.landing') : '/' }}"
  data-confirm-word="{{ $confirmWord }}">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content rounded-card border-0 bg-surface text-ink">
      <div class="modal-header items-start border-0 px-6 pt-6 pb-4">
        <h2 class="m-0 text-fluid-xl font-semibold [overflow-wrap:anywhere]" id="deleteAccountTitle">Delete your account?</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body flex flex-col gap-6 px-6 py-0">
        <p class="m-0 flex items-start gap-3 rounded-panel bg-danger-soft p-4 text-fluid-sm text-danger-text [&_i]:flex-none [&_i]:text-fluid-xl [&_i]:leading-none" role="note">
          <i class="ph ph-warning" aria-hidden="true"></i>
          <span>{{ $delete['warning'] ?? 'Deleting your account is permanent.' }}</span>
        </p>

        @if (! empty($delete['items']))
        <ul class="m-0 flex list-none flex-col gap-3 p-0 text-fluid-sm [&_i]:flex-none [&_i]:text-fluid-heading [&_i]:leading-[1.4] [&_i]:text-ink-muted [&_li]:flex [&_li]:items-start [&_li]:gap-3 [&_li]:[overflow-wrap:anywhere]">
          @foreach ($delete['items'] as $item)
          <li>
            <i class="ph ph-{{ $item['icon'] }}" aria-hidden="true"></i>
            <span>{{ $item['text'] }}</span>
          </li>
          @endforeach
        </ul>
        @endif

        <div class="grid min-w-0 gap-1">
          <label class="text-fluid-md text-brand-text" for="delete-account-confirm">Type <strong>{{ $confirmWord }}</strong> to confirm</label>
          <input
            class="min-h-11 w-full rounded-field border border-field-border bg-surface px-3 py-2 text-fluid-base text-ink placeholder:text-ink-subtle focus-visible:ring-4 focus-visible:ring-brand-halo focus-visible:outline-2 focus-visible:outline-offset-0 focus-visible:outline-brand-text [&.is-invalid]:border-danger-border"
            type="text"
            id="delete-account-confirm"
            autocomplete="off"
            autocapitalize="characters"
            spellcheck="false"
            placeholder="{{ $confirmWord }}"
            data-delete-input>
        </div>

        <p class="{{ $statusClass }}" role="alert" data-delete-message></p>
      </div>

      <div class="modal-footer flex flex-col-reverse gap-3 border-0 px-6 pt-3 pb-6 min-[576px]:flex-row min-[576px]:justify-end [&>*]:m-0 [&>*]:w-full min-[576px]:[&>*]:w-auto">
        <x-account.button variant="secondary" data-bs-dismiss="modal">Keep my account</x-account.button>
        <x-account.button variant="danger" data-delete-confirm disabled>Delete my account</x-account.button>
      </div>
    </div>
  </div>
</div>