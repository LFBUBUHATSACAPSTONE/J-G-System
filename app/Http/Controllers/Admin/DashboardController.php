<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

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
            ->get([
                'id',
                'reference',
                'status',
                'client_name',
                'event_name',
                'event_start_date',
                'package_name',
                'submitted_at',
                'created_at',
            ])
            ->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'reference' => '#'.$booking->reference,
                'status' => $booking->status,
                'client' => $booking->client_name,
                'package' => $booking->package_name,
                'event' => $booking->event_name,
                'date' => $booking->event_start_date->toDateString(),
                'submitted_at' => ($booking->submitted_at ?? $booking->created_at)->toIso8601String(),
            ]);

        $upcomingEvents = Booking::query()
            ->where('status', 'approved')
            ->whereIn('payment_state', ['paid', 'partial'])
            ->whereDate('event_start_date', '>=', today())
            ->orderBy('event_start_date')
            ->get([
                'id',
                'event_start_date',
                'event_name',
                'client_name',
                'package_name',
                'event_start_time',
                'event_end_time',
            ])
            ->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'date' => $booking->event_start_date->toDateString(),
                'event' => $booking->event_name,
                'client' => $booking->client_name,
                'package' => $booking->package_name,
                'time' => $this->formatEventTime($booking->event_start_time, $booking->event_end_time),
            ]);

        $packageRate = Booking::query()
            ->whereIn('status', ['approved', 'completed'])
            ->selectRaw('package_id, package_name, COUNT(*) AS booking_count')
            ->groupBy('package_id', 'package_name')
            ->orderByDesc('booking_count')
            ->get()
            ->map(fn (Booking $booking) => [
                'id' => $booking->package_id,
                'label' => $booking->package_name ?: 'Unassigned',
                'count' => (int) $booking->booking_count,
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
            $query->whereIn('payment_state', $filters['payment_state']);
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
