<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Location extends Model
{
    protected $keyType = 'uuid';

    public $incrementing = false;

    public $timestamps = false;

    protected $fillable = [
        'id',
        'name',
        'latitude',
        'longitude',
        'altitude',
    ];

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
