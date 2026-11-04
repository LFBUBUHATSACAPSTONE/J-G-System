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
$padClass = $password ? 'pr-12 pl-3' : 'px-3';
$controlClass = 'min-h-11 w-full rounded-field border border-field-border bg-surface py-2 text-fluid-base text-ink placeholder:text-ink-subtle focus-visible:ring-4 focus-visible:ring-brand-halo focus-visible:outline-2 focus-visible:outline-offset-0 focus-visible:outline-brand-text [&.is-invalid]:border-danger-border [&[readonly]]:cursor-not-allowed [&[readonly]]:text-ink-muted';
@endphp

<div class="grid min-w-0 gap-1">
  <label class="text-fluid-md text-brand-text" for="{{ $id }}">{{ $label }}</label>

  @if ($password)
  <div class="relative">
    @endif

    <input class="{{ $controlClass }} {{ $padClass }}" type="{{ $type }}" id="{{ $id }}"
      @if ($name) name="{{ $name }}" @endif
      @if ($type !=='password' ) value="{{ $value }}" @endif
      @if ($autocomplete) autocomplete="{{ $autocomplete }}" @endif
      @if ($inputmode) inputmode="{{ $inputmode }}" @endif
      @if ($maxlength) maxlength="{{ $maxlength }}" @endif
      @if ($placeholder) placeholder="{{ $placeholder }}" @endif
      @if ($describedBy !=='' ) aria-describedby="{{ $describedBy }}" @endif
      @readonly($readonly)>

    @if ($password)
    <button type="button" class="absolute top-1/2 right-1 grid size-10 -translate-y-1/2 cursor-pointer place-items-center rounded-field border-0 bg-transparent p-0 text-[1.25rem] text-ink-muted hover:text-brand-text focus-visible:outline-2 focus-visible:outline-brand-text" data-password-toggle="{{ $id }}"
      aria-pressed="false" aria-label="Show password"><i class="ph ph-eye" aria-hidden="true"></i></button>
  </div>
  @endif

  @if ($hint)
  <small class="text-sm text-ink-muted" id="{{ $hintId }}">{{ $hint }}</small>
  @endif
  @if ($name)
  <small class="text-sm text-danger-text d-none" data-field-error="{{ $name }}" role="alert"></small>
  @endif
</div>