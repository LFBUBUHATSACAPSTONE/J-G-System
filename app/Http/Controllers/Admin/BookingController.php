<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Carbon\CarbonPeriod;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class BookingController extends Controller
{
    public function index(): JsonResponse
    {
        $bookings = Booking::query()
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

    public function updateStatus(Request $request, string $booking): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'action' => ['required', 'in:approve,decline,cancel,verify'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $record = Booking::query()->find($booking);

        if (! $record) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        $action = $validator->validated()['action'];
        $result = DB::transaction(function () use ($record, $action): array {
            if ($action === 'approve') {
                $capacityLock = DB::table('booking_capacity_locks')
                    ->where('id', 1)
                    ->lockForUpdate()
                    ->first();

                if ($capacityLock === null) {
                    throw new \LogicException('The booking capacity lock row is missing.');
                }
            }

            $booking = Booking::query()->lockForUpdate()->findOrFail($record->id);
            $allowed = [
                'pending' => ['approve', 'decline'],
                'pending_payment' => ['cancel', 'verify'],
                'approved' => ['cancel'],
            ];

            if (! in_array($action, $allowed[$booking->status] ?? [], true)) {
                return ['invalid_status' => true];
            }

            $fullDates = [];

            if ($action === 'approve') {
                $start = $booking->event_start_date;
                $end = $booking->event_end_date ?? $start;
                $days = collect(CarbonPeriod::create($start, $end))
                    ->map(fn ($date) => $date->toDateString());
                $counted = Booking::query()
                    ->where('id', '!=', $booking->id)
                    ->whereIn('status', config('scheduling.counted_statuses', []))
                    ->whereDate('event_start_date', '<=', $end->toDateString())
                    ->where(function ($query) use ($start): void {
                        $query->whereDate('event_end_date', '>=', $start->toDateString())
                            ->orWhere(function ($query) use ($start): void {
                                $query->whereNull('event_end_date')
                                    ->whereDate('event_start_date', '>=', $start->toDateString());
                            });
                    })
                    ->lockForUpdate()
                    ->get(['event_start_date', 'event_end_date']);

                $dayCounts = [];
                foreach ($counted as $existing) {
                    $existingStart = $existing->event_start_date;
                    $existingEnd = $existing->event_end_date ?? $existingStart;
                    foreach (CarbonPeriod::create($existingStart, $existingEnd) as $date) {
                        $day = $date->toDateString();
                        $dayCounts[$day] = ($dayCounts[$day] ?? 0) + 1;
                    }
                }

                $limit = (int) config('scheduling.max_events_per_day');
                $fullDates = $days
                    ->filter(fn (string $day) => ($dayCounts[$day] ?? 0) >= $limit)
                    ->values()
                    ->all();

                if ($fullDates !== []) {
                    return ['full_dates' => $fullDates];
                }

                $booking->status = 'pending_payment';
            } elseif ($action === 'decline') {
                $booking->status = 'declined';
            } elseif ($action === 'cancel') {
                $booking->status = 'cancelled';
            } else {
                $booking->status = 'approved';
                $booking->payment_state = $booking->payment_state === 'paid' ? 'paid' : 'partial';
            }

            $booking->save();

            return ['booking' => $booking->fresh()->toAdminArray()];
        });

        if (isset($result['invalid_status'])) {
            return response()->json([
                'message' => 'That action is not available for this booking status.',
            ], 422);
        }

        if (isset($result['full_dates'])) {
            return response()->json([
                'message' => 'This booking cannot be approved because one or more event days are already at capacity.',
                'full_dates' => $result['full_dates'],
            ], 409);
        }

        return response()->json([
            'message' => 'Booking status updated.',
            'booking' => $result['booking'],
        ]);
    }

    public function update(Request $request, string $booking): JsonResponse
    {
        $record = Booking::query()->find($booking);

        if (! $record) {
            return response()->json(['message' => 'Booking not found.'], 404);
        }

        $validator = Validator::make($request->all(), [
            'event_name' => ['nullable', 'string', 'max:255'],
            'event_location' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $record->fill($validator->validated());
        $record->save();

        return response()->json([
            'message' => 'Booking details updated.',
            'booking' => $record->fresh()->toAdminArray(),
        ]);
    }
}
