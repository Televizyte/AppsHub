<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeedShare extends Model
{
    protected $table = 'feed_shares';

    protected $fillable = [
        'app_id',
        'feed_post_id',
        'user_id',
        'channel',
        'meta_json',
    ];

    protected $casts = [
        'app_id' => 'integer',
        'feed_post_id' => 'integer',
        'user_id' => 'integer',
        'meta_json' => 'array',
    ];

    public function post(): BelongsTo
    {
        return $this->belongsTo(FeedPost::class, 'feed_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
