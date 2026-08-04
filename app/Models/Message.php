<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasUuids;

    protected $fillable = [
        'device_id', 'recipient', 'body', 'status', 'provider_message_id', 'error',
        'scheduled_at', 'last_attempt_at', 'attempts', 'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'scheduled_at' => 'datetime',
            'last_attempt_at' => 'datetime',
            'sent_at' => 'datetime',
        ];
    }

    public function device(): BelongsTo
    {
        return $this->belongsTo(Device::class);
    }
}
