<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PackageEquipment extends Model
{
    protected $table = 'package_equipment';

    protected $fillable = [
        'package_id',
        'row_key',
        'value',
    ];

    protected function casts(): array
    {
        return [
            'row_key' => 'string',
            'value' => 'string',
        ];
    }

    public function package(): BelongsTo
    {
        return $this->belongsTo(Package::class, 'package_id');
    }
}
