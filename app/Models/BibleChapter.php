<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BibleChapter extends Model
{
    use HasFactory;

    protected $fillable = [
        'translation_id',
        'book_id',
        'chapter_number',
        'verse_count',
        'meta_json',
    ];

    protected $casts = [
        'meta_json' => 'array',
    ];

    public function translation(): BelongsTo
    {
        return $this->belongsTo(BibleTranslation::class, 'translation_id');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(BibleBook::class, 'book_id');
    }

    public function verses(): HasMany
    {
        return $this->hasMany(BibleVerse::class, 'chapter_id');
    }
}
