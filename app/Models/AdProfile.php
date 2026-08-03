<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AdProfile extends Model
{
    protected $table = 'ad_profiles';

    protected $fillable = [
        'app_id',
        'ads_enabled',
        'banner_unit_id',
        'native_unit_id',
        'interstitial_unit_id',
        'meta_json',
    ];

    protected $casts = [
        'ads_enabled' => 'boolean',
        'meta_json' => 'array',
    ];

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }
}
