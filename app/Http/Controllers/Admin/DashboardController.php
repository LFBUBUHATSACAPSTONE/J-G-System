<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __invoke(): JsonResponse
    {
        $categories = [
            'total' => [],
            'confirmed' => [
                'status' => ['approved'],
                'payment_state' => ['paid', 'partial'],
            ],
            'pending' => ['status' => ['pending']],
            'payment' => ['status' => ['pending_payment']],
            'cancelled' => ['status' => ['cancelled', 'declined']],
        ];

        $now = now();
        $currentMonthStart = $now->copy()->startOfMonth();
        $previousMonthStart = $currentMonthStart->copy()->subMonth();
        $previousMonthEnd = $currentMonthStart->copy()->subSecond();

        $stats = [];
        foreach ($categories as $key => $filters) {
            $stats[$key] = [
                'value' => $this->countBookings($filters),
                'change' => $this->countBookings($filters, $currentMonthStart, $now)
                    - $this->countBookings($filters, $previousMonthStart, $previousMonthEnd),
            ];
        }

        $attention = Booking::query()
            ->whereIn('status', ['pending', 'pending_payment'])
            ->orderByRaw('COALESCE(submitted_at, created_at) ASC')
            ->with(['eventDetails', 'schedule', 'package'])
            ->get()
            ->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'reference' => '#'.$booking->reference,
                'status' => $booking->status,
                'client' => $booking->client_name,
                'package' => $booking->package?->name,
                'event' => $booking->eventDetails?->event_name,
                'date' => $booking->schedule?->event_start_date?->toDateString(),
                'submitted_at' => ($booking->submitted_at ?? $booking->created_at)?->toIso8601String(),
            ]);

        $upcomingEvents = Booking::query()
            ->where('status', 'approved')
            ->whereHas('payment', fn ($query) => $query->whereIn('payment_state', ['paid', 'partial']))
            ->whereHas('schedule', fn ($query) => $query->whereDate('event_start_date', '>=', today()))
            ->join('booking_schedules', 'bookings.id', '=', 'booking_schedules.booking_id')
            ->orderBy('booking_schedules.event_start_date')
            ->select('bookings.*')
            ->with(['eventDetails', 'schedule', 'package'])
            ->get()
            ->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'date' => $booking->schedule->event_start_date->toDateString(),
                'event' => $booking->eventDetails?->event_name,
                'client' => $booking->client_name,
                'package' => $booking->package?->name,
                'time' => $this->formatEventTime(
                    $booking->schedule->event_start_time,
                    $booking->schedule->event_end_time,
                ),
            ]);

        $packageRate = DB::table('bookings')
            ->join('packages', 'packages.id', '=', 'bookings.package_id')
            ->whereIn('bookings.status', ['approved', 'completed'])
            ->selectRaw('packages.id AS package_id, packages.name AS package_name, COUNT(*) AS booking_count')
            ->groupBy('packages.id', 'packages.name')
            ->orderByDesc('booking_count')
            ->get()
            ->map(fn ($package) => [
                'id' => $package->package_id,
                'label' => $package->package_name ?: 'Unassigned',
                'count' => (int) $package->booking_count,
            ]);

        return response()->json([
            'stats' => $stats,
            'attention' => $attention->values(),
            'upcomingEvents' => $upcomingEvents->values(),
            'packageRate' => $packageRate->values(),
        ]);
    }

    private function countBookings(array $filters = [], ?Carbon $from = null, ?Carbon $to = null): int
    {
        $query = Booking::query();

        if (isset($filters['status'])) {
            $query->whereIn('status', $filters['status']);
        }

        if (isset($filters['payment_state'])) {
            $query->whereHas('payment', fn ($payment) => $payment->whereIn('payment_state', $filters['payment_state']));
        }

        if ($from && $to) {
            $query->whereBetween('created_at', [$from, $to]);
        }

        return $query->count();
    }

    private function formatEventTime(?string $start, ?string $end): ?string
    {
        if (! $start || ! $end) {
            return null;
        }

        return Carbon::parse($start)->format('g:i A').' - '.Carbon::parse($end)->format('g:i A');
    }
}
