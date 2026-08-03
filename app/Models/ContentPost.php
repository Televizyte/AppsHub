<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ContentPost extends Model
{
    protected $table = 'content_posts';

    protected $fillable = [
        'app_id',
        'bucket',
        'status',
        'title',
        'subtitle',
        'slug',
        'cover_image_url',
        'body_html',
        'blocks_json',
        'tags_json',
        'meta_json',
        'publish_at',
        'published_at',
        'author_name',
        'author_user_id',
        'is_featured',
        'sort_order',
    ];

    protected $casts = [
        'blocks_json' => 'array',
        'tags_json' => 'array',
        'meta_json' => 'array',
        'publish_at' => 'datetime',
        'published_at' => 'datetime',
        'is_featured' => 'boolean',
        'author_user_id' => 'integer',
    ];

    protected $appends = [
        'cover_image_src',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $post): void {
            if (! empty($post->author_user_id)) {
                $user = User::query()->find((int) $post->author_user_id);

                if ($user) {
                    $post->author_name = (string) $user->name;
                }
            }

            if (blank($post->slug) && filled($post->title)) {
                $post->slug = Str::slug((string) $post->title) . '-' . Str::lower(Str::random(6));
            }

            // Central publish-timing policy for every content_posts-backed engine
            // (quotes, articles, shorts, daily content, highlights, etc.).
            if ((string) ($post->status ?? '') === 'published') {
                if ($post->publish_at && $post->publish_at->isFuture()) {
                    // Approved now, publicly visible only when publish_at is reached.
                    $post->published_at = $post->publish_at;
                } elseif (blank($post->published_at)) {
                    // Immediate publication.
                    $post->published_at = now();
                }
            }
        });
    }

    public function getCoverImageSrcAttribute(): ?string
    {
        $val = trim((string) ($this->cover_image_url ?? ''));

        if ($val === '') {
            return null;
        }

        if (Str::startsWith($val, ['http://', 'https://'])) {
            return $val;
        }

        return Storage::disk('public')->url($val);
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    public function authorUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_user_id');
    }

    public function likes(): HasMany
    {
        return $this->hasMany(ContentPostLike::class, 'content_post_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(ContentPostComment::class, 'content_post_id');
    }
}
