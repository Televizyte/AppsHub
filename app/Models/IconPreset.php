<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IconPreset extends Model
{
    protected $table = 'icon_presets';

    protected $fillable = [
        'key',
        'label',
        'group',
        'svg',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];
}
