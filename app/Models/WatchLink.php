<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WatchLink extends Model
{
    protected $table = 'watch_links';

    protected $fillable = [
        'app_id',
        'title',
        'type',
        'url',
        'is_enabled',
        'sort_order',
        'meta_json',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'sort_order' => 'integer',
        'meta_json' => 'array',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    public function getSubtitleAttribute(): string
    {
        return (string) data_get($this->meta_json, 'subtitle', '');
    }

    public function getLabelAttribute(): string
    {
        return (string) data_get($this->meta_json, 'label', '');
    }

    public function getPlayerAttribute(): string
    {
        return (string) data_get($this->meta_json, 'player', '');
    }

    public function getGroupAttribute(): string
    {
        return (string) data_get($this->meta_json, 'group', '');
    }

    public function getImageUrlAttribute(): string
    {
        return (string) data_get($this->meta_json, 'image_url', '');
    }
}
