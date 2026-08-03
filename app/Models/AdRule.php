<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdRule extends Model
{
    protected $table = 'ad_rules';

    protected $fillable = [
        'app_id',
        'scope_type',
        'scope_key',
        'is_enabled',
        'banner_enabled',
        'native_enabled',
        'interstitial_enabled',
        'interstitial_cooldown_seconds',
        'settings_json',
    ];

    protected $casts = [
        'is_enabled' => 'boolean',
        'banner_enabled' => 'boolean',
        'native_enabled' => 'boolean',
        'interstitial_enabled' => 'boolean',
        'settings_json' => 'array',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }
}
