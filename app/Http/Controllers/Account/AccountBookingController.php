<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;

/**
 * Account > My Bookings. Only ever reads or changes the logged-in user's own bookings
 * (`bookings.user_id`). Nothing here deletes a row: cancelling only sets the status.
 *
 *   GET  user.account.bookings                    the page (HTML), or {ok, bookings[]} when the request asks for JSON
 *   POST user.account.bookings.cancel             Submit cancellation request (now requires reason)
 *
 * Cancel request contract (resources/js/account/bookings.js):
 *   200 {ok, message, booking{id, status: 'pending_cancellation', cancellation}}
 *   403 / 409 {message}   not the user's booking / not cancellable any more or the event has started
 *   422 {message, errors}  validation failed (reason required)
 */
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
            // JSON_PRESERVE_ZERO_FRACTION: the amounts are floats on purpose (see
            // Booking::toAccountArray), so 3000 has to leave the app as 3000.0 and not as 3000.
            return response()->json(['ok' => true, 'bookings' => $rows], 200, [], JSON_PRESERVE_ZERO_FRACTION);
        }

        return view('user.account.bookings', ['bookings' => $rows]);
    }

    /**
     * The client asks J&G to cancel a booking.
     *
     * This is a REQUEST, not an instant cancellation: the booking moves to
     * `pending_cancellation` and keeps its slot until an admin approves or declines it. The
     * client's reason is stored on its own column so the admin's later decision can never
     * overwrite it.
     *
     *   200 {ok, message, booking{id, status, cancellation}}
     *   403 {message}  not the client's booking
     *   409 {message}  already pending, not cancellable any more, or the event has started
     *   422 {message, errors}  the reason is missing or outside config/cancellation.php
     */
    public function requestCancellation(Request $request, string $booking): JsonResponse
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
