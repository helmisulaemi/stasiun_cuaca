<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    protected $keyType = 'uuid';

    public $incrementing = false;

    protected $fillable = [
        'id',
        'name',
        'email',
        'password_hash',
    ];

    protected $hidden = [
        'password_hash',
    ];

    protected function casts(): array
    {
        return [
            'password_hash' => 'hashed',
            'created_at' => 'datetime',
            'updated_at' => 'datetime',
        ];
    }

    public function deviceStatusHistories(): HasMany
    {
        return $this->hasMany(DeviceStatusHistory::class, 'changed_by_user_id');
    }

    public function getAuthPassword(): string
    {
        return $this->password_hash;
    }
}
