<?php

namespace App\Models;

use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference',
        'status',
        'client_name',
        'client_email',
        'client_phone',
        'client_address',
        'package_id',
        'rescheduled_from_id',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }

    public function eventDetails(): HasOne
    {
        return $this->hasOne(BookingEvent::class);
    }

    public function schedule(): HasOne
    {
        return $this->hasOne(BookingSchedule::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(BookingPayment::class);
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class);
    }

    public function toAdminArray(): array
    {
        $this->loadMissing(['eventDetails', 'schedule', 'payment', 'package']);

        $event = $this->eventDetails;
        $schedule = $this->schedule;
        $payment = $this->payment;
        $package = $this->package;
        $paymentMethod = $payment?->payment_method;
        $paymentAmount = $payment?->payment_amount;

        return [
            'id' => $this->id,
            'reference' => str_starts_with($this->reference, '#') ? $this->reference : '#'.$this->reference,
            'status' => $this->status,
            'client' => [
                'name' => $this->client_name,
                'email' => $this->client_email,
                'phone' => $this->client_phone,
                'address' => $this->client_address,
            ],
            'event' => [
                'name' => $event?->event_name,
                'type' => $event?->event_type,
                'type_other' => $event?->event_type_other,
                'location' => $event?->event_location,
                'contact_person' => $event?->event_contact_person,
                'guests' => $event?->guest_count,
                'venue_type' => $event?->venue_type,
                'start_date' => $schedule?->event_start_date,
                'end_date' => $schedule?->event_end_date ?? $schedule?->event_start_date,
                'start_time' => $schedule?->event_start_time,
                'end_time' => $schedule?->event_end_time,
            ],
            'payment' => [
                'method' => $paymentMethod,
                'label' => $paymentMethod === 'down' ? 'Down Payment' : ($paymentMethod === 'full' ? 'Full Payment' : $paymentMethod),
                'status' => match ($payment?->payment_state) {
                    'paid' => 'Fully Paid',
                    'partial' => 'Down payment paid',
                    'pending' => 'Awaiting verification',
                    default => null,
                },
                'state' => $payment?->payment_state,
                'down_payment_amount' => $paymentMethod === 'down' && $paymentAmount !== null
                    ? (float) $paymentAmount
                    : null,
            ],
            'package' => [
                'id' => $package?->id,
                'name' => $package?->name,
                'price' => $package?->price === null ? null : (float) $package->price,
            ],
            'submitted_at' => $this->submitted_at,
        ];
    }

    public static function fullCapacityDates(): array
    {
        $counts = [];
        $statuses = config('scheduling.counted_statuses', []);
        $limit = (int) config('scheduling.max_events_per_day', 0);

        if ($limit < 1 || $statuses === []) {
            return [];
        }

        static::query()
            ->whereIn('status', $statuses)
            ->with('schedule')
            ->get()
            ->each(function (self $booking) use (&$counts): void {
                $schedule = $booking->schedule;
                if (! $schedule || ! $schedule->event_start_date) {
                    return;
                }

                $start = $schedule->event_start_date;
                $end = $schedule->event_end_date ?? $start;

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
