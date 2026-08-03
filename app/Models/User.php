<?php

namespace App\Models;

use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable implements FilamentUser
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'admin_role',
        'admin_permissions',
        'assigned_app_ids',
        'admin_is_active',
        // NOTE: active_app_id exists in DB, but we won’t rely on mass assignment for it.
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'admin_permissions' => 'array',
            'assigned_app_ids' => 'array',
            'admin_is_active' => 'boolean',
            'last_login_at' => 'datetime',
        ];
    }

    /**
     * Filament access gate.
     *
     * Phase 1 remains intentionally safe so existing admin access is not broken.
     * The Roles & Permissions page now records roles/permissions; actual page-by-page
     * enforcement should be enabled after the current owner is confirmed as Super Admin.
     */
    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->isAdminActive()) {
            return false;
        }

        return $this->adminRoleKey() !== 'unassigned'
            || ! empty($this->admin_permissions ?? []);
    }

    public function adminRoleKey(): string
    {
        $role = (string) ($this->admin_role ?? '');

        return $role !== '' ? $role : 'unassigned';
    }

    public function isAdminActive(): bool
    {
        return (bool) ($this->admin_is_active ?? true);
    }

    public function isSuperAdmin(): bool
    {
        return $this->adminRoleKey() === 'super_admin' && $this->isAdminActive();
    }

    public function hasAdminRole(string|array $roles): bool
    {
        $roles = is_array($roles) ? $roles : [$roles];

        return in_array($this->adminRoleKey(), $roles, true) && $this->isAdminActive();
    }

    public function assignedAppIds(): array
    {
        $ids = $this->assigned_app_ids ?? [];

        if (! is_array($ids)) {
            return [];
        }

        return collect($ids)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    public function hasAdminPermission(string $permission): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        if (! $this->isAdminActive()) {
            return false;
        }

        $custom = $this->admin_permissions ?? [];

        if (is_array($custom) && (in_array('*', $custom, true) || in_array($permission, $custom, true))) {
            return true;
        }

        return in_array($permission, self::rolePermissions($this->adminRoleKey()), true);
    }

    public static function roleLabels(): array
    {
        return [
            'super_admin' => 'Super Admin',
            'app_admin' => 'App Admin',
            'content_manager' => 'Content Manager',
            'moderator' => 'Moderator',
            'analyst' => 'Analyst',
            'support_staff' => 'Support Staff',
            'unassigned' => 'Unassigned',
        ];
    }

    public static function rolePermissions(string $role): array
    {
        return match ($role) {
            'super_admin' => ['*'],
            'app_admin' => [
                'apps.view', 'app_settings.view', 'app_settings.update',
                'content.view', 'content.create', 'content.update', 'content.publish',
                'shorts.view', 'shorts.manage', 'shorts.channels', 'shorts.settings',
                'books.view', 'books.manage', 'quiz.view', 'quiz.manage',
                'media.view', 'media.upload', 'notifications.view', 'notifications.send',
                'users.view', 'comments.moderate', 'analytics.view',
            ],
            'content_manager' => [
                'content.view', 'content.create', 'content.update', 'content.publish',
                'shorts.view', 'shorts.manage', 'shorts.channels',
                'books.view', 'books.manage', 'quiz.view', 'quiz.manage',
                'media.view', 'media.upload',
            ],
            'moderator' => [
                'users.view', 'comments.moderate', 'devices.manage', 'analytics.view',
            ],
            'analyst' => [
                'apps.view', 'content.view', 'shorts.view', 'books.view', 'quiz.view', 'users.view', 'analytics.view',
            ],
            'support_staff' => [
                'users.view', 'devices.manage', 'notifications.view',
            ],
            default => [],
        };
    }
}
