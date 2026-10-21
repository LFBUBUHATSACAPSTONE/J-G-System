<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
            'sort_order' => 'integer',
            'features' => 'array',
        ];
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    /**
     * The equipment of this package: one row per equipment line of config/package-equipment.php.
     */
    public function equipment(): HasMany
    {
        return $this->hasMany(PackageEquipment::class, 'package_id', 'id');
    }

    /**
     * The equipment as the views and the JSON use it: row_key => text ("main_speaker" => "2 Units").
     * A line without a value is simply missing from the map; the comparison table prints the
     * empty mark for it.
     */
    public function equipmentMap(): array
    {
        if (! $this->relationLoaded('equipment')) {
            $this->load('equipment');
        }

        return $this->equipment
            ->pluck('value', 'row_key')
            ->all();
    }

    public function toAdminArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'available' => $this->available,
            'features' => $this->features ?? [],
            'equipment' => $this->equipmentMap(),
        ];
    }

    public function toPublicArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'price' => $this->price,
            'features' => $this->features ?? [],
            // What the admin typed in Admin > Packages: the comparison table of the landing
            // page prints these values, so the clients see the same equipment as the admin.
            'equipment' => $this->equipmentMap(),
        ];
    }
}
