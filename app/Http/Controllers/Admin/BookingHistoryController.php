<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\BookingSchedule;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class BookingHistoryController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json($this->historyData());
    }

    public function show(): View
    {
        return view('admin.booking-history', $this->historyData());
    }

    private function historyData(): array
    {
        // Cancelled / Declined bookings always belong here; Approved / Completed bookings belong
        // once their payment is verified. The payment state now lives in `booking_payments`, so the
        // rule is a whereHas on the relation (not a column on `bookings`).
        $bookings = Booking::query()
            ->with(['eventDetails', 'schedule', 'payment', 'package'])
            // The page and the month filter both key off the event dates, so a booking without a
            // schedule (incomplete legacy row) is skipped instead of breaking the view.
            ->whereHas('schedule')
            ->where(function ($query) {
                $query->whereIn('status', config('admin.history.terminal_statuses'))
                    ->orWhere(function ($query) {
                        $query->whereIn('status', config('admin.history.history_statuses'))
                            ->whereHas('payment', fn ($payment) => $payment
                                ->whereIn('payment_state', config('admin.history.paid_states')));
                    });
            })
            ->orderByDesc(
                BookingSchedule::select('event_start_date')
                    ->whereColumn('booking_schedules.booking_id', 'bookings.id')
                    ->limit(1)
            )
            ->orderByDesc('id')
            ->get()
            ->map(fn (Booking $booking) => $booking->toAdminArray())
            ->values()
            ->all();

        $packages = collect($bookings)
            ->map(fn (array $booking) => $booking['package'])
            ->filter(fn (array $package) => $package['id'] !== null)
            ->unique('id')
            ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();

        return compact('bookings', 'packages');
    }
}
