<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class BookCategory extends Model
{
    protected $table = 'book_categories';

    protected $fillable = [
        'app_id',
        'name',
        'slug',
        'description',
        'is_active',
        'sort_order',
        'meta_json',
    ];

    protected $casts = [
        'app_id' => 'integer',
        'is_active' => 'boolean',
        'sort_order' => 'integer',
        'meta_json' => 'array',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $category): void {
            if (blank($category->slug) && filled($category->name)) {
                $category->slug = Str::slug((string) $category->name);
            }
        });
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class, 'book_category_id');
    }
}
