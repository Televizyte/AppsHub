<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class Book extends Model
{
    protected $table = 'books';

    protected $fillable = [
        'app_id',
        'book_category_id',
        'title',
        'subtitle',
        'slug',
        'author_name',
        'description',
        'cover_image_url',
        'final_cover_image_url',
        'final_cover_image_path',
        'final_cover_mime',
        'book_type',
        'status',
        'access_type',
        'file_url',
        'file_path',
        'external_url',
        'is_featured',
        'is_downloadable',
        'sort_order',
        'published_at',
        'meta_json',
    ];

    protected $casts = [
        'app_id' => 'integer',
        'book_category_id' => 'integer',
        'is_featured' => 'boolean',
        'is_downloadable' => 'boolean',
        'sort_order' => 'integer',
        'published_at' => 'datetime',
        'meta_json' => 'array',
    ];

    protected $appends = [
        'cover_image_src',
        'final_cover_image_src',
        'file_src',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $book): void {
            if (blank($book->slug) && filled($book->title)) {
                $book->slug = Str::slug((string) $book->title) . '-' . Str::lower(Str::random(6));
            }

            if ((string) $book->status === 'published' && blank($book->published_at)) {
                $book->published_at = now();
            }
        });
    }

    public function getCoverImageSrcAttribute(): ?string
    {
        return $this->resolvePublicUrl($this->cover_image_url);
    }

    public function getFinalCoverImageSrcAttribute(): ?string
    {
        return $this->resolvePublicUrl($this->final_cover_image_url ?: $this->final_cover_image_path);
    }

    public function getFileSrcAttribute(): ?string
    {
        return $this->resolvePublicUrl($this->file_url ?: $this->file_path);
    }

    public function app(): BelongsTo
    {
        return $this->belongsTo(App::class, 'app_id');
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(BookCategory::class, 'book_category_id');
    }

    public function chapters(): HasMany
    {
        return $this->hasMany(BookChapter::class, 'book_id')->orderBy('sort_order')->orderBy('id');
    }

    private function resolvePublicUrl(?string $value): ?string
    {
        $raw = trim((string) $value);

        if ($raw === '') {
            return null;
        }

        if (Str::startsWith($raw, ['http://', 'https://', '/storage/'])) {
            return $raw;
        }

        if (Str::startsWith($raw, 'storage/')) {
            return url('/' . $raw);
        }

        if (Str::startsWith($raw, 'public/')) {
            $raw = Str::after($raw, 'public/');
        }

        return Storage::disk('public')->url($raw);
    }
}
