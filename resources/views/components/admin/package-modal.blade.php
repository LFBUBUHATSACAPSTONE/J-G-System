{{-- One modal shared by every card AND by "Add New Package". packages.js decides the mode:
       view   (card "Edit" link)  read-only fields, Edit link + Back          -> no form post
       edit   (modal "Edit" link) unlocked fields, Cancel + Save              -> PATCH admin.packages.update
       create ("Add New Package") empty unlocked fields, Cancel + Save        -> POST  admin.packages.store
     Field names: name, price (digits only, hidden input), features (one feature per line), equipment[row_key]
     (optional text per row of config/package-equipment.php; sent as one `equipment` object, blank rows left out).
     When the server rejects the form (redirect back with $errors + old input) the modal reopens
     in edit/create mode with the old values and the messages below; see docs/admin-packages.md. --}}

@php
$reopen = $errors->any()
? ['mode' => old('_mode'), 'id' => old('_package_id'), 'old' => old()]
: null;

// Browser-side checks (packages.js). Numbers mirror the server rules; see config/admin/packages.php
// (limits) and config/package-equipment.php (max_length, yes_values, blank_words).
$validation = [
'name' => (int) config('admin.packages.limits.name', 100),
'price' => (int) config('admin.packages.limits.price', 10000000),
'features' => (int) config('admin.packages.limits.features', 2000),
'equipment' => (int) config('package-equipment.max_length', 60),
'yes' => config('package-equipment.yes_values', []),
'blank' => config('package-equipment.blank_words', []),
];
@endphp

<div
  class="modal fade admin-package-modal"
  id="packageModal"
  tabindex="-1"
  aria-labelledby="packageModalTitle"
  aria-hidden="true"
  data-store-url="{{ Route::has('admin.packages.store') ? route('admin.packages.store') : '#' }}"
  data-update-url-template="{{ Route::has('admin.packages.update') ? route('admin.packages.update', ['package' => '__ID__']) : '#' }}"
  data-validation="{{ json_encode($validation) }}"
  @if ($reopen) data-reopen="{{ json_encode($reopen) }}" @endif>
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable admin-package-modal__dialog">
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
              autocomplete="off" data-package-field="name" data-editable>
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

          {{-- Equipment: the rows of the landing Package Comparison Table (config/package-equipment.php).
               Optional. A blank row prints "—" in the table. Groups are closed in view mode and open in edit / create.
               Each row has its own error line (data-package-equipment-error); the one below the groups is for server errors
               that name no row. No maxlength on purpose: packages.js shows a message instead of cutting a paste short. --}}
          <div class="admin-package-modal__equipment" role="group" aria-labelledby="pk-equipment-label" aria-describedby="pk-equipment-hint">
            <span id="pk-equipment-label" class="admin-field__label">Equipment</span>
            <small class="admin-field__hint" id="pk-equipment-hint">Shown in the landing page's comparison table. Type "Yes" for a check mark, or a value like "2 Units". Leave blank if not included.</small>

            @foreach (config('package-equipment.sections', []) as $section)
            <details class="admin-package-modal__group" data-package-group>
              <summary class="admin-package-modal__group-title">{{ $section['title'] }}</summary>
              <div class="admin-package-modal__group-grid">
                @foreach ($section['rows'] as $rowKey => $rowLabel)
                <div class="admin-field">
                  <label for="pk-eq-{{ $rowKey }}" class="admin-field__label">{{ $rowLabel }}</label>
                  <input type="text" id="pk-eq-{{ $rowKey }}" name="equipment[{{ $rowKey }}]" class="admin-field__control" readonly
                    autocomplete="off" data-package-equipment-field="{{ $rowKey }}" data-editable>
                  <small class="admin-field__error" id="pk-eq-{{ $rowKey }}-error" data-package-equipment-error="{{ $rowKey }}" hidden></small>
                </div>
                @endforeach
              </div>
            </details>
            @endforeach

            <small class="admin-field__error" id="pk-equipment-error" data-package-error="equipment" hidden></small>
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