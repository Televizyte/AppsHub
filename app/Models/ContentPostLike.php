<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentPostLike extends Model
{
    protected $table = 'content_post_likes';

    protected $fillable = [
        'app_id',
        'content_post_id',
        'user_id',
    ];

    protected $casts = [
        'app_id' => 'integer',
        'content_post_id' => 'integer',
        'user_id' => 'integer',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    public function post(): BelongsTo
    {
        return $this->belongsTo(ContentPost::class, 'content_post_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
