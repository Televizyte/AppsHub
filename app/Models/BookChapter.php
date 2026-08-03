<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class BookChapter extends Model
{
    protected $table = 'book_chapters';

    protected $fillable = [
        'book_id',
        'title',
        'slug',
        'subtitle',
        'body_html',
        'summary',
        'key_thought',
        'reflection_questions',
        'prayer_points',
        'action_steps',
        'memory_verse',
        'status',
        'sort_order',
        'meta_json',
    ];

    protected $casts = [
        'book_id' => 'integer',
        'sort_order' => 'integer',
        'meta_json' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $chapter): void {
            if (blank($chapter->slug) && filled($chapter->title)) {
                $chapter->slug = Str::slug((string) $chapter->title) . '-' . Str::lower(Str::random(5));
            }
        });
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class, 'book_id');
    }
}
