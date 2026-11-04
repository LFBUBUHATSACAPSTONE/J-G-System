{{-- Admin Calendar page. Variables from the controller:
       $month    => Carbon, any day in the month to show (the page works out the full grid)
       $events   => bookings that touch the visible grid (shape in docs/admin-bookings.md; the
                    page reads status, reference, client.name, package.name and event.*)
       $holidays => OPTIONAL ['2026-04-02' => 'Maundy Thursday', ...] for movable holidays
     Month arrows are plain links (?month=YYYY-MM), so the page works without JS. Colours and the
     status-to-colour mapping come from config/admin/calendar.php. Nothing is hard-coded here. --}}
@php
$month = $month->copy()->startOfMonth();
$gridStart = $month->copy()->startOfWeek(0); // Sunday
$gridEnd = $month->copy()->endOfMonth()->endOfWeek(6); // Saturday
$statuses = config('admin.calendar.statuses');
$tones = config('admin.calendar.tones');
$fixedHolidays = config('admin.calendar.holidays');
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
// Dates with no coloured tone (only pending bookings) look free; approved bookings are listed first.
$toneRank = array_flip(array_keys($tones));
foreach ($byDate as $iso => $list) {
usort($list, fn ($a, $b) => ($toneRank[$statuses[$a['status']]['tone']] ?? 99) <=> ($toneRank[$statuses[$b['status']]['tone']] ?? 99));
  $byDate[$iso] = $list;
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
  // Rows in the grid (4, 5 or 6). The CSS divides the free screen height by this so the whole month
  // fits without scrolling when the window is tall enough (see --cal-rows in _calendar.scss).
  $weeks = (int) ceil(($month->dayOfWeek + $month->daysInMonth) / 7);
  $dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
  $prev = $month->copy()->subMonth()->format('Y-m');
  $next = $month->copy()->addMonth()->format('Y-m');
  // Class strings for this page. Whole strings, never built from pieces, so Tailwind can find them.
  $cal = [
  'layout' => 'flex flex-col gap-4 min-[1200px]:flex-row min-[1200px]:items-start',
  'wrap' => '@container min-w-0 flex-auto rounded-card bg-cal-wrap p-2 min-[576px]:p-4',
  'bar' => 'mb-4 grid justify-items-center gap-2 @min-[40rem]:grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] @min-[40rem]:items-center @min-[40rem]:justify-items-stretch',
  'nav' => 'flex items-center justify-center gap-[var(--cal-gap)] [--cal-arrow:2.75rem] [--cal-title-em:8.6] [--cal-gap:0.5rem] @min-[30rem]:[--cal-gap:1rem] @min-[40rem]:col-start-2 @min-[40rem]:row-start-1',
  'month' => 'm-0 w-[calc(var(--cal-title-em)*1em)] flex-none bg-[linear-gradient(90deg,var(--color-brand-text),var(--color-brand-end))] bg-clip-text text-center text-[length:clamp(1rem,calc((100cqi_-_2*var(--cal-arrow)_-_2*var(--cal-gap))/var(--cal-title-em)),2.25rem)] font-bold whitespace-nowrap text-transparent [-webkit-text-fill-color:transparent] forced-colors:bg-none forced-colors:text-[CanvasText] forced-colors:[-webkit-text-fill-color:currentColor]',
  'navLink' => 'grid size-[var(--cal-arrow,2.75rem)] flex-none place-items-center rounded-full text-fluid-icon text-ink no-underline hover:bg-surface focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-brand-text',
  'legend' => 'm-0 flex list-none flex-wrap justify-center gap-x-4 gap-y-1 p-0 text-fluid-sm text-ink @min-[40rem]:col-start-1 @min-[40rem]:row-start-1 @min-[40rem]:justify-start',
  'legendItem' => 'flex items-center gap-2',
  'swatch' => 'size-[1.1rem] rounded-[0.3rem] border-2',
  'table' => 'w-full table-fixed border-separate border-spacing-0',
  'tbody' => '[&_tr:last-child>td:first-child]:rounded-bl-panel [&_tr:last-child>td:last-child]:rounded-br-panel',
  'dow' => 'bg-brand px-1 py-3 text-center text-[length:clamp(0.8rem,0.7rem_+_0.5vw,1rem)] font-bold first:rounded-tl-panel last:rounded-tr-panel',
  'dowWeekday' => 'text-on-brand',
  'dowWeekend' => 'text-cal-weekend-head',
  'dowFull' => 'absolute size-px overflow-hidden whitespace-nowrap [clip-path:inset(50%)] @min-[50rem]:static @min-[50rem]:size-auto @min-[50rem]:overflow-visible @min-[50rem]:[clip-path:none]',
  'dowShort' => '@min-[50rem]:hidden',
  'cell' => 'h-[clamp(4.25rem,min(calc(3rem_+_3vw),calc((100dvh_-_var(--cal-chrome,18rem))/var(--cal-rows,6))),6.25rem)] bg-surface p-1 align-top',
  'day' => 'flex size-full flex-col items-center justify-center gap-0 rounded-panel border-0 p-1 text-center [font:inherit] @max-[26rem]:p-0',
  'dayPlain' => 'text-inherit',
  'dayButton' => 'group/day cursor-pointer text-ink focus-visible:inset-ring-2 focus-visible:inset-ring-brand-text focus-visible:outline-2 focus-visible:-outline-offset-2 focus-visible:outline-brand-text aria-pressed:inset-ring-2 aria-pressed:inset-ring-brand-text',
  'today' => 'inset-ring-2 inset-ring-brand-text',
  'num' => 'text-[length:clamp(1.1rem,0.8rem_+_1.2vw,1.75rem)] leading-[1.1] font-medium',
  'holiday' => 'max-w-full text-[0.75rem] leading-[1.2] text-brand-text [overflow-wrap:break-word] hyphens-auto @max-[26rem]:mt-1 @max-[26rem]:size-1.5 @max-[26rem]:flex-none @max-[26rem]:overflow-hidden @max-[26rem]:rounded-full @max-[26rem]:bg-brand-text @max-[26rem]:text-[0px] @max-[26rem]:leading-[0]',
  'holidayHide' => '@min-[26.01rem]:group-hover/day:hidden @min-[26.01rem]:group-focus-visible/day:hidden @min-[26.01rem]:group-aria-pressed/day:hidden',
  'peek' => 'hidden flex-col text-[0.75rem] leading-[1.25] font-semibold text-brand-text [overflow-wrap:anywhere] @min-[26.01rem]:group-hover/day:flex @min-[26.01rem]:group-focus-visible/day:flex @min-[26.01rem]:group-aria-pressed/day:flex',
  'panel' => 'w-full rounded-card bg-surface p-4 min-[576px]:p-6 min-[1200px]:sticky min-[1200px]:top-6 min-[1200px]:w-84 min-[1200px]:flex-[0_0_21rem]',
  'panelTitle' => 'm-0 text-fluid-xl font-bold text-brand-text',
  'panelSub' => 'mt-1 mb-0 text-fluid-md text-ink-muted',
  'panelBody' => 'mt-4 flex flex-col gap-3 empty:hidden',
  ];
  $swatchTones = ['confirmed' => 'border-success-border bg-success-soft', 'pending' => 'border-warning-border bg-warning-soft'];
  $dayTones = [
  'confirmed' => 'bg-success-soft aria-pressed:bg-success focus-visible:bg-success hover:bg-success',
  'pending' => 'bg-warning-soft aria-pressed:bg-warning focus-visible:bg-warning hover:bg-warning',
  ];
  @endphp
  <!DOCTYPE html>
  <html lang="en">

  <head>
    <x-admin-header />
  </head>

  <body class="bg-sidebar font-sans">
    <x-admin.sidebar />

    <main class="admin-content font-sans" data-calendar>
      <x-admin.page-header />

      <div class="{{ $cal['layout'] }}">
        <section class="{{ $cal['wrap'] }}" aria-labelledby="calMonth" style="--cal-rows: {{ $weeks }}">
          {{-- Month bar: arrows + month, with the legend beside it (not under the grid, where it pushed the
             page past the screen height). Wide calendars put the legend on the right of the same row. --}}
          <div class="{{ $cal['bar'] }}">
            <div class="{{ $cal['nav'] }}">
              <a class="{{ $cal['navLink'] }}" href="{{ route('admin.calendar', ['month' => $prev]) }}" aria-label="Previous month">
                <i class="ph ph-caret-left" aria-hidden="true"></i>
              </a>
              <h2 class="{{ $cal['month'] }}" id="calMonth">{{ $month->format('F Y') }}</h2>
              <a class="{{ $cal['navLink'] }}" href="{{ route('admin.calendar', ['month' => $next]) }}" aria-label="Next month">
                <i class="ph ph-caret-right" aria-hidden="true"></i>
              </a>
            </div>

            <ul class="{{ $cal['legend'] }}" aria-label="Legend">
              @foreach ($tones as $tone => $label)
              <li class="{{ $cal['legendItem'] }}"><span class="{{ $cal['swatch'] }} {{ $swatchTones[$tone] ?? '' }}" aria-hidden="true"></span>{{ $label }}</li>
              @endforeach
            </ul>
          </div>

          <table class="{{ $cal['table'] }}">
            <caption class="sr-only">Bookings and holidays for {{ $month->format('F Y') }}</caption>
            <thead>
              <tr>
                @foreach ($dayNames as $i => $name)
                <th scope="col" class="{{ $cal['dow'] }} {{ in_array($i, [0, 6]) ? $cal['dowWeekend'] : $cal['dowWeekday'] }}">
                  <span class="{{ $cal['dowFull'] }}">{{ $name }}</span>
                  <span class="{{ $cal['dowShort'] }}" aria-hidden="true">{{ substr($name, 0, 3) }}</span>
                </th>
                @endforeach
              </tr>
            </thead>
            <tbody class="{{ $cal['tbody'] }}">
              @for ($week = $gridStart->copy(); $week->lte($gridEnd); $week->addWeek())
              <tr>
                @for ($i = 0; $i < 7; $i++)
                  @php
                  $d=$week->copy()->addDays($i);
                  $iso = $d->toDateString();
                  $dayBookings = $byDate[$iso] ?? [];
                  $tone = $dayBookings ? $toneFor($dayBookings) : null;
                  if (! $tone) { $dayBookings = []; }
                  $holiday = $holidayFor($d);
                  $cellColour = ! $d->isSameMonth($month) ? 'text-ink-subtle' : ($d->isWeekend() ? 'text-cal-weekend' : 'text-ink');
                  $isToday = $iso === $today;
                  @endphp
                  <td class="{{ $cal['cell'] }} {{ $cellColour }}">
                    @if ($dayBookings)
                    <button type="button" class="{{ $cal['day'] }} {{ $cal['dayButton'] }} {{ $dayTones[$tone] ?? 'bg-transparent' }} {{ $isToday ? $cal['today'] : '' }}"
                      data-cal-date="{{ $iso }}" aria-pressed="false" aria-controls="calPanel">
                      <span class="{{ $cal['num'] }}">{{ $d->day }}</span>
                      @if ($holiday)<span class="{{ $cal['holiday'] }} {{ $cal['holidayHide'] }}">{{ $holiday }}</span>@endif
                      <span class="{{ $cal['peek'] }}" aria-hidden="true">
                        @if (count($dayBookings) === 1)
                        <span>{{ $dayBookings[0]['reference'] }}</span>
                        <span>{{ $dayBookings[0]['package']['name'] }}</span>
                        @else
                        <span>{{ count($dayBookings) }} bookings</span>
                        @endif
                      </span>
                      <span class="sr-only">, {{ $d->format('l, F j') }}: {{ count($dayBookings) }} {{ \Illuminate\Support\Str::plural('booking', count($dayBookings)) }}, {{ strtolower($tones[$tone]) }}</span>
                    </button>
                    @else
                    <div class="{{ $cal['day'] }} {{ $cal['dayPlain'] }} bg-transparent {{ $isToday ? $cal['today'] : '' }}">
                      <span class="{{ $cal['num'] }}">{{ $d->day }}</span>
                      @if ($holiday)<span class="{{ $cal['holiday'] }}">{{ $holiday }}</span>@endif
                    </div>
                    @endif
                  </td>
                  @endfor
              </tr>
              @endfor
            </tbody>
          </table>
        </section>

        <aside class="{{ $cal['panel'] }}" id="calPanel" aria-labelledby="calPanelTitle" data-cal-panel
          data-empty-title="Event details"
          data-empty-sub="Hover a highlighted date to preview it. Select it to keep it open.">
          <h2 class="{{ $cal['panelTitle'] }}" id="calPanelTitle" data-cal-title>Event details</h2>
          <p class="{{ $cal['panelSub'] }}" data-cal-sub>Hover a highlighted date to preview it. Select it to keep it open.</p>
          <div class="{{ $cal['panelBody'] }}" data-cal-body></div>
        </aside>
      </div>

      {{-- Announces only when a date is selected or cleared, not on every hover. --}}
      <p class="sr-only" role="status" data-cal-status></p>

      {{-- Panel content per date. The script copies one of these into the panel. --}}
      <div hidden>
        @foreach ($byDate as $iso => $dayBookings)
        @continue(! $toneFor($dayBookings))
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