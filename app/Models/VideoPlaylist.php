<?php

namespace App\Models;

use App\Support\Publishing\PublicationVisibility;
use App\Support\Video\VideoDomainGuard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class VideoPlaylist extends Model
{
    protected $fillable = [
        'app_id', 'video_channel_id', 'slug', 'title', 'description', 'provider',
        'provider_playlist_id', 'external_url', 'thumbnail_media_asset_id',
        'status', 'visibility', 'is_featured', 'sort_order', 'publish_at',
        'published_at', 'settings_json',
    ];

    protected $casts = [
        'app_id' => 'integer', 'video_channel_id' => 'integer',
        'thumbnail_media_asset_id' => 'integer', 'is_featured' => 'boolean',
        'sort_order' => 'integer', 'publish_at' => 'datetime',
        'published_at' => 'datetime', 'settings_json' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $playlist): void {
            if ($playlist->exists && $playlist->isDirty('app_id')) {
                throw new \DomainException('Video playlist app ownership is immutable.');
            }

            if (! VideoChannel::query()->whereKey($playlist->video_channel_id)->where('app_id', $playlist->app_id)->exists()) {
                throw new \DomainException('Video playlist channel must belong to the same app.');
            }

            if ($playlist->thumbnail_media_asset_id !== null
                && ! MediaAsset::query()->whereKey($playlist->thumbnail_media_asset_id)->where('app_id', $playlist->app_id)->exists()) {
                throw new \DomainException('Video playlist thumbnail must belong to the same app.');
            }
        });
    }

    public function app(): BelongsTo { return $this->belongsTo(App::class); }
    public function channel(): BelongsTo { return $this->belongsTo(VideoChannel::class, 'video_channel_id'); }
    public function thumbnailMediaAsset(): BelongsTo { return $this->belongsTo(MediaAsset::class, 'thumbnail_media_asset_id'); }
    public function items(): HasMany
    {
        return $this->hasMany(VideoPlaylistItem::class)->orderBy('sort_order')->orderBy('id');
    }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return PublicationVisibility::apply($query)->where('visibility', 'public');
    }

    public function acceptsChannel(VideoChannel $channel): bool
    {
        return VideoDomainGuard::sameApp($this->app_id, $channel->app_id);
    }

    public function acceptsThumbnail(MediaAsset $asset): bool
    {
        return VideoDomainGuard::sameApp($this->app_id, $asset->app_id);
    }
}
