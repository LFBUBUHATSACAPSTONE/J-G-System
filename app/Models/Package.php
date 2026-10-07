<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    public $incrementing = false;

    protected $keyType = 'string';

    protected $fillable = [
        'id',
        'name',
        'price',
        'available',
        'sort_order',
        'features',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'available' => 'boolean',
            'features' => 'array',
        ];
    }

    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'available' => $this->available,
            'features' => $this->features ?? [],
        ];
    }

    public function toPublicArray(): array
    {
        return [
            'name' => $this->name,
            'price' => $this->price,
            'features' => $this->features ?? [],
        ];
    }
}
