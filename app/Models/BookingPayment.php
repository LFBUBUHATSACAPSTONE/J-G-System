<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BookingPayment extends Model
{
    protected $fillable = [
        'payment_method',
        'payment_state',
        'payment_reference',
        'payment_receipt_path',
        'payment_amount',
    ];

    protected function casts(): array
    {
        return [
            'payment_amount' => 'decimal:2',
        ];
    }

    public function booking(): BelongsTo
    {
        return $this->belongsTo(Booking::class);
    }
}
