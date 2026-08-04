<?php

namespace App\Models;

use App\Support\Publishing\PublicationVisibility;
use App\Support\Video\VideoDomainGuard;
use App\Support\Video\VideoSourceContract;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Video extends Model
{
    protected $fillable = [
        'app_id', 'video_channel_id', 'slug', 'title', 'description', 'source_type',
        'provider', 'provider_video_id', 'media_asset_id', 'external_url',
        'thumbnail_media_asset_id', 'mime_type', 'duration_seconds', 'aspect_ratio',
        'is_live', 'status', 'visibility', 'is_featured', 'sort_order', 'publish_at',
        'published_at', 'playback_settings_json', 'settings_json',
    ];

    protected $casts = [
        'app_id' => 'integer', 'video_channel_id' => 'integer', 'media_asset_id' => 'integer',
        'thumbnail_media_asset_id' => 'integer', 'duration_seconds' => 'integer',
        'is_live' => 'boolean', 'is_featured' => 'boolean', 'sort_order' => 'integer',
        'publish_at' => 'datetime', 'published_at' => 'datetime',
        'playback_settings_json' => 'array', 'settings_json' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $video): void {
            if ($video->exists && $video->isDirty('app_id')) {
                throw new \DomainException('Video app ownership is immutable.');
            }

            if (! VideoSourceContract::isValid($video->getAttributes())) {
                throw new \DomainException('Video source configuration is invalid.');
            }

            if (! VideoChannel::query()->whereKey($video->video_channel_id)->where('app_id', $video->app_id)->exists()) {
                throw new \DomainException('Video channel must belong to the same app.');
            }

            if ($video->source_type === VideoSourceContract::UPLOADED_VIDEO
                && ! MediaAsset::query()->whereKey($video->media_asset_id)
                    ->where('app_id', $video->app_id)->where('type', 'video')->exists()) {
                throw new \DomainException('Uploaded video media asset must belong to the same app.');
            }

            if ($video->thumbnail_media_asset_id !== null
                && ! MediaAsset::query()->whereKey($video->thumbnail_media_asset_id)->where('app_id', $video->app_id)->exists()) {
                throw new \DomainException('Video thumbnail must belong to the same app.');
            }
        });
    }

    public function app(): BelongsTo { return $this->belongsTo(App::class); }
    public function channel(): BelongsTo { return $this->belongsTo(VideoChannel::class, 'video_channel_id'); }
    public function mediaAsset(): BelongsTo { return $this->belongsTo(MediaAsset::class, 'media_asset_id'); }
    public function thumbnailMediaAsset(): BelongsTo { return $this->belongsTo(MediaAsset::class, 'thumbnail_media_asset_id'); }
    public function playlistItems(): HasMany { return $this->hasMany(VideoPlaylistItem::class); }

    public function scopePubliclyVisible(Builder $query): Builder
    {
        return PublicationVisibility::apply($query)->where('visibility', 'public');
    }

    public function hasValidSource(): bool
    {
        return VideoSourceContract::isValid($this->getAttributes());
    }

    public function acceptsChannel(VideoChannel $channel): bool
    {
        return VideoDomainGuard::sameApp($this->app_id, $channel->app_id);
    }

    public function acceptsMediaAsset(MediaAsset $asset): bool
    {
        return $this->source_type === VideoSourceContract::UPLOADED_VIDEO
            && (string) $asset->type === 'video'
            && VideoDomainGuard::sameApp($this->app_id, $asset->app_id);
    }

    public function acceptsThumbnail(MediaAsset $asset): bool
    {
        return VideoDomainGuard::sameApp($this->app_id, $asset->app_id);
    }
}
