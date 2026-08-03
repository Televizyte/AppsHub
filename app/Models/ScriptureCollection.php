<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ScriptureCollection extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_id',
        'title',
        'slug',
        'description',
        'visibility',
        'is_active',
        'meta_json',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta_json' => 'array',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    public function verses(): BelongsToMany
    {
        return $this->belongsToMany(BibleVerse::class, 'scripture_collection_items', 'collection_id', 'verse_id')
            ->withPivot(['sort_order', 'custom_note'])
            ->withTimestamps()
            ->orderBy('scripture_collection_items.sort_order');
    }
}
