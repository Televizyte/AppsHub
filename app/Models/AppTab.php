<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppTab extends Model
{
    protected $table = 'app_tabs';

    protected $fillable = [
        'app_id',
        'key',
        'title',
        'icon',
        'sort_order',
        'is_enabled',
        'meta_json',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'meta_json' => 'array',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }
}
