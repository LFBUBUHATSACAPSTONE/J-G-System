{{-- One labelled input with its error line. `password` adds the show/hide eye button.
     No `name` = a display-only input that is never sent (the customer's login email). --}}
@props([
'name' => null,
'label',
'id' => null,
'type' => 'text',
'value' => '',
'autocomplete' => null,
'inputmode' => null,
'maxlength' => null,
'placeholder' => null,
'hint' => null,
'describedby' => null,
'readonly' => false,
'password' => false,
])

@php
$id = $id ?? 'pf-' . ($name ?? 'field');
$hintId = $hint ? $id . '-hint' : null;
$describedBy = trim(($describedby ?? '') . ' ' . ($hintId ?? ''));
@endphp

<div class="profile-field">
  <label class="profile-field__label" for="{{ $id }}">{{ $label }}</label>

  @if ($password)
  <div class="profile-field__password">
    @endif

    <input class="profile-field__control" type="{{ $type }}" id="{{ $id }}"
      @if ($name) name="{{ $name }}" @endif
      @if ($type !=='password' ) value="{{ $value }}" @endif
      @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
      @if ($inputmode) inputmode="{{ $inputmode }}" @endif
      @if ($maxlength) maxlength="{{ $maxlength }}" @endif
      @if ($placeholder) placeholder="{{ $placeholder }}" @endif
      @if ($describedBy !=='' ) aria-describedby="{{ $describedBy }}" @endif
      @readonly($readonly)>

    @if ($password)
    <button type="button" class="profile-field__toggle" data-password-toggle="{{ $id }}"
      aria-pressed="false" aria-label="Show password"><i class="ph ph-eye" aria-hidden="true"></i></button>
  </div>
  @endif

  @if ($hint)
  <small class="profile-field__hint" id="{{ $hintId }}">{{ $hint }}</small>
  @endif
  @if ($name)
  <small class="profile-field__error d-none" data-field-error="{{ $name }}" role="alert"></small>
  @endif
</div>