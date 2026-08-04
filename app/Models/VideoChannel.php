<?php

namespace App\Models;

use App\Support\Publishing\PublicationVisibility;
use App\Support\Video\VideoDomainGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VideoChannel extends Model
{
    protected $fillable = [
        'app_id', 'slug', 'title', 'description', 'thumbnail_media_asset_id',
        'status', 'visibility', 'is_featured', 'sort_order', 'publish_at',
        'published_at', 'notification_settings_json', 'settings_json',
    ];

    protected $casts = [
        'app_id' => 'integer', 'thumbnail_media_asset_id' => 'integer',
        'is_featured' => 'boolean', 'sort_order' => 'integer',
        'publish_at' => 'datetime', 'published_at' => 'datetime',
        'notification_settings_json' => 'array', 'settings_json' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $channel): void {
            if ($channel->exists && $channel->isDirty('app_id')) {
                throw new \DomainException('Video channel app ownership is immutable.');
            }

            if ($channel->thumbnail_media_asset_id !== null
                && ! MediaAsset::query()->whereKey($channel->thumbnail_media_asset_id)->where('app_id', $channel->app_id)->exists()) {
                throw new \DomainException('Video channel thumbnail must belong to the same app.');
            }
        });
    }

    public function app(): BelongsTo { return $this->belongsTo(App::class); }
    public function thumbnailMediaAsset(): BelongsTo { return $this->belongsTo(MediaAsset::class, 'thumbnail_media_asset_id'); }
    public function playlists(): HasMany { return $this->hasMany(VideoPlaylist::class); }
    public function videos(): HasMany { return $this->hasMany(Video::class); }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return PublicationVisibility::apply($query)->where('visibility', 'public');
    }

    public function acceptsThumbnail(MediaAsset $asset): bool
    {
        return VideoDomainGuard::sameApp($this->app_id, $asset->app_id);
    }
}
