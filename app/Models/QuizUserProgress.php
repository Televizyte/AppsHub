<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizUserProgress extends Model
{
    use HasFactory;

    protected $table = 'quiz_user_progress';

    protected $fillable = [
        'app_id',
        'user_id',
        'device_id',
        'guest_session_id',
        'quiz_pack_id',
        'quiz_level_id',
        'current_question_index',
        'answered_questions_json',
        'score',
        'status',
        'started_at',
        'completed_at',
        'meta_json',
    ];

    protected $casts = [
        'app_id' => 'integer',
        'user_id' => 'integer',
        'quiz_pack_id' => 'integer',
        'quiz_level_id' => 'integer',
        'current_question_index' => 'integer',
        'answered_questions_json' => 'array',
        'score' => 'integer',
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
