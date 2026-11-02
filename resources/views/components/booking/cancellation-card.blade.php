{{-- Cancellation request card, shared by the admin booking modal and the customer's booking details modal.
     States (pending / approved / declined / cancelled directly) are painted by js/shared/cancellation-tone.js,
     which sets `data-tone` on the card plus the icon and the pill; the title, meta line and reasons are filled
     by js/admin/cancellation.js (admin) and js/account/bookings.js (customer) through the hooks below.
     Props: `section` = attribute that marks the card, `prefix` = start of every hook name, `titleId`,
     `userLabel` = label above the client's reason, `actions` = Decline / Approve row (admin only, pending only). --}}
@props([
'section' => 'data-cancellation-section',
'prefix' => 'data-cancellation',
'titleId' => 'cancellation-title',
'userLabel' => "Client's reason",
'actions' => false,
])
@php
$cardClass = 'group/cc m-0 flex flex-col gap-4 rounded-panel border border-field-border bg-panel p-4 min-[576px]:p-5 [&[hidden]]:hidden data-[tone=pending]:border-warning-border data-[tone=pending]:bg-warning-soft/40 data-[tone=cancelled]:border-danger-border data-[tone=cancelled]:bg-danger-soft/70 data-[tone=declined]:border-brand/40 data-[tone=declined]:bg-brand-soft';
$iconClass = 'grid size-9 shrink-0 place-items-center rounded-full bg-surface text-xl leading-none text-ink-muted group-data-[tone=pending]/cc:text-warning-text group-data-[tone=cancelled]/cc:text-danger-text group-data-[tone=declined]/cc:text-brand-text';
$pillClass = 'shrink-0 rounded-full border border-field-border bg-surface px-3 py-1 text-[0.8125rem] leading-[1.4] font-medium text-ink-muted empty:hidden group-data-[tone=pending]/cc:border-warning-border group-data-[tone=pending]/cc:text-warning-text group-data-[tone=cancelled]/cc:border-danger-border group-data-[tone=cancelled]/cc:text-danger-text group-data-[tone=declined]/cc:border-brand/40 group-data-[tone=declined]/cc:text-brand-text';
$reasonClass = 'grid gap-1 [&[hidden]]:hidden';
$labelClass = 'm-0 flex flex-wrap items-baseline gap-x-2 text-sm';
$dateClass = 'text-ink-muted empty:hidden before:mr-2 before:content-["·"]';
$quoteClass = 'm-0 rounded-r-field rounded-l-sm border-l-4 border-l-field-border bg-surface px-3 py-2 text-sm wrap-anywhere whitespace-pre-line text-ink group-data-[tone=pending]/cc:border-l-warning-border group-data-[tone=cancelled]/cc:border-l-danger-border group-data-[tone=declined]/cc:border-l-brand';
$buttonClass = 'inline-flex min-h-10 w-full cursor-pointer items-center justify-center gap-2 rounded-full border px-5 py-1 text-fluid-sm font-semibold whitespace-nowrap focus-visible:outline-2 focus-visible:outline-offset-2 min-[420px]:w-auto [&_.ph]:text-fluid-lg';
@endphp
<section {{ $attributes->class([$cardClass]) }} aria-labelledby="{{ $titleId }}" {{ $section }} data-tone="neutral" hidden>
  <header class="flex items-start gap-3">
    <span class="{{ $iconClass }}"><i class="ph ph-x-circle" aria-hidden="true" data-cc-icon></i></span>
    <div class="grid min-w-0 flex-1 gap-1">
      <h3 class="m-0 text-fluid-base leading-snug font-semibold text-ink" id="{{ $titleId }}" {{ $prefix }}-title></h3>
      <p class="m-0 text-sm text-ink-muted empty:hidden" {{ $prefix }}-meta></p>
    </div>
    <span class="{{ $pillClass }}" data-cc-pill></span>
  </header>

  <div class="{{ $reasonClass }}" {{ $prefix }}-user hidden>
    <p class="{{ $labelClass }}">
      <span class="font-semibold text-ink" {{ $prefix }}-user-label>{{ $userLabel }}</span>
      <span class="{{ $dateClass }}" {{ $prefix }}-user-date></span>
    </p>
    <p class="{{ $quoteClass }}" {{ $prefix }}-user-reason></p>
  </div>

  <div class="{{ $reasonClass }}" {{ $prefix }}-admin hidden>
    <p class="{{ $labelClass }}">
      <span class="font-semibold text-ink" {{ $prefix }}-admin-label>J&amp;G's reason</span>
      <span class="{{ $dateClass }}" {{ $prefix }}-admin-date></span>
    </p>
    <p class="{{ $quoteClass }}" {{ $prefix }}-admin-reason></p>
  </div>

  @if ($actions)
  <div class="flex flex-col-reverse gap-2 min-[420px]:flex-row min-[420px]:justify-end [&[hidden]]:hidden" {{ $prefix }}-actions hidden>
    <button type="button" class="{{ $buttonClass }} border-brand-text bg-surface text-brand-text hover:bg-brand-soft focus-visible:outline-brand-text" data-decline-cancellation>
      <i class="ph ph-x" aria-hidden="true"></i>
      <span>Decline Cancellation</span>
    </button>
    <button type="button" class="{{ $buttonClass }} border-danger bg-danger text-on-brand hover:opacity-90 focus-visible:outline-danger" data-approve-cancellation>
      <i class="ph ph-check" aria-hidden="true"></i>
      <span>Approve Cancellation</span>
    </button>
  </div>
  @endif
</section>