<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_id',
        'quiz_collection_id',
        'title',
        'slug',
        'description',
        'icon',
        'image_url',
        'type',
        'status',
        'sort_order',
        'is_enabled',
        'settings_json',
    ];

    protected $casts = [
        'app_id' => 'integer',
        'quiz_collection_id' => 'integer',
        'sort_order' => 'integer',
        'is_enabled' => 'boolean',
        'settings_json' => 'array',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    public function collection(): BelongsTo
    {
        return $this->belongsTo(QuizCollection::class, 'quiz_collection_id');
    }

    public function packs(): HasMany
    {
        return $this->hasMany(QuizPack::class, 'quiz_category_id')
            ->orderByDesc('is_today')
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('date')
            ->orderBy('id');
    }

    public function enabledPacks(): HasMany
    {
        return $this->packs()->where('is_enabled', true)->where('status', 'published');
    }
}
