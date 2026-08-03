<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizAttempt extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_id',
        'user_id',
        'device_id',
        'guest_session_id',
        'quiz_pack_id',
        'quiz_level_id',
        'score',
        'total_questions',
        'correct_count',
        'wrong_count',
        'percentage',
        'passed',
        'answers_json',
        'started_at',
        'completed_at',
        'meta_json',
    ];

    protected $casts = [
        'app_id' => 'integer',
        'user_id' => 'integer',
        'quiz_pack_id' => 'integer',
        'quiz_level_id' => 'integer',
        'score' => 'integer',
        'total_questions' => 'integer',
        'correct_count' => 'integer',
        'wrong_count' => 'integer',
        'percentage' => 'integer',
        'passed' => 'boolean',
        'answers_json' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'meta_json' => 'array',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    public function pack(): BelongsTo
    {
        return $this->belongsTo(QuizPack::class, 'quiz_pack_id');
    }

    public function level(): BelongsTo
    {
        return $this->belongsTo(QuizLevel::class, 'quiz_level_id');
    }
}
