<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingSchedule;
use App\Support\Scheduling;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    // Minutes in a bookable day; the upper bound of every busy window. Mirrors the user
    // booking flow (App\Http\Controllers\User\BookingController), which enforces the same rules.
    private const DAY_MINUTES = 1440;

    public function index(): JsonResponse
    {
        $bookings = Booking::query()
            ->with(['eventDetails', 'schedule', 'payment', 'package'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Booking $booking) => $booking->toAdminArray())
            ->values();

        return response()->json([
            'bookings' => $bookings,
            'full_dates' => Booking::fullCapacityDates(),
        ]);
    }

    public function updateStatus(Request $request, string $booking): JsonResponse|RedirectResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => ['required', 'in:approve,decline,cancel,verify'],
        ]);

        if ($validator->fails()) {
            if (! $request->expectsJson()) {
                return redirect()->back()
                    ->withErrors($validator)
                    ->withInput();
            }

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $action = $validator->validated()['action'];
        $result = DB::transaction(function () use ($booking, $action): array {
            if ($action === 'approve') {
                $capacityLock = DB::table('booking_capacity_locks')
                    ->where('id', 1)
                    ->lockForUpdate()
                    ->first();

                if ($capacityLock === null) {
                    throw new \LogicException('The booking capacity lock row is missing.');
                }
            }

            $record = Booking::query()->lockForUpdate()->find($booking);
            if (! $record) {
                return ['not_found' => true];
            }

            $allowed = [
                'pending' => ['approve', 'decline'],
                'pending_payment' => ['cancel', 'verify'],
                'approved' => ['cancel'],
            ];

            if (! in_array($action, $allowed[$record->status] ?? [], true)) {
                return ['invalid_status' => true];
            }

            $fullDates = [];

            if ($action === 'approve') {
                $record->load('schedule');
                $schedule = $record->schedule;
                if (! $schedule) {
                    return ['missing_schedule' => true];
                }

                $start = $schedule->event_start_date;
                $end = $schedule->event_end_date ?? $start;
                $days = collect(CarbonPeriod::create($start, $end))
                    ->map(fn ($date) => $date->toDateString());
                $counted = $this->overlappingCountedBookings($record);

                $dayCounts = [];
                foreach ($counted as $existing) {
                    $existingSchedule = $existing->schedule;
                    if (! $existingSchedule) {
                        continue;
                    }

                    $existingStart = $existingSchedule->event_start_date;
                    $existingEnd = $existingSchedule->event_end_date ?? $existingStart;
                    foreach (CarbonPeriod::create($existingStart, $existingEnd) as $date) {
                        $day = $date->toDateString();
                        $dayCounts[$day] = ($dayCounts[$day] ?? 0) + 1;
                    }
                }

                $limit = (int) config('scheduling.max_events_per_day', 0);
                $fullDates = $days
                    ->filter(fn (string $day) => ($dayCounts[$day] ?? 0) >= $limit)
                    ->values()
                    ->all();

                if ($fullDates !== []) {
                    return ['full_dates' => $fullDates];
                }

                // The day still has room, but this clock may still clash with a booking that
                // already holds a slot: overlapping it, or sitting inside the required break
                // around it (same rules the client's booking flow is held to).
                $conflict = $this->scheduleConflict($schedule, $counted);
                if ($conflict) {
                    return ['schedule_conflict' => $this->scheduleConflictMessage($conflict, 'approved')];
                }

                $record->status = 'pending_payment';
            } elseif ($action === 'decline') {
                $record->status = 'declined';
            } elseif ($action === 'cancel') {
                $record->status = 'cancelled';
            } else {
                $record->load('payment');
                if (! $record->payment) {
                    return ['missing_payment' => true];
                }

                // Verify is the second gate for the same rule: while it waits for payment the
                // booking already holds its slot, so a schedule clashing with another counted
                // booking must never reach "approved".
                $record->load('schedule');
                $conflict = $this->scheduleConflict($record->schedule, $this->overlappingCountedBookings($record));
                if ($conflict) {
                    return ['schedule_conflict' => $this->scheduleConflictMessage($conflict, 'verified')];
                }

                $record->status = 'approved';
                // Verifying the payment settles it, so the state follows what the client chose:
                // a full payment is 'paid' (Fully Paid), a down payment is 'partial' (Down
                // payment paid). A state already recorded as 'paid' is kept, so re-verifying
                // never downgrades a booking that is already fully paid.
                $record->payment->payment_state = $record->payment->payment_state === 'paid'
                    || $record->payment->payment_method === 'full'
                    ? 'paid'
                    : 'partial';
                $record->payment->save();
            }

            $record->save();

            return ['booking' => $record->fresh(['eventDetails', 'schedule', 'payment', 'package'])->toAdminArray()];
        });

        if (isset($result['not_found'])) {
            return $this->statusResponse($request, 'Booking not found.', 404);
        }

        if (isset($result['invalid_status'])) {
            return $this->statusResponse($request, 'That action is not available for this booking status.', 422);
        }

        if (isset($result['missing_schedule'])) {
            return $this->statusResponse($request, 'Booking schedule details are missing.', 409);
        }

        if (isset($result['missing_payment'])) {
            return $this->statusResponse($request, 'Booking payment details are missing.', 409);
        }

        if (isset($result['full_dates'])) {
            if (! $request->expectsJson()) {
                return redirect()->back()
                    ->with('error', 'This booking cannot be approved because one or more event days are already at capacity.');
            }

            return response()->json([
                'message' => 'This booking cannot be approved because one or more event days are already at capacity.',
                'full_dates' => $result['full_dates'],
            ], 409);
        }

        if (isset($result['schedule_conflict'])) {
            return $this->statusResponse($request, $result['schedule_conflict'], 409);
        }

        if (! $request->expectsJson()) {
            return redirect()->back()->with('status', 'Booking status updated.');
        }

        return response()->json([
            'message' => 'Booking status updated.',
            'booking' => $result['booking'],
        ]);
    }

    private function statusResponse(Request $request, string $message, int $status): JsonResponse|RedirectResponse
    {
        if (! $request->expectsJson()) {
            return redirect()->back()->with('error', $message);
        }

        return response()->json(['message' => $message], $status);
    }

    public function update(Request $request, string $booking): JsonResponse
    {
        $record = Booking::query()->with('eventDetails')->find($booking);

        if (! $record) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'client_name' => ['required', 'string', 'max:255'],
            'client_email' => ['required', 'email', 'max:255'],
            'client_phone' => ['required', 'string', 'max:30'],
            'client_address' => ['required', 'string', 'max:1000'],
            'event_name' => ['required', 'string', 'max:255'],
            'event_type' => ['required', 'string', 'max:255'],
            'event_type_other' => ['nullable', 'string', 'max:255'],
            'event_location' => ['required', 'string', 'max:255'],
            'venue_contact_person' => ['nullable', 'string', 'max:30'],
            'guest_count' => ['nullable', 'integer', 'min:0'],
            'venue_type' => ['required', 'in:'.implode(',', config('admin.bookings.venue_types', []))],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        if (! $record->eventDetails) {
            return response()->json(['message' => 'Booking event details are missing.'], 409);
        }

        $validated = $validator->validated();
        DB::transaction(function () use ($record, $validated): void {
            $record->fill(collect($validated)->only([
                'client_name',
                'client_email',
                'client_phone',
                'client_address',
            ])->all())->save();
            $record->eventDetails->fill([
                'event_name' => $validated['event_name'],
                'event_type' => $validated['event_type'],
                'event_type_other' => $validated['event_type'] === 'Others'
                    ? ($validated['event_type_other'] ?? null)
                    : null,
                'event_location' => $validated['event_location'],
                'event_contact_person' => $validated['venue_contact_person'] ?? null,
                'guest_count' => $validated['guest_count'] ?? null,
                'venue_type' => $validated['venue_type'],
            ])->save();
        });

        return response()->json([
            'message' => 'Booking details updated.',
            'booking' => $record->fresh(['eventDetails', 'schedule', 'payment', 'package'])->toAdminArray(),
        ]);
    }

    /**
     * Bookings that already hold a slot (config/scheduling.php `counted_statuses`) and share
     * at least one day with $record's own schedule — EXCLUDING $record itself, whose slot is
     * the one being decided (it counts as soon as it is approved, so it would otherwise clash
     * with itself at Verify). Rows are locked for update, exactly like the capacity check on
     * Approve, so concurrent admin actions are decided against the same data.
     *
     * @return Collection<int, Booking>
     */
    private function overlappingCountedBookings(Booking $record): Collection
    {
        $schedule = $record->schedule;
        if (! $schedule || ! $schedule->event_start_date) {
            return collect();
        }

        $start = $schedule->event_start_date;
        $end = $schedule->event_end_date ?? $start;

        return Booking::query()
            ->where('id', '!=', $record->id)
            ->whereIn('status', config('scheduling.counted_statuses', []))
            ->whereHas('schedule', function ($query) use ($start, $end): void {
                $query->whereDate('event_start_date', '<=', $end->toDateString())
                    ->whereDate('event_end_date', '>=', $start->toDateString());
            })
            ->lockForUpdate()
            ->with('schedule')
            ->get();
    }

    /**
     * First clash between $schedule and a counted booking in $counted: overlapping it, or
     * leaving less than the required break (config/scheduling.php `min_gap_minutes`) around
     * it. Returns ['date' => 'Y-m-d', 'from' => int, 'to' => int, 'reference' => ?string] —
     * the busy window in minutes past midnight — or null when the schedule is clear.
     *
     * The rules mirror App\Http\Controllers\User\BookingController::scheduleConflict() (the rules
     * the client's booking flow is held to), so a booking can never be approved or verified
     * into a slot the flow itself would refuse: the clock is read per day (occupiedSpan())
     * so a multi-day or past-midnight event is compared on the days it really covers, and a
     * counted booking without a readable clock holds its whole day instead of being guessed
     * at. A schedule without times cannot be checked and passes, matching how it is stored.
     */
    private function scheduleConflict(?BookingSchedule $schedule, Collection $counted): ?array
    {
        if (! $schedule || ! $schedule->event_start_date) {
            return null;
        }

        $from = $this->timeInMinutes($schedule->event_start_time);
        $to = $this->timeInMinutes($schedule->event_end_time);
        if ($from === null || $to === null) {
            return null;
        }

        $first = $schedule->event_start_date->copy()->startOfDay();
        $last = ($schedule->event_end_date ?? $schedule->event_start_date)->copy()->startOfDay();
        if ($last->lessThan($first)) {
            $last = $first->copy();
        }

        foreach ($this->blockedWindows($counted, $first, $last) as $iso => $windows) {
            [$windowFrom, $windowTo] = $this->occupiedSpan($first, $last, $from, $to, Carbon::parse($iso));

            foreach ($windows as [$busyFrom, $busyTo, $reference]) {
                if ($windowFrom < $busyTo && $windowTo > $busyFrom) {
                    return [
                        'date' => $iso,
                        'from' => $busyFrom,
                        'to' => $busyTo,
                        'reference' => $reference,
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Per day in $first..$last, the [from, to, reference] windows (minutes past midnight) a
     * new booking may not touch: each counted booking's span on that day PLUS the required
     * break before and after it. A booking whose clock is missing (or a single-day one whose
     * end is not after its start) holds the whole day rather than being guessed at, so a
     * legacy row can never be double-booked.
     *
     * @return array<string, array<int, array{0:int,1:int,2:?string}>>
     */
    private function blockedWindows(Collection $counted, Carbon $first, Carbon $last): array
    {
        $gap = Scheduling::minGapMinutes();
        $startIso = $first->toDateString();
        $endIso = $last->toDateString();
        $windows = [];

        foreach ($counted as $existing) {
            $existingSchedule = $existing->schedule;
            if (! $existingSchedule || ! $existingSchedule->event_start_date) {
                continue;
            }

            $existingFrom = $this->timeInMinutes($existingSchedule->event_start_time);
            $existingTo = $this->timeInMinutes($existingSchedule->event_end_time);
            $existingFirst = $existingSchedule->event_start_date->copy()->startOfDay();
            $existingLast = ($existingSchedule->event_end_date ?? $existingSchedule->event_start_date)->copy()->startOfDay();
            if ($existingLast->lessThan($existingFirst)) {
                $existingLast = $existingFirst->copy();
            }
            $singleDay = $existingFirst->isSameDay($existingLast);

            foreach (CarbonPeriod::create($existingFirst, $existingLast) as $date) {
                $iso = $date->toDateString();
                if ($iso < $startIso || $iso > $endIso) {
                    continue;
                }

                if ($existingFrom === null || $existingTo === null || ($singleDay && $existingTo <= $existingFrom)) {
                    $windows[$iso][] = [0, self::DAY_MINUTES, $existing->reference];

                    continue;
                }

                [$spanFrom, $spanTo] = $this->occupiedSpan(
                    $existingFirst,
                    $existingLast,
                    $existingFrom,
                    $existingTo,
                    $date,
                );
                $windows[$iso][] = [
                    max(0, $spanFrom - $gap),
                    min(self::DAY_MINUTES, $spanTo + $gap),
                    $existing->reference,
                ];
            }
        }

        return $windows;
    }

    /**
     * The [from, to] minute span ONE of the days of a booking occupies. The two stored clocks
     * are read as a single continuous interval running from the start clock on the first day
     * to the end clock on the last day: the rest of the first day from the start clock, every
     * day in between in full, and the last day up to the end clock.
     *
     * @return array{0:int,1:int} [from, to] in minutes past midnight
     */
    private function occupiedSpan(Carbon $first, Carbon $last, int $from, int $to, Carbon $day): array
    {
        if ($first->isSameDay($last)) {
            return [$from, $to];
        }

        $iso = $day->toDateString();

        if ($iso === $first->toDateString()) {
            return [$from, self::DAY_MINUTES];
        }

        if ($iso === $last->toDateString()) {
            return [0, $to];
        }

        return [0, self::DAY_MINUTES];
    }

    // "9:00 AM" -> 540. Null when the string is missing or not a valid 12-hour time.
    private function timeInMinutes(?string $time): ?int
    {
        if (! preg_match('/^(?:[0-9]|1[0-2]):[0-5][0-9] (?:AM|PM)$/', (string) $time)) {
            return null;
        }

        [$clock, $period] = explode(' ', (string) $time);
        [$hour, $minute] = explode(':', $clock);
        $hour = ((int) $hour % 12) + ($period === 'PM' ? 12 : 0);

        return ($hour * 60) + (int) $minute;
    }

    /**
     * Wording for a rejected Approve / Verify: names the action, the conflicting booking and
     * the busy window (required break included), so the admin knows which booking to look at.
     */
    private function scheduleConflictMessage(array $conflict, string $action): string
    {
        $reference = $conflict['reference'] ? '#'.$conflict['reference'] : 'another booking';
        $gap = Scheduling::minGapMinutes();
        $break = $gap > 0
            ? ' Bookings on the same day need at least '.Scheduling::humanizeMinutes($gap).' between them.'
            : '';

        return 'This booking cannot be '.$action.' because its schedule conflicts with '.$reference.': '
            .Carbon::parse($conflict['date'])->format('F j, Y')
            .' is unavailable from '.Scheduling::minutesToClock($conflict['from'])
            .' to '.Scheduling::minutesToClock($conflict['to']).'.'
            .$break;
    }
}
