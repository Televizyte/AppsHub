<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class DesignProject extends Model
{
    protected $table = 'design_projects';

    protected $fillable = [
        'app_id',
        'scope',
        'design_type',
        'template_category',
        'title',
        'subtitle',
        'slug',
        'status',
        'is_template',
        'is_active',
        'canvas_json',
        'layers_json',
        'settings_json',
        'preview_json',
        'created_by',
        'updated_by',
        'sort_order',
    ];

    protected $casts = [
        'canvas_json' => 'array',
        'layers_json' => 'array',
        'settings_json' => 'array',
        'preview_json' => 'array',
        'is_template' => 'boolean',
        'is_active' => 'boolean',
        'created_by' => 'integer',
        'updated_by' => 'integer',
        'sort_order' => 'integer',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $project): void {
            if (blank($project->slug) && filled($project->title)) {
                $project->slug = Str::slug((string) $project->title) . '-' . Str::lower(Str::random(6));
            }
        });
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function scopeForApp($query, ?int $appId)
    {
        return $query->where(function ($inner) use ($appId) {
            $inner->where('scope', 'global');

            if ($appId) {
                $inner->orWhere('app_id', $appId);
            }
        });
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeTemplates($query)
    {
        return $query->where('is_template', true);
    }
}
