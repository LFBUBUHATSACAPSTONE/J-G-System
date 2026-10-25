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
// Tailwind class strings for the fields (kept whole so Tailwind can find them). The inputs are all
// data-editable: while the modal is editing (is-editing, toggled by packages.js) they get the purple border.
$labelClass = 'text-fluid-md text-brand-text';
$controlClass = 'min-h-10 w-full appearance-none rounded-field border border-field-border bg-surface px-3 py-2 text-fluid-md text-ink placeholder:text-ink-subtle placeholder:opacity-100 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text aria-invalid:border-danger-border group-[.is-editing]/pkg:border-brand-text!';
$textareaClass = str_replace('min-h-10', 'min-h-36', $controlClass) . ' resize-y';
$errorClass = 'text-[0.85rem] font-medium text-danger-text';
$hintClass = 'text-[0.85rem] text-ink-muted';
@endphp

<div
  class="modal fade admin-package-modal group/pkg [[hidden]]:hidden!"
  id="packageModal"
  tabindex="-1"
  aria-labelledby="packageModalTitle"
  aria-hidden="true"
  data-store-url="{{ Route::has('admin.packages.store') ? route('admin.packages.store') : '#' }}"
  data-update-url-template="{{ Route::has('admin.packages.update') ? route('admin.packages.update', ['package' => '__ID__']) : '#' }}"
  data-validation="{{ json_encode($validation) }}"
  @if ($reopen) data-reopen="{{ json_encode($reopen) }}" @endif>
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable min-[576px]:[--bs-modal-width:44rem]">
    <form class="modal-content rounded-card border-0 bg-surface font-sans text-ink" method="POST" action="#" novalidate data-package-form>
      @csrf
      <input type="hidden" name="_method" value="PATCH" data-package-method disabled>
      <input type="hidden" name="_mode" value="" data-package-mode>
      <input type="hidden" name="_package_id" value="" data-package-id>

      <div class="modal-body px-6 pt-6 pb-4">
        <div class="mb-4 flex items-baseline justify-between gap-4">
          <h2 id="packageModalTitle" class="m-0 text-fluid-xl font-bold" data-package-title>Package Information</h2>
          <button type="button" class="admin-btn admin-btn--outline admin-btn--sm" data-package-edit>
            <i class="ph ph-pencil-simple" aria-hidden="true"></i>
            <span>Edit</span>
          </button>
        </div>

        <div class="grid gap-4 rounded-card bg-panel p-6">
          <div class="grid min-w-0 gap-1">
            <label for="pk-name" class="{{ $labelClass }}">Package Name</label>
            <input type="text" id="pk-name" name="name" class="{{ $controlClass }}" readonly
              autocomplete="off" data-package-field="name" data-editable>
            <small class="{{ $errorClass }}" id="pk-name-error" data-package-error="name"
              @unless ($errors->has('name')) hidden @endunless>{{ $errors->first('name') }}</small>
          </div>

          <div class="grid min-w-0 gap-1">
            <label for="pk-price" class="{{ $labelClass }}">Package Price</label>
            {{-- Visible box shows "Php 5,000"; the hidden input is what gets submitted (digits only). --}}
            <input type="text" id="pk-price" class="{{ $controlClass }}" readonly
              inputmode="numeric" autocomplete="off" data-package-price data-editable>
            <input type="hidden" name="price" data-package-field="price">
            <small class="{{ $errorClass }}" id="pk-price-error" data-package-error="price"
              @unless ($errors->has('price')) hidden @endunless>{{ $errors->first('price') }}</small>
          </div>

          <div class="grid min-w-0 gap-1">
            <span id="pk-features-label" class="{{ $labelClass }}">Package Description</span>
            {{-- View mode shows the bulleted list; edit/create mode swaps in the textarea. --}}
            <ul class="m-0 min-h-36 list-disc rounded-field border border-field-border bg-surface py-3 pr-3 pl-8 text-fluid-md" aria-labelledby="pk-features-label" data-package-features-view></ul>
            <textarea id="pk-features" name="features" class="{{ $textareaClass }}"
              rows="6" aria-labelledby="pk-features-label" aria-describedby="pk-features-hint"
              data-package-field="features" data-editable hidden></textarea>
            <small class="{{ $hintClass }}" id="pk-features-hint" data-package-hint hidden>One feature per line.</small>
            <small class="{{ $errorClass }}" id="pk-features-error" data-package-error="features"
              @unless ($errors->has('features')) hidden @endunless>{{ $errors->first('features') }}</small>
          </div>

          {{-- Equipment: the rows of the landing Package Comparison Table (config/package-equipment.php).
               Optional. A blank row prints "—" in the table. Groups are closed in view mode and open in edit / create.
               Each row has its own error line (data-package-equipment-error); the one below the groups is for server errors
               that name no row. No maxlength on purpose: packages.js shows a message instead of cutting a paste short. --}}
          <div class="grid gap-2 border-t border-field-border pt-4" role="group" aria-labelledby="pk-equipment-label" aria-describedby="pk-equipment-hint">
            <span id="pk-equipment-label" class="{{ $labelClass }}">Equipment</span>
            <small class="{{ $hintClass }}" id="pk-equipment-hint">Shown in the landing page's comparison table. Type "Yes" for a check mark, or a value like "2 Units". Leave blank if not included.</small>

            @foreach (config('package-equipment.sections', []) as $section)
            <details class="border-b border-field-border last-of-type:border-b-0" data-package-group>
              <summary class="cursor-pointer py-2 font-semibold text-brand-text">{{ $section['title'] }}</summary>
              <div class="grid grid-cols-1 items-start gap-3 pt-2 pb-4 min-[576px]:grid-cols-2">
                @foreach ($section['rows'] as $rowKey => $rowLabel)
                <div class="grid min-w-0 gap-1">
                  <label for="pk-eq-{{ $rowKey }}" class="{{ $labelClass }}">{{ $rowLabel }}</label>
                  <input type="text" id="pk-eq-{{ $rowKey }}" name="equipment[{{ $rowKey }}]" class="{{ $controlClass }}" readonly
                    autocomplete="off" data-package-equipment-field="{{ $rowKey }}" data-editable>
                  <small class="{{ $errorClass }}" id="pk-eq-{{ $rowKey }}-error" data-package-equipment-error="{{ $rowKey }}" hidden></small>
                </div>
                @endforeach
              </div>
            </details>
            @endforeach

            <small class="{{ $errorClass }}" id="pk-equipment-error" data-package-error="equipment" hidden></small>
          </div>
        </div>
      </div>

      <div class="modal-footer flex-nowrap gap-3 border-0 px-6 pt-3 pb-6 *:m-0">
        <button type="button" class="admin-btn admin-btn--brand flex-[1_1_0] px-4" data-bs-dismiss="modal" data-package-back>Back</button>
        <button type="button" class="admin-btn admin-btn--light flex-[1_1_0] px-4" data-package-cancel hidden>Cancel</button>
        <button type="submit" class="admin-btn admin-btn--brand flex-[1_1_0] px-4" data-package-save hidden>Save</button>
      </div>
    </form>
  </div>
</div>