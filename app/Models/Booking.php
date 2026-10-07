<?php

namespace App\Models;

use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Model;

class Booking extends Model
{
    protected $fillable = [
        'reference',
        'status',
        'client_name',
        'client_email',
        'event_name',
        'event_type',
        'event_location',
        'event_start_date',
        'event_end_date',
        'event_start_time',
        'event_end_time',
        'package_id',
        'package_name',
        'package_price',
        'payment_method',
        'payment_state',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'event_start_date' => 'date',
            'event_end_date' => 'date',
            'package_price' => 'decimal:2',
            'submitted_at' => 'datetime',
        ];
    }

    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'status' => $this->status,
            'client' => [
                'name' => $this->client_name,
                'email' => $this->client_email,
                'phone' => null,
                'address' => null,
            ],
            'event' => [
                'name' => $this->event_name,
                'type' => $this->event_type,
                'location' => $this->event_location,
                'contact_person' => null,
                'guests' => null,
                'venue_type' => null,
                'start_date' => $this->event_start_date,
                'end_date' => $this->event_end_date ?? $this->event_start_date,
                'start_time' => $this->event_start_time,
                'end_time' => $this->event_end_time,
            ],
            'payment' => [
                'method' => $this->payment_method,
                'label' => $this->payment_method,
                'status' => match ($this->payment_state) {
                    'paid' => 'Fully Paid',
                    'partial' => 'Down payment paid',
                    'pending' => 'Awaiting verification',
                    default => null,
                },
                'state' => $this->payment_state,
            ],
            'package' => [
                'id' => $this->package_id,
                'name' => $this->package_name,
                'price' => $this->package_price === null ? null : (float) $this->package_price,
            ],
            'submitted_at' => $this->submitted_at,
        ];
    }

    public static function fullCapacityDates(): array
    {
        $counts = [];
        $statuses = config('scheduling.counted_statuses', []);
        $limit = (int) config('scheduling.max_events_per_day');

        if ($limit < 1 || $statuses === []) {
            return [];
        }

        static::query()
            ->whereIn('status', $statuses)
            ->get(['event_start_date', 'event_end_date'])
            ->each(function (self $booking) use (&$counts): void {
                $start = $booking->event_start_date;
                $end = $booking->event_end_date ?? $start;

                foreach (CarbonPeriod::create($start, $end) as $date) {
                    $day = $date->toDateString();
                    $counts[$day] = ($counts[$day] ?? 0) + 1;
                }
            });

        return collect($counts)
            ->filter(fn (int $count) => $count >= $limit)
            ->keys()
            ->sort()
            ->values()
            ->all();
    }
}
