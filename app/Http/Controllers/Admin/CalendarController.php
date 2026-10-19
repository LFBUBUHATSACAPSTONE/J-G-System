<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class CalendarController extends Controller
{
    public function show(Request $request): View
    {
        return view('admin.calendar', $this->calendarData($request));
    }

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->calendarData($request));
    }

    /**
     * Everything both the page and the JSON endpoint need:
     *
     *   month    => Carbon, any day in the requested month (the view works out the grid)
     *   events   => bookings that touch the visible grid, shape from Booking::toAdminArray()
     *   holidays => fixed-date holidays from config, keyed 'Y-m-d' (the shape the view reads)
     *
     * The view recomputes the grid from `month`, so the controller uses the same bounds
     * (Sunday before/after) to decide which bookings to send.
     *
     * @return array{month: Carbon, events: array<int, array<string, mixed>>, holidays: array<string, string>}
     */
    private function calendarData(Request $request): array
    {
        $month = $this->resolveMonth($request->query('month'));
        $gridStart = $month->copy()->startOfWeek(0); // Sunday
        $gridEnd = $month->copy()->endOfMonth()->endOfWeek(6); // Saturday

        // Only the statuses the calendar knows about appear (approved only, per
        // config/admin/calendar.php): pending, cancelled and declined dates look free again.
        // The view repeats this check, so the rule lives in that config file.
        $statuses = array_keys(config('admin.calendar.statuses', []));

        $events = $statuses === []
            ? collect()
            : Booking::query()
                ->with(['eventDetails', 'schedule', 'payment', 'package'])
                ->whereIn('status', $statuses)
                ->whereHas('schedule', function ($query) use ($gridStart, $gridEnd): void {
                    // A booking is on the grid when it overlaps [gridStart, gridEnd]. A missing
                    // end date means a single-day event (the view falls back to the start date).
                    $query->whereDate('event_start_date', '<=', $gridEnd->toDateString())
                        ->where(function ($query) use ($gridStart): void {
                            $query->whereDate('event_end_date', '>=', $gridStart->toDateString())
                                ->orWhere(function ($query) use ($gridStart): void {
                                    $query->whereNull('event_end_date')
                                        ->whereDate('event_start_date', '>=', $gridStart->toDateString());
                                });
                        });
                })
                ->orderBy(
                    BookingSchedule::select('event_start_date')
                        ->whereColumn('booking_schedules.booking_id', 'bookings.id')
                        ->limit(1)
                )
                ->orderBy('id')
                ->get()
                ->map(fn (Booking $booking) => $booking->toAdminArray())
                ->values()
                ->all();

        return [
            'month' => $month,
            'events' => $events,
            'holidays' => $this->holidaysBetween($gridStart, $gridEnd),
        ];
    }

    /**
     * The page opens on the current month; ?month=YYYY-MM (as used by the arrows) wins when valid.
     */
    private function resolveMonth(mixed $requested): Carbon
    {
        if (is_string($requested) && preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $requested)) {
            return Carbon::parse($requested.'-01')->startOfMonth();
        }

        return Carbon::now()->startOfMonth();
    }

    /**
     * Fixed-date holidays (config keys are 'm-d') resolved to real dates on the visible grid,
     * keyed 'Y-m-d'. Movable holidays are not stored yet; pass them alongside this list when
     * they exist, the view lets them win on the same date.
     *
     * @return array<string, string>
     */
    private function holidaysBetween(Carbon $gridStart, Carbon $gridEnd): array
    {
        $holidays = [];

        foreach (config('admin.calendar.holidays', []) as $monthDay => $name) {
            [$month, $day] = array_pad(array_map('intval', explode('-', $monthDay)), 2, 0);
            if ($month < 1 || $day < 1) {
                continue;
            }

            foreach ([$gridStart->year, $gridEnd->year] as $year) {
                if (! checkdate($month, $day, $year)) {
                    continue;
                }

                $date = Carbon::create($year, $month, $day)->startOfDay();
                if ($date->betweenIncluded($gridStart, $gridEnd)) {
                    $holidays[$date->toDateString()] = $name;
                }
            }
        }

        ksort($holidays);

        return $holidays;
    }
}
