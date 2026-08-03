<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserFollow extends Model
{
    protected $table = 'user_follows';

    protected $fillable = [
        'app_id',
        'follower_user_id',
        'following_user_id',
    ];

    protected $casts = [
        'app_id' => 'integer',
        'follower_user_id' => 'integer',
        'following_user_id' => 'integer',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    public function follower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'follower_user_id');
    }

    public function following(): BelongsTo
    {
        return $this->belongsTo(User::class, 'following_user_id');
    }
}
