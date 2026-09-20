@props([
'type' => 'button',
'disabled' => false,
'ariaLabel' => '',
'id' => '',
'icon' => '',
'iconAlt' => '',
])

<a {{ $attributes->merge([
        'type' => $type,
        'id' => $id,
        'aria-label' => $ariaLabel,
        'class' => 'auth-modal__sso-btn' . ($disabled ? ' is-disabled' : ''),
    ]) }}
  @if($disabled) aria-disabled="true" tabindex="-1" @endif>
  @if($icon)
  <img src="{{ asset($icon) }}" alt="{{ $iconAlt }}">
  @endif
  <span>{{ $slot }}</span>
</a>