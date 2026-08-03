<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppItem extends Model
{
    protected $table = 'app_items';

    protected $fillable = [
        'section_id',
        'type',
        'title',
        'subtitle',
        'icon',
        'image_url',
        'route',
        'url',
        'payload_json',
        'sort_order',
        'is_enabled',
    ];

    protected $casts = [
        'is_enabled'   => 'boolean',
        'payload_json' => 'array',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $item) {
            // ✅ Prevent MySQL error: type has no default in DB
            if (!is_string($item->type) || trim($item->type) === '') {
                $item->type = 'link';
            }
        });
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(AppSection::class, 'section_id');
    }
}
