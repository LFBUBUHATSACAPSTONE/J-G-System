{{-- Profile photo / initials circle, shared by the admin account and the customer Profile page.
     `editable` adds the pencil button and the hidden file input (the admin uploads a photo; the customer
     page shows initials only, because there is no photo upload on that side). --}}
@props([
'initials' => '?',
'url' => null,
'editable' => false,
'maxMb' => 2,
])

<div class="profile-avatar-block">
  <div class="profile-avatar-wrap">
    <div class="profile-avatar" data-avatar @unless ($editable) aria-hidden="true" @endunless>
      @if ($url)
      <img src="{{ $url }}" alt="Your profile photo">
      @else
      {{ $initials }}
      @endif
    </div>

    @if ($editable)
    <button type="button" class="profile-avatar__edit" data-avatar-pick aria-label="Change profile photo">
      <i class="ph ph-pencil-simple" aria-hidden="true"></i>
    </button>
    <input type="file" name="avatar" class="d-none" accept="image/jpeg,image/png,image/webp"
      data-avatar-input data-max-mb="{{ $maxMb }}" tabindex="-1" aria-label="Profile photo file">
    @endif
  </div>

  @if ($editable)
  <small class="profile-field__error d-none" data-field-error="avatar" role="alert"></small>
  @endif
</div>