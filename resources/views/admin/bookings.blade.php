{{-- Admin Bookings page (front end only). Variables from the controller
       $bookings => list of bookings
       $packages => [['id' => 'budget-lite', 'name' => 'Budget Lite'], ...]  (admin-managed)
       $fullDates => ['2026-10-14', ...] days already at the event limit (config/scheduling.php);
                     optional, a pending booking on one of them can't be approved (docs/event-capacity.md)
     Filtering, sorting and search run in the browser (resources/js/admin/bookings.js).
     Nothing here is hard-coded; status badge + actions come from config/admin/bookings.php. 

--}}
<!DOCTYPE html>
<html lang="en">

<head>
  <x-admin-header />
</head>

<body>
  <x-admin.sidebar />

  <main class="admin-content admin-bookings p-4">
    <x-admin.page-header />

    @if (session('status'))
    <p class="admin-alert" role="status">{{ session('status') }}</p>
    @endif

    {{-- Approve refused by the server (event limit reached). role=alert announces it at once. --}}
    @if (session('error'))
    <p class="admin-alert admin-alert--danger" role="alert">{{ session('error') }}</p>
    @endif

    {{-- Filters --}}
    <div class="admin-bookings__filters" role="search" aria-label="Filter bookings">
      <div class="admin-filter">
        <label for="bk-sort" class="admin-filter__label">Sorted by:</label>
        <div class="admin-filter__control">
          <select id="bk-sort" class="admin-filter__select" data-bookings-sort>

            @foreach (config('admin.bookings.sorts') as $value => $label)
            <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="admin-filter">
        <label for="bk-status" class="admin-filter__label">Status:</label>
        <div class="admin-filter__control">
          <select id="bk-status" class="admin-filter__select" data-bookings-status>
            @foreach (config('admin.bookings.status_filters') as $value=> $label)
            <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="admin-filter">
        <label for="bk-package" class="admin-filter__label">Package:</label>
        <div class="admin-filter__control">
          <select id="bk-package" class="admin-filter__select" data-bookings-package>
            <option value="all">All</option>
            @foreach ($packages as $package)
            <option value="{{ $package['id'] }}">{{ $package['name'] }}</option>
            @endforeach
          </select>
        </div>
      </div>

      <div class="admin-filter admin-filter--search">
        <i class="ph ph-magnifying-glass admin-filter__icon" aria-hidden="true"></i>
        <label for="bk-search" class="visually-hidden">Search bookings</label>
        <input type="search" id="bk-search" class="admin-filter__input" placeholder="Search"
          autocomplete="off" data-bookings-search>
      </div>
    </div>

    {{-- Table --}}
    <div class="admin-bookings__panel">
      <table class="admin-bookings__table" role="table">
        <caption class="visually-hidden">Bookings</caption>
        <thead role="rowgroup">
          <tr role="row">
            <th role="columnheader" scope="col">Client</th>
            <th role="columnheader" scope="col">Schedule</th>
            <th role="columnheader" scope="col">Package</th>
            <th role="columnheader" scope="col">Event Type</th>
            <th role="columnheader" scope="col">Status</th>
            <th role="columnheader" scope="col">Actions</th>
          </tr>
        </thead>
        <tbody role="rowgroup" data-bookings-body>
          @foreach ($bookings as $booking)
          <x-admin.booking-row :booking="$booking" :index="$loop->index" :full-dates="$fullDates ?? []" />
          @endforeach
        </tbody>
      </table>

      <p class="admin-empty admin-bookings__empty" data-bookings-empty @if (count($bookings)> 0) hidden @endif>
        @if (count($bookings) > 0) No bookings match your filters. @else No bookings yet. @endif
      </p>
    </div>

    {{-- Announces "Showing X of Y" to screen readers after each filter change. --}}
    <p class="visually-hidden" role="status" data-bookings-count></p>

    <x-admin.booking-modal />
  </main>
</body>

</html>