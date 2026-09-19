<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorReading extends Model
{
    protected $keyType = 'uuid';

    public $incrementing = false;

    public $timestamps = false;

    protected $primaryKey = 'id';

    protected $fillable = [
        'id',
        'sensor_id',
        'device_id',
        'device_ts',
        'server_ts',
        'seq',
        'raw_value',
        'calibrated_value',
        'quality_flag',
        'battery_v',
        'rssi',
        'firmware',
    ];

    protected function casts(): array
    {
        return [
            'device_ts' => 'datetime',
            'server_ts' => 'datetime',
            'seq' => 'integer',
            'raw_value' => 'float',
            'calibrated_value' => 'float',
            'battery_v' => 'float',
            'rssi' => 'integer',
        ];
    }

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(Sensor::class);
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
