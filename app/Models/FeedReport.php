<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedReport extends Model
{
    protected $table = 'feed_reports';

    protected $fillable = [
        'app_id',
        'feed_post_id',
        'feed_comment_id',
        'user_id',
        'reason',
        'details',
        'status',
        'meta_json',
    ];

    protected $casts = [
        'app_id' => 'integer',
        'feed_post_id' => 'integer',
        'feed_comment_id' => 'integer',
        'user_id' => 'integer',
        'meta_json' => 'array',
    ];

    public const STATUS_OPEN = 'open';
    public const STATUS_REVIEWED = 'reviewed';
    public const STATUS_DISMISSED = 'dismissed';
    public const STATUS_ACTIONED = 'actioned';

    public function post(): BelongsTo
    {
        return $this->belongsTo(FeedPost::class, 'feed_post_id');
    }

    public function comment(): BelongsTo
    {
        return $this->belongsTo(FeedComment::class, 'feed_comment_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
