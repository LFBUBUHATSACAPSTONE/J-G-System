{{-- Admin dashboard (front end only). Every figure comes from the controller (the exact shape of $stats, $upcomingEvents,
     $pendingApprovals and $packageRate). Nothing here is hard-coded. --}}
<!DOCTYPE html>
<html lang="en">

<head>
  <x-admin-header />
</head>

<body>
  <x-admin.sidebar />

  <main class="admin-content admin-dashboard p-4">
    <x-admin.page-header />

    <div class="admin-dashboard__grid">
      <x-admin.stat-card class="admin-dashboard__total" featured
        label="Total Bookings"
        :value="$stats['total']['value']"
        :delta="$stats['total']['delta'] ?? null"
        :direction="$stats['total']['direction'] ?? 'up'" />

      <x-admin.stat-card class="admin-dashboard__confirmed"
        label="Confirmed Events"
        :value="$stats['confirmed']['value']"
        :delta="$stats['confirmed']['delta'] ?? null"
        :direction="$stats['confirmed']['direction'] ?? 'up'" />

      <x-admin.stat-card class="admin-dashboard__pending"
        label="Pending Approval"
        :value="$stats['pending']['value']"
        :delta="$stats['pending']['delta'] ?? null"
        :direction="$stats['pending']['direction'] ?? 'up'" />

      <x-admin.stat-card class="admin-dashboard__cancelled"
        label="Cancelled Events"
        :value="$stats['cancelled']['value']"
        :delta="$stats['cancelled']['delta'] ?? null"
        :direction="$stats['cancelled']['direction'] ?? 'up'" />

      {{-- Upcoming events --}}
      <section class="admin-card admin-dashboard__upcoming" aria-labelledby="upcoming-title">
        <h2 id="upcoming-title" class="admin-card__title">Upcoming Events</h2>

        @forelse ($upcomingEvents as $event)
        @if ($loop->first) <ul class="admin-list"> @endif
          <li class="admin-row">
            <time datetime="{{ $event['date']->toDateString() }}">{{ $event['date']->format('F j, Y') }}</time>
            <span class="admin-row__package">{{ $event['package'] }}</span>
          </li>
          @if ($loop->last)
        </ul> @endif
        @empty
        <p class="admin-empty">No upcoming events.</p>
        @endforelse
      </section>

      {{-- Pending approvals --}}
      <section class="admin-card admin-dashboard__pending-list" aria-labelledby="pending-title">
        <h2 id="pending-title" class="admin-card__title">Pending Approval</h2>

        @forelse ($pendingApprovals as $booking)
        @if ($loop->first) <ul class="admin-list"> @endif
          @php
          // Route::has() keeps the dashboard rendering before the Bookings page exists.
          $href = Route::has('admin.bookings') ? route('admin.bookings') : '#';
          @endphp
          <li class="admin-row admin-row--booking">
            <span class="admin-row__ref">{{ $booking['reference'] }}</span>
            <time datetime="{{ $booking['date']->toDateString() }}">{{ $booking['date']->format('F j, Y') }}</time>
            <span class="admin-row__package">{{ $booking['package'] }}</span>
            <a href="{{ $href }}" class="admin-row__link" aria-label="Review booking {{ $booking['reference'] }}">
              Booking <i class="ph ph-arrow-right" aria-hidden="true"></i>
            </a>
          </li>
          @if ($loop->last)
        </ul> @endif
        @empty
        <p class="admin-empty">Nothing is waiting for approval.</p>
        @endforelse
      </section>

      <x-admin.package-rate class="admin-dashboard__rate" :packages="$packageRate" />
    </div>
  </main>
</body>

</html>