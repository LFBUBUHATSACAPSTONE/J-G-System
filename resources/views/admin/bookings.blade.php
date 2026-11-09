{{-- Admin Bookings page. Variables from the route
       $bookings => list of bookings
       $packages => [['id' => 'budget-lite', 'name' => 'Budget Lite'], ...]  (admin-managed)
       $fullDates => ['2026-10-14', ...] days already at the event limit (config/scheduling.php);
                     optional, a pending booking on one of them can't be approved (docs/event-capacity.md)
     Filtering, sorting and search run in the browser (resources/js/admin/bookings.js).
     Status badge + actions come from config/admin/bookings.php.

--}}
<!DOCTYPE html>
<html lang="en">

<head>
  <x-admin-header />
</head>

<body class="app-shell bg-sidebar font-sans">
  <x-admin.sidebar />

  <main class="font-sans admin-content admin-bookings">
    <x-admin.page-header />

    @if (session('status'))
    <p class="mb-6 rounded-panel border border-success-border bg-success-soft px-6 py-3 font-medium text-success-text data-[tone=danger]:border-danger-border data-[tone=danger]:bg-danger-soft data-[tone=danger]:text-danger-text" role="status">{{ session('status') }}</p>
    @endif

    {{-- Approve refused by the server (event limit reached). role=alert announces it at once. --}}
    @if (session('error'))
    <p class="mb-6 rounded-panel border border-success-border bg-success-soft px-6 py-3 font-medium text-success-text data-[tone=danger]:border-danger-border data-[tone=danger]:bg-danger-soft data-[tone=danger]:text-danger-text" data-tone="danger" role="alert">{{ session('error') }}</p>
    @endif
    <p class="mb-6 rounded-panel border border-success-border bg-success-soft px-6 py-3 font-medium text-success-text data-[tone=danger]:border-danger-border data-[tone=danger]:bg-danger-soft data-[tone=danger]:text-danger-text" role="status" tabindex="-1" data-bookings-feedback hidden></p>

    {{-- Filters
         Below 768px: search + a "Filters" button; Sort / Status / Package live in a bottom sheet
         (Bootstrap .offcanvas-md) and active ones show as removable chips. From 768px the sheet
         is "display: contents", so the four pills sit inline exactly as before.
         The selects keep their data-bookings-* hooks, so bookings.js filtering is unchanged. --}}
    <div class="admin-bookings__filters mb-6 flex flex-wrap gap-4 max-md:gap-3" role="search" aria-label="Filter bookings" data-filter-bar>
      <div class="admin-filter-sheet offcanvas-md offcanvas-bottom rounded-t-card font-sans [--bs-offcanvas-bg:var(--color-page)] [--bs-offcanvas-border-width:0] [--bs-offcanvas-color:var(--color-ink)] [--bs-offcanvas-height:auto] [--bs-offcanvas-padding-x:1.5rem] [--bs-offcanvas-padding-y:1rem] md:contents" tabindex="-1" id="bookingsFilterSheet"
        aria-labelledby="bookingsFilterSheetTitle" data-filter-sheet>
        <div class="offcanvas-header">
          <h2 class="offcanvas-title text-fluid-lg font-semibold" id="bookingsFilterSheetTitle">Filters</h2>
          <button type="button" class="btn-close-x" data-bs-dismiss="offcanvas" data-bs-target="#bookingsFilterSheet"
            aria-label="Close filters"></button>
        </div>

        <div class="offcanvas-body flex flex-col gap-3 pt-0 md:contents!">
          <div class="admin-filter relative flex min-h-13 items-center gap-3 rounded-control bg-surface px-6 py-1 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-brand-text data-open:z-20 data-open:outline-2 data-open:outline-offset-2 data-open:outline-brand-text flex-none md:flex-[1_1_14rem]">
            <label for="bk-sort" class="admin-filter__label text-fluid-sm font-medium whitespace-nowrap text-ink">Sorted by:</label>
            <div class="admin-filter__control relative min-w-0 flex-1">
              <select id="bk-sort" class="admin-filter__select" data-bookings-sort>
                @foreach (config('admin.bookings.sorts') as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="admin-filter relative flex min-h-13 items-center gap-3 rounded-control bg-surface px-6 py-1 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-brand-text data-open:z-20 data-open:outline-2 data-open:outline-offset-2 data-open:outline-brand-text flex-none md:flex-[1_1_14rem]">
            <label for="bk-status" class="admin-filter__label text-fluid-sm font-medium whitespace-nowrap text-ink">Status:</label>
            <div class="admin-filter__control relative min-w-0 flex-1">
              <select id="bk-status" class="admin-filter__select" data-bookings-status>
                @foreach (config('admin.bookings.status_filters') as $value=> $label)
                <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="admin-filter relative flex min-h-13 items-center gap-3 rounded-control bg-surface px-6 py-1 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-brand-text data-open:z-20 data-open:outline-2 data-open:outline-offset-2 data-open:outline-brand-text flex-none md:flex-[1_1_14rem]">
            <label for="bk-package" class="admin-filter__label text-fluid-sm font-medium whitespace-nowrap text-ink">Package:</label>
            <div class="admin-filter__control relative min-w-0 flex-1">
              <select id="bk-package" class="admin-filter__select" data-bookings-package>
                <option value="all">All</option>
                @foreach ($packages as $package)
                <option value="{{ $package['id'] }}">{{ $package['name'] }}</option>
                @endforeach
              </select>
            </div>
          </div>

          {{-- Event date range: From / To + quick chips. Logic in resources/js/admin/date-range.js,
               filtering in bookings.js, text in config/admin/bookings.php 'date_filter'.
               Phones: inside the sheet. From 768px: its own full-width row under the filters
               (the sheet is "display: contents", so md:order-1 puts it after the search pill). --}}
          @php($dateFilter = config('admin.bookings.date_filter'))
          <div class="admin-date-filter flex flex-col gap-3 md:order-1 md:flex-[0_0_100%] md:flex-row md:flex-wrap md:items-center" role="group" aria-label="{{ $dateFilter['label'] }}" data-date-range data-date-summary="{{ json_encode($dateFilter['summary']) }}">
            <div class="flex min-h-13 flex-wrap items-center gap-x-3 gap-y-1 rounded-control bg-surface px-6 py-1 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-brand-text">
              <span class="text-fluid-sm font-medium whitespace-nowrap text-ink">{{ $dateFilter['label'] }}:</span>
              <label for="bk-date-from" class="text-fluid-sm text-ink-muted">{{ $dateFilter['from'] }}</label>
              <input type="date" id="bk-date-from" class="min-w-0 border-0 bg-transparent py-2 text-fluid-sm text-ink outline-0" data-date-from>
              <label for="bk-date-to" class="text-fluid-sm text-ink-muted">{{ $dateFilter['to'] }}</label>
              <input type="date" id="bk-date-to" class="min-w-0 border-0 bg-transparent py-2 text-fluid-sm text-ink outline-0" data-date-to>
            </div>
            <div class="flex flex-wrap items-center gap-2">
              @foreach ($dateFilter['chips'] as $key => $label)
              <button type="button" class="inline-flex min-h-9 cursor-pointer items-center rounded-control border border-brand-text bg-surface px-3 py-1 text-[0.85rem] font-medium text-brand-text hover:bg-brand-soft-hover aria-pressed:border-brand aria-pressed:bg-brand aria-pressed:text-on-brand focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text" aria-pressed="false" data-date-chip="{{ $key }}">{{ $label }}</button>
              @endforeach
              <button type="button" class="inline-flex min-h-9 cursor-pointer items-center rounded-control border-0 bg-transparent px-3 py-1 text-[0.85rem] font-medium text-brand-text underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text [[hidden]]:hidden" data-date-clear hidden>{{ $dateFilter['clear'] }}</button>
            </div>
          </div>

          {{-- Sheet footer (phones only): filtering is live, "Done" just closes the sheet. --}}
          <div class="admin-filter-sheet__actions mt-2 grid grid-cols-2 gap-3 md:hidden">
            <button type="button" class="inline-flex min-h-12 cursor-pointer items-center justify-center rounded-panel border border-brand-text bg-transparent px-4 py-2 text-fluid-base font-semibold text-brand-text hover:bg-brand-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text disabled:cursor-not-allowed disabled:opacity-50" data-filter-clear disabled>Clear all</button>
            <button type="button" class="inline-flex min-h-12 cursor-pointer items-center justify-center rounded-panel border-0 bg-brand px-4 py-2 text-fluid-base font-semibold text-on-brand hover:bg-brand-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text disabled:cursor-not-allowed disabled:opacity-50" data-bs-dismiss="offcanvas"
              data-bs-target="#bookingsFilterSheet">Done</button>
          </div>
        </div>
      </div>

      <div class="admin-filter relative flex min-h-13 items-center gap-3 rounded-control bg-surface px-6 py-1 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-brand-text data-open:z-20 data-open:outline-2 data-open:outline-offset-2 data-open:outline-brand-text admin-filter--search flex-[3_1_18rem] max-md:order-1 max-md:min-w-0 max-md:flex-[1_1_0] max-md:px-4">
        <i class="ph ph-magnifying-glass admin-filter__icon text-fluid-2xl text-ink" aria-hidden="true"></i>
        <label for="bk-search" class="sr-only">Search bookings</label>
        <input type="search" id="bk-search" class="admin-filter__input min-w-0 flex-1 border-0 bg-transparent py-2 text-fluid-sm text-ink outline-0 placeholder:text-ink-subtle placeholder:opacity-100" placeholder="Search"
          autocomplete="off" data-bookings-search>
      </div>

      <button type="button" class="admin-filters-toggle inline-flex min-h-13 cursor-pointer items-center gap-2 rounded-control border-0 bg-surface px-6 py-1 text-fluid-sm font-medium whitespace-nowrap text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text max-md:order-2 md:hidden [&_.ph]:text-fluid-xl" data-bs-toggle="offcanvas"
        data-bs-target="#bookingsFilterSheet" aria-controls="bookingsFilterSheet" data-filter-toggle>
        <i class="ph ph-sliders-horizontal" aria-hidden="true"></i>
        <span>Filters</span>
        <span class="admin-filters-toggle__badge inline-flex h-[1.4rem] min-w-[1.4rem] items-center justify-center rounded-full bg-brand px-1 text-xs font-semibold text-white [[hidden]]:hidden" data-filter-count hidden>0</span>
      </button>

      {{-- Active filters as removable chips (phones only; filled by filter-sheet.js) --}}
      <div class="admin-filter-chips flex flex-[0_0_100%] flex-wrap gap-2 max-md:order-3 md:hidden [[hidden]]:hidden" data-filter-chips hidden></div>
    </div>

    {{-- Focus bar: shown only while a link has narrowed the list to one booking (?selected_booking=).
         bookings.js shows it, hides it again when a filter changes, and "Show all" restores the list. --}}
    <div class="admin-bookings__focus mb-4 flex flex-wrap items-center justify-between gap-3 rounded-panel bg-brand-soft px-4 py-2 text-fluid-sm text-ink [&[hidden]]:hidden" data-bookings-focus hidden>
      <span class="font-medium">{{ config('admin.bookings.focus.showing_one') }}</span>
      <button type="button" class="inline-flex min-h-10 cursor-pointer items-center rounded-control border border-brand-text bg-surface px-4 py-1 text-fluid-sm font-semibold text-brand-text hover:bg-brand-soft-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text" data-bookings-show-all>{{ config('admin.bookings.focus.show_all') }}</button>
    </div>

    {{-- Table --}}
    <div class="admin-bookings__panel rounded-card bg-panel px-6 pt-1 pb-2">
      <table class="admin-bookings__table block w-full border-separate [border-spacing:0_1.1rem] min-[1200px]:table" role="table">
        <caption class="sr-only">Bookings</caption>
        <thead role="rowgroup" class="sr-only min-[1200px]:table-header-group min-[1200px]:not-sr-only min-[1200px]:[&_th]:bg-brand min-[1200px]:[&_th]:p-4 min-[1200px]:[&_th]:text-center min-[1200px]:[&_th]:text-fluid-md min-[1200px]:[&_th]:font-semibold min-[1200px]:[&_th]:text-white min-[1200px]:[&_th:first-child]:rounded-l-panel min-[1200px]:[&_th:last-child]:rounded-r-panel">
          <tr role="row">
            <th role="columnheader" scope="col">Client</th>
            <th role="columnheader" scope="col">Schedule</th>
            <th role="columnheader" scope="col">Package</th>
            <th role="columnheader" scope="col">Event Type</th>
            <th role="columnheader" scope="col">Status</th>
            <th role="columnheader" scope="col">Actions</th>
          </tr>
        </thead>
        <tbody role="rowgroup" tabindex="-1" class="block outline-none min-[1200px]:table-row-group" data-bookings-body>
          @foreach ($bookings as $booking)
          <x-admin.booking-row :booking="$booking" :index="$loop->index" :full-dates="$fullDates ?? []" />
          @endforeach
        </tbody>
      </table>

      <p class="admin-bookings__empty m-0 px-2 py-6 text-center text-fluid-sm text-ink-muted" data-bookings-empty data-date-messages="{{ json_encode(config('admin.bookings.date_filter')) }}" @if (count($bookings)> 0) hidden @endif>
        <span data-bookings-empty-text>@if (count($bookings) > 0) No bookings match your filters. @else No bookings yet. @endif</span>
        <button type="button" class="ml-2 cursor-pointer border-0 bg-transparent p-0 font-semibold text-brand-text underline focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text [[hidden]]:hidden" data-bookings-empty-clear hidden>{{ config('admin.bookings.date_filter.clear') }}</button>
      </p>
    </div>

    {{-- Announces "Showing X of Y" to screen readers after each filter change. --}}
    <p class="sr-only" role="status" data-bookings-count></p>

    <x-admin.booking-modal />

    {{-- Approve / Decline / Verify Payment / Cancel confirmation. Decline and Cancel also ask for a reason.
         Filled by bookings.js from each action form's data-action-modal-copy (config/admin/bookings.php). --}}
    <x-booking.action-modal id="bookingActionModal" :reason="true" />
  </main>
</body>

</html>