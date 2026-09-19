<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Device extends Model
{
    use SoftDeletes;

    protected $keyType = 'uuid';

    public $incrementing = false;

    protected $fillable = [
        'name',
        'location_id',
        'status',
        'secret_hash',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(Location::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(DeviceStatusHistory::class);
    }

    public function sensorInstallations(): HasMany
    {
        return $this->hasMany(SensorInstallation::class);
    }

    public function sensorReadings(): HasMany
    {
        return $this->hasMany(SensorReading::class);
    }
}
