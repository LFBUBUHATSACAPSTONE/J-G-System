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

    // Cancellation statuses
    public const STATUS_PENDING_CANCELLATION = 'pending_cancellation';
    public const STATUS_USER_CANCELLED = 'user_cancelled'; // Legacy self-cancel status
    public const STATUS_ADMIN_CANCELLED = 'admin_cancelled'; // Legacy direct-cancel status
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'user_id',
        'reference',
        'status',
        'client_name',
        'client_email',
        'client_phone',
        'client_address',
        'package_id',
        'rescheduled_from_id',
        'submitted_at',
        'approved_at',
        'previous_status',
        'status_reason',
        'user_cancellation_reason',
        'user_cancelled_at',
        'admin_cancellation_reason',
        'admin_cancellation_action',
        'admin_cancelled_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'approved_at' => 'datetime',
            'user_cancelled_at' => 'datetime',
            'admin_cancelled_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        // Stamp the exact moment a booking becomes approved. The chat drops the booking
        // card at this instant and NEVER moves it, so only a status CHANGE to "approved"
        // sets it, and only when it is still empty: a later edit keeps the original time.
        static::saving(function (self $booking): void {
            if ($booking->isDirty('status')
                && $booking->status === 'approved'
                && $booking->approved_at === null) {
                $booking->approved_at = now();
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
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

    /**
     * The cancellation lifecycle of this booking, or null when there is nothing to show.
     *
     * `status` is the answer the client sees:
     *   pending            the client asked; the admin has not decided yet
     *   approved           the admin approved the client's request (booking status: cancelled)
     *   declined           the admin declined the client's request (booking stays active)
     *   cancelled_by_admin the admin cancelled directly, without a client request
     *
     * The two reasons live in separate columns on purpose: approving or declining must never
     * overwrite what the client wrote, and a direct admin cancellation has no client reason.
     */
    public function cancellationDetails(): ?array
    {
        $hasUserRequest = filled($this->user_cancellation_reason) || $this->user_cancelled_at !== null;
        $hasAdminAction = $this->admin_cancellation_action !== null
            || filled($this->admin_cancellation_reason)
            || $this->admin_cancelled_at !== null;

        $status = match (true) {
            $this->status === self::STATUS_PENDING_CANCELLATION => 'pending',
            $this->admin_cancellation_action === 'approved' => 'approved',
            $this->admin_cancellation_action === 'rejected' => 'declined',
            $this->admin_cancellation_action === null
                && filled($this->admin_cancellation_reason)
                && in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_ADMIN_CANCELLED], true) => 'cancelled_by_admin',
            ! $hasUserRequest && ! $hasAdminAction => match ($this->status) {
                self::STATUS_USER_CANCELLED => 'user_cancelled',
                self::STATUS_CANCELLED, self::STATUS_ADMIN_CANCELLED => 'cancelled',
                default => null,
            },
            default => null,
        };

        if ($status === null) {
            return null;
        }

        // A legacy terminal status (rows cancelled before the request workflow existed) has no
        // reasons to show. Leave it to the generic status_reason note instead of an empty section.
        if (! $hasUserRequest && ! $hasAdminAction && in_array($status, ['cancelled', 'user_cancelled'], true)) {
            return null;
        }

        return [
            'status' => $status,
            'user_reason' => $this->user_cancellation_reason,
            'user_cancelled_at' => $this->user_cancelled_at?->toIso8601String(),
            'admin_reason' => $this->admin_cancellation_reason,
            'admin_action' => $this->admin_cancellation_action,
            'admin_cancelled_at' => $this->admin_cancelled_at?->toIso8601String(),
        ];
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

        $base = [
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
                // The screenshot the client uploaded on the booking payment step, shown in the
                // admin booking modal (payment.proof_url). Built with asset() (the request root),
                // not Storage::url(), so it also resolves when the app is served from a
                // sub-directory. Null when nothing was uploaded.
                'proof_url' => $payment?->payment_receipt_path
                    ? asset('storage/'.$payment->payment_receipt_path)
                    : null,
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
            // Why a booking was declined. The cancellation request has its own, richer payload
            // below; this column is only used by the old "Decline booking" action.
            'status_reason' => $this->status_reason,
        ];

        // The client's request and the admin's decision (pending / approved / declined / direct).
        if ($cancellation = $this->cancellationDetails()) {
            $base['cancellation'] = $cancellation;
        }

        return $base;
    }

    /**
     * Why this booking cannot be rescheduled, or null when it can. The one place for the rule, used by
     * the booking flow (BookingController) and by My Bookings (Account\AccountBookingController).
     *
     * Pass $alreadyRescheduled when the caller already knows it (a list screen asks once for all
     * bookings instead of once per booking).
     */
    public function rescheduleBlockReason(?bool $alreadyRescheduled = null): ?string
    {
        if (! $this->isReschedulableCancellation()) {
            return 'Only a booking you cancelled can be rescheduled.';
        }

        $alreadyRescheduled ??= static::query()->where('rescheduled_from_id', $this->id)->exists();
        if ($alreadyRescheduled) {
            return 'This booking was already rescheduled once.';
        }

        $this->loadMissing('schedule');
        if (! $this->schedule
            || ! $this->schedule->event_start_date
            || $this->schedule->event_start_date->format('Y-m') < now()->format('Y-m')) {
            return 'The original month has passed, so this booking can no longer be rescheduled.';
        }

        return null;
    }

    /**
     * A booking can be rescheduled after the user cancelled it themselves or an admin approved
     * the user's cancellation request. Direct admin cancellations remain ineligible.
     */
    public function isReschedulableCancellation(): bool
    {
        return $this->status === self::STATUS_USER_CANCELLED
            || ($this->status === self::STATUS_CANCELLED
                && $this->admin_cancellation_action === 'approved');
    }

    /**
     * The shape the My Bookings page renders (resources/views/user/account/bookings.blade.php):
     * the admin array without `client`, plain Y-m-d dates, `payment.amount` and the reschedule fields.
     */
    public function toAccountArray(?bool $alreadyRescheduled = null): array
    {
        $data = $this->toAdminArray();
        $payment = $this->payment;

        $isReschedulableCancellation = $this->isReschedulableCancellation();
        $reason = $isReschedulableCancellation
            ? $this->rescheduleBlockReason($alreadyRescheduled)
            : null;

        $accountData = [
            'id' => $data['id'],
            'reference' => $data['reference'],
            'status' => $data['status'],
            'package' => [
                'id' => $data['package']['id'],
                'name' => $data['package']['name'],
                'price' => $data['package']['price'],
            ],
            'event' => array_merge($data['event'], [
                'start_date' => $data['event']['start_date']?->toDateString(),
                'end_date' => $data['event']['end_date']?->toDateString(),
            ]),
            'payment' => [
                'method' => $data['payment']['method'],
                'state' => $data['payment']['state'],
                'amount' => $payment?->payment_amount === null ? null : (float) $payment->payment_amount,
            ],
            'can_reschedule' => $isReschedulableCancellation && $reason === null,
            'reschedule_note' => $reason,
            'status_reason' => $data['status_reason'],
        ];

        if (isset($data['cancellation'])) {
            $accountData['cancellation'] = $data['cancellation'];
        }

        return $accountData;
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
