{{-- A profile row: label, grey value box and, when `action` is set, an Edit / Change button that opens
     the slot (the inputs) inline. Without `action` the row is read-only and can show a hint.
     `empty` is the text shown (muted) when there is no value yet. --}}
@props([
'name',
'label',
'value' => '',
'action' => null,
'masked' => false,
'empty' => null,
'hint' => null,
])

@php
$labelId = "pf-{$name}-label";
$panelId = "pf-{$name}-panel";
@endphp

<div class="profile-row" data-row="{{ $name }}" role="group" aria-labelledby="{{ $labelId }}">
  <span class="profile-row__label" id="{{ $labelId }}">{{ $label }}</span>

  <div class="profile-row__view" data-row-view>
    @if ($masked)
    <span class="profile-row__value">
      <span aria-hidden="true">••••••••••••</span>
      <span class="visually-hidden">Hidden for security</span>
    </span>
    @else
    <span @class(['profile-row__value', 'is-empty'=> $value === '' && $empty]) data-row-value
      @if ($empty) data-empty="{{ $empty }}" @endif>{{ $value !== '' ? $value : $empty }}</span>
    @endif

    @if ($action)
    <button type="button" class="profile-row__action" data-row-edit aria-expanded="false"
      aria-controls="{{ $panelId }}" aria-label="{{ $action }} {{ mb_strtolower($label) }}">{{ $action }}</button>
    @endif
  </div>

  @if ($hint)
  <small class="profile-row__hint">{{ $hint }}</small>
  @endif

  @if ($action)
  <div class="profile-row__panel" id="{{ $panelId }}" data-row-panel hidden>
    {{ $slot }}
    <div class="profile-row__panel-actions">
      <button type="button" class="profile-row__action" data-row-cancel>Cancel</button>
    </div>
  </div>
  @endif
</div>