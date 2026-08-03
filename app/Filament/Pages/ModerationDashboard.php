<?php

namespace App\Filament\Pages;

use App\Support\AdminAccess;

use App\Models\App;
use App\Models\ContentPostComment;
use App\Support\ActiveApp;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class ModerationDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-shield-check';
    protected static ?string $navigationLabel = 'Moderation';
    protected static ?string $navigationGroup = 'Engagement';
    protected static ?int $navigationSort = 6;
    protected static string $view = 'filament.pages.moderation-dashboard';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::page('moderation');
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('moderation');
    }
    protected static ?string $slug = 'moderation';

    public ?array $activeApp = null;
    public array $stats = [];
    public array $recentComments = [];
    public array $recentActivity = [];


    public function mount(): void { $this->loadDashboard(); }
    public function refreshDashboard(): void { $this->loadDashboard(); }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')->label('Refresh')->icon('heroicon-o-arrow-path')->color('gray')->action(fn () => $this->refreshDashboard()),
        ];
    }

    public function publishComment(int $commentId): void { $this->updateCommentStatus($commentId, ContentPostComment::STATUS_PUBLISHED, 'Comment published.'); }
    public function hideComment(int $commentId): void { $this->updateCommentStatus($commentId, ContentPostComment::STATUS_HIDDEN, 'Comment hidden.'); }
    public function markPendingComment(int $commentId): void { $this->updateCommentStatus($commentId, ContentPostComment::STATUS_PENDING, 'Comment moved to pending.'); }

    public function deleteComment(int $commentId): void
    {
        $appId = (int) ($this->activeApp['id'] ?? 0);
        if ($appId <= 0) return;

        $comment = ContentPostComment::query()->where('app_id', $appId)->find($commentId);
        if (! $comment) {
            Notification::make()->title('Comment not found')->danger()->send();
            $this->loadDashboard();
            return;
        }

        $comment->delete();
        Notification::make()->title('Comment deleted')->success()->send();
        $this->loadDashboard();
    }

    public function statusBadgeClass(string $status): string
    {
        return match ($status) {
            ContentPostComment::STATUS_PUBLISHED => 'dxm-pill good',
            ContentPostComment::STATUS_PENDING => 'dxm-pill warn',
            ContentPostComment::STATUS_HIDDEN => 'dxm-pill danger',
            default => 'dxm-pill',
        };
    }

    private function updateCommentStatus(int $commentId, string $status, string $message): void
    {
        $appId = (int) ($this->activeApp['id'] ?? 0);
        if ($appId <= 0 || ! in_array($status, ContentPostComment::allowedStatuses(), true)) return;

        $comment = ContentPostComment::query()->where('app_id', $appId)->find($commentId);
        if (! $comment) {
            Notification::make()->title('Comment not found')->danger()->send();
            $this->loadDashboard();
            return;
        }

        $comment->status = $status;
        $comment->save();

        Notification::make()->title($message)->success()->send();
        $this->loadDashboard();
    }

    private function loadDashboard(): void
    {
        $appId = (int) (ActiveApp::get() ?? 0);
        $app = $appId > 0 ? App::query()->select(['id','name','slug'])->find($appId) : null;

        if (! $app) {
            $this->activeApp = null; $this->stats = []; $this->recentComments = []; $this->recentActivity = [];
            return;
        }

        $this->activeApp = ['id'=>(int)$app->id, 'name'=>(string)$app->name, 'slug'=>(string)$app->slug];

        $base = ContentPostComment::query()->where('app_id', $app->id);
        $this->stats = [
            'total_comments'=>(clone $base)->count(),
            'published_comments'=>(clone $base)->where('status', ContentPostComment::STATUS_PUBLISHED)->count(),
            'pending_comments'=>(clone $base)->where('status', ContentPostComment::STATUS_PENDING)->count(),
            'hidden_comments'=>(clone $base)->where('status', ContentPostComment::STATUS_HIDDEN)->count(),
            'distinct_posts'=>(clone $base)->distinct('content_post_id')->count('content_post_id'),
            'distinct_commenters'=>(clone $base)->whereNotNull('user_id')->distinct('user_id')->count('user_id'),
        ];

        $this->recentComments = DB::table('content_post_comments')
            ->leftJoin('content_posts','content_posts.id','=','content_post_comments.content_post_id')
            ->leftJoin('users','users.id','=','content_post_comments.user_id')
            ->where('content_post_comments.app_id',$app->id)
            ->orderByDesc('content_post_comments.created_at')->orderByDesc('content_post_comments.id')->limit(50)
            ->select(['content_post_comments.id','content_post_comments.body','content_post_comments.status','content_post_comments.created_at','content_post_comments.updated_at','content_posts.title as post_title','content_posts.slug as post_slug','content_posts.bucket as post_bucket','users.name as user_name','users.email as user_email'])
            ->get()->map(fn($r)=>[
                'id'=>(int)($r->id ?? 0),'body'=>(string)($r->body ?? ''),'status'=>(string)($r->status ?? ''),
                'created_at'=>$r->created_at ? (string)$r->created_at : null,'updated_at'=>$r->updated_at ? (string)$r->updated_at : null,
                'post_title'=>$r->post_title ? (string)$r->post_title : 'Untitled','post_slug'=>$r->post_slug ? (string)$r->post_slug : null,
                'post_bucket'=>$r->post_bucket ? (string)$r->post_bucket : null,'user_name'=>$r->user_name ? (string)$r->user_name : 'Guest / Unknown',
                'user_email'=>$r->user_email ? (string)$r->user_email : null,
            ])->values()->all();

        $this->recentActivity = collect($this->recentComments)
            ->whereIn('status', [ContentPostComment::STATUS_PENDING, ContentPostComment::STATUS_HIDDEN])
            ->take(20)->values()->all();
    }
}
