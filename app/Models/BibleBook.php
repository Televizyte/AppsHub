<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BibleBook extends Model
{
    use HasFactory;

    protected $fillable = [
        'translation_id',
        'book_number',
        'testament',
        'name',
        'short_name',
        'slug',
        'chapter_count',
        'meta_json',
    ];

    protected $casts = [
        'meta_json' => 'array',
    ];

    public function translation(): BelongsTo
    {
        return $this->belongsTo(BibleTranslation::class, 'translation_id');
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(BibleChapter::class, 'book_id');
    }

    public function verses(): HasMany
    {
        return $this->hasMany(BibleVerse::class, 'book_id');
    }
}
