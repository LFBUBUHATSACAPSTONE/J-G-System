<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
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
                $counted = Booking::query()
                    ->where('id', '!=', $record->id)
                    ->whereIn('status', config('scheduling.counted_statuses', []))
                    ->whereHas('schedule', function ($query) use ($start, $end): void {
                        $query->whereDate('event_start_date', '<=', $end->toDateString())
                            ->whereDate('event_end_date', '>=', $start->toDateString());
                    })
                    ->lockForUpdate()
                    ->with('schedule')
                    ->get();

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

                $record->status = 'approved';
                $record->payment->payment_state = $record->payment->payment_state === 'paid'
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
}
