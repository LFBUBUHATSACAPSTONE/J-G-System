{{-- Delete Account confirmation. Opened by the sidebar's Delete Account tab (config/account.php -> nav).
     Copy comes from config/account.php -> delete. delete.js unlocks the button once the user types the
     confirm word, then sends DELETE to `user.account.destroy`. That route does not exist yet, so the URL
     below is empty and the page only shows a "not connected" notice: no request is sent. --}}
@php
$delete = config('account.delete', []);
$confirmWord = $delete['confirm_word'] ?? 'DELETE';
@endphp

<div
  class="modal fade account-modal"
  id="deleteAccountModal"
  tabindex="-1"
  aria-labelledby="deleteAccountTitle"
  aria-hidden="true"
  data-delete-account
  data-delete-url="{{ Route::has('user.account.destroy') ? route('user.account.destroy') : '' }}"
  data-redirect-url="{{ Route::has('user.landing') ? route('user.landing') : '/' }}"
  data-confirm-word="{{ $confirmWord }}">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content">
      <div class="modal-header account-modal__header">
        <h2 class="account-modal__title" id="deleteAccountTitle">Delete your account?</h2>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>

      <div class="modal-body account-modal__body">
        <p class="account-delete__warning" role="note">
          <i class="ph ph-warning" aria-hidden="true"></i>
          <span>{{ $delete['warning'] ?? 'Deleting your account is permanent.' }}</span>
        </p>

        @if (! empty($delete['items']))
        <ul class="account-delete__list">
          @foreach ($delete['items'] as $item)
          <li>
            <i class="ph ph-{{ $item['icon'] }}" aria-hidden="true"></i>
            <span>{{ $item['text'] }}</span>
          </li>
          @endforeach
        </ul>
        @endif

        <div class="account-form__field">
          <label for="delete-account-confirm">Type <strong>{{ $confirmWord }}</strong> to confirm</label>
          <input
            type="text"
            id="delete-account-confirm"
            autocomplete="off"
            autocapitalize="characters"
            spellcheck="false"
            placeholder="{{ $confirmWord }}"
            data-delete-input>
        </div>

        <p class="account-form__status d-none" role="alert" data-delete-message></p>
      </div>

      <div class="modal-footer account-modal__footer">
        <button type="button" class="account-btn account-btn--secondary" data-bs-dismiss="modal">Keep my account</button>
        <button type="button" class="account-btn account-btn--danger" data-delete-confirm disabled>Delete my account</button>
      </div>
    </div>
  </div>
</div>