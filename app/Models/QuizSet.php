<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class QuizSet extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_id','key','title','subtitle','type','source_bucket','source_key',
        'image_url','difficulty','status','is_enabled','sort_order','settings_json',
    ];

    protected $casts = [
        'app_id' => 'integer',
        'is_enabled' => 'boolean',
        'sort_order' => 'integer',
        'settings_json' => 'array',
    ];

    public function studyGroups(): HasMany
    {
        return $this->hasMany(QuizStudyGroup::class)->orderBy('sort_order')->orderBy('id');
    }

    public function levels(): HasMany
    {
        return $this->hasMany(QuizLevel::class)->orderBy('sort_order')->orderBy('level_number')->orderBy('id');
    }

    public function enabledLevels(): HasMany
    {
        return $this->levels()->where('is_enabled', true);
    }

    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('level')->orderBy('sort_order')->orderBy('id');
    }

    public function enabledQuestions(): HasMany
    {
        return $this->questions()->where('is_enabled', true);
    }

    public function ensureDefaultLevels(): void
    {
        $defaults = [
            ['level_number' => 1, 'title' => 'Level 1', 'difficulty' => 'easy', 'description' => 'Simple recall questions.', 'question_target' => 5, 'sort_order' => 1],
            ['level_number' => 2, 'title' => 'Level 2', 'difficulty' => 'medium', 'description' => 'Understanding and application questions.', 'question_target' => 5, 'sort_order' => 2],
            ['level_number' => 3, 'title' => 'Level 3', 'difficulty' => 'hard', 'description' => 'Deeper thinking and mastery questions.', 'question_target' => 5, 'sort_order' => 3],
        ];

        foreach ($defaults as $level) {
            $this->levels()->firstOrCreate(
                ['level_number' => $level['level_number']],
                array_merge($level, [
                    'is_enabled' => true,
                    'settings_json' => ['mode' => 'manual'],
                ])
            );
        }
    }
}
