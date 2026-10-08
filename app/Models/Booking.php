<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'event_name',
        'event_type',
        'event_type_other',
        'event_location',
        'event_contact_person',
        'guest_count',
        'venue_type',
        'event_start_date',
        'event_end_date',
        'event_start_time',
        'event_end_time',
        'package_id',
        'package_name',
        'package_price',
        'payment_method',
        'payment_state',
        'payment_reference',
        'payment_receipt_path',
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
}
