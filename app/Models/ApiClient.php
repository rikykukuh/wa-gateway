<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ApiClient extends Model
{
    use HasUuids;

    protected $fillable = [
        'name',
        'email',
        'key_prefix',
        'api_key_hash',
        'encrypted_api_key',
        'daily_message_limit',
        'min_delay_seconds',
        'is_active',
        'last_used_at',
    ];

    protected $hidden = ['api_key_hash', 'encrypted_api_key'];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'last_used_at' => 'datetime',
        ];
    }

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
