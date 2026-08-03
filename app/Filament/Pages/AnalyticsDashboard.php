<?php

namespace App\Filament\Pages;

use App\Support\AdminAccess;

use App\Models\App;
use App\Support\ActiveApp;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Illuminate\Support\Facades\DB;

class AnalyticsDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';
    protected static ?string $navigationLabel = 'Analytics';
    protected static ?string $navigationGroup = 'Engagement';
    protected static ?int $navigationSort = 5;
    protected static string $view = 'filament.pages.analytics-dashboard';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::page('analytics');
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('analytics');
    }
    protected static ?string $slug = 'analytics-dashboard';

    public ?array $activeApp = null;
    public array $stats = [];
    public array $topPosts = [];
    public array $recentWatchEvents = [];
    public array $contentBuckets = [];
    public array $watchTypes = [];
    public array $insights = [];


    public function mount(): void { $this->loadDashboard(); }
    public function refreshDashboard(): void { $this->loadDashboard(); }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')->label('Refresh')->icon('heroicon-o-arrow-path')->color('gray')->action(fn () => $this->refreshDashboard()),
        ];
    }

    private function loadDashboard(): void
    {
        $appId = (int) (ActiveApp::get() ?? 0);
        $app = $appId > 0 ? App::query()->select(['id','name','slug'])->find($appId) : null;
        if (! $app) {
            $this->activeApp = null; $this->stats = []; $this->topPosts = []; $this->recentWatchEvents = []; $this->contentBuckets = []; $this->watchTypes = []; $this->insights = [];
            return;
        }

        $this->activeApp = ['id'=>(int)$app->id, 'name'=>(string)$app->name, 'slug'=>(string)$app->slug];

        $contentViews = (int) DB::table('content_views')->where('app_id', $app->id)->count();
        $watchEvents = (int) DB::table('watch_events')->where('app_id', $app->id)->count();
        $plays = (int) DB::table('watch_events')->where('app_id', $app->id)->where('event_type','play')->count();
        $complete = (int) DB::table('watch_events')->where('app_id', $app->id)->where('event_type','complete')->count();
        $contentUsers = (int) DB::table('content_views')->where('app_id',$app->id)->whereNotNull('user_id')->distinct('user_id')->count('user_id');
        $contentSessions = (int) DB::table('content_views')->where('app_id',$app->id)->whereNotNull('session_id')->distinct('session_id')->count('session_id');
        $watchSessions = (int) DB::table('watch_events')->where('app_id',$app->id)->whereNotNull('session_id')->distinct('session_id')->count('session_id');

        $this->stats = [
            'content_views_total'=>$contentViews,
            'watch_events_total'=>$watchEvents,
            'watch_play_count'=>$plays,
            'watch_complete_count'=>$complete,
            'watch_completion_rate'=>$plays > 0 ? (int) round(($complete / max(1, $plays)) * 100) : 0,
            'content_unique_users'=>$contentUsers,
            'content_unique_sessions'=>$contentSessions,
            'watch_unique_sessions'=>$watchSessions,
            'total_engagement'=>$contentViews + $watchEvents,
        ];

        $this->topPosts = DB::table('content_views')
            ->leftJoin('content_posts','content_posts.id','=','content_views.content_post_id')
            ->where('content_views.app_id',$app->id)
            ->groupBy('content_views.content_post_id','content_posts.title','content_posts.slug','content_posts.bucket')
            ->selectRaw('content_views.content_post_id as post_id, content_posts.title as title, content_posts.slug as slug, content_posts.bucket as bucket, COUNT(*) as views_count')
            ->orderByDesc('views_count')->limit(10)->get()
            ->map(fn($r)=>[
                'post_id'=>(int)($r->post_id ?? 0),
                'title'=>(string)($r->title ?: 'Untitled Content'),
                'slug'=>$r->slug ? (string)$r->slug : null,
                'bucket'=>$r->bucket ? (string)$r->bucket : 'content',
                'views_count'=>(int)($r->views_count ?? 0),
            ])->values()->all();

        $bucketRows = DB::table('content_views')
            ->leftJoin('content_posts','content_posts.id','=','content_views.content_post_id')
            ->where('content_views.app_id',$app->id)
            ->groupBy('content_posts.bucket')
            ->selectRaw('COALESCE(content_posts.bucket, "content") as bucket, COUNT(*) as views_count')
            ->orderByDesc('views_count')->limit(8)->get();
        $bucketMax = max(1, (int) $bucketRows->max('views_count'));
        $this->contentBuckets = $bucketRows->map(fn($r)=>[
            'bucket'=>(string)($r->bucket ?: 'content'),
            'views_count'=>(int)($r->views_count ?? 0),
            'percent'=>(int) round((((int)($r->views_count ?? 0)) / $bucketMax) * 100),
        ])->values()->all();

        $typeRows = DB::table('watch_events')->where('app_id',$app->id)->groupBy('event_type')
            ->selectRaw('event_type, COUNT(*) as events_count')->orderByDesc('events_count')->limit(8)->get();
        $typeMax = max(1, (int) $typeRows->max('events_count'));
        $this->watchTypes = $typeRows->map(fn($r)=>[
            'event_type'=>(string)($r->event_type ?: 'unknown'),
            'events_count'=>(int)($r->events_count ?? 0),
            'percent'=>(int) round((((int)($r->events_count ?? 0)) / $typeMax) * 100),
        ])->values()->all();

        $this->recentWatchEvents = DB::table('watch_events')
            ->leftJoin('watch_links','watch_links.id','=','watch_events.watch_link_id')
            ->leftJoin('content_posts','content_posts.id','=','watch_events.content_post_id')
            ->leftJoin('users','users.id','=','watch_events.user_id')
            ->where('watch_events.app_id',$app->id)
            ->orderByDesc('watch_events.occurred_at')->orderByDesc('watch_events.id')->limit(12)
            ->select(['watch_events.id','watch_events.event_type','watch_events.session_id','watch_events.platform','watch_events.position_seconds','watch_events.duration_seconds','watch_events.progress_percent','watch_events.occurred_at','watch_links.title as watch_link_title','content_posts.title as content_post_title','users.name as user_name'])
            ->get()->map(fn($r)=>[
                'id'=>(int)($r->id ?? 0),
                'event_type'=>(string)($r->event_type ?? 'unknown'),
                'target_title'=>(string)($r->watch_link_title ?: $r->content_post_title ?: 'Unknown target'),
                'user_name'=>$r->user_name ? (string)$r->user_name : 'Guest',
                'session_id'=>$r->session_id ? (string)$r->session_id : null,
                'platform'=>$r->platform ? (string)$r->platform : null,
                'position_seconds'=>is_numeric($r->position_seconds) ? (int)$r->position_seconds : null,
                'duration_seconds'=>is_numeric($r->duration_seconds) ? (int)$r->duration_seconds : null,
                'progress_percent'=>is_numeric($r->progress_percent) ? (int)$r->progress_percent : null,
                'occurred_at'=>$r->occurred_at ? (string)$r->occurred_at : null,
            ])->values()->all();

        $this->insights = [];
        if (($contentViews + $watchEvents) <= 0) $this->insights[] = ['tone'=>'warn','title'=>'No engagement data yet','body'=>'The app is ready, but users have not generated enough tracked views or watch events yet.'];
        if (! empty($this->topPosts[0]['title'])) $this->insights[] = ['tone'=>'good','title'=>'Best content right now','body'=>'"' . $this->topPosts[0]['title'] . '" currently has the highest tracked views.'];
        if (($this->stats['watch_completion_rate'] ?? 0) >= 60) $this->insights[] = ['tone'=>'good','title'=>'Strong watch completion','body'=>'Watch completion is healthy. Keep promoting similar video content.'];
        elseif ($watchEvents > 0) $this->insights[] = ['tone'=>'info','title'=>'Watch completion can improve','body'=>'Some users play videos but do not complete them. Consider shorter clips or better thumbnails.'];
        $this->insights[] = ['tone'=>'info','title'=>'Recommended next action','body'=>'Use Push Notifications to bring users back to your top content, new video, devotional, or quiz after publishing.'];
    }
}
