<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorInstallation extends Model
{
    protected $keyType = 'uuid';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'device_id',
        'sensor_id',
        'installed_at',
        'removed_at',
    ];

    protected function casts(): array
    {
        return [
            'installed_at' => 'datetime',
            'removed_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(Sensor::class);
    }
}
