{{-- Admin dashboard (front end only). Variables from the controller
       $stats          => ['total' => ['value' => 5, 'change' => 2], ...]   keys = config/admin/dashboard.php 'cards'
       $attention      => bookings that need an action, oldest first
       $upcomingEvents => approved + paid bookings, soonest first
       $packageRate    => [['id' => 'budget-lite', 'label' => 'Budget Lite', 'count' => 18], ...]
     Nothing here is hard-coded: card titles, filters and limits come from config/admin/dashboard.php,
     status look from config/admin/bookings.php. Every link goes to Bookings (or Calendar) with query
     params that resources/js/admin/bookings.js reads: ?status= ?package= ?booking= --}}

@php
// Route::has() keeps the dashboard rendering before the other pages exist.
$bookingsUrl = fn (array $query = []) => Route::has('admin.bookings') ? route('admin.bookings', $query) : '#';

$statuses = config('admin.bookings.statuses', []);
$actionLabels = config('admin.dashboard.attention_actions', []);

$attentionAll = collect($attention);
$attentionShown = $attentionAll->take(config('admin.dashboard.attention_limit', 5));
$upcoming = collect($upcomingEvents)->take(config('admin.dashboard.upcoming_limit', 5));

$calendarMonth = ($upcoming->first()['date'] ?? now())->format('Y-m');
$calendarUrl = Route::has('admin.calendar') ? route('admin.calendar', ['month' => $calendarMonth]) : '#';
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
  <x-admin-header />
</head>

<body>
  <x-admin.sidebar />

  <main class="admin-content admin-dashboard p-3 p-sm-4">
    <x-admin.page-header />

    {{-- KPI cards: each one opens Bookings already filtered to what it counts. --}}
    <div class="admin-dashboard__stats">
      @foreach (config('admin.dashboard.cards', []) as $key => $card)
      @continue(! isset($stats[$key]))
      <x-admin.stat-card
        :featured="$card['featured'] ?? false"
        :label="$card['label']"
        :value="$stats[$key]['value']"
        :change="$stats[$key]['change'] ?? null"
        :higher="$card['higher'] ?? 'neutral'"
        :hint="$card['hint'] ?? null"
        :href="$bookingsUrl(($card['status'] ?? 'all') === 'all' ? [] : ['status' => $card['status']])" />
      @endforeach
    </div>

    <div class="admin-dashboard__grid">

      {{-- Needs your attention: pending approvals and payments to verify, in one queue. --}}
      <section class="admin-card admin-dashboard__attention" aria-labelledby="attention-title">
        <div class="admin-card__head">
          <h2 id="attention-title" class="admin-card__title">Needs your attention</h2>
          @if ($attentionAll->isNotEmpty())
          <span class="admin-count" aria-label="{{ $attentionAll->count() }} waiting">{{ $attentionAll->count() }}</span>
          @endif
        </div>

        @if ($attentionShown->isNotEmpty())
        <ul class="admin-attn">
          @foreach ($attentionShown as $item)
          @php
          $status = $statuses[$item['status']] ?? ['label' => ucfirst(str_replace('_', ' ', $item['status'])), 'group' => 'all'];
          $tone = $status['badge']['tone'] ?? 'neutral';
          $action = $actionLabels[$item['status']] ?? 'Open';
          $href = $bookingsUrl(['status' => $status['group'], 'booking' => $item['id']]);
          @endphp
          <li class="admin-attn__item admin-attn__item--{{ $tone }}">
            <span class="admin-badge admin-badge--{{ $tone }}">
              @if (! empty($status['icon']))<i class="ph ph-{{ $status['icon'] }}" aria-hidden="true"></i>@endif
              {{ $status['label'] }}
            </span>

            <div class="admin-attn__info">
              <p class="admin-attn__who">
                <strong>{{ $item['client'] }}</strong>
                <span class="admin-attn__ref">{{ $item['reference'] }}</span>
              </p>
              <p class="admin-attn__meta">
                <span class="admin-attn__pkg">{{ $item['package'] }} ·
                  <time datetime="{{ $item['date']->toDateString() }}">{{ $item['date']->format('M j, Y') }}</time></span>
                <span class="admin-attn__dot" aria-hidden="true">·</span>
                <span class="admin-attn__req">requested <time datetime="{{ $item['submitted_at']->toIso8601String() }}">{{ $item['submitted_at']->diffForHumans() }}</time></span>
              </p>
            </div>

            <a href="{{ $href }}" class="admin-attn__action"
              aria-label="{{ $action }}, booking {{ $item['reference'] }} from {{ $item['client'] }}">
              {{ $action }} <i class="ph ph-arrow-right" aria-hidden="true"></i>
            </a>
          </li>
          @endforeach
        </ul>

        @if ($attentionAll->count() > $attentionShown->count())
        <a href="{{ $bookingsUrl() }}" class="admin-card__more">
          View all {{ $attentionAll->count() }} in Bookings <i class="ph ph-arrow-right" aria-hidden="true"></i>
        </a>
        @endif
        @else
        <p class="admin-empty">You're all caught up. Nothing needs review right now.</p>
        @endif
      </section>

      {{-- Upcoming events: approved and paid, soonest first. --}}
      <section class="admin-card admin-dashboard__upcoming" aria-labelledby="upcoming-title">
        <div class="admin-card__head">
          <h2 id="upcoming-title" class="admin-card__title">Upcoming Events</h2>
          <a href="{{ $calendarUrl }}" class="admin-card__more admin-card__more--head">
            <span class="admin-card__more-prefix">View </span>calendar <i class="ph ph-arrow-right" aria-hidden="true"></i>
          </a>
        </div>

        @forelse ($upcoming as $event)
        @if ($loop->first) <ul class="admin-upcoming"> @endif
          @php
          // "Today" / "Tomorrow" / "In 5 days"; nothing for dates already past.
          $days = (int) now()->startOfDay()->diffInDays($event['date']->copy()->startOfDay(), false);
          $when = $days === 0 ? 'Today' : ($days === 1 ? 'Tomorrow' : ($days > 1 ? "In {$days} days" : null));
          @endphp
          <li>
            <a href="{{ $bookingsUrl(['booking' => $event['id']]) }}" class="admin-upcoming__item"
              aria-label="{{ $event['event'] }} for {{ $event['client'] }}, {{ $event['date']->format('F j, Y') }}. View booking">
              <time class="admin-upcoming__date" datetime="{{ $event['date']->toDateString() }}">
                <span class="admin-upcoming__month">{{ $event['date']->format('M') }}</span>
                <span class="admin-upcoming__day">{{ $event['date']->format('j') }}</span>
              </time>
              <span class="admin-upcoming__info">
                <strong>{{ $event['event'] }}</strong>
                <span>{{ $event['client'] }} · {{ $event['package'] }}</span>
              </span>
              <span class="admin-upcoming__when">
                @if ($when)<strong>{{ $when }}</strong>@endif
                @if (! empty($event['time']))<span>{{ $event['time'] }}</span>@endif
              </span>
            </a>
          </li>
          @if ($loop->last)
        </ul> @endif
        @empty
        <p class="admin-empty">No upcoming events.</p>
        @endforelse
      </section>

      <x-admin.package-rate class="admin-dashboard__rate" :packages="$packageRate" />
    </div>
  </main>
</body>

</html>