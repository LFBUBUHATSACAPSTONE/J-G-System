<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
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
        $bookings = Booking::query()
            ->where(function ($query) {
                $query->whereIn('status', config('admin.history.terminal_statuses'))
                    ->orWhere(function ($query) {
                        $query->whereIn('status', config('admin.history.history_statuses'))
                            ->whereIn('payment_state', config('admin.history.paid_states'));
                    });
            })
            ->orderByDesc('event_start_date')
            ->orderByDesc('id')
            ->get()
            ->map(fn (Booking $booking) => $this->formatBooking($booking))
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

    private function formatBooking(Booking $booking): array
    {
        $paymentState = $booking->payment_state;
        $paymentStatus = match ($paymentState) {
            'paid' => 'Fully Paid',
            'partial' => 'Down payment paid',
            'pending' => 'Awaiting verification',
            default => null,
        };

        $paymentMethod = $booking->payment_method
            ? ucfirst(strtolower($booking->payment_method))
            : null;

        return [
            'id' => $booking->id,
            'reference' => str_starts_with($booking->reference, '#')
                ? $booking->reference
                : '#'.$booking->reference,
            'status' => $booking->status,
            'client' => [
                'name' => $booking->client_name,
                'email' => $booking->client_email,
                'phone' => $booking->client_phone,
                'address' => $booking->client_address,
            ],
            'event' => [
                'name' => $booking->event_name,
                'type' => $booking->event_type === 'Others' && $booking->event_type_other
                    ? $booking->event_type_other
                    : $booking->event_type,
                'location' => $booking->event_location,
                'contact_person' => $booking->event_contact_person,
                'guests' => $booking->guest_count,
                'venue_type' => $booking->venue_type,
                'start_date' => $booking->event_start_date,
                'end_date' => $booking->event_end_date ?? $booking->event_start_date,
                'start_time' => $booking->event_start_time,
                'end_time' => $booking->event_end_time,
            ],
            'payment' => [
                'label' => $paymentMethod,
                'status' => $paymentStatus,
                'state' => $paymentState,
            ],
            'package' => [
                'id' => $booking->package_id,
                'name' => $booking->package_name,
                'price' => $booking->package_price,
            ],
        ];
    }
}
