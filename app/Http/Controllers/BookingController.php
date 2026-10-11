<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Package;
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

            [$booking, $fullDates] = DB::transaction(function () use ($original, $schedule) {
                $fullDates = $this->fullDatesBetween(
                    Carbon::parse($schedule['event_start_date']),
                    Carbon::parse($schedule['event_end_date']),
                    true,
                );
                if ($fullDates) {
                    return [null, $fullDates];
                }

                $booking = Booking::create(array_merge(
                    $original->only(['client_name', 'client_email', 'client_phone', 'client_address']),
                    [
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

                return [$booking, []];
            });
            if ($fullDates) {
                return $this->capacityConflict($fullDates);
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

        [$booking, $fullDates] = DB::transaction(function () use ($draft, $package, $data, $paymentCents) {
            $fullDates = $this->fullDatesBetween(
                Carbon::parse($draft['event_start_date']),
                Carbon::parse($draft['event_end_date']),
                true,
            );
            if ($fullDates) {
                return [null, $fullDates];
            }

            $booking = Booking::create([
                'reference' => $this->newReference(),
                'status' => 'pending',
                'client_name' => $draft['client_name'],
                'client_email' => $draft['client_email'],
                'client_phone' => $draft['client_phone'],
                'client_address' => $draft['client_address'],
                'package_id' => $package->id,
                'submitted_at' => now(),
            ]);

            $this->createBookingDetails($booking, $draft, [
                'event_start_date' => $draft['event_start_date'],
                'event_end_date' => $draft['event_end_date'],
                'event_start_time' => $draft['event_start_time'] ?? null,
                'event_end_time' => $draft['event_end_time'] ?? null,
            ], [
                'payment_method' => $data['payment_option'],
                'payment_state' => 'pending',
                'payment_amount' => number_format($paymentCents / 100, 2, '.', ''),
            ]);

            return [$booking, []];
        });
        if ($fullDates) {
            return $this->capacityConflict($fullDates);
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
        if ($booking->status !== 'user_cancelled') {
            return [null, 'Only a booking you cancelled can be rescheduled.'];
        }
        if (Booking::where('rescheduled_from_id', $booking->id)->exists()) {
            return [null, 'This booking was already rescheduled once.'];
        }
        if (! $booking->schedule || $booking->schedule->event_start_date->format('Y-m') < now()->format('Y-m')) {
            return [null, 'The original month has passed, so this booking can no longer be rescheduled.'];
        }

        return [$booking, null];
    }

    private function fullDatesBetween(Carbon $start, Carbon $end, bool $lock = false): array
    {
        $limit = (int) config('scheduling.max_events_per_day', 3);
        $statuses = config('scheduling.counted_statuses', []);
        if ($limit < 1 || ! $statuses) {
            return [];
        }

        $counts = [];
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
        ]);

        foreach ($bookings as $booking) {
            foreach (CarbonPeriod::create($booking->event_start_date, $booking->event_end_date) as $date) {
                $iso = $date->toDateString();
                if ($iso >= $start->toDateString() && $iso <= $end->toDateString()) {
                    $counts[$iso] = ($counts[$iso] ?? 0) + 1;
                }
            }
        }

        $full = [];
        foreach (CarbonPeriod::create($start, $end) as $date) {
            $iso = $date->toDateString();
            if (($counts[$iso] ?? 0) >= $limit) {
                $full[] = $iso;
            }
        }

        return $full;
    }

    private function timeInMinutes(string $time): int
    {
        $date = Carbon::createFromFormat('g:i A', $time);

        return ((int) $date->format('G') * 60) + (int) $date->format('i');
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
