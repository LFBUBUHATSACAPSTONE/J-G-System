{{-- "Edited" label shown under a field the admin changed after the client booked.
     bookings.js shows it when the booking's `edited` map has this path and fills in the original
     value (the map is { path => original value }, see booking-row.blade.php). Hidden otherwise. --}}
@props(['path'])

<p class="m-0 flex flex-wrap items-center gap-2 text-sm text-ink-muted wrap-anywhere" data-edited-note="{{ $path }}" hidden>
  <span class="inline-block rounded-field bg-warning-soft px-2 py-1 text-xs leading-[1.2] font-semibold text-warning-text">Edited</span>
  <span>Originally: <span data-edited-original></span></span>
</p>