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
        'last_seen_at',
        'last_battery_v',
        'last_rssi',
        'fw_version',
    ];

    protected function casts(): array
    {
        return [
            'last_seen_at' => 'datetime',
            'last_battery_v' => 'float',
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
