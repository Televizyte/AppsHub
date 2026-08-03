<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizStudyGroup extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_set_id',
        'key',
        'title',
        'subtitle',
        'type',
        'bible_book',
        'testament',
        'description',
        'image_url',
        'sort_order',
        'is_enabled',
        'settings_json',
    ];

    protected $casts = [
        'quiz_set_id' => 'integer',
        'sort_order' => 'integer',
        'is_enabled' => 'boolean',
        'settings_json' => 'array',
    ];

    public function quizSet(): BelongsTo
    {
        return $this->belongsTo(QuizSet::class);
    }

    public function levels(): HasMany
    {
        return $this->hasMany(QuizLevel::class, 'quiz_study_group_id')
            ->orderBy('sort_order')
            ->orderBy('level_number')
            ->orderBy('id');
    }
}
