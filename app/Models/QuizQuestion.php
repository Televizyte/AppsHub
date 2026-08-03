<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizQuestion extends Model
{
    use HasFactory;

    protected $fillable = [
        'quiz_set_id',
        'quiz_level_id',
        'quiz_pack_id',
        'level',
        'question_text',
        'option_a',
        'option_b',
        'option_c',
        'option_d',
        'correct_option',
        'explanation',
        'answer_note',
        'bible_reference',
        'bible_book',
        'chapter_start',
        'verse_start',
        'chapter_end',
        'verse_end',
        'study_focus',
        'question_kind',
        'points',
        'sort_order',
        'is_enabled',
        'meta_json',
    ];

    protected $casts = [
        'quiz_set_id' => 'integer',
        'quiz_level_id' => 'integer',
        'quiz_pack_id' => 'integer',
        'level' => 'integer',
        'chapter_start' => 'integer',
        'verse_start' => 'integer',
        'chapter_end' => 'integer',
        'verse_end' => 'integer',
        'points' => 'integer',
        'sort_order' => 'integer',
        'is_enabled' => 'boolean',
        'meta_json' => 'array',
    ];

    public function quizSet(): BelongsTo
    {
        return $this->belongsTo(QuizSet::class);
    }

    public function pack(): BelongsTo
    {
        return $this->belongsTo(QuizPack::class, 'quiz_pack_id');
    }

    public function quizLevel(): BelongsTo
    {
        return $this->belongsTo(QuizLevel::class);
    }

    public function options(): array
    {
        return collect([
            'A' => $this->option_a,
            'B' => $this->option_b,
            'C' => $this->option_c,
            'D' => $this->option_d,
        ])
            ->filter(fn ($value) => filled($value))
            ->map(fn ($value, $key) => [
                'key' => $key,
                'text' => $value,
            ])
            ->values()
            ->all();
    }

    public function studyReferencePayload(): array
    {
        return [
            'bible_reference' => $this->bible_reference,
            'bible_book' => $this->bible_book,
            'chapter_start' => $this->chapter_start,
            'verse_start' => $this->verse_start,
            'chapter_end' => $this->chapter_end,
            'verse_end' => $this->verse_end,
            'study_focus' => $this->study_focus,
            'question_kind' => $this->question_kind,
            'answer_note' => $this->answer_note,
        ];
    }
}
