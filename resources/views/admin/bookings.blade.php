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

<body>
  <x-admin.sidebar />

  <main class="font-sans admin-content admin-bookings">
    <x-admin.page-header />

    @if (session('status'))
    <p class="admin-alert" role="status">{{ session('status') }}</p>
    @endif

    {{-- Approve refused by the server (event limit reached). role=alert announces it at once. --}}
    @if (session('error'))
    <p class="admin-alert admin-alert--danger" role="alert">{{ session('error') }}</p>
    @endif
    <p class="admin-alert" role="status" tabindex="-1" data-bookings-feedback hidden></p>

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
          <button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#bookingsFilterSheet"
            aria-label="Close filters"></button>
        </div>

        <div class="offcanvas-body flex flex-col gap-3 pt-0 md:contents!">
          <div class="admin-filter relative flex min-h-13 items-center gap-3 rounded-full bg-surface px-6 py-1 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-brand-text data-open:z-20 data-open:outline-2 data-open:outline-offset-2 data-open:outline-brand-text flex-none md:flex-[1_1_14rem]">
            <label for="bk-sort" class="admin-filter__label text-fluid-sm font-medium whitespace-nowrap text-ink">Sorted by:</label>
            <div class="admin-filter__control relative min-w-0 flex-1">
              <select id="bk-sort" class="admin-filter__select" data-bookings-sort>
                @foreach (config('admin.bookings.sorts') as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="admin-filter relative flex min-h-13 items-center gap-3 rounded-full bg-surface px-6 py-1 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-brand-text data-open:z-20 data-open:outline-2 data-open:outline-offset-2 data-open:outline-brand-text flex-none md:flex-[1_1_14rem]">
            <label for="bk-status" class="admin-filter__label text-fluid-sm font-medium whitespace-nowrap text-ink">Status:</label>
            <div class="admin-filter__control relative min-w-0 flex-1">
              <select id="bk-status" class="admin-filter__select" data-bookings-status>
                @foreach (config('admin.bookings.status_filters') as $value=> $label)
                <option value="{{ $value }}">{{ $label }}</option>
                @endforeach
              </select>
            </div>
          </div>

          <div class="admin-filter relative flex min-h-13 items-center gap-3 rounded-full bg-surface px-6 py-1 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-brand-text data-open:z-20 data-open:outline-2 data-open:outline-offset-2 data-open:outline-brand-text flex-none md:flex-[1_1_14rem]">
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

          {{-- Sheet footer (phones only): filtering is live, "Done" just closes the sheet. --}}
          <div class="admin-filter-sheet__actions mt-2 grid grid-cols-2 gap-3 md:hidden">
            <button type="button" class="admin-btn admin-btn--outline px-4 disabled:cursor-not-allowed disabled:opacity-50" data-filter-clear disabled>Clear all</button>
            <button type="button" class="admin-btn admin-btn--brand px-4 disabled:cursor-not-allowed disabled:opacity-50" data-bs-dismiss="offcanvas"
              data-bs-target="#bookingsFilterSheet">Done</button>
          </div>
        </div>
      </div>

      <div class="admin-filter relative flex min-h-13 items-center gap-3 rounded-full bg-surface px-6 py-1 focus-within:outline-2 focus-within:outline-offset-2 focus-within:outline-brand-text data-open:z-20 data-open:outline-2 data-open:outline-offset-2 data-open:outline-brand-text admin-filter--search flex-[3_1_18rem] max-md:order-1 max-md:min-w-0 max-md:flex-[1_1_0] max-md:px-4">
        <i class="ph ph-magnifying-glass admin-filter__icon text-fluid-2xl text-ink" aria-hidden="true"></i>
        <label for="bk-search" class="visually-hidden">Search bookings</label>
        <input type="search" id="bk-search" class="admin-filter__input min-w-0 flex-1 border-0 bg-transparent py-2 text-fluid-sm text-ink outline-0 placeholder:text-ink-subtle placeholder:opacity-100" placeholder="Search"
          autocomplete="off" data-bookings-search>
      </div>

      <button type="button" class="admin-filters-toggle inline-flex min-h-13 cursor-pointer items-center gap-2 rounded-full border-0 bg-surface px-6 py-1 text-fluid-sm font-medium whitespace-nowrap text-ink focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text max-md:order-2 md:hidden [&_.ph]:text-fluid-xl" data-bs-toggle="offcanvas"
        data-bs-target="#bookingsFilterSheet" aria-controls="bookingsFilterSheet" data-filter-toggle>
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
        <caption class="visually-hidden">Bookings</caption>
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
        <tbody role="rowgroup" class="block min-[1200px]:table-row-group" data-bookings-body>
          @foreach ($bookings as $booking)
          <x-admin.booking-row :booking="$booking" :index="$loop->index" :full-dates="$fullDates ?? []" />
          @endforeach
        </tbody>
      </table>

      <p class="admin-empty admin-bookings__empty px-2 py-6 text-center" data-bookings-empty @if (count($bookings)> 0) hidden @endif>
        @if (count($bookings) > 0) No bookings match your filters. @else No bookings yet. @endif
      </p>
    </div>

    {{-- Announces "Showing X of Y" to screen readers after each filter change. --}}
    <p class="visually-hidden" role="status" data-bookings-count></p>

    <x-admin.booking-modal />

    {{-- Approve / Decline / Verify Payment / Cancel confirmation. Decline and Cancel also ask for a reason.
         Filled by bookings.js from each action form's data-action-modal-copy (config/admin/bookings.php). --}}
    <x-booking.action-modal id="bookingActionModal" :reason="true" />
  </main>
</body>

</html>