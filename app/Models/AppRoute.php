<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppRoute extends Model
{
    protected $table = 'app_routes';

    protected $fillable = [
        'app_id',
        'key',
        'title',
        'route',
        'tab_key',
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
