<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContentPostComment extends Model
{
    public const STATUS_PUBLISHED = 'published';
    public const STATUS_HIDDEN = 'hidden';
    public const STATUS_PENDING = 'pending';

    protected $table = 'content_post_comments';

    protected $fillable = [
        'app_id',
        'content_post_id',
        'user_id',
        'body',
        'status',
    ];

    protected $casts = [
        'app_id' => 'integer',
        'content_post_id' => 'integer',
        'user_id' => 'integer',
    ];

    public static function allowedStatuses(): array
    {
        return [
            self::STATUS_PUBLISHED,
            self::STATUS_HIDDEN,
            self::STATUS_PENDING,
        ];
    }

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
