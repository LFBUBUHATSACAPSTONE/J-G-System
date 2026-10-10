<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Booking extends Model
{
    use HasFactory;

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

    public function packageDetails(): HasOne
    {
        return $this->hasOne(BookingPackage::class);
    }

    protected $fillable = [
        'reference',
        'status',
        'client_name',
        'client_email',
        'client_phone',
        'client_address',
        'rescheduled_from_id',
        'submitted_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
        ];
    }
}
