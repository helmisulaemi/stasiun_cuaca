<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SensorCalibration extends Model
{
    protected $keyType = 'uuid';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'sensor_id',
        'offset',
        'scale',
        'effective_from',
        'effective_to',
    ];

    protected function casts(): array
    {
        return [
            'offset' => 'decimal:5',
            'scale' => 'decimal:5',
            'effective_from' => 'datetime',
            'effective_to' => 'datetime',
        ];
    }

    public function sensor(): BelongsTo
    {
        return $this->belongsTo(Sensor::class);
    }
}
