<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FeedSetting extends Model
{
    protected $table = 'feed_settings';

    protected $fillable = [
        'app_id',
        'is_enabled',
        'auto_approve_system_posts',
        'auto_approve_tool_posts',
        'auto_approve_text_posts',
        'comments_enabled',
        'comments_require_approval',
        'user_text_posts_enabled',
        'external_media_uploads_enabled',
        'settings_json',
    ];

    protected $casts = [
        'app_id' => 'integer',
        'is_enabled' => 'boolean',
        'auto_approve_system_posts' => 'boolean',
        'auto_approve_tool_posts' => 'boolean',
        'auto_approve_text_posts' => 'boolean',
        'comments_enabled' => 'boolean',
        'comments_require_approval' => 'boolean',
        'user_text_posts_enabled' => 'boolean',
        'external_media_uploads_enabled' => 'boolean',
        'settings_json' => 'array',
    ];
}
