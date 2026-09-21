@props([
'type' => 'button',
'disabled' => false,
'ariaLabel' => '',
'id' => '',
'icon' => '',
'iconAlt' => '',
'tabIndex' => 0,
])

<a {{ $attributes->merge([
        'type' => $type,
        'id' => $id,
        'aria-label' => $ariaLabel,
        'tabindex' => $disabled ? '-1' : $tabIndex,
        'class' => 'auth-modal__sso-btn' . ($disabled ? ' is-disabled' : ''),
    ]) }}
  @if($disabled) aria-disabled="true" @endif>

  @if($icon)
  <img src="{{ asset($icon) }}" alt="{{ $iconAlt }}">
  @endif

  <span>{{ $slot }}</span>
</a>