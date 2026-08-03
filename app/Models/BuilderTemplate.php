<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BuilderTemplate extends Model
{
    protected $table = 'builder_templates';

    protected $fillable = [
        'category',
        'key',
        'title',
        'subtitle',
        'description',
        'badge',
        'glyph',
        'tone',
        'status',
        'apply_mode',
        'payload_json',
        'preview_json',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'preview_json' => 'array',
        'is_active' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
