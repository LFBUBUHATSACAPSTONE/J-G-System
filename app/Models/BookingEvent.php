<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingEvent extends Model
{
    protected $fillable = [
        'event_name',
        'event_type',
        'event_type_other',
        'event_location',
        'event_contact_person',
        'guest_count',
        'venue_type',
    ];

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
