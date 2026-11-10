{{-- Event date filter pill, shared by the admin Bookings and Booking History pages.
     Looks like the other filter pills ("Event date: All"). Tapping it opens a panel with From / To and the
     Today / Tomorrow / This week shortcuts: a popover from 768px, an inline section inside the filter sheet on
     phones. Once a range is set the pill shows it ("Event date: Nov 1 – Nov 10") with an x to clear it.
     Filtering stays live (no Apply button): the page scripts react to every change, "Done" only closes the panel.
     Logic: resources/js/admin/date-range.js. Text: config/admin/<page>.php 'date_filter'.
     Props: page = 'bookings' | 'history' (config group), prefix = id prefix ('bk' | 'bh'). --}}
@props(['page', 'prefix'])

@php
$filter = config("admin.{$page}.date_filter");
$pillClass = 'admin-filter admin-filter--date group/pill relative flex min-h-13 items-center gap-3 rounded-control bg-surface px-6 py-1 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-brand-text data-open:z-20 data-open:outline-2 data-open:outline-offset-2 data-open:outline-brand-text';
$inputClass = 'min-h-11 w-full min-w-0 rounded-field border border-field-border bg-surface px-3 py-2 text-fluid-sm text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text';
$chipClass = 'inline-flex min-h-10 cursor-pointer items-center justify-center rounded-control border border-brand-text bg-surface px-3 py-1 text-[0.85rem] font-medium text-brand-text hover:bg-brand-soft-hover aria-pressed:border-brand aria-pressed:bg-brand aria-pressed:text-on-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text';
@endphp

<div class="admin-date-filter relative flex-none md:flex-[1_1_14rem]" role="group" aria-label="{{ $filter['label'] }}" data-date-range data-date-summary="{{ json_encode($filter['summary']) }}" data-date-all="{{ $filter['all'] }}">
  <div class="{{ $pillClass }}" data-date-pill>
    <button type="button" class="flex min-h-11 min-w-0 flex-1 cursor-pointer items-center gap-3 border-0 bg-transparent p-0 text-left text-fluid-sm text-ink focus-visible:outline-0" aria-expanded="false" aria-controls="{{ $prefix }}-date-panel" data-date-trigger>
      <span class="admin-filter__label font-medium whitespace-nowrap">{{ $filter['label'] }}:</span>
      <span class="min-w-0 flex-1 truncate group-data-active/pill:font-semibold group-data-active/pill:text-brand-text" data-date-value>{{ $filter['all'] }}</span>
      <i class="ph ph-caret-down flex-none text-fluid-base transition-transform duration-200 group-data-open/pill:rotate-180 motion-reduce:transition-none" aria-hidden="true"></i>
    </button>
    <button type="button" class="-mr-2 inline-flex size-9 flex-none cursor-pointer items-center justify-center rounded-full border-0 bg-transparent p-0 text-ink hover:bg-brand-soft-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text [[hidden]]:hidden" aria-label="{{ $filter['clear'] }}" data-date-clear hidden>
      <i class="ph ph-x text-fluid-base" aria-hidden="true"></i>
    </button>
  </div>

  <div id="{{ $prefix }}-date-panel" class="mt-2 flex flex-col gap-4 rounded-panel bg-surface p-4 md:absolute md:top-full md:left-0 md:z-30 md:w-[min(26rem,calc(100vw-2rem))] md:border md:border-dropdown-border md:shadow-[0_12px_32px_-8px_rgb(0_0_0/0.22),0_2px_6px_rgb(0_0_0/0.08)] md:data-[align=end]:right-0 md:data-[align=end]:left-auto md:animate-dropdown-in motion-reduce:animate-none [&[hidden]]:hidden" role="region" aria-label="{{ $filter['label'] }}" data-date-panel hidden>
    <div class="grid grid-cols-1 gap-3 min-[480px]:grid-cols-2">
      <div class="flex min-w-0 flex-col gap-1">
        <label for="{{ $prefix }}-date-from" class="text-[0.85rem] font-medium text-ink-muted">{{ $filter['from'] }}</label>
        <input type="date" id="{{ $prefix }}-date-from" class="{{ $inputClass }}" data-date-from>
      </div>
      <div class="flex min-w-0 flex-col gap-1">
        <label for="{{ $prefix }}-date-to" class="text-[0.85rem] font-medium text-ink-muted">{{ $filter['to'] }}</label>
        <input type="date" id="{{ $prefix }}-date-to" class="{{ $inputClass }}" data-date-to>
      </div>
    </div>

    <div class="grid grid-cols-3 gap-2">
      @foreach ($filter['chips'] as $key => $label)
      <button type="button" class="{{ $chipClass }}" aria-pressed="false" data-date-chip="{{ $key }}">{{ $label }}</button>
      @endforeach
    </div>

    <div class="flex items-center justify-between gap-3">
      <button type="button" class="inline-flex min-h-10 cursor-pointer items-center border-0 bg-transparent px-1 py-1 text-[0.85rem] font-medium text-brand-text underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text [[hidden]]:hidden" data-date-clear hidden>{{ $filter['clear'] }}</button>
      <button type="button" class="ml-auto inline-flex min-h-10 cursor-pointer items-center justify-center rounded-control border-0 bg-brand px-6 py-1 text-[0.85rem] font-semibold text-on-brand hover:bg-brand-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text" data-date-done>{{ $filter['done'] }}</button>
    </div>
  </div>
</div>