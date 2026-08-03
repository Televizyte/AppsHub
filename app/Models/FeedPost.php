<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class FeedPost extends Model
{
    protected $table = 'feed_posts';

    protected $fillable = [
        'app_id',
        'bucket',
        'post_type',
        'source_engine',
        'source_id',
        'title',
        'body',
        'excerpt',
        'thumbnail_url',
        'media_url',
        'deep_link',
        'cta_label',
        'status',
        'approval_status',
        'visibility',
        'is_pinned',
        'is_featured',
        'sort_order',
        'published_at',
        'created_by_type',
        'created_by_id',
        'meta_json',
    ];

    protected $casts = [
        'app_id' => 'integer',
        'is_pinned' => 'boolean',
        'is_featured' => 'boolean',
        'sort_order' => 'integer',
        'published_at' => 'datetime',
        'created_by_id' => 'integer',
        'meta_json' => 'array',
    ];

    public const STATUS_DRAFT = 'draft';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_PENDING = 'pending';
    public const STATUS_HIDDEN = 'hidden';
    public const STATUS_ARCHIVED = 'archived';

    public const APPROVAL_APPROVED = 'approved';
    public const APPROVAL_PENDING = 'pending';
    public const APPROVAL_REJECTED = 'rejected';

    public const VISIBILITY_PUBLIC = 'public';
    public const VISIBILITY_APP_USERS = 'app_users';
    public const VISIBILITY_ADMIN_ONLY = 'admin_only';

    protected static function booted(): void
    {
        static::saving(function (self $post): void {
            foreach (['bucket', 'post_type', 'source_engine', 'status', 'approval_status', 'visibility'] as $field) {
                if (is_string($post->{$field} ?? null)) {
                    $post->{$field} = Str::of($post->{$field})->trim()->lower()->replace(' ', '_')->toString();
                }
            }

            if (blank($post->status)) {
                $post->status = self::STATUS_DRAFT;
            }

            if (blank($post->approval_status)) {
                $post->approval_status = $post->status === self::STATUS_PUBLISHED
                    ? self::APPROVAL_APPROVED
                    : self::APPROVAL_PENDING;
            }

            if (blank($post->visibility)) {
                $post->visibility = self::VISIBILITY_PUBLIC;
            }
        });
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(FeedComment::class, 'feed_post_id');
    }

    public function reactions(): HasMany
    {
        return $this->hasMany(FeedReaction::class, 'feed_post_id');
    }

    public function saves(): HasMany
    {
        return $this->hasMany(FeedSave::class, 'feed_post_id');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(FeedShare::class, 'feed_post_id');
    }

    public function reports(): HasMany
    {
        return $this->hasMany(FeedReport::class, 'feed_post_id');
    }
}
