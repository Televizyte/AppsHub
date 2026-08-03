<?php

namespace App\Filament\Pages;

use App\Models\App;
use App\Models\FeedComment;
use App\Models\FeedPost;
use App\Models\FeedReport;
use App\Models\FeedSetting;
use App\Support\ActiveApp;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class FeedEngine extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-chat-bubble-left-right';
    protected static ?string $navigationLabel = 'Feed Engine';
    protected static ?string $navigationGroup = 'Engagement';
    protected static ?int $navigationSort = 19;
    protected static ?string $slug = 'feed-engine';

    protected static string $view = 'filament.pages.feed-engine';

    public ?App $currentApp = null;
    public string $activeTab = 'overview';
    public string $postView = 'cards';
    public string $statusFilter = 'all';
    public string $bucketFilter = 'all';

    public array $form = [];
    public array $settings = [];

    protected $queryString = [
        'activeTab' => ['as' => 'tab', 'except' => 'overview'],
        'postView' => ['as' => 'view', 'except' => 'cards'],
        'statusFilter' => ['as' => 'status', 'except' => 'all'],
        'bucketFilter' => ['as' => 'bucket', 'except' => 'all'],
    ];

    public static function canAccess(): bool
    {
        return true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return true;
    }

    public function mount(): void
    {
        $appId = $this->activeAppId();
        $this->currentApp = $appId > 0 ? App::query()->find($appId) : null;

        $tab = (string) request()->query('tab', 'overview');
        if (in_array($tab, $this->tabs(), true)) {
            $this->activeTab = $tab;
        }

        $this->resetForm(false);
        $this->settings = $this->loadSettings();
    }

    public function selectTab(string $tab): void
    {
        if (in_array($tab, $this->tabs(), true)) {
            $this->activeTab = $tab;
        }
    }

    public function setPostView(string $view): void
    {
        $this->postView = in_array($view, ['cards', 'compact'], true) ? $view : 'cards';
    }

    public function resetForm(bool $notify = true): void
    {
        $this->form = [
            'post_type' => 'announcement',
            'bucket' => 'announcements',
            'status' => 'draft',
            'title' => '',
            'excerpt' => '',
            'body' => '',
            'cta_label' => 'Open',
            'thumbnail_url' => '',
            'media_url' => '',
            'source_engine' => '',
            'source_id' => '',
            'deep_link' => '',
            'is_pinned' => false,
            'is_featured' => false,
            'published_at' => now()->format('Y-m-d\TH:i'),
            'sort_order' => 0,
        ];

        if ($notify) {
            Notification::make()->title('Feed form reset')->success()->send();
        }
    }

    public function quickType(string $type, string $bucket): void
    {
        $this->form['post_type'] = $type;
        $this->form['bucket'] = $bucket;
        $this->activeTab = 'create';
    }

    public function saveDraft(): void
    {
        $this->storePost('draft');
    }

    public function publishNow(): void
    {
        $this->storePost('published');
    }

    protected function storePost(string $status): void
    {
        if (! Schema::hasTable('feed_posts')) {
            Notification::make()->title('Feed tables are not available yet')->danger()->send();
            return;
        }

        $title = trim((string) ($this->form['title'] ?? ''));
        if ($title === '') {
            Notification::make()->title('Post title is required')->body('Add a short title before saving this feed post.')->danger()->send();
            $this->activeTab = 'create';
            return;
        }

        $appId = $this->activeAppId();
        if ($appId <= 0) {
            Notification::make()->title('No active app selected')->body('Select an active app before creating feed posts.')->danger()->send();
            return;
        }

        FeedPost::query()->create([
            'app_id' => $appId,
            'bucket' => (string) ($this->form['bucket'] ?? 'announcements'),
            'post_type' => (string) ($this->form['post_type'] ?? 'announcement'),
            'source_engine' => $this->blankToNull($this->form['source_engine'] ?? null),
            'source_id' => $this->blankToNull($this->form['source_id'] ?? null),
            'title' => $title,
            'body' => $this->blankToNull($this->form['body'] ?? null),
            'excerpt' => $this->blankToNull($this->form['excerpt'] ?? null),
            'thumbnail_url' => $this->blankToNull($this->form['thumbnail_url'] ?? null),
            'media_url' => $this->blankToNull($this->form['media_url'] ?? null),
            'deep_link' => $this->blankToNull($this->form['deep_link'] ?? null),
            'cta_label' => $this->blankToNull($this->form['cta_label'] ?? null),
            'status' => $status,
            'approval_status' => $status === 'published' ? 'approved' : 'approved',
            'visibility' => 'public',
            'is_pinned' => (bool) ($this->form['is_pinned'] ?? false),
            'is_featured' => (bool) ($this->form['is_featured'] ?? false),
            'sort_order' => (int) ($this->form['sort_order'] ?? 0),
            'published_at' => $status === 'published' ? now() : null,
            'created_by_type' => 'admin',
            'created_by_id' => auth()->id(),
            'meta_json' => [],
        ]);

        $this->resetForm(false);
        $this->activeTab = $status === 'published' ? 'published' : 'overview';

        Notification::make()
            ->title($status === 'published' ? 'Feed post published' : 'Feed draft saved')
            ->success()
            ->send();
    }

    public function publishPost(int $id): void
    {
        $post = $this->postQuery()->whereKey($id)->first();
        if (! $post) {
            return;
        }

        $post->update([
            'status' => 'published',
            'approval_status' => 'approved',
            'published_at' => $post->published_at ?: now(),
        ]);

        Notification::make()->title('Post published')->success()->send();
    }

    public function hidePost(int $id): void
    {
        $post = $this->postQuery()->whereKey($id)->first();
        if (! $post) {
            return;
        }

        $post->update(['status' => 'hidden']);
        Notification::make()->title('Post hidden')->success()->send();
    }

    public function togglePin(int $id): void
    {
        $post = $this->postQuery()->whereKey($id)->first();
        if (! $post) {
            return;
        }

        $post->update(['is_pinned' => ! (bool) $post->is_pinned]);
    }

    public function toggleSetting(string $key): void
    {
        if (! array_key_exists($key, $this->settings)) {
            return;
        }

        $this->settings[$key] = ! (bool) $this->settings[$key];
        $this->saveSettings(false);
    }

    public function saveSettings(bool $notify = true): void
    {
        if (! Schema::hasTable('feed_settings')) {
            return;
        }

        $appId = $this->activeAppId();
        if ($appId <= 0) {
            return;
        }

        FeedSetting::query()->updateOrCreate(
            ['app_id' => $appId],
            [
                'is_enabled' => (bool) ($this->settings['is_enabled'] ?? true),
                'auto_approve_system_posts' => (bool) ($this->settings['auto_approve_system_posts'] ?? true),
                'auto_approve_tool_posts' => (bool) ($this->settings['auto_approve_tool_posts'] ?? false),
                'auto_approve_text_posts' => (bool) ($this->settings['auto_approve_text_posts'] ?? false),
                'comments_enabled' => (bool) ($this->settings['comments_enabled'] ?? true),
                'comments_require_approval' => (bool) ($this->settings['comments_require_approval'] ?? false),
                'user_text_posts_enabled' => (bool) ($this->settings['user_text_posts_enabled'] ?? false),
                'external_media_uploads_enabled' => (bool) ($this->settings['external_media_uploads_enabled'] ?? false),
                'settings_json' => [
                    'show_home_preview' => (bool) ($this->settings['show_home_preview'] ?? true),
                    'allow_quote_shares' => (bool) ($this->settings['allow_quote_shares'] ?? true),
                    'allow_quiz_score_shares' => (bool) ($this->settings['allow_quiz_score_shares'] ?? true),
                    'allow_game_achievement_shares' => (bool) ($this->settings['allow_game_achievement_shares'] ?? true),
                ],
            ]
        );

        if ($notify) {
            Notification::make()->title('Feed settings saved')->success()->send();
        }
    }

    protected function loadSettings(): array
    {
        $defaults = [
            'is_enabled' => true,
            'show_home_preview' => true,
            'auto_approve_system_posts' => true,
            'auto_approve_tool_posts' => false,
            'auto_approve_text_posts' => false,
            'comments_enabled' => true,
            'comments_require_approval' => false,
            'user_text_posts_enabled' => false,
            'external_media_uploads_enabled' => false,
            'allow_quote_shares' => true,
            'allow_quiz_score_shares' => true,
            'allow_game_achievement_shares' => true,
        ];

        if (! Schema::hasTable('feed_settings')) {
            return $defaults;
        }

        $setting = FeedSetting::query()->where('app_id', $this->activeAppId())->first();
        if (! $setting) {
            return $defaults;
        }

        $json = is_array($setting->settings_json) ? $setting->settings_json : [];

        return array_merge($defaults, [
            'is_enabled' => (bool) $setting->is_enabled,
            'auto_approve_system_posts' => (bool) $setting->auto_approve_system_posts,
            'auto_approve_tool_posts' => (bool) $setting->auto_approve_tool_posts,
            'auto_approve_text_posts' => (bool) $setting->auto_approve_text_posts,
            'comments_enabled' => (bool) $setting->comments_enabled,
            'comments_require_approval' => (bool) $setting->comments_require_approval,
            'user_text_posts_enabled' => (bool) $setting->user_text_posts_enabled,
            'external_media_uploads_enabled' => (bool) $setting->external_media_uploads_enabled,
            'show_home_preview' => (bool) ($json['show_home_preview'] ?? true),
            'allow_quote_shares' => (bool) ($json['allow_quote_shares'] ?? true),
            'allow_quiz_score_shares' => (bool) ($json['allow_quiz_score_shares'] ?? true),
            'allow_game_achievement_shares' => (bool) ($json['allow_game_achievement_shares'] ?? true),
        ]);
    }

    public function stats(): array
    {
        if (! Schema::hasTable('feed_posts')) {
            return [
                'total' => 0, 'published' => 0, 'drafts' => 0, 'pending' => 0,
                'pinned' => 0, 'reports' => 0, 'comments' => 0,
            ];
        }

        $query = $this->postQuery();
        $appId = $this->activeAppId();

        return [
            'total' => (clone $query)->count(),
            'published' => (clone $query)->where('status', 'published')->count(),
            'drafts' => (clone $query)->where('status', 'draft')->count(),
            'pending' => (clone $query)->where('approval_status', 'pending')->count(),
            'pinned' => (clone $query)->where('is_pinned', true)->count(),
            'comments' => Schema::hasTable('feed_comments') ? FeedComment::query()->whereHas('post', fn ($q) => $q->where('app_id', $appId))->count() : 0,
            'reports' => Schema::hasTable('feed_reports') ? FeedReport::query()->whereHas('post', fn ($q) => $q->where('app_id', $appId))->count() : 0,
        ];
    }

    public function posts(string $scope = 'recent', int $limit = 8)
    {
        if (! Schema::hasTable('feed_posts')) {
            return collect();
        }

        $query = $this->postQuery()->withCount(['comments', 'reactions', 'saves', 'shares', 'reports']);

        if ($scope === 'published') {
            $query->where('status', 'published');
        } elseif ($scope === 'pending') {
            $query->where(function ($q) {
                $q->where('approval_status', 'pending')->orWhere('status', 'pending');
            });
        } elseif ($scope === 'drafts') {
            $query->where('status', 'draft');
        }

        if ($this->bucketFilter !== 'all') {
            $query->where('bucket', $this->bucketFilter);
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        return $query
            ->orderByDesc('is_pinned')
            ->orderByDesc('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();
    }

    protected function postQuery()
    {
        return FeedPost::query()->where('app_id', $this->activeAppId());
    }

    public function tabs(): array
    {
        return ['overview', 'published', 'pending', 'auto_rules', 'settings', 'preview'];
    }

    public function bucketOptions(): array
    {
        return [
            'admin_posts' => 'Admin Posts',
            'announcements' => 'Announcements',
            'articles' => 'Articles',
            'quotes' => 'Quotes',
            'short_videos' => 'Short Videos',
            'videos' => 'Videos',
            'books' => 'Books',
            'daily_scripture' => 'Daily Scripture',
            'bible_quiz' => 'Bible Quiz',
            'game_achievements' => 'Achievements',
            'live_broadcast' => 'Live',
            'events' => 'Events',
            'testimonies' => 'Testimonies',
            'devotional' => 'Devotional',
            'user_activity' => 'User Activity',
        ];
    }

    public function postTypeOptions(): array
    {
        return [
            'announcement' => 'Announcement',
            'article' => 'Article',
            'short_video' => 'Short Video',
            'quote' => 'Quote',
            'daily_scripture' => 'Daily Scripture',
            'book' => 'Book',
            'live' => 'Live Reminder',
            'event' => 'Event',
            'quiz_result' => 'Quiz Result',
            'game_achievement' => 'Game Achievement',
            'user_reflection' => 'User Reflection',
            'testimony' => 'Testimony',
            'text_post' => 'Text Post',
        ];
    }

    public function sourceOptions(): array
    {
        return [
            '' => 'No linked content',
            'content' => 'Article / Content Publisher',
            'short_video' => 'Short Video Engine',
            'quote' => 'Quote Engine',
            'quote_creator' => 'Quote Creator',
            'book' => 'Book Engine',
            'bible' => 'Bible / Scripture',
            'quiz' => 'Quiz Engine',
            'game' => 'Game Engine',
            'watch' => 'Watch / Live Broadcast',
            'event' => 'Event',
            'user_activity' => 'User Activity',
        ];
    }

    public function quickPostTypes(): array
    {
        return [
            ['type' => 'announcement', 'bucket' => 'announcements', 'label' => 'Announcement', 'note' => 'General app update or important notice.', 'icon' => 'announcement'],
            ['type' => 'article', 'bucket' => 'articles', 'label' => 'Article Share', 'note' => 'Point users to a Content Publisher article.', 'icon' => 'article'],
            ['type' => 'short_video', 'bucket' => 'short_videos', 'label' => 'Short Video', 'note' => 'Open a reel or short video channel.', 'icon' => 'video'],
            ['type' => 'quote', 'bucket' => 'quotes', 'label' => 'Quote', 'note' => 'Share SOD, scripture, or quote design.', 'icon' => 'quote'],
            ['type' => 'live', 'bucket' => 'live_broadcast', 'label' => 'Live Reminder', 'note' => 'Send users to the Watch tab or live player.', 'icon' => 'live'],
            ['type' => 'testimony', 'bucket' => 'testimonies', 'label' => 'Testimony', 'note' => 'Approved testimony or community story.', 'icon' => 'testimony'],
        ];
    }

    public function autoRules(): array
    {
        return [
            ['title' => 'Auto-post new articles', 'engine' => 'Content Publisher', 'status' => 'Planned', 'note' => 'Create feed cards when selected articles are published.'],
            ['title' => 'Auto-post new short videos', 'engine' => 'Short Video Engine', 'status' => 'Planned', 'note' => 'Create short video feed cards with deep links to the reel.'],
            ['title' => 'Auto-post new quotes', 'engine' => 'Quote Engine', 'status' => 'Planned', 'note' => 'Feature daily quote, scripture, SOD, and quote creator cards.'],
            ['title' => 'Auto-post live reminders', 'engine' => 'Watch Engine', 'status' => 'Planned', 'note' => 'Pin live or upcoming service reminders at the top of the feed.'],
            ['title' => 'Auto-post quiz/game achievements', 'engine' => 'Quiz + Games', 'status' => 'Later', 'note' => 'Share approved user achievements and scores.'],
        ];
    }


    public function createPostUrl(?string $type = null, ?string $bucket = null): string
    {
        $params = ['return' => 'feed-engine'];

        if ($type) {
            $params['type'] = $type;
        }

        if ($bucket) {
            $params['bucket'] = $bucket;
        }

        return url('/admin/beginner/feed-posts/create?' . http_build_query($params));
    }

    public function editPostUrl(int $id): string
    {
        return url('/admin/beginner/feed-posts/' . $id . '/edit?return=feed-engine');
    }

    protected function activeAppId(): int
    {
        return (int) (ActiveApp::ensureId() ?? 0);
    }

    protected function blankToNull($value): ?string
    {
        $value = trim((string) ($value ?? ''));
        return $value === '' ? null : $value;
    }

    public function labelFor(?string $value, array $options): string
    {
        $value = (string) $value;
        return $options[$value] ?? Str::of($value)->replace('_', ' ')->title()->toString();
    }
}
