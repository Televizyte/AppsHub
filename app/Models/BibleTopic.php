<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class BibleTopic extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
        'meta_json',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta_json' => 'array',
    ];

    public function verses(): BelongsToMany
    {
        return $this->belongsToMany(BibleVerse::class, 'bible_topic_verse', 'topic_id', 'verse_id')
            ->withPivot(['weight', 'note'])
            ->withTimestamps();
    }
}
