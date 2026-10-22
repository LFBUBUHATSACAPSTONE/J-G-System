<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Package;
use App\Support\Scheduling;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

class BookingController extends Controller
{
    // Minutes in a bookable day; used as the upper bound of every time window.
    private const DAY_MINUTES = 1440;

    // Shortest bookable event, mirroring the Event Schedule step's client-side rule
    // (resources/js/booking/event-schedule.js). The browser check is only a convenience;
    // this is the gate the server actually enforces.
    private const MIN_EVENT_MINUTES = 60;

    private const EVENT_TYPES = [
        'Baby Shower',
        'Bridal Shower',
        'Birthday Party',
        'Concert',
        'Family Reunion',
        'Team-Building Event',
        'Wedding',
        'Others',
    ];

    public function show(Request $request): View|RedirectResponse|Response
    {
        if ($rescheduleId = $request->query('reschedule')) {
            [$booking, $message] = $this->eligibleForReschedule((string) $rescheduleId);

            if ($message) {
                return response()->view('user.booking', ['rescheduleBlocked' => $message], 403);
            }

            $month = $booking->schedule->event_start_date->format('Y-m');

            return view('user.booking', $this->summaryData($booking) + [
                'reschedule' => [
                    'booking_id' => (string) $booking->id,
                    'reference' => '#'.$booking->reference,
                    'month' => $month,
                ],
            ]);
        }

        $draft = $request->session()->get('booking_draft', []);
        $packageId = (string) $request->query('package', $draft['package_id'] ?? '');
        $package = Package::query()->whereKey($packageId)->where('available', true)->first();
        if (! $package) {
            return redirect()->to(route('user.landing').'#packages');
        }

        if (($draft['package_id'] ?? null) !== $packageId) {
            $draft = ['package_id' => $packageId];
            $request->session()->put('booking_draft', $draft);
        }

        return view('user.booking', $this->summaryData($draft) + [
            'packageId' => $packageId,
            'packageName' => $package->name,
            'packageCost' => $package->price,
        ]);
    }

    public function saveClientInformation(Request $request): JsonResponse
    {
        $draft = $request->session()->get('booking_draft');
        if (! $draft || ! Package::query()
            ->whereKey($draft['package_id'] ?? '')
            ->where('available', true)
            ->exists()) {
            return $this->jsonError('Choose a package before entering your booking details.', 409);
        }

        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z\s]+$/'],
            'last_name' => ['required', 'string', 'max:100', 'regex:/^[A-Za-z\s]+$/'],
            'email' => ['required', 'email', 'max:255'],
            'contact_number' => ['required', 'regex:/^09\d{9}$/'],
            'address' => ['required', 'string', 'max:1000'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $data = $validator->validated();
        $draft = array_merge($draft, [
            'client_name' => trim($data['first_name'].' '.$data['last_name']),
            'client_email' => $data['email'],
            'client_phone' => $data['contact_number'],
            'client_address' => $data['address'],
        ]);
        $request->session()->put('booking_draft', $draft);

        return response()->json(['ok' => true]);
    }

