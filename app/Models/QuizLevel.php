<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizLevel extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_set_id',
        'quiz_study_group_id',
        'quiz_pack_id',
        'level_number',
        'title',
        'difficulty',
        'description',
        'question_target',
        'sort_order',
        'is_enabled',
        'settings_json',
    ];

    protected $casts = [
        'quiz_set_id' => 'integer',
        'quiz_study_group_id' => 'integer',
        'quiz_pack_id' => 'integer',
        'level_number' => 'integer',
        'question_target' => 'integer',
        'sort_order' => 'integer',
        'is_enabled' => 'boolean',
        'settings_json' => 'array',
    ];

    public function quizSet(): BelongsTo
    {
        return $this->belongsTo(QuizSet::class);
    }

    public function pack(): BelongsTo
    {
        return $this->belongsTo(QuizPack::class, 'quiz_pack_id');
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(QuizStudyGroup::class, 'quiz_study_group_id');
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('sort_order')->orderBy('id');
    }

    public function enabledQuestions(): HasMany
    {
        return $this->questions()->where('is_enabled', true);
    }
}
