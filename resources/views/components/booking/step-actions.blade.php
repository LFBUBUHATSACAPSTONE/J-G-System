{{-- The row under a booking step's card (Phase 9B; was `.client-info__actions`). Markup order is Cancel / Previous
     first, Continue second: from 480px they sit side by side in that order, on phones the row is reversed so
     Continue is on top. Put two <x-booking.step-button> inside. Used by Personal Information, Event Information and
     Event Schedule; the summary and confirmation steps keep their own wrapper until 9E and 9F. --}}
<div {{ $attributes->class(['flex w-full flex-col-reverse gap-[1rem] min-[480px]:flex-row']) }}>
  {{ $slot }}
</div>