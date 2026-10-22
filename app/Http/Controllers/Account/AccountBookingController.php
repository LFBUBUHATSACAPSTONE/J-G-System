<?php

namespace App\Http\Controllers\Account;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * Account > My Bookings. Only ever reads or changes the logged-in user's own bookings
 * (`bookings.user_id`). Nothing here deletes a row: cancelling only sets the status.
 *
 *   GET  user.account.bookings         the page (HTML), or {ok, bookings[]} when the request asks for JSON
 *   POST user.account.bookings.cancel  JSON only
 *
 * Cancel contract (resources/js/account/bookings.js):
 *   200 {ok, message, booking{id, status: 'user_cancelled', can_reschedule}}
 *   403 / 409 {message}   not the user's booking / not cancellable any more or the event has started
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

    public function cancel(Request $request, string $booking): JsonResponse
    {
        $user = $request->user();

        $result = DB::transaction(function () use ($user, $booking): array {
            $model = Booking::query()->whereKey($booking)->lockForUpdate()->first();

            // Someone else's booking and a booking that does not exist answer the same, so ids cannot be probed.
            if (! $model || (int) $model->user_id !== (int) $user->id) {
                return ['error' => 'This booking is not available to you.', 'status' => 403];
            }

            if (! in_array($model->status, config('account.bookings.cancellable', []), true)) {
                return ['error' => 'This booking can no longer be cancelled.', 'status' => 409];
            }

            $model->loadMissing('schedule');
            $start = $model->schedule?->event_start_date;
            if (! $start || ! $start->copy()->startOfDay()->gt(Carbon::today())) {
                return ['error' => 'This event has already started, so it can no longer be cancelled online.', 'status' => 409];
            }

            $model->status = 'user_cancelled';
            $model->save();

            return ['booking' => $model];
        });

        if (isset($result['error'])) {
            return response()->json(['message' => $result['error']], $result['status']);
        }

        /** @var Booking $cancelled */
        $cancelled = $result['booking'];

        return response()->json([
            'ok' => true,
            'message' => 'Your booking has been cancelled.',
            'booking' => [
                'id' => $cancelled->id,
                'status' => 'user_cancelled',
                'can_reschedule' => $cancelled->rescheduleBlockReason() === null,
            ],
        ]);
    }
}
