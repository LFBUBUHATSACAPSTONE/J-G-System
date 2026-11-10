<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\Notifications\BookingWorkflowNotifier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;


class AccountBookingController extends Controller
{
    public function index(Request $request): View|JsonResponse
    {
        $bookings = $request->user()
            ->bookings()
            ->with(['eventDetails', 'schedule', 'payment', 'package'])
            ->orderByDesc('submitted_at')
            ->orderByDesc('id')
            ->get();

        // One query for "was this booking already rescheduled?" instead of one per booking.
        $rescheduled = Booking::query()
            ->whereIn('rescheduled_from_id', $bookings->pluck('id'))
            ->pluck('rescheduled_from_id')
            ->flip();

        $rows = $bookings
            ->map(fn (Booking $booking) => $booking->toAccountArray($rescheduled->has($booking->id)))
            ->all();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'bookings' => $rows], 200, [], JSON_PRESERVE_ZERO_FRACTION);
        }

        return view('user.account.bookings', ['bookings' => $rows]);
    }

    public function requestCancellation(Request $request, string $booking, BookingWorkflowNotifier $notifications): JsonResponse
    {
        $min = (int) config('cancellation.min', 10);
        $max = (int) config('cancellation.max', 500);
        $reason = trim((string) $request->input('reason', ''));

        $validator = Validator::make(['reason' => $reason], [
            'reason' => ['required', 'string', "min:{$min}", "max:{$max}"],
        ], [
            'reason.required' => 'Please provide a reason for cancellation.',
            'reason.min' => "The cancellation reason must be at least {$min} characters.",
            'reason.max' => "The cancellation reason must not exceed {$max} characters.",
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = $request->user();

        $result = DB::transaction(function () use ($user, $booking, $reason): array {
            $model = Booking::query()->whereKey($booking)->lockForUpdate()->first();

            // Someone else's booking and a booking that does not exist answer the same, so ids cannot be probed.
            if (! $model || (int) $model->user_id !== (int) $user->id) {
                return ['error' => 'This booking is not available to you.', 'status' => 403];
            }

            if ($model->status === Booking::STATUS_PENDING_CANCELLATION) {
                return ['error' => 'You already have a pending cancellation request for this booking.', 'status' => 409];
            }

            if (! in_array($model->status, config('account.bookings.cancellable', []), true)) {
                return ['error' => 'This booking can no longer be cancelled.', 'status' => 409];
            }

            $model->loadMissing('schedule');
            $start = $model->schedule?->event_start_date;
            if (! $start || ! $start->copy()->startOfDay()->gt(Carbon::today())) {
                return ['error' => 'This event has already started, so it can no longer be cancelled online.', 'status' => 409];
            }

            // Remember what the booking was so a declined request can restore exactly that
            // status. A new request also clears the previous decision, if any.
            $model->previous_status = $model->status;
            $model->status = Booking::STATUS_PENDING_CANCELLATION;
            $model->user_cancellation_reason = $reason;
            $model->user_cancelled_at = now();
            $model->admin_cancellation_reason = null;
            $model->admin_cancellation_action = null;
            $model->admin_cancelled_at = null;
            $model->save();

            return ['booking' => $model];
        });

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], $result['status']);
        }

        /** @var Booking $bookingModel */
        $bookingModel = $result['booking'];
        $notifications->notifyAdmins($bookingModel, 'cancellation_requested');

        return response()->json([
            'ok' => true,
            'message' => 'Your cancellation request has been submitted and is awaiting admin review.',
            'booking' => [
                'id' => $bookingModel->id,
                'status' => $bookingModel->status,
                'cancellation' => $bookingModel->cancellationDetails(),
            ],
        ]);
    }
}
