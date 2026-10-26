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
'attentionLimit' => config('admin.dashboard.attention_limit', 3),
'upcomingLimit' => config('admin.dashboard.upcoming_limit', 3),
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

  <main class="admin-content font-sans" data-admin-dashboard
    data-endpoint="{{ route('admin.dashboard.data') }}"
    data-config="{{ json_encode($dashboardConfig, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_HEX_TAG) }}"
    aria-busy="true">
    <x-admin.page-header :compact="true" />

    <div class="mb-6 grid grid-cols-2 gap-4 min-[768px]:grid-cols-6 min-[768px]:gap-6 min-[1400px]:grid-cols-5 max-[399.98px]:grid-cols-1 max-[767.98px]:*:first:col-span-full min-[768px]:max-[1399.98px]:*:col-span-2 min-[768px]:max-[1399.98px]:[&>:nth-child(n+4)]:col-span-3" data-dashboard-stats></div>

    <div class="grid grid-cols-[minmax(0,1fr)] gap-4 min-[768px]:gap-6 min-[1200px]:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
      <section class="rounded-card bg-surface p-4 min-[576px]:p-6" aria-labelledby="attention-title">
        <div class="flex items-start justify-between gap-x-4 gap-y-2 max-[575.98px]:items-center">
          <div class="flex min-w-0 items-start gap-2">
            <h2 id="attention-title" class="mt-0 mb-4 text-fluid-base font-semibold text-brand-text min-[576px]:text-fluid-heading flex items-center gap-2">Needs your attention</h2>
            <span class="inline-flex min-w-7 items-center justify-center rounded-full bg-warning px-2 text-[0.8rem] font-bold text-ink" data-dashboard-attention-count hidden></span>
          </div>
          <a href="{{ route('admin.bookings') }}" class="inline-flex items-center gap-1 text-fluid-sm font-semibold text-brand-text no-underline focus-visible:rounded-field focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text mt-1 max-[575.98px]:mt-0 max-[575.98px]:whitespace-nowrap">
            <span class="max-[399.98px]:sr-only">View </span>bookings <i class="ph ph-arrow-right" aria-hidden="true"></i>
          </a>
        </div>
        <div data-dashboard-attention>
          <p class="m-0 text-fluid-sm text-ink-muted">Loading dashboard data...</p>
        </div>
      </section>

      <section class="rounded-card bg-surface p-4 min-[576px]:p-6" aria-labelledby="upcoming-title">
        <div class="flex items-start justify-between gap-x-4 gap-y-2 max-[575.98px]:items-center">
          <h2 id="upcoming-title" class="mt-0 mb-4 text-fluid-base font-semibold text-brand-text min-[576px]:text-fluid-heading flex items-center gap-2">Upcoming Events</h2>
          <a href="{{ route('admin.calendar') }}" class="inline-flex items-center gap-1 text-fluid-sm font-semibold text-brand-text no-underline focus-visible:rounded-field focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text mt-1 max-[575.98px]:mt-0 max-[575.98px]:whitespace-nowrap"
            data-dashboard-calendar>
            <span class="max-[399.98px]:sr-only">View </span>calendar <i class="ph ph-arrow-right" aria-hidden="true"></i>
          </a>
        </div>
        <div data-dashboard-upcoming>
          <p class="m-0 text-fluid-sm text-ink-muted">Loading upcoming events...</p>
        </div>
      </section>

      <section class="rounded-card bg-surface p-4 min-[576px]:p-6 min-[1200px]:col-span-full" aria-labelledby="package-rate-title">
        <h2 id="package-rate-title" class="mt-0 mb-4 text-fluid-base font-semibold text-brand-text min-[576px]:text-fluid-heading">Package Rate <span class="text-[0.8em] font-normal text-[#2563eb] max-[575.98px]:block">(approved)</span></h2>
        <div data-dashboard-package-rate>
          <p class="m-0 text-fluid-sm text-ink-muted">Loading package totals...</p>
        </div>
      </section>
    </div>
  </main>
</body>

</html>