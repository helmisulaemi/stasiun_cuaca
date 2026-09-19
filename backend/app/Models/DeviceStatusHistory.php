<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DeviceStatusHistory extends Model
{
    protected $keyType = 'uuid';

    public $incrementing = false;

    public $timestamps = false;

    protected $table = 'device_status_history';

    protected $fillable = [
        'device_id',
        'status',
        'changed_at',
        'changed_by_user_id',
    ];

    protected function casts(): array
    {
        return [
            'changed_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }

    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by_user_id');
    }
}
