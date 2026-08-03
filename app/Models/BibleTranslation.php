<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BibleTranslation extends Model
{
    use HasFactory;

    protected $fillable = [
        'key',
        'name',
        'language',
        'copyright_note',
        'license_type',
        'is_active',
        'meta_json',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'meta_json' => 'array',
    ];

    public function books(): HasMany
    {
        return $this->hasMany(BibleBook::class, 'translation_id');
    }

    public function verses(): HasMany
    {
        return $this->hasMany(BibleVerse::class, 'translation_id');
    }
}
