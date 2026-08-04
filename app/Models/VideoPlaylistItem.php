<?php

namespace App\Models;

use App\Support\Video\VideoDomainGuard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VideoPlaylistItem extends Model
{
    protected $fillable = [
        'app_id', 'video_playlist_id', 'video_id', 'sort_order', 'settings_json',
    ];

    protected $casts = [
        'app_id' => 'integer', 'video_playlist_id' => 'integer', 'video_id' => 'integer',
        'sort_order' => 'integer', 'settings_json' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $item): void {
            if ($item->exists && $item->isDirty('app_id')) {
                throw new \DomainException('Playlist item app ownership is immutable.');
            }

            $playlistOwned = VideoPlaylist::query()
                ->whereKey($item->video_playlist_id)
                ->where('app_id', $item->app_id)
                ->exists();
            $videoOwned = Video::query()
                ->whereKey($item->video_id)
                ->where('app_id', $item->app_id)
                ->exists();

            if (! $playlistOwned || ! $videoOwned) {
                throw new \DomainException('Playlist membership must remain within one app.');
            }
        });
    }

    public function app(): BelongsTo { return $this->belongsTo(App::class); }
    public function playlist(): BelongsTo { return $this->belongsTo(VideoPlaylist::class, 'video_playlist_id'); }
    public function video(): BelongsTo { return $this->belongsTo(Video::class); }

    public function acceptsMembership(VideoPlaylist $playlist, Video $video): bool
    {
        return VideoDomainGuard::sameApp($this->app_id, $playlist->app_id, $video->app_id);
    }
}
