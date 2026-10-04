{{-- One modal shared by every card AND by "Add New Package". packages.js decides the mode:
       view   (card "Edit" link)  read-only fields, Edit link + Back          -> no form post
       edit   (modal "Edit" link) unlocked fields, Cancel + Save              -> PATCH admin.packages.update
       create ("Add New Package") empty unlocked fields, Cancel + Save        -> POST  admin.packages.store
     Field names: name, price (digits only, hidden input), features (one feature per line).
     When the server rejects the form (redirect back with $errors + old input) the modal reopens
     in edit/create mode with the old values and the messages below; see docs/admin-packages.md. --}}
     
@php
$reopen = $errors->any()
? ['mode' => old('_mode'), 'id' => old('_package_id'), 'old' => old()]
: null;
@endphp

<div
  class="modal fade admin-package-modal"
  id="packageModal"
  tabindex="-1"
  aria-labelledby="packageModalTitle"
  aria-hidden="true"
  data-store-url="{{ Route::has('admin.packages.store') ? route('admin.packages.store') : '#' }}"
  data-update-url-template="{{ Route::has('admin.packages.update') ? route('admin.packages.update', ['package' => '__ID__']) : '#' }}"
  @if ($reopen) data-reopen="{{ json_encode($reopen) }}" @endif>
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <form class="modal-content admin-package-modal__card" method="POST" action="#" novalidate data-package-form>
      @csrf
      <input type="hidden" name="_method" value="PATCH" data-package-method disabled>
      <input type="hidden" name="_mode" value="" data-package-mode>
      <input type="hidden" name="_package_id" value="" data-package-id>

      <div class="modal-body admin-package-modal__body">
        <div class="admin-package-modal__head">
          <h2 id="packageModalTitle" class="admin-package-modal__heading" data-package-title>Package Information</h2>
          <button type="button" class="admin-btn admin-btn--outline admin-btn--sm" data-package-edit>
            <i class="ph ph-pencil-simple" aria-hidden="true"></i>
            <span>Edit</span>
          </button>
        </div>

        <div class="admin-package-modal__panel">
          <div class="admin-field">
            <label for="pk-name" class="admin-field__label">Package Name</label>
            <input type="text" id="pk-name" name="name" class="admin-field__control" readonly
              maxlength="100" autocomplete="off" data-package-field="name" data-editable>
            <small class="admin-field__error" id="pk-name-error" data-package-error="name"
              @unless ($errors->has('name')) hidden @endunless>{{ $errors->first('name') }}</small>
          </div>

          <div class="admin-field">
            <label for="pk-price" class="admin-field__label">Package Price</label>
            {{-- Visible box shows "Php 5,000"; the hidden input is what gets submitted (digits only). --}}
            <input type="text" id="pk-price" class="admin-field__control" readonly
              inputmode="numeric" autocomplete="off" data-package-price data-editable>
            <input type="hidden" name="price" data-package-field="price">
            <small class="admin-field__error" id="pk-price-error" data-package-error="price"
              @unless ($errors->has('price')) hidden @endunless>{{ $errors->first('price') }}</small>
          </div>

          <div class="admin-field">
            <span id="pk-features-label" class="admin-field__label">Package Description</span>
            {{-- View mode shows the bulleted list; edit/create mode swaps in the textarea. --}}
            <ul class="admin-package-modal__features" aria-labelledby="pk-features-label" data-package-features-view></ul>
            <textarea id="pk-features" name="features" class="admin-field__control admin-package-modal__textarea"
              rows="6" aria-labelledby="pk-features-label" aria-describedby="pk-features-hint"
              data-package-field="features" data-editable hidden></textarea>
            <small class="admin-field__hint" id="pk-features-hint" data-package-hint hidden>One feature per line.</small>
            <small class="admin-field__error" id="pk-features-error" data-package-error="features"
              @unless ($errors->has('features')) hidden @endunless>{{ $errors->first('features') }}</small>
          </div>
        </div>
      </div>

      <div class="modal-footer admin-package-modal__footer">
        <button type="button" class="admin-btn admin-btn--brand" data-bs-dismiss="modal" data-package-back>Back</button>
        <button type="button" class="admin-btn admin-btn--light" data-package-cancel hidden>Cancel</button>
        <button type="submit" class="admin-btn admin-btn--brand" data-package-save hidden>Save</button>
      </div>
    </form>
  </div>
</div>