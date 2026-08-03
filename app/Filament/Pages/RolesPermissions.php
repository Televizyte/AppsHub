<?php

namespace App\Filament\Pages;

use App\Support\AdminAccess;

use App\Models\User;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class RolesPermissions extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Roles & Permissions';
    protected static ?string $navigationGroup = 'System';
    protected static ?int $navigationSort = 95;
    protected static string $view = 'filament.pages.roles-permissions';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::page('roles_permissions');
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('roles_permissions');
    }
    protected static ?string $slug = 'roles-permissions';

    public array $stats = [];
    public array $roles = [];
    public array $adminUsers = [];
    public array $apps = [];
    public array $permissionGroups = [];
    public array $rules = [];
    public array $accessPreview = [];

    public string $activeTab = 'overview';
    public string $workspaceMode = 'index';
    public string $editingRoleKey = '';
    public int $editingUserId = 0;
    public int $previewUserId = 0;

    public string $search = '';
    public string $roleFilter = 'all';
    public string $statusFilter = 'all';

    public array $roleForm = [
        'name' => '',
        'key' => '',
        'description' => '',
        'permissions' => [],
        'assigned_app_ids' => [],
        'is_active' => true,
    ];

    public array $userForm = [
        'name' => '',
        'email' => '',
        'password' => '',
        'admin_role' => 'unassigned',
        'assigned_app_ids' => [],
        'admin_is_active' => true,
    ];


    public function mount(): void
    {
        $this->activeTab = $this->safeTab((string) request('tab', 'overview'));
        $this->workspaceMode = $this->safeWorkspace((string) request('workspace', 'index'));
        $this->editingRoleKey = (string) request('role', '');
        $this->editingUserId = (int) request('user', 0);
        $this->previewUserId = (int) request('preview_user', 0);

        $this->loadCenter();
        $this->hydrateWorkspaceFromRequest();
        $this->buildAccessPreview();
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon('heroicon-o-arrow-path')
                ->color('gray')
                ->action(fn () => $this->refreshCenter()),
        ];
    }

    public function refreshCenter(): void
    {
        $this->loadCenter();
        $this->buildAccessPreview();
        Notification::make()->title('Roles & permissions refreshed')->success()->send();
    }

    public function updatedSearch(): void
    {
        $this->loadCenter();
    }

    public function updatedRoleFilter(): void
    {
        $this->loadCenter();
    }

    public function updatedStatusFilter(): void
    {
        $this->loadCenter();
    }

    public function updatedPreviewUserId(): void
    {
        $this->buildAccessPreview();
    }

    public function generateRoleKey(): void
    {
        $this->roleForm['key'] = Str::slug((string) ($this->roleForm['name'] ?? ''), '_');
    }

    public function startCreateRole(): mixed
    {
        return redirect('/admin/roles-permissions?tab=roles&workspace=role_editor&role=new');
    }

    public function startEditRole(string $roleKey): mixed
    {
        return redirect('/admin/roles-permissions?tab=roles&workspace=role_editor&role='.urlencode($roleKey));
    }

    public function closeRoleEditor(): mixed
    {
        return redirect('/admin/roles-permissions?tab=roles');
    }

    public function saveRole(): mixed
    {
        if (! $this->hasCustomRolesTable()) {
            Notification::make()->title('Run the Phase 2 roles migration first')->warning()->send();
            return null;
        }

        $name = trim((string) ($this->roleForm['name'] ?? ''));
        $key = Str::slug((string) ($this->roleForm['key'] ?: $name), '_');

        if ($name === '' || $key === '') {
            Notification::make()->title('Role name and key are required')->danger()->send();
            return null;
        }

        if (array_key_exists($key, $this->systemRoleDefinitions())) {
            Notification::make()->title('This is a protected system role key')->warning()->send();
            return null;
        }

        $permissions = $this->cleanPermissionList($this->roleForm['permissions'] ?? []);
        $assignedApps = $this->cleanAppIds($this->roleForm['assigned_app_ids'] ?? []);
        $description = trim((string) ($this->roleForm['description'] ?? ''));
        $isActive = (bool) ($this->roleForm['is_active'] ?? true);

        $existing = DB::table('admin_roles')->where('key', $key)->first();
        $editingExistingDifferent = $this->editingRoleKey !== '' && $this->editingRoleKey !== 'new' && $this->editingRoleKey !== $key;

        if ($existing && ($this->editingRoleKey === 'new' || $editingExistingDifferent)) {
            Notification::make()->title('A role with this key already exists')->danger()->send();
            return null;
        }

        $payload = [
            'name' => $name,
            'key' => $key,
            'description' => $description,
            'permissions' => json_encode($permissions),
            'assigned_app_ids' => json_encode($assignedApps),
            'is_system' => 0,
            'is_active' => $isActive ? 1 : 0,
            'updated_at' => now(),
        ];

        if ($this->editingRoleKey !== '' && $this->editingRoleKey !== 'new' && $this->editingRoleKey !== $key) {
            DB::table('users')->where('admin_role', $this->editingRoleKey)->update([
                'admin_role' => $key,
                'admin_permissions' => json_encode($permissions),
                'updated_at' => now(),
            ]);
            DB::table('admin_roles')->where('key', $this->editingRoleKey)->delete();
        }

        if ($existing || ($this->editingRoleKey !== '' && $this->editingRoleKey !== 'new')) {
            DB::table('admin_roles')->where('key', $key)->update($payload);
        } else {
            $payload['created_at'] = now();
            DB::table('admin_roles')->insert($payload);
        }

        DB::table('users')->where('admin_role', $key)->update([
            'admin_permissions' => json_encode($permissions),
            'updated_at' => now(),
        ]);

        $this->loadCenter();
        Notification::make()->title('Custom role saved')->success()->send();

        return redirect('/admin/roles-permissions?tab=roles');
    }

    public function deleteCustomRole(string $roleKey): void
    {
        if (! $this->hasCustomRolesTable()) {
            return;
        }

        if (array_key_exists($roleKey, $this->systemRoleDefinitions())) {
            Notification::make()->title('System roles cannot be removed')->warning()->send();
            return;
        }

        $assignedCount = Schema::hasTable('users') ? DB::table('users')->where('admin_role', $roleKey)->count() : 0;
        if ($assignedCount > 0) {
            Notification::make()->title('Role is assigned to admin users')->body('Unassign or move those users before deleting this role.')->warning()->send();
            return;
        }

        DB::table('admin_roles')->where('key', $roleKey)->where('is_system', 0)->delete();
        $this->loadCenter();
        Notification::make()->title('Custom role removed')->success()->send();
    }

    public function startCreateUser(): mixed
    {
        return redirect('/admin/roles-permissions?tab=admin_users&workspace=user_editor&user=new');
    }

    public function startEditUser(int $userId): mixed
    {
        return redirect('/admin/roles-permissions?tab=admin_users&workspace=user_editor&user='.$userId);
    }

    public function closeUserEditor(): mixed
    {
        return redirect('/admin/roles-permissions?tab=admin_users');
    }

    public function saveAdminUser(): mixed
    {
        if (! Schema::hasTable('users')) {
            Notification::make()->title('Users table not found')->danger()->send();
            return null;
        }

        if (! $this->hasRoleColumns()) {
            Notification::make()->title('Run the roles migration first')->warning()->send();
            return null;
        }

        $name = trim((string) ($this->userForm['name'] ?? ''));
        $email = strtolower(trim((string) ($this->userForm['email'] ?? '')));
        $password = (string) ($this->userForm['password'] ?? '');
        $role = (string) ($this->userForm['admin_role'] ?? 'unassigned');
        $active = (bool) ($this->userForm['admin_is_active'] ?? true);
        $assignedApps = $this->cleanAppIds($this->userForm['assigned_app_ids'] ?? []);
        $roleDef = $this->roleDefinitions()[$role] ?? $this->roleDefinitions()['unassigned'];
        $permissions = $roleDef['permissions'] ?? [];
        $isNew = $this->editingUserId <= 0;

        if ($name === '' || ! filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Notification::make()->title('Name and a valid email are required')->danger()->send();
            return null;
        }

        if ($isNew && $password === '') {
            Notification::make()->title('Password is required for a new admin account')->danger()->send();
            return null;
        }

        $duplicate = DB::table('users')
            ->where('email', $email)
            ->when(! $isNew, fn ($q) => $q->where('id', '!=', $this->editingUserId))
            ->exists();

        if ($duplicate) {
            Notification::make()->title('This email is already used by another user')->danger()->send();
            return null;
        }

        $payload = [
            'name' => $name,
            'email' => $email,
            'admin_role' => $role === 'unassigned' ? null : $role,
            'admin_permissions' => json_encode($this->cleanPermissionList($permissions)),
            'assigned_app_ids' => json_encode($assignedApps),
            'admin_is_active' => $active ? 1 : 0,
            'updated_at' => now(),
        ];

        if ($password !== '') {
            $payload['password'] = Hash::make($password);
        }

        if ($isNew) {
            $payload['created_at'] = now();
            DB::table('users')->insert($payload);
            Notification::make()->title('Admin account created')->success()->send();
        } else {
            if (Auth::id() === $this->editingUserId && ! $active) {
                Notification::make()->title('You cannot deactivate your own admin account here')->warning()->send();
                return null;
            }
            DB::table('users')->where('id', $this->editingUserId)->update($payload);
            Notification::make()->title('Admin account updated')->success()->send();
        }

        $this->loadCenter();
        return redirect('/admin/roles-permissions?tab=admin_users');
    }

    public function assignRole(int $userId, string $role): void
    {
        if (! $this->hasRoleColumns()) {
            Notification::make()->title('Run the roles migration first')->warning()->send();
            return;
        }

        if (! array_key_exists($role, $this->roleDefinitions())) {
            Notification::make()->title('Unknown role')->danger()->send();
            return;
        }

        $roleDef = $this->roleDefinitions()[$role];

        DB::table('users')->where('id', $userId)->update([
            'admin_role' => $role === 'unassigned' ? null : $role,
            'admin_permissions' => json_encode($this->cleanPermissionList($roleDef['permissions'] ?? [])),
            'admin_is_active' => $role === 'unassigned' ? 0 : 1,
            'updated_at' => now(),
        ]);

        $this->loadCenter();
        Notification::make()->title('Admin role updated')->success()->send();
    }

    public function grantCurrentUserSuperAdmin(): void
    {
        $user = Auth::user();

        if (! $user || ! $this->hasRoleColumns()) {
            Notification::make()->title('Run the roles migration first')->warning()->send();
            return;
        }

        DB::table('users')->where('id', $user->id)->update([
            'admin_role' => 'super_admin',
            'admin_permissions' => json_encode(['*']),
            'admin_is_active' => 1,
            'updated_at' => now(),
        ]);

        $this->loadCenter();
        Notification::make()->title('Current admin is now Super Admin')->success()->send();
    }

    public function deactivateAdmin(int $userId): void
    {
        if (! $this->hasRoleColumns()) {
            return;
        }

        if (Auth::id() === $userId) {
            Notification::make()->title('You cannot deactivate your own admin access here')->warning()->send();
            return;
        }

        DB::table('users')->where('id', $userId)->update([
            'admin_is_active' => 0,
            'updated_at' => now(),
        ]);

        $this->loadCenter();
        Notification::make()->title('Admin access deactivated')->success()->send();
    }

    public function activateAdmin(int $userId): void
    {
        if (! $this->hasRoleColumns()) {
            return;
        }

        DB::table('users')->where('id', $userId)->update([
            'admin_is_active' => 1,
            'updated_at' => now(),
        ]);

        $this->loadCenter();
        Notification::make()->title('Admin access activated')->success()->send();
    }

    public function formatDate(?string $value): string
    {
        if (! $value) {
            return '—';
        }

        try {
            return Carbon::parse($value)->timezone(config('app.timezone', 'Africa/Lagos'))->format('M j, Y · g:i A');
        } catch (\Throwable) {
            return $value;
        }
    }

    public function roleTone(string $role): string
    {
        return match ($role) {
            'super_admin' => 'danger',
            'app_admin' => 'good',
            'content_manager' => 'info',
            'moderator' => 'warn',
            'analyst' => 'purple',
            'support_staff' => 'cyan',
            default => 'custom',
        };
    }

    public function roleAssignedCount(string $roleKey): int
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'admin_role')) {
            return 0;
        }

        return (int) DB::table('users')->where('admin_role', $roleKey)->count();
    }

    public function appNameList(array $ids): string
    {
        $map = collect($this->apps)->pluck('name', 'id');
        $names = collect($ids)->map(fn ($id) => $map[(int) $id] ?? null)->filter()->values();

        return $names->isEmpty() ? 'No app assigned' : $names->implode(', ');
    }

    private function loadCenter(): void
    {
        $this->apps = $this->loadApps();
        $this->permissionGroups = $this->permissionGroups();
        $this->roles = array_values($this->roleDefinitions());
        $this->rules = $this->rules();
        $this->adminUsers = $this->loadAdminUsers();
        $this->stats = $this->loadStats();
    }

    private function hydrateWorkspaceFromRequest(): void
    {
        if ($this->workspaceMode === 'role_editor') {
            $this->loadRoleEditorForm($this->editingRoleKey ?: 'new');
        }

        if ($this->workspaceMode === 'user_editor') {
            $this->loadAdminUserEditorForm($this->editingUserId);
        }
    }

    private function loadRoleEditorForm(string $roleKey): void
    {
        $this->editingRoleKey = $roleKey;
        $this->roleForm = [
            'name' => '',
            'key' => '',
            'description' => '',
            'permissions' => [],
            'assigned_app_ids' => [],
            'is_active' => true,
        ];

        if ($roleKey === 'new' || $roleKey === '') {
            return;
        }

        $role = $this->roleDefinitions()[$roleKey] ?? null;
        if (! $role || ($role['system'] ?? false)) {
            return;
        }

        $this->roleForm = [
            'name' => (string) ($role['label'] ?? ''),
            'key' => (string) ($role['key'] ?? ''),
            'description' => (string) ($role['description'] ?? ''),
            'permissions' => $this->cleanPermissionList($role['permissions'] ?? []),
            'assigned_app_ids' => $this->cleanAppIds($role['assigned_app_ids'] ?? []),
            'is_active' => (bool) ($role['is_active'] ?? true),
        ];
    }

    private function loadAdminUserEditorForm(int $userId): void
    {
        $this->editingUserId = $userId;
        $this->userForm = [
            'name' => '',
            'email' => '',
            'password' => '',
            'admin_role' => 'unassigned',
            'assigned_app_ids' => [],
            'admin_is_active' => true,
        ];

        if ($userId <= 0 || ! Schema::hasTable('users')) {
            return;
        }

        $user = DB::table('users')->where('id', $userId)->first();
        if (! $user) {
            return;
        }

        $this->userForm = [
            'name' => (string) ($user->name ?? ''),
            'email' => (string) ($user->email ?? ''),
            'password' => '',
            'admin_role' => (string) (($user->admin_role ?? '') ?: 'unassigned'),
            'assigned_app_ids' => $this->decodeJsonIds($user->assigned_app_ids ?? '[]'),
            'admin_is_active' => (bool) ($user->admin_is_active ?? true),
        ];
    }

    private function loadStats(): array
    {
        $users = collect($this->adminUsers);
        $roles = collect($this->roles);

        return [
            'migration_ready' => $this->hasRoleColumns(),
            'custom_roles_ready' => $this->hasCustomRolesTable(),
            'total_users' => Schema::hasTable('users') ? (int) DB::table('users')->count() : 0,
            'shown_users' => $users->count(),
            'assigned_admins' => $users->where('role_key', '!=', 'unassigned')->count(),
            'super_admins' => $users->where('role_key', 'super_admin')->count(),
            'active_admins' => $users->where('admin_is_active', true)->where('role_key', '!=', 'unassigned')->count(),
            'inactive_admins' => $users->where('admin_is_active', false)->where('role_key', '!=', 'unassigned')->count(),
            'roles' => $roles->where('key', '!=', 'unassigned')->count(),
            'custom_roles' => $roles->where('system', false)->where('key', '!=', 'unassigned')->count(),
            'permission_groups' => count($this->permissionGroups),
            'has_super_admin' => $this->hasRoleColumns() && Schema::hasTable('users')
                ? DB::table('users')->where('admin_role', 'super_admin')->where('admin_is_active', 1)->exists()
                : false,
        ];
    }

    private function loadAdminUsers(): array
    {
        if (! Schema::hasTable('users')) {
            return [];
        }

        $hasRoleColumns = $this->hasRoleColumns();
        $query = DB::table('users');

        $select = ['id', 'name', 'email', 'active_app_id', 'created_at', 'updated_at'];
        foreach (['admin_role', 'admin_permissions', 'admin_is_active', 'assigned_app_ids', 'last_login_at'] as $column) {
            if (Schema::hasColumn('users', $column)) {
                $select[] = $column;
            }
        }

        $search = trim($this->search);
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($hasRoleColumns && $this->roleFilter !== 'all') {
            if ($this->roleFilter === 'unassigned') {
                $query->whereNull('admin_role');
            } else {
                $query->where('admin_role', $this->roleFilter);
            }
        }

        if ($hasRoleColumns && $this->statusFilter !== 'all') {
            if ($this->statusFilter === 'active') {
                $query->where('admin_is_active', 1);
            } elseif ($this->statusFilter === 'inactive') {
                $query->where('admin_is_active', 0);
            }
        }

        $appNames = collect($this->apps)->pluck('name', 'id');
        $roleDefs = $this->roleDefinitions();

        return $query->orderByRaw($hasRoleColumns ? "CASE WHEN admin_role = 'super_admin' THEN 0 WHEN admin_role IS NULL THEN 2 ELSE 1 END" : 'id asc')
            ->orderBy('name')
            ->limit(120)
            ->get($select)
            ->map(function ($user) use ($hasRoleColumns, $appNames, $roleDefs) {
                $role = $hasRoleColumns ? (string) (($user->admin_role ?? '') ?: 'unassigned') : 'unassigned';
                $active = $hasRoleColumns ? (bool) ($user->admin_is_active ?? true) : true;
                $activeAppId = is_numeric($user->active_app_id ?? null) ? (int) $user->active_app_id : null;
                $assignedIds = $this->decodeJsonIds($user->assigned_app_ids ?? '[]');

                return [
                    'id' => (int) $user->id,
                    'name' => (string) ($user->name ?: 'Unnamed User'),
                    'email' => (string) ($user->email ?: ''),
                    'role_key' => $role,
                    'role_label' => $roleDefs[$role]['label'] ?? Str::headline(str_replace('_', ' ', $role)),
                    'admin_is_active' => $active,
                    'active_app_id' => $activeAppId,
                    'active_app_name' => $activeAppId ? (string) ($appNames[$activeAppId] ?? 'App #'.$activeAppId) : '—',
                    'assigned_app_ids' => $assignedIds,
                    'assigned_app_names' => $this->appNameList($assignedIds),
                    'permission_count' => count($this->cleanPermissionList($user->admin_permissions ? json_decode((string) $user->admin_permissions, true) : ($roleDefs[$role]['permissions'] ?? []))),
                    'created_at' => (string) ($user->created_at ?? ''),
                    'updated_at' => (string) ($user->updated_at ?? ''),
                    'last_login_at' => (string) ($user->last_login_at ?? ''),
                ];
            })
            ->values()
            ->all();
    }

    private function loadApps(): array
    {
        if (! Schema::hasTable('apps')) {
            return [];
        }

        return DB::table('apps')
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->limit(100)
            ->get(['id', 'name', 'slug', 'is_active'])
            ->map(fn ($app) => [
                'id' => (int) $app->id,
                'name' => (string) $app->name,
                'slug' => (string) $app->slug,
                'is_active' => (bool) $app->is_active,
            ])
            ->all();
    }

    private function buildAccessPreview(): void
    {
        $user = null;
        if ($this->previewUserId > 0 && Schema::hasTable('users')) {
            $user = collect($this->adminUsers)->firstWhere('id', $this->previewUserId);
        }

        if (! $user) {
            $user = collect($this->adminUsers)->firstWhere('role_key', '!=', 'unassigned') ?: collect($this->adminUsers)->first();
            $this->previewUserId = (int) ($user['id'] ?? 0);
        }

        if (! $user) {
            $this->accessPreview = ['user' => null, 'allowed' => [], 'blocked' => []];
            return;
        }

        $roleDef = $this->roleDefinitions()[$user['role_key']] ?? $this->roleDefinitions()['unassigned'];
        $permissions = $this->cleanPermissionList($user['role_key'] === 'super_admin' ? ['*'] : ($roleDef['permissions'] ?? []));
        if ($user['permission_count'] > 0 && Schema::hasTable('users')) {
            $raw = DB::table('users')->where('id', $user['id'])->value('admin_permissions');
            $decoded = is_string($raw) ? json_decode($raw, true) : [];
            if (is_array($decoded) && count($decoded)) {
                $permissions = $this->cleanPermissionList($decoded);
            }
        }

        $modules = $this->accessModules();
        $allowed = [];
        $blocked = [];
        foreach ($modules as $module) {
            $required = $module['permission'];
            $can = in_array('*', $permissions, true) || in_array($required, $permissions, true);
            if ($can) {
                $allowed[] = $module;
            } else {
                $blocked[] = $module;
            }
        }

        $this->accessPreview = [
            'user' => $user,
            'permissions' => $permissions,
            'assigned_apps' => $this->appNameList($user['assigned_app_ids'] ?? []),
            'allowed' => $allowed,
            'blocked' => $blocked,
        ];
    }

    private function accessModules(): array
    {
        return [
            ['label' => 'Control Center', 'permission' => 'system.view', 'scope' => 'Global/system'],
            ['label' => 'All Apps', 'permission' => 'apps.view', 'scope' => 'Global/system'],
            ['label' => '+ New App', 'permission' => 'apps.create', 'scope' => 'Global/system'],
            ['label' => 'App Settings', 'permission' => 'app_settings.view', 'scope' => 'Assigned app'],
            ['label' => 'Content Channels', 'permission' => 'content.view', 'scope' => 'Assigned app'],
            ['label' => 'Short Video Engine', 'permission' => 'shorts.view', 'scope' => 'Assigned app'],
            ['label' => 'Books Engine', 'permission' => 'books.view', 'scope' => 'Assigned app'],
            ['label' => 'Quiz Center', 'permission' => 'quiz.view', 'scope' => 'Assigned app'],
            ['label' => 'Media Library', 'permission' => 'media.view', 'scope' => 'Assigned app'],
            ['label' => 'Push Notifications', 'permission' => 'notifications.view', 'scope' => 'Assigned app'],
            ['label' => 'App Users', 'permission' => 'users.view', 'scope' => 'Assigned app'],
            ['label' => 'Analytics', 'permission' => 'analytics.view', 'scope' => 'Assigned app'],
            ['label' => 'Monetization', 'permission' => 'monetization.view', 'scope' => 'System/finance'],
            ['label' => 'Roles & Permissions', 'permission' => 'roles.view', 'scope' => 'System/admin'],
        ];
    }

    private function safeTab(string $tab): string
    {
        return in_array($tab, ['overview', 'roles', 'admin_users', 'matrix', 'access_preview', 'rules'], true) ? $tab : 'overview';
    }

    private function safeWorkspace(string $workspace): string
    {
        return in_array($workspace, ['index', 'role_editor', 'user_editor'], true) ? $workspace : 'index';
    }

    private function hasRoleColumns(): bool
    {
        return Schema::hasTable('users')
            && Schema::hasColumn('users', 'admin_role')
            && Schema::hasColumn('users', 'admin_is_active')
            && Schema::hasColumn('users', 'assigned_app_ids')
            && Schema::hasColumn('users', 'admin_permissions');
    }

    private function hasCustomRolesTable(): bool
    {
        return Schema::hasTable('admin_roles');
    }

    private function roleDefinitions(): array
    {
        return array_replace($this->systemRoleDefinitions(), $this->customRoleDefinitions(), [
            'unassigned' => [
                'key' => 'unassigned',
                'label' => 'Unassigned',
                'description' => 'No backend admin role assigned. Phase 2 still avoids aggressive lockout until enforcement is enabled.',
                'tone' => '',
                'system' => false,
                'is_active' => true,
                'permissions' => [],
                'assigned_app_ids' => [],
            ],
        ]);
    }

    private function systemRoleDefinitions(): array
    {
        return [
            'super_admin' => [
                'key' => 'super_admin',
                'label' => 'Super Admin',
                'description' => 'Full owner access across all apps, engines, users, billing, settings, roles, and system controls.',
                'tone' => 'danger',
                'system' => true,
                'is_active' => true,
                'permissions' => ['*'],
                'assigned_app_ids' => [],
            ],
            'app_admin' => [
                'key' => 'app_admin',
                'label' => 'App Admin',
                'description' => 'Template role for app-level managers. Custom staff roles can be created for real users.',
                'tone' => 'good',
                'system' => true,
                'is_active' => true,
                'permissions' => User::rolePermissions('app_admin'),
                'assigned_app_ids' => [],
            ],
            'content_manager' => [
                'key' => 'content_manager',
                'label' => 'Content Manager',
                'description' => 'Template role for content, books, quiz, shorts, media, and publishing workflow.',
                'tone' => 'info',
                'system' => true,
                'is_active' => true,
                'permissions' => User::rolePermissions('content_manager'),
                'assigned_app_ids' => [],
            ],
            'moderator' => [
                'key' => 'moderator',
                'label' => 'Moderator',
                'description' => 'Template role for comments, reports, app users, devices, and moderation activity.',
                'tone' => 'warn',
                'system' => true,
                'is_active' => true,
                'permissions' => User::rolePermissions('moderator'),
                'assigned_app_ids' => [],
            ],
            'analyst' => [
                'key' => 'analyst',
                'label' => 'Analyst',
                'description' => 'Template role for read-only analytics, reports, summaries, and app performance visibility.',
                'tone' => 'purple',
                'system' => true,
                'is_active' => true,
                'permissions' => User::rolePermissions('analyst'),
                'assigned_app_ids' => [],
            ],
            'support_staff' => [
                'key' => 'support_staff',
                'label' => 'Support Staff',
                'description' => 'Template role for basic user/device support and notification status checks.',
                'tone' => 'cyan',
                'system' => true,
                'is_active' => true,
                'permissions' => User::rolePermissions('support_staff'),
                'assigned_app_ids' => [],
            ],
        ];
    }

    private function customRoleDefinitions(): array
    {
        if (! $this->hasCustomRolesTable()) {
            return [];
        }

        return DB::table('admin_roles')
            ->where('is_system', 0)
            ->orderByDesc('is_active')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(function ($role) {
                return [(string) $role->key => [
                    'key' => (string) $role->key,
                    'label' => (string) $role->name,
                    'description' => (string) ($role->description ?? ''),
                    'tone' => 'custom',
                    'system' => false,
                    'is_active' => (bool) ($role->is_active ?? true),
                    'permissions' => $this->cleanPermissionList(json_decode((string) ($role->permissions ?? '[]'), true) ?: []),
                    'assigned_app_ids' => $this->decodeJsonIds($role->assigned_app_ids ?? '[]'),
                ]];
            })
            ->all();
    }

    private function permissionGroups(): array
    {
        return [
            'Apps' => ['apps.view', 'apps.create', 'apps.update', 'apps.delete'],
            'App Settings' => ['app_settings.view', 'app_settings.update'],
            'Content' => ['content.view', 'content.create', 'content.update', 'content.delete', 'content.publish'],
            'Short Videos' => ['shorts.view', 'shorts.manage', 'shorts.channels', 'shorts.settings'],
            'Books' => ['books.view', 'books.manage'],
            'Quiz' => ['quiz.view', 'quiz.manage'],
            'Media' => ['media.view', 'media.upload', 'media.delete'],
            'Notifications' => ['notifications.view', 'notifications.send', 'notifications.manage'],
            'Users & Interactions' => ['users.view', 'users.manage', 'devices.manage', 'comments.moderate'],
            'Analytics' => ['analytics.view'],
            'Monetization' => ['monetization.view', 'monetization.manage'],
            'Roles & System' => ['roles.view', 'roles.manage', 'system.view', 'system.manage'],
        ];
    }

    private function rules(): array
    {
        return [
            ['title' => 'Roles & Permissions is the backend staff layer', 'body' => 'This area manages backend/admin/staff accounts. It is different from App Users, which manages frontend viewers and device tokens.'],
            ['title' => 'App Users is the viewer layer', 'body' => 'App Users shows mobile app viewers, comments, likes, follows, and devices scoped to the selected app.'],
            ['title' => 'Custom roles are now supported', 'body' => 'Create real staff roles such as Dunamis Content Staff, Celebration Moderator, or Push Notification Staff, then assign users and app access.'],
            ['title' => 'Phase 2 is still safe', 'body' => 'This phase manages roles and staff accounts but still avoids aggressive page lockout. Page-by-page enforcement comes after testing access preview.'],
            ['title' => 'Assigned apps matter', 'body' => 'A staff user assigned only to Dunamis TV should later see only Dunamis TV workspace tools and not other apps or global system controls.'],
            ['title' => 'Access Preview comes before enforcement', 'body' => 'Use Access Preview to see what a user should or should not see before we activate sidebar/page restrictions in the next phase.'],
        ];
    }

    private function cleanPermissionList(mixed $permissions): array
    {
        if (! is_array($permissions)) {
            return [];
        }

        return collect($permissions)
            ->filter(fn ($permission) => is_string($permission) && trim($permission) !== '')
            ->map(fn ($permission) => trim($permission))
            ->unique()
            ->values()
            ->all();
    }

    private function cleanAppIds(mixed $ids): array
    {
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

    private function decodeJsonIds(mixed $raw): array
    {
        if (is_array($raw)) {
            return $this->cleanAppIds($raw);
        }

        $decoded = is_string($raw) ? json_decode($raw, true) : [];

        return $this->cleanAppIds(is_array($decoded) ? $decoded : []);
    }
}
