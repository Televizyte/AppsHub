<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentView extends Model
{
    protected $table = 'content_views';

    protected $fillable = [
        'app_id',
        'content_post_id',
        'user_id',
        'session_id',
        'source',
        'platform',
        'device_type',
        'app_version',
        'os_version',
        'ip_address',
        'user_agent',
        'meta_json',
        'viewed_at',
    ];

    protected $casts = [
        'meta_json' => 'array',
        'viewed_at' => 'datetime',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    public function contentPost(): BelongsTo
    {
        return $this->belongsTo(ContentPost::class, 'content_post_id');
    }
}