    public function saveEventInformation(Request $request): JsonResponse
    {
        $draft = $request->session()->get('booking_draft');
        if (! $draft || empty($draft['client_email'])) {
            return $this->jsonError('Save your personal information before continuing.', 409);
        }

        $validator = Validator::make($request->all(), [
            'event_name' => ['required', 'string', 'max:255'],
            'event_type' => ['required', 'string', 'in:'.implode(',', self::EVENT_TYPES)],
            'event_type_other' => ['nullable', 'required_if:event_type,Others', 'string', 'max:255'],
            'event_location' => ['required', 'string', 'max:255'],
            'venue_contact_person' => ['nullable', 'regex:/^09\d{9}$/'],
            'guest_count' => ['nullable', 'integer', 'min:1', 'max:1000000'],
            'venue_type' => ['required', 'in:Indoor,Outdoor,Both'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $data = $validator->validated();
        $draft = array_merge($draft, [
            'event_name' => $data['event_name'],
            'event_type' => $data['event_type'],
            'event_type_other' => $data['event_type'] === 'Others' ? $data['event_type_other'] : null,
            'event_location' => $data['event_location'],
            'event_contact_person' => $data['venue_contact_person'] ?? null,
            'guest_count' => $data['guest_count'] ?? null,
            'venue_type' => $data['venue_type'],
        ]);
        $request->session()->put('booking_draft', $draft);

        return response()->json(['ok' => true]);
    }

    public function availability(Request $request): JsonResponse
    {
        $month = (string) $request->query('month', '');
        if (! preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $month)) {
            return $this->validationError(['month' => ['Use YYYY-MM.']]);
        }

        $monthStart = Carbon::createFromFormat('!Y-m', $month);
        $monthEnd = $monthStart->copy()->endOfMonth();
        $full = $this->fullDatesBetween($monthStart, $monthEnd);

        return response()->json([
            'limit' => (int) config('scheduling.max_events_per_day', 3),
            'gap_minutes' => max(0, (int) config('scheduling.min_gap_minutes', 0)),
            'month' => $month,
            'full' => $full,
        ]);
    }

    public function saveEventSchedule(Request $request): JsonResponse
    {
        $rescheduleId = $request->input('reschedule_id');
        $reschedule = null;
        if ($rescheduleId) {
            [$reschedule, $message] = $this->eligibleForReschedule((string) $rescheduleId);
            if ($message) {
                return $this->jsonError($message, 403);
            }
        } else {
            $draft = $request->session()->get('booking_draft');
            if (! $draft || empty($draft['event_name'])) {
                return $this->jsonError('Save your event information before selecting a schedule.', 409);
            }
        }

        $validator = Validator::make($request->all(), [
            'event_start_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:today'],
            'event_end_date' => ['required', 'date_format:Y-m-d', 'after_or_equal:event_start_date'],
            'start_time' => ['required', 'regex:/^(?:[1-9]|1[0-2]):[0-5][0-9] (?:AM|PM)$/'],
            'end_time' => ['required', 'regex:/^(?:[1-9]|1[0-2]):[0-5][0-9] (?:AM|PM)$/'],
        ]);

        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $data = $validator->validated();
        if ($reschedule) {
            $month = $reschedule->schedule->event_start_date->format('Y-m');
            if (! str_starts_with($data['event_start_date'], $month) || ! str_starts_with($data['event_end_date'], $month)) {
                $message = 'Rescheduling is limited to '.$reschedule->schedule->event_start_date->format('F Y').'.';

                return $this->validationError(['event_start_date' => [$message]], $message);
            }
        }

        if ($data['event_start_date'] === $data['event_end_date']
            && $this->timeInMinutes($data['end_time']) <= $this->timeInMinutes($data['start_time'])) {
            return $this->validationError(['end_time' => ['End time must be later than start time.']]);
        }

        // The event is one continuous span from the start clock on the first day to the end
        // clock on the last day, so a multi-day event may end earlier in the clock than it
        // starts (6:00 PM - 2:00 AM). The span still has to be positive and long enough.
        $startMinutes = $this->timeInMinutes($data['start_time']);
        $endMinutes = $this->timeInMinutes($data['end_time']);
        $daySpan = CarbonPeriod::create(
            Carbon::parse($data['event_start_date']),
            Carbon::parse($data['event_end_date']),
        )->count() - 1;
        $duration = ($daySpan * self::DAY_MINUTES) + $endMinutes - $startMinutes;

        if ($duration <= 0) {
            return $this->validationError(['end_time' => ['End time must be later than start time.']]);
        }

        if ($duration < self::MIN_EVENT_MINUTES) {
            return $this->validationError([
                'end_time' => ['The event must be at least '.Scheduling::humanizeMinutes(self::MIN_EVENT_MINUTES).' long.'],
            ]);
        }

        $fullDates = $this->fullDatesBetween(
            Carbon::parse($data['event_start_date']),
            Carbon::parse($data['event_end_date']),
        );
        if ($fullDates) {
            $message = count($fullDates) > 1
                ? 'One or more of the days you picked is fully booked. Please choose other dates.'
                : 'That day is fully booked. Please choose another date.';

            return response()->json([
                'message' => $message,
                'errors' => ['event_start_date' => [$message]],
                'full_dates' => $fullDates,
            ], 422);
        }

        // The day has slots left, but this exact time may still clash: overlapping an
        // existing event, or leaving less than the required break before/after one.
        $conflict = $this->scheduleConflict(
            Carbon::parse($data['event_start_date']),
            Carbon::parse($data['event_end_date']),
            $data['start_time'],
            $data['end_time'],
        );
        if ($conflict) {
            return $this->validationError(['start_time' => [$conflict['message']]], $conflict['message']);
        }

        $schedule = [
            'event_start_date' => $data['event_start_date'],
            'event_end_date' => $data['event_end_date'],
            'event_start_time' => $data['start_time'],
            'event_end_time' => $data['end_time'],
        ];

        if ($reschedule) {
            $request->session()->put('booking_reschedule_'.$reschedule->id, $schedule);
        } else {
            $request->session()->put('booking_draft', array_merge($draft, $schedule));
        }

        return response()->json(['ok' => true]);
    }

    public function submitBooking(Request $request): JsonResponse
    {
        $rescheduleId = $request->input('reschedule_id');
        if ($rescheduleId) {
            [$original, $message] = $this->eligibleForReschedule((string) $rescheduleId);
            if ($message) {
                return $this->jsonError($message, 403);
            }

            $schedule = $request->session()->get('booking_reschedule_'.$original->id);
            if (! $schedule) {
                return $this->jsonError('Choose and save a new schedule before confirming the reschedule.', 409);
            }

            [$booking, $fullDates, $conflict] = DB::transaction(function () use ($original, $schedule) {
                $fullDates = $this->fullDatesBetween(
                    Carbon::parse($schedule['event_start_date']),
                    Carbon::parse($schedule['event_end_date']),
                    true,
                );
                if ($fullDates) {
                    return [null, $fullDates, null];
                }

                // Re-check the clock under the same lock: between saving the schedule and
                // confirming here, another booking may have taken the slot or moved next to it.
                $conflict = $this->scheduleConflictFromSchedule($schedule, true);
                if ($conflict) {
                    return [null, [], $conflict];
                }

                $booking = Booking::create(array_merge(
                    $original->only(['client_name', 'client_email', 'client_phone', 'client_address']),
                    [
                        'user_id' => $original->user_id,
                        'reference' => $this->newReference(),
                        'status' => 'pending',
                        'package_id' => $original->package_id,
                        'rescheduled_from_id' => $original->id,
                        'submitted_at' => now(),
                    ],
                ));

                $event = $original->eventDetails;
                $payment = $original->payment;
                $this->createBookingDetails(
                    $booking,
                    [
                        'event_name' => $event->event_name,
                        'event_type' => $event->event_type,
                        'event_type_other' => $event->event_type_other,
                        'event_location' => $event->event_location,
                        'event_contact_person' => $event->event_contact_person,
                        'guest_count' => $event->guest_count,
                        'venue_type' => $event->venue_type,
                    ],
                    $schedule,
                    [
                        'payment_method' => $payment->payment_method,
                        'payment_state' => $payment->payment_state,
                        'payment_reference' => $payment->payment_reference,
                        'payment_receipt_path' => $payment->payment_receipt_path,
                        'payment_amount' => $payment->payment_amount,
                    ],
                );

                return [$booking, [], null];
            });
            if ($fullDates) {
                return $this->capacityConflict($fullDates);
            }
            if ($conflict) {
                return $this->validationError(['start_time' => [$conflict]], $conflict);
            }

            $request->session()->forget('booking_reschedule_'.$original->id);

            return response()->json(['ok' => true, 'reference' => $booking->reference]);
        }

        $draft = $request->session()->get('booking_draft');
        if (! $draft || empty($draft['client_email']) || empty($draft['event_name'])
            || empty($draft['event_start_date']) || empty($draft['event_end_date'])) {
            return $this->jsonError('Complete each booking step before submitting your booking.', 409);
        }

        $package = Package::query()
            ->whereKey($draft['package_id'] ?? '')
            ->where('available', true)
            ->first();
        if (! $package) {
            return $this->jsonError('The selected package is no longer available. Choose a package again.', 409);
        }

        $validator = Validator::make($request->all(), [
            'payment_option' => ['required', 'in:full,down'],
            'down_payment_amount' => ['required_if:payment_option,down', 'nullable', 'decimal:0,2', 'min:0.01'],
            // The screenshot of the payment details form's confirmation page. The booking
            // summary step already requires it in the browser; the server is the real gate.
            'payment_proof' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ]);
        if ($validator->fails()) {
            return $this->validationError($validator->errors()->toArray());
        }

        $data = $validator->validated();
        $packageCents = (int) $package['price'] * 100;
        $paymentCents = $data['payment_option'] === 'full'
            ? $packageCents
            : (int) round((float) $data['down_payment_amount'] * 100);
        if ($data['payment_option'] === 'down' && $paymentCents < (int) ceil($packageCents * 0.3)) {
            $minimum = number_format(ceil($packageCents * 0.3) / 100, 2);

            return $this->validationError([
                'down_payment_amount' => ["The down payment must be at least Php {$minimum} (30% of the package price)."],
            ]);
        }
        if ($paymentCents > $packageCents) {
            return $this->validationError([
                'down_payment_amount' => ['The payment amount cannot exceed the package price.'],
            ]);
        }

        $userId = $request->user()?->id;

        [$booking, $fullDates, $conflict] = DB::transaction(function () use ($draft, $package, $data, $paymentCents, $userId, $request) {
            $fullDates = $this->fullDatesBetween(
                Carbon::parse($draft['event_start_date']),
                Carbon::parse($draft['event_end_date']),
                true,
            );
            if ($fullDates) {
                return [null, $fullDates, null];
            }

            // Re-check the clock under the same lock: the schedule step could have passed
            // while someone else booked next to (or on top of) this slot.
            $conflict = $this->scheduleConflictFromSchedule($draft, true);
            if ($conflict) {
                return [null, [], $conflict];
            }

            $booking = Booking::create([
                'user_id' => $userId,
                'reference' => $this->newReference(),
                'status' => 'pending',
                'client_name' => $draft['client_name'],
                'client_email' => $draft['client_email'],
                'client_phone' => $draft['client_phone'],
                'client_address' => $draft['client_address'],
                'package_id' => $package->id,
                'submitted_at' => now(),
            ]);

            // Store the uploaded proof once and remember where it landed; the admin
            // modal shows it through Booking::toAdminArray() -> payment.proof_url.
            $proofPath = $request->file('payment_proof')->store('payment-receipts', 'public');

            $this->createBookingDetails($booking, $draft, [
                'event_start_date' => $draft['event_start_date'],
                'event_end_date' => $draft['event_end_date'],
                'event_start_time' => $draft['event_start_time'] ?? null,
                'event_end_time' => $draft['event_end_time'] ?? null,
            ], [
                'payment_method' => $data['payment_option'],
                'payment_state' => 'pending',
                'payment_amount' => number_format($paymentCents / 100, 2, '.', ''),
                'payment_receipt_path' => $proofPath,
            ]);

            return [$booking, [], null];
        });
        if ($fullDates) {
            return $this->capacityConflict($fullDates);
        }
        if ($conflict) {
            return $this->validationError(['start_time' => [$conflict]], $conflict);
        }

        $request->session()->forget('booking_draft');

        return response()->json(['ok' => true, 'reference' => $booking->reference]);
    }

    private function summaryData(array|Booking $source): array
    {
        if ($source instanceof Booking) {
            $data = $source->toArray();
            $data = array_merge(
                $data,
                $source->eventDetails->toArray(),
                $source->schedule->toArray(),
                [
                    'package_id' => $source->package?->id,
                    'package_name' => $source->package?->name,
                    'package_price' => $source->package?->price,
                ],
            );
        } else {
            $data = $source;
            $package = Package::find($data['package_id'] ?? '');
            $data['package_name'] = $package?->name;
            $data['package_price'] = $package?->price;
        }

        $start = $data['event_start_date'] ?? null;
        $end = $data['event_end_date'] ?? null;
        $formatDate = fn ($date) => $date ? Carbon::parse($date)->format('F j, Y') : '';

        return [
            'fullName' => $data['client_name'] ?? '',
            'email' => $data['client_email'] ?? '',
            'contactNumber' => $data['client_phone'] ?? '',
            'eventName' => $data['event_name'] ?? '',
            'eventType' => ($data['event_type'] ?? '') === 'Others'
                ? ($data['event_type_other'] ?? 'Others')
                : ($data['event_type'] ?? ''),
            'eventLocation' => $data['event_location'] ?? '',
            'eventContactPerson' => $data['event_contact_person'] ?? '',
            'eventDate' => $start && $end
                ? ($start === $end ? $formatDate($start) : $formatDate($start).' – '.$formatDate($end))
                : '',
            'startTime' => $data['event_start_time'] ?? '',
            'endTime' => $data['event_end_time'] ?? '',
            'packageCost' => $data['package_price'] ?? null,
            'packageId' => $data['package_id'] ?? null,
            'packageName' => $data['package_name'] ?? '',
        ];
    }

    private function createBookingDetails(
        Booking $booking,
        array $event,
        array $schedule,
        array $payment,
    ): void {
        $booking->eventDetails()->create($event);
        $booking->schedule()->create($schedule);
        $booking->payment()->create($payment);
    }

    private function eligibleForReschedule(string $id): array
    {
        $booking = ctype_digit($id) ? Booking::find((int) $id) : Booking::where('reference', $id)->first();
        if (! $booking) {
            return [null, 'This booking cannot be rescheduled.'];
        }

        // A booking linked to an account can only be rescheduled by that account.
        if ($booking->user_id !== null && (int) $booking->user_id !== (int) auth()->id()) {
            return [null, 'This booking cannot be rescheduled.'];
        }

        $reason = $booking->rescheduleBlockReason();
        if ($reason) {
            return [null, $reason];
        }

        return [$booking, null];
    }

    /**
     * Per-day occupancy of $start..$end by counted bookings (config/scheduling.php):
     *   'counts'  — bookings covering each day; a multi-day booking counts on EVERY day.
     *   'blocked' — windows [from, to] (minutes past midnight) a new booking may not touch:
     *               each booking's own span on that day PLUS the required break before and
     *               after it, merged per day. A window reaching 00:00 and 24:00 means the
     *               whole day is occupied and no other event fits, no matter how many slots
     *               the quota still shows.
     * A booking is read as ONE continuous span (occupiedSpan()): a multi-day or
     * past-midnight event therefore blocks the rest of its first day, its middle days in
     * full and its last day up to the end clock — not the same window on every date.
     * Times are stored as "h:mm AM/PM" strings; a booking with unreadable times holds the
     * entire day, so a legacy row can never be double-booked by accident.
     */
    private function dayOccupancy(Carbon $start, Carbon $end, bool $lock = false): array
    {
        $statuses = config('scheduling.counted_statuses', []);
        if (! $statuses) {
            return ['counts' => [], 'blocked' => []];
        }

        $query = Booking::query()
            ->whereIn('status', $statuses)
            ->join('booking_schedules', 'bookings.id', '=', 'booking_schedules.booking_id')
            ->whereDate('booking_schedules.event_start_date', '<=', $end->toDateString())
            ->whereDate('booking_schedules.event_end_date', '>=', $start->toDateString());
        if ($lock) {
            $query->lockForUpdate();
        }
        $bookings = $query->get([
            'booking_schedules.event_start_date',
            'booking_schedules.event_end_date',
            'booking_schedules.event_start_time',
            'booking_schedules.event_end_time',
        ]);

        $gap = Scheduling::minGapMinutes();
        $counts = [];
        $windows = [];

        foreach ($bookings as $booking) {
            $from = $this->timeInMinutes($booking->event_start_time);
            $to = $this->timeInMinutes($booking->event_end_time);
            $first = Carbon::parse($booking->event_start_date)->startOfDay();
            $last = Carbon::parse($booking->event_end_date ?? $booking->event_start_date)->startOfDay();
            $singleDay = $first->isSameDay($last);

            foreach (CarbonPeriod::create($first, $last) as $date) {
                $iso = $date->toDateString();
                if ($iso < $start->toDateString() || $iso > $end->toDateString()) {
                    continue;
                }

                $counts[$iso] = ($counts[$iso] ?? 0) + 1;

                // No times, or a single-day event whose end is not after its start: hold the
                // whole day rather than guess where the event actually sits (legacy rows).
                if ($from === null || $to === null || ($singleDay && $to <= $from)) {
                    $windows[$iso][] = [0, self::DAY_MINUTES];

                    continue;
                }

                [$spanFrom, $spanTo] = $this->occupiedSpan($first, $last, $from, $to, $date);
                $windows[$iso][] = [
                    max(0, $spanFrom - $gap),
                    min(self::DAY_MINUTES, $spanTo + $gap),
                ];
            }
        }

        $blocked = [];
        foreach ($windows as $iso => $day) {
            $blocked[$iso] = $this->mergeIntervals($day);
        }

        return ['counts' => $counts, 'blocked' => $blocked];
    }

    /**
     * Collapse overlapping [from, to] windows into the smallest set covering the same time.
     */
    private function mergeIntervals(array $windows): array
    {
        usort($windows, fn (array $a, array $b) => [$a[0], $a[1]] <=> [$b[0], $b[1]]);

        $merged = [];
        foreach ($windows as [$from, $to]) {
            $last = count($merged) - 1;
            if ($last >= 0 && $from <= $merged[$last][1]) {
                $merged[$last][1] = max($merged[$last][1], $to);
            } else {
                $merged[] = [$from, $to];
            }
        }

        return $merged;
    }

    /**
     * The [from, to] minute span a booking occupies on ONE of the days it covers.
     *
     * The two stored clocks are read as a single continuous interval that runs from the
     * start clock on the first day to the end clock on the last day: the booking fills the
     * rest of its first day from the start clock, every day in between in full, and its
     * last day up to the end clock. A single-day booking is just its start-to-end span.
     *
     * Reading the same window onto every date instead would let a multi-day or
     * past-midnight event clash with days it never touches (and miss the days it does),
     * which is why the first/middle/last day are treated apart.
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

    private function fullDatesBetween(Carbon $start, Carbon $end, bool $lock = false): array
    {
        $limit = (int) config('scheduling.max_events_per_day', 3);
        if ($limit < 1) {
            return [];
        }

        ['counts' => $counts, 'blocked' => $blocked] = $this->dayOccupancy($start, $end, $lock);

        $full = [];
        foreach (CarbonPeriod::create($start, $end) as $date) {
            $iso = $date->toDateString();
            $windows = $blocked[$iso] ?? [];

            // At the event quota, or the merged busy windows already span 00:00 - 24:00.
            $covered = $windows !== []
                && $windows[0][0] <= 0
                && $windows[0][1] >= self::DAY_MINUTES;
            if (($counts[$iso] ?? 0) >= $limit || $covered) {
                $full[] = $iso;
            }
        }

        return $full;
    }

    /**
     * First day in $start..$end where $startTime - $endTime cannot sit: it overlaps an
     * existing booking or leaves less than the required break around one. Returns
     * ['date' => 'Y-m-d', 'message' => '…'] or null when every day in the range is clear.
     *
     * The selected times describe one continuous event: the proposed span is read per day
     * (occupiedSpan()) so a multi-day or past-midnight booking is checked on the days it
     * really covers, exactly like the existing bookings it is compared against.
     */
    private function scheduleConflict(
        Carbon $start,
        Carbon $end,
        string $startTime,
        string $endTime,
        bool $lock = false,
    ): ?array {
        $from = $this->timeInMinutes($startTime);
        $to = $this->timeInMinutes($endTime);
        if ($from === null || $to === null) {
            return null;
        }

        $first = $start->copy()->startOfDay();
        $last = $end->copy()->startOfDay();

        // The required break is already baked into the existing bookings' windows, so the
        // proposed span is checked as-is.
        ['blocked' => $blocked] = $this->dayOccupancy($start, $end, $lock);

        foreach (CarbonPeriod::create($first, $last) as $date) {
            $iso = $date->toDateString();
            [$windowFrom, $windowTo] = $this->occupiedSpan($first, $last, $from, $to, $date);

            foreach ($blocked[$iso] ?? [] as [$busyFrom, $busyTo]) {
                if ($windowFrom < $busyTo && $windowTo > $busyFrom) {
                    return [
                        'date' => $iso,
                        'message' => $this->scheduleConflictMessage($iso, $busyFrom, $busyTo),
                    ];
                }
            }
        }

        return null;
    }

    /**
     * Same check for a stored schedule array (event_start_date / event_end_date /
     * event_start_time / event_end_time). A schedule without times cannot be checked and
     * passes, matching how it is stored.
     */
    private function scheduleConflictFromSchedule(array $schedule, bool $lock = false): ?string
    {
        if (empty($schedule['event_start_date']) || empty($schedule['event_end_date'])
            || empty($schedule['event_start_time']) || empty($schedule['event_end_time'])) {
            return null;
        }

        $conflict = $this->scheduleConflict(
            Carbon::parse($schedule['event_start_date']),
            Carbon::parse($schedule['event_end_date']),
            (string) $schedule['event_start_time'],
            (string) $schedule['event_end_time'],
            $lock,
        );

        return $conflict['message'] ?? null;
    }

    /**
     * Message for a schedule 422: names the day, the blocked window (break included) and
     * the required gap, so the client can pick a workable time without guessing.
     */
    private function scheduleConflictMessage(string $iso, int $busyFrom, int $busyTo): string
    {
        $gap = Scheduling::minGapMinutes();
        $break = $gap > 0
            ? ' Bookings on the same day need at least '.Scheduling::humanizeMinutes($gap).' between them.'
            : '';

        return Carbon::parse($iso)->format('F j, Y')
            .' is unavailable from '.Scheduling::minutesToClock($busyFrom)
            .' to '.Scheduling::minutesToClock($busyTo).'.'.$break
            .' Please pick a different time or date.';
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

    private function newReference(): string
    {
        do {
            $reference = 'JG'.random_int(10000, 99999);
        } while (Booking::where('reference', $reference)->exists());

        return $reference;
    }

    private function capacityConflict(array $fullDates): JsonResponse
    {
        $message = count($fullDates) > 1
            ? 'One or more of the days you picked is fully booked. Please choose other dates.'
            : 'That day is fully booked. Please choose another date.';

        return response()->json([
            'message' => $message,
            'errors' => ['event_start_date' => [$message]],
            'full_dates' => $fullDates,
        ], 422);
    }

    private function validationError(array $errors, string $message = 'The given data was invalid.'): JsonResponse
    {
        return response()->json(['message' => $message, 'errors' => $errors], 422);
    }

    private function jsonError(string $message, int $status): JsonResponse
    {
        return response()->json(['message' => $message], $status);
    }
}
