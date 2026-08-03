<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContentPostSave extends Model
{
    protected $fillable = [
        'app_id',
        'content_post_id',
        'user_id',
    ];
}
