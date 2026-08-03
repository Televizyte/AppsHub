<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DeviceToken extends Model
{
    protected $table = 'device_tokens';

    protected $fillable = [
        'app_id',
        'user_id',
        'platform',
        'token',
        'is_active',
        'last_seen_at',
        'meta_json',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_seen_at' => 'datetime',
        'meta_json' => 'array',
        'app_id' => 'integer',
        'user_id' => 'integer',
    ];
}
