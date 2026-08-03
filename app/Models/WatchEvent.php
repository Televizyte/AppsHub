<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WatchEvent extends Model
{
    protected $table = 'watch_events';

    protected $fillable = [
        'app_id',
        'watch_link_id',
        'content_post_id',
        'user_id',
        'session_id',
        'event_type',
        'source',
        'platform',
        'position_seconds',
        'duration_seconds',
        'progress_percent',
        'device_type',
        'app_version',
        'os_version',
        'ip_address',
        'user_agent',
        'meta_json',
        'occurred_at',
    ];

    protected $casts = [
        'meta_json' => 'array',
        'occurred_at' => 'datetime',
        'position_seconds' => 'integer',
        'duration_seconds' => 'integer',
        'progress_percent' => 'integer',
        'watch_link_id' => 'integer',
        'content_post_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }
}
