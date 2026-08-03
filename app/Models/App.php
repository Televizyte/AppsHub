<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use App\Support\AppBranding;

class App extends Model
{
    protected $table = 'apps';

    protected $fillable = [
        'name',
        'slug',
        'api_token',
        'branding_json',
        'is_active',
    ];

    protected $casts = [
        'branding_json' => 'array',
        'is_active' => 'boolean',
    ];

    protected $appends = [
        'logo_url',
    ];

    /**
     * Returns a public URL for the selected app logo.
     * Supports branding_json logo_url, logo_path, and logo_asset_id.
     */
    public function getLogoUrlAttribute(): ?string
    {
        return AppBranding::logoUrl($this);
    }

    public function tabs(): HasMany
    {
        return $this->hasMany(AppTab::class, 'app_id');
    }

    public function routes(): HasMany
    {
        return $this->hasMany(AppRoute::class, 'app_id');
    }

    public function sections(): HasMany
    {
        return $this->hasMany(AppSection::class, 'app_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(AppItem::class, 'app_id');
    }

    public function adProfile(): HasOne
    {
        return $this->hasOne(AdProfile::class, 'app_id');
    }

    public function adRules(): HasMany
    {
        return $this->hasMany(AdRule::class, 'app_id');
    }

    public function contentPosts(): HasMany
    {
        return $this->hasMany(ContentPost::class, 'app_id');
    }

    public function watchLinks(): HasMany
    {
        return $this->hasMany(WatchLink::class, 'app_id');
    }

    public function pushNotifications(): HasMany
    {
        return $this->hasMany(PushNotification::class, 'app_id');
    }

    public function bookCategories(): HasMany
    {
        return $this->hasMany(BookCategory::class, 'app_id');
    }

    public function books(): HasMany
    {
        return $this->hasMany(Book::class, 'app_id');
    }
}
