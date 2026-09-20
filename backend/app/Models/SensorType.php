<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SensorType extends Model
{
    protected $keyType = 'uuid';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'unit',
        'min_value',
        'max_value',
        'precision',
    ];

    public function sensors(): HasMany
    {
        return $this->hasMany(Sensor::class);
    }
}
