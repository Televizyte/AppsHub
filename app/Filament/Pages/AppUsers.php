<?php

namespace App\Filament\Pages;

use App\Support\AdminAccess;

use App\Models\App;
use App\Models\ContentPostComment;
use App\Support\ActiveApp;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class AppUsers extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationLabel = 'App Users';
    protected static ?string $navigationGroup = 'Engagement';
    protected static ?int $navigationSort = 4;
    protected static string $view = 'filament.pages.app-users';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::page('app_users');
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('app_users');
    }
    protected static ?string $slug = 'app-users';

    public ?array $activeApp = null;
    public array $stats = [];
    public array $users = [];
    public array $devices = [];
    public array $comments = [];
    public array $likes = [];
    public array $follows = [];
    public array $rules = [];
    public string $search = '';


    public function mount(): void
    {
        $this->loadCenter();
    }

    public function refreshCenter(): void
    {
        $this->loadCenter();
        Notification::make()->title('User center refreshed')->success()->send();
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

    public function updatedSearch(): void
    {
        $this->loadCenter();
    }

    public function publishComment(int $commentId): void
    {
        $this->setCommentStatus($commentId, ContentPostComment::STATUS_PUBLISHED, 'Comment published.');
    }

    public function hideComment(int $commentId): void
    {
        $this->setCommentStatus($commentId, ContentPostComment::STATUS_HIDDEN, 'Comment hidden.');
    }

    public function pendingComment(int $commentId): void
    {
        $this->setCommentStatus($commentId, ContentPostComment::STATUS_PENDING, 'Comment moved to pending.');
    }

    public function deactivateDevice(int $deviceId): void
    {
        $appId = (int) ($this->activeApp['id'] ?? 0);
        if ($appId <= 0 || ! Schema::hasTable('device_tokens')) {
            return;
        }

        DB::table('device_tokens')->where('app_id', $appId)->where('id', $deviceId)->update([
            'is_active' => 0,
            'updated_at' => now(),
        ]);

        $this->loadCenter();
        Notification::make()->title('Device token deactivated')->success()->send();
    }

    public function activateDevice(int $deviceId): void
    {
        $appId = (int) ($this->activeApp['id'] ?? 0);
        if ($appId <= 0 || ! Schema::hasTable('device_tokens')) {
            return;
        }

        DB::table('device_tokens')->where('app_id', $appId)->where('id', $deviceId)->update([
            'is_active' => 1,
            'updated_at' => now(),
        ]);

        $this->loadCenter();
        Notification::make()->title('Device token activated')->success()->send();
    }

    public function statusPill(string $status): string
    {
        return match ($status) {
            ContentPostComment::STATUS_PUBLISHED, 'active', 'published' => 'au-pill good',
            ContentPostComment::STATUS_PENDING, 'pending' => 'au-pill warn',
            ContentPostComment::STATUS_HIDDEN, 'hidden', 'inactive' => 'au-pill danger',
            default => 'au-pill',
        };
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

    private function setCommentStatus(int $commentId, string $status, string $message): void
    {
        $appId = (int) ($this->activeApp['id'] ?? 0);
        if ($appId <= 0 || ! Schema::hasTable('content_post_comments')) {
            return;
        }

        if (! in_array($status, ContentPostComment::allowedStatuses(), true)) {
            return;
        }

        DB::table('content_post_comments')->where('app_id', $appId)->where('id', $commentId)->update([
            'status' => $status,
            'updated_at' => now(),
        ]);

        $this->loadCenter();
        Notification::make()->title($message)->success()->send();
    }

    private function loadCenter(): void
    {
        $appId = (int) (ActiveApp::ensureId() ?? 0);
        $app = $appId > 0 ? App::query()->select(['id', 'name', 'slug'])->find($appId) : null;

        if (! $app) {
            $this->activeApp = null;
            $this->stats = [];
            $this->users = [];
            $this->devices = [];
            $this->comments = [];
            $this->likes = [];
            $this->follows = [];
            $this->rules = $this->defaultRules();
            return;
        }

        $this->activeApp = [
            'id' => (int) $app->id,
            'name' => (string) $app->name,
            'slug' => (string) $app->slug,
        ];

        $this->stats = $this->loadStats((int) $app->id);
        $this->users = $this->loadUsers((int) $app->id);
        $this->devices = $this->loadDevices((int) $app->id);
        $this->comments = $this->loadComments((int) $app->id);
        $this->likes = $this->loadLikes((int) $app->id);
        $this->follows = $this->loadFollows((int) $app->id);
        $this->rules = $this->defaultRules();
    }

    private function loadStats(int $appId): array
    {
        $users = Schema::hasTable('users') ? DB::table('users')->where('active_app_id', $appId)->count() : 0;
        $devices = Schema::hasTable('device_tokens') ? DB::table('device_tokens')->where('app_id', $appId)->count() : 0;
        $activeDevices = Schema::hasTable('device_tokens') ? DB::table('device_tokens')->where('app_id', $appId)->where('is_active', 1)->count() : 0;
        $linkedDeviceUsers = Schema::hasTable('device_tokens') ? DB::table('device_tokens')->where('app_id', $appId)->whereNotNull('user_id')->distinct('user_id')->count('user_id') : 0;
        $likes = Schema::hasTable('content_post_likes') ? DB::table('content_post_likes')->where('app_id', $appId)->count() : 0;
        $comments = Schema::hasTable('content_post_comments') ? DB::table('content_post_comments')->where('app_id', $appId)->count() : 0;
        $pendingComments = Schema::hasTable('content_post_comments') ? DB::table('content_post_comments')->where('app_id', $appId)->where('status', ContentPostComment::STATUS_PENDING)->count() : 0;
        $hiddenComments = Schema::hasTable('content_post_comments') ? DB::table('content_post_comments')->where('app_id', $appId)->where('status', ContentPostComment::STATUS_HIDDEN)->count() : 0;
        $follows = Schema::hasTable('user_follows') ? DB::table('user_follows')->where('app_id', $appId)->count() : 0;

        return [
            'users' => (int) $users,
            'devices' => (int) $devices,
            'active_devices' => (int) $activeDevices,
            'linked_device_users' => (int) $linkedDeviceUsers,
            'likes' => (int) $likes,
            'comments' => (int) $comments,
            'pending_comments' => (int) $pendingComments,
            'hidden_comments' => (int) $hiddenComments,
            'follows' => (int) $follows,
        ];
    }

    private function loadUsers(int $appId): array
    {
        if (! Schema::hasTable('users')) {
            return [];
        }

        $query = DB::table('users')->where('active_app_id', $appId);
        $search = trim($this->search);
        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->orderByDesc('created_at')->limit(30)->get(['id', 'name', 'email', 'created_at', 'updated_at']);
        $ids = $users->pluck('id')->map(fn ($id) => (int) $id)->all();

        $deviceCounts = $this->groupCount('device_tokens', 'user_id', $ids, $appId);
        $likeCounts = $this->groupCount('content_post_likes', 'user_id', $ids, $appId);
        $commentCounts = $this->groupCount('content_post_comments', 'user_id', $ids, $appId);
        $followCounts = $this->groupCount('user_follows', 'follower_user_id', $ids, $appId);

        return $users->map(fn ($user) => [
            'id' => (int) $user->id,
            'name' => (string) ($user->name ?: 'Unnamed User'),
            'email' => (string) ($user->email ?: ''),
            'created_at' => (string) ($user->created_at ?? ''),
            'updated_at' => (string) ($user->updated_at ?? ''),
            'devices' => (int) ($deviceCounts[(int) $user->id] ?? 0),
            'likes' => (int) ($likeCounts[(int) $user->id] ?? 0),
            'comments' => (int) ($commentCounts[(int) $user->id] ?? 0),
            'follows' => (int) ($followCounts[(int) $user->id] ?? 0),
        ])->values()->all();
    }

    private function loadDevices(int $appId): array
    {
        if (! Schema::hasTable('device_tokens')) {
            return [];
        }

        return DB::table('device_tokens')
            ->leftJoin('users', 'users.id', '=', 'device_tokens.user_id')
            ->where('device_tokens.app_id', $appId)
            ->orderByDesc(DB::raw('COALESCE(device_tokens.last_seen_at, device_tokens.updated_at, device_tokens.created_at)'))
            ->limit(40)
            ->get([
                'device_tokens.id',
                'device_tokens.platform',
                'device_tokens.token',
                'device_tokens.is_active',
                'device_tokens.last_seen_at',
                'device_tokens.created_at',
                'device_tokens.updated_at',
                'users.name as user_name',
                'users.email as user_email',
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'platform' => (string) ($row->platform ?: 'unknown'),
                'token' => (string) $row->token,
                'is_active' => (bool) $row->is_active,
                'last_seen_at' => (string) ($row->last_seen_at ?: $row->updated_at ?: $row->created_at ?: ''),
                'user_name' => (string) ($row->user_name ?: 'Guest / device only'),
                'user_email' => (string) ($row->user_email ?: ''),
            ])->values()->all();
    }

    private function loadComments(int $appId): array
    {
        if (! Schema::hasTable('content_post_comments')) {
            return [];
        }

        return DB::table('content_post_comments')
            ->leftJoin('users', 'users.id', '=', 'content_post_comments.user_id')
            ->leftJoin('content_posts', 'content_posts.id', '=', 'content_post_comments.content_post_id')
            ->where('content_post_comments.app_id', $appId)
            ->orderByDesc('content_post_comments.created_at')
            ->limit(30)
            ->get([
                'content_post_comments.id',
                'content_post_comments.body',
                'content_post_comments.status',
                'content_post_comments.created_at',
                'users.name as user_name',
                'users.email as user_email',
                'content_posts.title as post_title',
                'content_posts.bucket as post_bucket',
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'body' => (string) $row->body,
                'status' => (string) $row->status,
                'created_at' => (string) ($row->created_at ?? ''),
                'user_name' => (string) ($row->user_name ?: 'Unknown user'),
                'user_email' => (string) ($row->user_email ?: ''),
                'post_title' => (string) ($row->post_title ?: 'Untitled content'),
                'post_bucket' => (string) ($row->post_bucket ?: ''),
            ])->values()->all();
    }

    private function loadLikes(int $appId): array
    {
        if (! Schema::hasTable('content_post_likes')) {
            return [];
        }

        return DB::table('content_post_likes')
            ->leftJoin('users', 'users.id', '=', 'content_post_likes.user_id')
            ->leftJoin('content_posts', 'content_posts.id', '=', 'content_post_likes.content_post_id')
            ->where('content_post_likes.app_id', $appId)
            ->orderByDesc('content_post_likes.created_at')
            ->limit(30)
            ->get([
                'content_post_likes.id',
                'content_post_likes.created_at',
                'users.name as user_name',
                'users.email as user_email',
                'content_posts.title as post_title',
                'content_posts.bucket as post_bucket',
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'created_at' => (string) ($row->created_at ?? ''),
                'user_name' => (string) ($row->user_name ?: 'Unknown user'),
                'user_email' => (string) ($row->user_email ?: ''),
                'post_title' => (string) ($row->post_title ?: 'Untitled content'),
                'post_bucket' => (string) ($row->post_bucket ?: ''),
            ])->values()->all();
    }

    private function loadFollows(int $appId): array
    {
        if (! Schema::hasTable('user_follows')) {
            return [];
        }

        return DB::table('user_follows')
            ->leftJoin('users as followers', 'followers.id', '=', 'user_follows.follower_user_id')
            ->leftJoin('users as followings', 'followings.id', '=', 'user_follows.following_user_id')
            ->where('user_follows.app_id', $appId)
            ->orderByDesc('user_follows.created_at')
            ->limit(30)
            ->get([
                'user_follows.id',
                'user_follows.created_at',
                'followers.name as follower_name',
                'followers.email as follower_email',
                'followings.name as following_name',
                'followings.email as following_email',
            ])
            ->map(fn ($row) => [
                'id' => (int) $row->id,
                'created_at' => (string) ($row->created_at ?? ''),
                'follower_name' => (string) ($row->follower_name ?: 'Unknown user'),
                'follower_email' => (string) ($row->follower_email ?: ''),
                'following_name' => (string) ($row->following_name ?: 'Unknown user'),
                'following_email' => (string) ($row->following_email ?: ''),
            ])->values()->all();
    }

    private function groupCount(string $table, string $column, array $ids, int $appId): array
    {
        if (! $ids || ! Schema::hasTable($table)) {
            return [];
        }

        return DB::table($table)
            ->where('app_id', $appId)
            ->whereIn($column, $ids)
            ->groupBy($column)
            ->select($column, DB::raw('COUNT(*) as total'))
            ->pluck('total', $column)
            ->mapWithKeys(fn ($count, $id) => [(int) $id => (int) $count])
            ->all();
    }

    private function defaultRules(): array
    {
        return [
            ['title' => 'Browsing stays public', 'body' => 'Users can open the app, watch videos, watch shorts, read articles, and browse free content without signing in.', 'tone' => 'good'],
            ['title' => 'Interactions require login', 'body' => 'Like, comment, save/favorite, follow, report, and personal sync actions should require a signed-in user token.', 'tone' => 'info'],
            ['title' => 'Viewer accounts are separate', 'body' => 'Frontend viewers are different from AppsHub admins. Admin/staff permissions will be handled in the roles module.', 'tone' => 'warn'],
            ['title' => 'Device tokens can exist first', 'body' => 'A device may receive app-level push notifications before the viewer creates an account. When login exists, tokens can be linked to the user.', 'tone' => 'info'],
        ];
    }
}
