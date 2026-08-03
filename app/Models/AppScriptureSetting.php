<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppScriptureSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_id',
        'default_translation_id',
        'daily_scripture_mode',
        'enabled_topics_json',
        'enabled_collections_json',
        'show_reference',
        'show_translation',
        'meta_json',
    ];

    protected $casts = [
        'enabled_topics_json' => 'array',
        'enabled_collections_json' => 'array',
        'show_reference' => 'boolean',
        'show_translation' => 'boolean',
        'meta_json' => 'array',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    public function defaultTranslation(): BelongsTo
    {
        return $this->belongsTo(BibleTranslation::class, 'default_translation_id');
    }
}
