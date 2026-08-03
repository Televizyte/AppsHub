<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedComment extends Model
{
    protected $table = 'feed_comments';

    protected $fillable = [
        'app_id',
        'feed_post_id',
        'user_id',
        'parent_id',
        'body',
        'status',
        'moderation_note',
        'meta_json',
    ];

    protected $casts = [
        'app_id' => 'integer',
        'feed_post_id' => 'integer',
        'user_id' => 'integer',
        'parent_id' => 'integer',
        'meta_json' => 'array',
    ];

    public const STATUS_PENDING = 'pending';
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_HIDDEN = 'hidden';
    public const STATUS_REJECTED = 'rejected';

    public function post(): BelongsTo
    {
        return $this->belongsTo(FeedPost::class, 'feed_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
