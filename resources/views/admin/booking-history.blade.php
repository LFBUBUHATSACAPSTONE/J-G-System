{{-- Admin Booking History page (front end only). Variables from the controller:
       $bookings => list of bookings (same shape as the Bookings page, see docs/admin-booking-history.md)
       $packages => [['id' => 'budget-lite', 'name' => 'Budget Lite'], ...]  (admin-managed)
     Which bookings are sent is the controller's job (rule in config/admin-history.php).
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

  <main class="admin-content admin-bookings admin-history p-4">
    <x-admin.page-header />

    @if (session('status'))
    <p class="admin-alert" role="status">{{ session('status') }}</p>
    @endif

    {{-- State tabs. Buttons with aria-pressed (there are no tab panels to switch). Counts are filled by history.js. --}}
    <div class="admin-history__tabs" role="group" aria-label="Show bookings by state">
      @foreach (config('admin-history.tabs') as $value => $label)
      <button type="button" class="admin-tab" data-history-tab="{{ $value }}"
        aria-pressed="{{ $loop->first ? 'true' : 'false' }}">
        {{ $label }}
        <span class="admin-tab__count" data-history-tab-count="{{ $value }}"></span>
      </button>
      @endforeach
    </div>

    {{-- Filters --}}
    <div class="admin-bookings__filters" role="search" aria-label="Filter booking history">
      <div class="admin-filter">
        <label for="bh-sort" class="admin-filter__label">Sorted by:</label>
        <div class="admin-filter__control">
          <select id="bh-sort" class="admin-filter__select" data-history-sort>
            @foreach (config('admin-history.sorts') as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
          </select>
          <i class="ph ph-caret-down admin-filter__chevron" aria-hidden="true"></i>
        </div>
      </div>

      <div class="admin-filter">
        <label for="bh-package" class="admin-filter__label">Package:</label>
        <div class="admin-filter__control">
          <select id="bh-package" class="admin-filter__select" data-history-package>
            <option value="all">All</option>
            @foreach ($packages as $package)
            <option value="{{ $package['id'] }}">{{ $package['name'] }}</option>
            @endforeach
          </select>
          <i class="ph ph-caret-down admin-filter__chevron" aria-hidden="true"></i>
        </div>
      </div>

      <div class="admin-filter">
        <label for="bh-month" class="admin-filter__label">Month:</label>
        <div class="admin-filter__control">
          <select id="bh-month" class="admin-filter__select" data-history-month>
            <option value="all">All</option>
            @foreach ($months as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
          </select>
          <i class="ph ph-caret-down admin-filter__chevron" aria-hidden="true"></i>
        </div>
      </div>

      <div class="admin-filter admin-filter--search">
        <i class="ph ph-magnifying-glass admin-filter__icon" aria-hidden="true"></i>
        <label for="bh-search" class="visually-hidden">Search booking history</label>
        <input type="search" id="bh-search" class="admin-filter__input" placeholder="Search"
          autocomplete="off" data-history-search>
      </div>
    </div>

    {{-- Table --}}
    <div class="admin-bookings__panel">
      <table class="admin-bookings__table" role="table">
        <caption class="visually-hidden">Booking history</caption>
        <thead role="rowgroup">
          <tr role="row">
            <th role="columnheader" scope="col">Client</th>
            <th role="columnheader" scope="col">Schedule</th>
            <th role="columnheader" scope="col">Package</th>
            <th role="columnheader" scope="col">Event Type</th>
            <th role="columnheader" scope="col"><span class="visually-hidden">Details</span></th>
            <th role="columnheader" scope="col">Status</th>
          </tr>
        </thead>
        <tbody role="rowgroup" data-history-body>
          @foreach ($bookings as $booking)
          <x-admin.booking-row :booking="$booking" :index="$loop->index" :history="true" />
          @endforeach
        </tbody>
      </table>

      <p class="admin-empty admin-bookings__empty" data-history-empty @if (count($bookings)> 0) hidden @endif>
        @if (count($bookings) > 0) No bookings match your filters. @else No booking history yet. @endif
      </p>
    </div>

    {{-- Announces "Showing X of Y" to screen readers after each filter change. --}}
    <p class="visually-hidden" role="status" data-history-count></p>

    <x-admin.booking-modal />
  </main>
</body>

</html>