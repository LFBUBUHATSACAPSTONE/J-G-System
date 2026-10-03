{{-- Admin Calendar page (front end only). Variables from the controller:
       $month    => Carbon, any day in the month to show (the page works out the full grid)
       $events   => bookings that touch the visible grid (shape in docs/admin-bookings.md; the
                    page reads status, reference, client.name, package.name and event.*)
       $holidays => OPTIONAL ['2026-04-02' => 'Maundy Thursday', ...] for movable holidays
     Month arrows are plain links (?month=YYYY-MM), so the page works without JS. Colours and the
     status-to-colour mapping come from config/admin-calendar.php. Nothing is hard-coded here. --}}


@php
$month = $month->copy()->startOfMonth();
$gridStart = $month->copy()->startOfWeek(0); // Sunday
$gridEnd = $month->copy()->endOfMonth()->endOfWeek(6); // Saturday
$statuses = config('admin-calendar.statuses');
$tones = config('admin-calendar.tones');
$fixedHolidays = config('admin-calendar.holidays');
$movableHolidays = $holidays ?? [];
$today = now()->toDateString();

// Bookings grouped by date. A multi-day booking appears on every day it covers.
$byDate = [];
foreach ($events as $booking) {
if (! isset($statuses[$booking['status']])) {
continue; // cancelled / declined: the date is free again
}
$cursor = $booking['event']['start_date']->copy()->startOfDay();
while ($cursor->lte($booking['event']['end_date'])) {
if ($cursor->betweenIncluded($gridStart, $gridEnd)) {
$byDate[$cursor->toDateString()][] = $booking;
}
$cursor->addDay();
}
}
$holidayFor = fn ($d) => $movableHolidays[$d->toDateString()] ?? $fixedHolidays[$d->format('m-d')] ?? null;


// Cell colour = first tone in the config's priority list that the date holds.
$toneFor = function (array $dayBookings) use ($statuses, $tones) {
$present = array_map(fn ($b) => $statuses[$b['status']]['tone'], $dayBookings);


foreach (array_keys($tones) as $tone) {
if (in_array($tone, $present, true)) {
return $tone;
}
}
};

$dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];

$prev = $month->copy()->subMonth()->format('Y-m');
$next = $month->copy()->addMonth()->format('Y-m');
@endphp
<!DOCTYPE html>
<html lang="en">

<head>
  <x-admin-header />
</head>

<body>
  <x-admin.sidebar />

  <main class="admin-content admin-cal p-4" data-calendar>
    <x-admin.page-header />

    <div class="admin-cal__layout">
      <section class="admin-cal__wrap" aria-labelledby="calMonth">
        <div class="admin-cal__nav">
          <a class="admin-cal__nav-link" href="{{ route('admin.calendar', ['month' => $prev]) }}" aria-label="Previous month">
            <i class="ph ph-caret-left" aria-hidden="true"></i>
          </a>
          <h2 class="admin-cal__month" id="calMonth">{{ $month->format('F Y') }}</h2>
          <a class="admin-cal__nav-link" href="{{ route('admin.calendar', ['month' => $next]) }}" aria-label="Next month">
            <i class="ph ph-caret-right" aria-hidden="true"></i>
          </a>
        </div>

        <table class="admin-cal__table">
          <caption class="visually-hidden">Bookings and holidays for {{ $month->format('F Y') }}</caption>
          <thead>
            <tr>
              @foreach ($dayNames as $i => $name)
              <th scope="col" @class(['admin-cal__dow', 'admin-cal__dow--weekend'=> in_array($i, [0, 6])])>
                <span class="admin-cal__dow-full">{{ $name }}</span>
                <span class="admin-cal__dow-short" aria-hidden="true">{{ substr($name, 0, 3) }}</span>
              </th>
              @endforeach
            </tr>
          </thead>
          <tbody>
            @for ($week = $gridStart->copy(); $week->lte($gridEnd); $week->addWeek())
            <tr>
              @for ($i = 0; $i < 7; $i++)
                @php
                $d=$week->copy()->addDays($i);
                $iso = $d->toDateString();
                $dayBookings = $byDate[$iso] ?? [];
                $tone = $dayBookings ? $toneFor($dayBookings) : null;
                $holiday = $holidayFor($d);
                $cellClass = [
                'admin-cal__cell',
                'admin-cal__cell--weekend' => $d->isWeekend(),
                'admin-cal__cell--outside' => ! $d->isSameMonth($month),
                'admin-cal__cell--today' => $iso === $today,
                ];
                @endphp
                <td @class($cellClass)>
                  @if ($dayBookings)
                  <button type="button" class="admin-cal__day admin-cal__day--{{ $tone }}"
                    data-cal-date="{{ $iso }}" aria-pressed="false" aria-controls="calPanel">
                    <span class="admin-cal__num">{{ $d->day }}</span>
                    @if ($holiday)<span class="admin-cal__holiday">{{ $holiday }}</span>@endif
                    <span class="admin-cal__peek" aria-hidden="true">
                      @if (count($dayBookings) === 1)
                      <span>{{ $dayBookings[0]['reference'] }}</span>
                      <span>{{ $dayBookings[0]['package']['name'] }}</span>
                      @else
                      <span>{{ count($dayBookings) }} bookings</span>
                      @endif
                    </span>
                    <span class="visually-hidden">, {{ $d->format('l, F j') }}: {{ count($dayBookings) }} {{ \Illuminate\Support\Str::plural('booking', count($dayBookings)) }}, {{ strtolower($tones[$tone]) }}</span>
                  </button>
                  @else
                  <div class="admin-cal__day">
                    <span class="admin-cal__num">{{ $d->day }}</span>
                    @if ($holiday)<span class="admin-cal__holiday">{{ $holiday }}</span>@endif
                  </div>
                  @endif
                </td>
                @endfor
            </tr>
            @endfor
          </tbody>
        </table>

        <ul class="admin-cal__legend" aria-label="Legend">
          @foreach ($tones as $tone => $label)
          <li><span class="admin-cal__swatch admin-cal__swatch--{{ $tone }}" aria-hidden="true"></span>{{ $label }}</li>
          @endforeach
        </ul>
      </section>

      <aside class="admin-cal__panel" id="calPanel" aria-labelledby="calPanelTitle" data-cal-panel
        data-empty-title="Event details"
        data-empty-sub="Hover a highlighted date to preview it. Select it to keep it open.">
        <h2 class="admin-cal__panel-title" id="calPanelTitle" data-cal-title>Event details</h2>
        <p class="admin-cal__panel-sub" data-cal-sub>Hover a highlighted date to preview it. Select it to keep it open.</p>
        <div class="admin-cal__panel-body" data-cal-body></div>
      </aside>
    </div>

    {{-- Announces only when a date is selected or cleared, not on every hover. --}}
    <p class="visually-hidden" role="status" data-cal-status></p>

    {{-- Panel content per date. The script copies one of these into the panel. --}}
    <div hidden>
      @foreach ($byDate as $iso => $dayBookings)
      @php $d = \Illuminate\Support\Carbon::parse($iso); @endphp
      <template data-cal-detail="{{ $iso }}" data-title="{{ $d->format('l, F j, Y') }}" data-sub="{{ $holidayFor($d) }}">
        @foreach ($dayBookings as $booking)
        @php $s = $statuses[$booking['status']]; @endphp
        <x-admin.calendar-event :booking="$booking" :tone="$s['tone']" :label="$s['label']" />
        @endforeach
      </template>
      @endforeach
    </div>
  </main>
</body>

</html>