{{-- Admin Booking History page. Variables from the controller:
       $bookings => list of bookings (same shape as the Bookings page, see docs/admin-booking-history.md)
       $packages => [['id' => 'budget-lite', 'name' => 'Budget Lite'], ...]  (admin-managed)
     Which bookings are sent is the controller's job (rule in config/admin/history.php).
     State tabs, filters, sorting and search run in the browser (resources/js/admin/history.js).
     The row and the View Details modal are the Bookings page's own components. --}}
@php
// Month filter options come from the data, newest first: ['2026-10' => 'October 2026', ...]
$months = collect($bookings)
->map(fn ($booking) => $booking['event']['start_date']->format('Y-m'))
->unique()
->sortDesc()
->mapWithKeys(fn ($month) => [$month => \Illuminate\Support\Carbon::parse($month . '-01')->format('F Y')]);
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
  <x-admin-header />
</head>

<body>
  <x-admin.sidebar />

  <main class="font-sans admin-content admin-bookings admin-history">
    <x-admin.page-header />

    @if (session('status'))
    <p class="mb-6 rounded-panel border border-success-border bg-success-soft px-6 py-3 font-medium text-success-text data-[tone=danger]:border-danger-border data-[tone=danger]:bg-danger-soft data-[tone=danger]:text-danger-text" role="status">{{ session('status') }}</p>
    @endif

    {{-- State tabs. Buttons with aria-pressed (there are no tab panels to switch). Counts are filled by history.js. --}}
    <div class="admin-history__tabs -mx-(--admin-pad) mb-4 flex flex-nowrap gap-3 overflow-x-auto px-(--admin-pad) py-1 [-webkit-overflow-scrolling:touch] scrollbar-none md:mx-0 md:flex-wrap md:overflow-x-visible md:p-0 [&::-webkit-scrollbar]:hidden" role="group" aria-label="Show bookings by state">
      @foreach (config('admin.history.tabs') as $value => $label)
      <button type="button" class="admin-tab group/tab inline-flex min-h-11 flex-none cursor-pointer items-center gap-2 rounded-full border border-transparent bg-surface px-6 py-1 text-fluid-sm font-medium whitespace-nowrap text-ink not-aria-pressed:hover:border-brand-text focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text aria-pressed:border-brand aria-pressed:bg-brand aria-pressed:text-white" data-history-tab="{{ $value }}"
        aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
        {{ $label }}
        <span class="admin-tab__count text-[0.8rem] font-normal text-ink-muted group-aria-pressed/tab:text-on-brand-muted empty:hidden" data-history-tab-count="{{ $value }}"></span>
      </button>
      @endforeach
    </div>

    {{-- Filters
         Same pattern as the Bookings page. Below 768px: search + a "Filters" button; Sort / Package / Month
         live in a bottom sheet (Bootstrap .offcanvas-md) and active ones show as removable chips.
         From 768px the sheet is "display: contents", so the pills sit inline as before.
         The state tabs above stay outside the sheet on purpose: they are the main way to switch
         state, so "Clear all" resets the selects only, never the tab.
         The selects keep their data-history-* hooks, so history.js filtering is unchanged;
         the sheet itself is wired by filter-sheet.js (started from bookings.js on every [data-filter-bar]). --}}
    <div class="admin-bookings__filters mb-6 flex flex-wrap gap-4 max-md:gap-3" role="search" aria-label="Filter booking history" data-filter-bar>
      <div class="admin-filter-sheet offcanvas-md offcanvas-bottom rounded-t-card font-sans [--bs-offcanvas-bg:var(--color-page)] [--bs-offcanvas-border-width:0] [--bs-offcanvas-color:var(--color-ink)] [--bs-offcanvas-height:auto] [--bs-offcanvas-padding-x:1.5rem] [--bs-offcanvas-padding-y:1rem] md:contents" tabindex="-1" id="historyFilterSheet"
        aria-labelledby="historyFilterSheetTitle" data-filter-sheet>
        <div class="offcanvas-header">
          <h2 class="offcanvas-title text-fluid-lg font-semibold" id="historyFilterSheetTitle">Filters</h2>
          <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#historyFilterSheet"
            aria-label="Close filters"></button>
        </div>

        <div class="offcanvas-body flex flex-col gap-3 pt-0 md:contents!">
          <div class="admin-filter relative flex min-h-13 items-center gap-3 rounded-full bg-surface px-6 py-1 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-brand-text data-open:z-20 data-open:outline-2 data-open:outline-offset-2 data-open:outline-brand-text flex-none md:flex-[1_1_14rem]">
            <label for="bh-sort" class="admin-filter__label text-fluid-sm font-medium whitespace-nowrap text-ink">Sorted by:</label>
            <div class="admin-filter__control relative min-w-0 flex-1">
              <select id="bh-sort" class="admin-filter__select" data-history-sort>
                @foreach (config('admin.history.sorts') as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="admin-filter relative flex min-h-13 items-center gap-3 rounded-full bg-surface px-6 py-1 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-brand-text data-open:z-20 data-open:outline-2 data-open:outline-offset-2 data-open:outline-brand-text flex-none md:flex-[1_1_14rem]">
            <label for="bh-package" class="admin-filter__label text-fluid-sm font-medium whitespace-nowrap text-ink">Package:</label>
            <div class="admin-filter__control relative min-w-0 flex-1">
              <select id="bh-package" class="admin-filter__select" data-history-package>
                <option value="all">All</option>
                @foreach ($packages as $package)
                <option value="{{ $package['id'] }}">{{ $package['name'] }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="admin-filter relative flex min-h-13 items-center gap-3 rounded-full bg-surface px-6 py-1 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-brand-text data-open:z-20 data-open:outline-2 data-open:outline-offset-2 data-open:outline-brand-text flex-none md:flex-[1_1_14rem]">
            <label for="bh-month" class="admin-filter__label text-fluid-sm font-medium whitespace-nowrap text-ink">Month:</label>
            <div class="admin-filter__control relative min-w-0 flex-1">
              <select id="bh-month" class="admin-filter__select" data-history-month>
                <option value="all">All</option>
                @foreach ($months as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>

          {{-- Sheet footer (phones only): filtering is live, "Done" just closes the sheet. --}}
          <div class="admin-filter-sheet__actions mt-2 grid grid-cols-2 gap-3 md:hidden">
            <button type="button" class="inline-flex min-h-12 cursor-pointer items-center justify-center rounded-panel border border-brand-text bg-transparent px-4 py-2 text-fluid-base font-semibold text-brand-text hover:bg-brand-soft focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text disabled:cursor-not-allowed disabled:opacity-50" data-filter-clear disabled>Clear all</button>
            <button type="button" class="inline-flex min-h-12 cursor-pointer items-center justify-center rounded-panel border-0 bg-brand px-4 py-2 text-fluid-base font-semibold text-on-brand hover:bg-brand-hover focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text disabled:cursor-not-allowed disabled:opacity-50" data-bs-dismiss="offcanvas"
              data-bs-target="#historyFilterSheet">Done</button>
          </div>
        </div>
      </div>

      <div class="admin-filter relative flex min-h-13 items-center gap-3 rounded-full bg-surface px-6 py-1 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-brand-text data-open:z-20 data-open:outline-2 data-open:outline-offset-2 data-open:outline-brand-text admin-filter--search flex-[3_1_18rem] max-md:order-1 max-md:min-w-0 max-md:flex-[1_1_0] max-md:px-4">
        <i class="ph ph-magnifying-glass admin-filter__icon text-fluid-2xl text-ink" aria-hidden="true"></i>
        <label for="bh-search" class="visually-hidden">Search booking history</label>
        <input type="search" id="bh-search" class="admin-filter__input min-w-0 flex-1 border-0 bg-transparent py-2 text-fluid-sm text-ink outline-0 placeholder:text-ink-subtle placeholder:opacity-100" placeholder="Search"
          autocomplete="off" data-history-search>
      </div>

      <button type="button" class="admin-filters-toggle inline-flex min-h-13 cursor-pointer items-center gap-2 rounded-full border-0 bg-surface px-6 py-1 text-fluid-sm font-medium whitespace-nowrap text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text max-md:order-2 md:hidden [&_.ph]:text-fluid-xl" data-bs-toggle="offcanvas"
        data-bs-target="#historyFilterSheet" aria-controls="historyFilterSheet" data-filter-toggle>
        <i class="ph ph-sliders-horizontal" aria-hidden="true"></i>
        <span>Filters</span>
        <span class="admin-filters-toggle__badge inline-flex h-[1.4rem] min-w-[1.4rem] items-center justify-center rounded-full bg-brand px-1 text-xs font-semibold text-white [[hidden]]:hidden" data-filter-count hidden>0</span>
      </button>

      {{-- Active filters as removable chips (phones only; filled by filter-sheet.js) --}}
      <div class="admin-filter-chips flex flex-[0_0_100%] flex-wrap gap-2 max-md:order-3 md:hidden [[hidden]]:hidden" data-filter-chips hidden></div>
    </div>

    {{-- Table --}}
    <div class="admin-bookings__panel rounded-card bg-panel px-6 pt-1 pb-2">
      <table class="admin-bookings__table block w-full border-separate [border-spacing:0_1.1rem] min-[1200px]:table" role="table">
        <caption class="visually-hidden">Booking history</caption>
        <thead role="rowgroup" class="sr-only min-[1200px]:table-header-group min-[1200px]:not-sr-only min-[1200px]:[&_th]:bg-brand min-[1200px]:[&_th]:p-4 min-[1200px]:[&_th]:text-center min-[1200px]:[&_th]:text-fluid-md min-[1200px]:[&_th]:font-semibold min-[1200px]:[&_th]:text-white min-[1200px]:[&_th:first-child]:rounded-l-panel min-[1200px]:[&_th:last-child]:rounded-r-panel">
          <tr role="row">
            <th role="columnheader" scope="col">Client</th>
            <th role="columnheader" scope="col">Schedule</th>
            <th role="columnheader" scope="col">Package</th>
            <th role="columnheader" scope="col">Event Type</th>
            <th role="columnheader" scope="col">Status</th>
            <th role="columnheader" scope="col"><span class="visually-hidden">Details</span></th>
          </tr>
        </thead>
        <tbody role="rowgroup" class="block min-[1200px]:table-row-group" data-history-body>
          @foreach ($bookings as $booking)
          <x-admin.booking-row :booking="$booking" :index="$loop->index" :history="true" />
          @endforeach
        </tbody>
      </table>

      <p class="admin-empty admin-bookings__empty px-2 py-6 text-center" data-history-empty @if (count($bookings)> 0) hidden @endif>
        @if (count($bookings) > 0) No bookings match your filters. @else No booking history yet. @endif
      </p>
    </div>

    {{-- Announces "Showing X of Y" to screen readers after each filter change. --}}
    <p class="visually-hidden" role="status" data-history-count></p>

    <x-admin.booking-modal />
  </main>
</body>

</html>