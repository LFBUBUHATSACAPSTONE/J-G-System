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
$valueClass = 'min-w-0 text-fluid-base text-ink [overflow-wrap:anywhere] [&.is-empty]:text-ink-muted';
$actionClass = 'min-h-11 flex-none cursor-pointer border-0 bg-transparent px-2 py-0 text-fluid-md font-semibold text-brand-text no-underline hover:text-brand-hover focus-visible:rounded-field focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text';
@endphp

<div class="grid gap-1" data-row="{{ $name }}" role="group" aria-labelledby="{{ $labelId }}">
  <span class="text-fluid-md font-semibold text-ink" id="{{ $labelId }}">{{ $label }}</span>

  <div class="flex min-h-14 items-center justify-between gap-3 rounded-panel bg-panel px-4 py-2" data-row-view>
    @if ($masked)
    <span class="{{ $valueClass }}">
      <span aria-hidden="true">••••••••••••</span>
      <span class="sr-only">Hidden for security</span>
    </span>
    @else
    <span @class([$valueClass, 'is-empty'=> $value === '' && $empty]) data-row-value
      @if ($empty) data-empty="{{ $empty }}" @endif>{{ $value !== '' ? $value : $empty }}</span>
    @endif

    @if ($action)
    <button type="button" class="{{ $actionClass }}" data-row-edit aria-expanded="false"
      aria-controls="{{ $panelId }}" aria-label="{{ $action }} {{ mb_strtolower($label) }}">{{ $action }}</button>
    @endif
  </div>

  @if ($hint)
  <small class="text-sm text-ink-muted">{{ $hint }}</small>
  @endif

  @if ($action)
  <div class="grid gap-3 rounded-panel bg-panel p-4" id="{{ $panelId }}" data-row-panel hidden>
    {{ $slot }}
    <div class="flex justify-end">
      <button type="button" class="{{ $actionClass }}" data-row-cancel>Cancel</button>
    </div>
  </div>
  @endif
</div>