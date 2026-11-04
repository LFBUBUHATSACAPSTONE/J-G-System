@props([
'type' => 'button',
'disabled' => false,
'ariaLabel' => '',
'id' => '',
'icon' => '',
'iconAlt' => '',
'tabIndex' => 0,
])

@php
$classes = 'flex flex-1 items-center justify-center gap-[0.5rem] rounded-field [border:1px_solid_var(--color-auth-border)] bg-transparent [padding:0.65rem] text-[0.85rem] text-auth-text no-underline hover:border-auth-accent hover:text-auth-text hover:no-underline'
. ($disabled ? ' pointer-events-none cursor-not-allowed opacity-50' : '');
@endphp

<a {{ $attributes->merge([
        'type' => $type,
        'id' => $id,
        'aria-label' => $ariaLabel,
        'tabindex' => $disabled ? '-1' : $tabIndex,
        'class' => $classes,
    ]) }}
  @if($disabled) aria-disabled="true" @endif>

  @if($icon)
  <img src="{{ asset($icon) }}" alt="{{ $iconAlt }}">
  @endif

  <span>{{ $slot }}</span>
</a>