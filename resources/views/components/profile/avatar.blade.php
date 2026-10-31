{{-- Profile photo / initials circle, shared by the admin account and the customer Profile page.
     `editable` adds the pencil button and the hidden file input (the admin uploads a photo; the customer
     page shows initials only, because there is no photo upload on that side). --}}
@props([
'initials' => '?',
'url' => null,
'editable' => false,
'maxMb' => 2,
])

<div class="mb-2 grid justify-items-center gap-2">
  <div class="relative size-32">
    <div class="grid size-full place-items-center overflow-hidden rounded-full border-4 border-brand bg-brand-soft text-[2.25rem] font-semibold text-brand-text [&_img]:size-full [&_img]:object-cover" data-avatar @unless ($editable) aria-hidden="true" @endunless>
      @if ($url)
      <img src="{{ $url }}" alt="Your profile photo">
      @else
      {{ $initials }}
      @endif
    </div>

    @if ($editable)
    <button type="button" class="absolute right-0 bottom-1 grid size-10 cursor-pointer place-items-center rounded-full border-3 border-surface bg-brand p-0 text-[1.125rem] text-on-brand before:absolute before:-inset-1 hover:bg-brand-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text" data-avatar-pick aria-label="Change profile photo">
      <i class="ph ph-pencil-simple" aria-hidden="true"></i>
    </button>
    <input type="file" name="avatar" class="hidden" accept="image/jpeg,image/png,image/webp"
      data-avatar-input data-max-mb="{{ $maxMb }}" tabindex="-1" aria-label="Profile photo file">
    @endif
  </div>

  @if ($editable)
  <small class="text-sm text-danger-text d-none" data-field-error="avatar" role="alert"></small>
  @endif
</div>