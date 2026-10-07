@php
$dashboardConfig = [
  'cards' => collect(config('admin.dashboard.cards', []))->map(fn ($card, $key) => [
    'key' => $key,
    'label' => $card['label'],
    'status' => $card['status'] ?? 'all',
    'higher' => $card['higher'] ?? 'neutral',
    'hint' => $card['hint'] ?? null,
    'featured' => $card['featured'] ?? false,
  ])->values(),
  'statuses' => config('admin.bookings.statuses', []),
  'attentionActions' => config('admin.dashboard.attention_actions', []),
  'attentionLimit' => config('admin.dashboard.attention_limit', 5),
  'upcomingLimit' => config('admin.dashboard.upcoming_limit', 5),
  'chartTop' => config('admin.dashboard.chart_top', 5),
  'bookingsUrl' => route('admin.bookings'),
  'calendarUrl' => route('admin.calendar'),
];
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
  <x-admin-header />
</head>

<body>
  <x-admin.sidebar />

  <main class="admin-content admin-dashboard p-4" data-admin-dashboard
    data-endpoint="{{ route('admin.dashboard.data') }}"
    data-config="{{ json_encode($dashboardConfig, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
    aria-busy="true">
    <x-admin.page-header />

    <div class="admin-dashboard__stats" data-dashboard-stats></div>

    <div class="admin-dashboard__grid">
      <section class="admin-card admin-dashboard__attention" aria-labelledby="attention-title">
        <div class="admin-card__head">
          <h2 id="attention-title" class="admin-card__title">Needs your attention</h2>
          <span class="admin-count" data-dashboard-attention-count hidden></span>
        </div>
        <div data-dashboard-attention>
          <p class="admin-empty">Loading dashboard data...</p>
        </div>
      </section>

      <section class="admin-card admin-dashboard__upcoming" aria-labelledby="upcoming-title">
        <div class="admin-card__head">
          <h2 id="upcoming-title" class="admin-card__title">Upcoming Events</h2>
          <a href="{{ route('admin.calendar') }}" class="admin-card__more admin-card__more--head"
            data-dashboard-calendar>
            View calendar <i class="ph ph-arrow-right" aria-hidden="true"></i>
          </a>
        </div>
        <div data-dashboard-upcoming>
          <p class="admin-empty">Loading upcoming events...</p>
        </div>
      </section>

      <section class="admin-card admin-package-rate admin-dashboard__rate" aria-labelledby="package-rate-title">
        <h2 id="package-rate-title" class="admin-card__title">Package Rate</h2>
        <div data-dashboard-package-rate>
          <p class="admin-empty">Loading package totals...</p>
        </div>
      </section>
    </div>
  </main>
</body>

</html>
