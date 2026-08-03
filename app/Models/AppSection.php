<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AppSection extends Model
{
    protected $table = 'app_sections';

    protected $fillable = [
        'app_id',
        'tab_key',
        'route_key',
        'key',
        'title',
        'subtitle',
        'template',
        'sort_order',
        'is_enabled',
        'visibility_json',
        'empty_state_json',
        'meta_json',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'visibility_json' => 'array',
        'empty_state_json' => 'array',
        'meta_json' => 'array',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AppItem::class, 'section_id');
    }
}
