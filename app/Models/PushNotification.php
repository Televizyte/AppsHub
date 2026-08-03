<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PushNotification extends Model
{
    protected $table = 'push_notifications';

    protected $fillable = [
        'app_id',
        'title',
        'body',
        'image_url',
        'media_asset_id',
        'deep_link_url',
        'click_action',
        'target_type',
        'target_value',
        'timezone',
        'scheduled_for',
        'recurrence_type',
        'recurrence_weekdays',
        'recurrence_month_day',
        'recurrence_hour',
        'recurrence_minute',
        'ends_at',
        'max_runs',
        'runs_count',
        'status',
        'meta_json',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'ends_at' => 'datetime',
        'recurrence_weekdays' => 'array',
        'meta_json' => 'array',
        'runs_count' => 'integer',
        'max_runs' => 'integer',
        'recurrence_month_day' => 'integer',
        'recurrence_hour' => 'integer',
        'recurrence_minute' => 'integer',
        'media_asset_id' => 'integer',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'media_asset_id');
    }
}
