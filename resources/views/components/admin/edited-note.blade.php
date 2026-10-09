{{-- "Edited" label shown under a field the admin changed after the client booked.
     bookings.js shows it when the booking's `edited` map has this path and fills in the original
     value (the map is { path => original value }, see booking-row.blade.php). Hidden otherwise. --}}
@props(['path'])

<p class="admin-field__edited" data-edited-note="{{ $path }}" hidden>
  <span class="admin-field__tag admin-field__tag--edited">Edited</span>
  <span class="admin-field__original">Originally: <span data-edited-original></span></span>
</p>