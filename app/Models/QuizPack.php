<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizPack extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_id',
        'quiz_collection_id',
        'quiz_category_id',
        'quiz_set_id',
        'quiz_study_group_id',
        'title',
        'slug',
        'subtitle',
        'description',
        'date',
        'source_type',
        'source_id',
        'bible_book',
        'cover_image_url',
        'difficulty',
        'question_target',
        'total_questions',
        'is_today',
        'is_featured',
        'status',
        'sort_order',
        'is_enabled',
        'settings_json',
    ];

    protected $casts = [
        'app_id' => 'integer',
        'quiz_collection_id' => 'integer',
        'quiz_category_id' => 'integer',
        'quiz_set_id' => 'integer',
        'quiz_study_group_id' => 'integer',
        'date' => 'date',
        'question_target' => 'integer',
        'total_questions' => 'integer',
        'sort_order' => 'integer',
        'is_today' => 'boolean',
        'is_featured' => 'boolean',
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

    public function category(): BelongsTo
    {
        return $this->belongsTo(QuizCategory::class, 'quiz_category_id');
    }

    public function quizSet(): BelongsTo
    {
        return $this->belongsTo(QuizSet::class);
    }

    public function studyGroup(): BelongsTo
    {
        return $this->belongsTo(QuizStudyGroup::class, 'quiz_study_group_id');
    }

    public function levels(): HasMany
    {
        return $this->hasMany(QuizLevel::class)
            ->orderBy('sort_order')
            ->orderBy('level_number')
            ->orderBy('id');
    }

    public function enabledLevels(): HasMany
    {
        return $this->levels()->where('is_enabled', true);
    }
}
