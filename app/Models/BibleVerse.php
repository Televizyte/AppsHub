<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BibleVerse extends Model
{
    use HasFactory;

    protected $fillable = [
        'translation_id',
        'book_id',
        'chapter_id',
        'book_name',
        'chapter_number',
        'verse_number',
        'reference',
        'text',
        'search_text',
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

    public function chapter(): BelongsTo
    {
        return $this->belongsTo(BibleChapter::class, 'chapter_id');
    }

    public function topics(): BelongsToMany
    {
        return $this->belongsToMany(BibleTopic::class, 'bible_topic_verse', 'verse_id', 'topic_id')
            ->withPivot(['weight', 'note'])
            ->withTimestamps();
    }
}
