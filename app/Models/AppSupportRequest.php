<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppSupportRequest extends Model
{
    protected $guarded = [];

    protected $casts = [
        'due_at' => 'datetime',
        'verified_at' => 'datetime',
        'completed_at' => 'datetime',
        'meta_json' => 'array',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
