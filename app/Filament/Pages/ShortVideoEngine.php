<?php

namespace App\Filament\Pages;

use App\Support\AdminAccess;

use App\Models\App;
use App\Models\ContentPost;
use App\Support\ActiveApp;
use App\Support\AdminMode;
use App\Support\ShortVideos\ShortVideoPayload;
use Carbon\Carbon;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ShortVideoEngine extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-video-camera';
    protected static ?string $navigationLabel = 'Short Video Engine';
    protected static ?string $navigationGroup = 'Shared Engines';
    protected static ?int $navigationSort = 12;
    protected static ?string $slug = 'short-video-engine';

    protected static string $view = 'filament.pages.short-video-engine';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::page('short_video_engine');
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('short_video_engine');
    }

    public ?App $currentApp = null;

    public string $activeTab = 'overview';
    public string $search = '';
    public string $statusFilter = 'all';
    public string $categoryFilter = 'all';
    public string $featuredFilter = 'all';
    public string $libraryView = 'grid';
    public string $overviewView = 'grid';
    public string $categoryView = 'grid';
    public string $channelView = 'grid';
    public bool $showCategoryEditor = false;
    public bool $showChannelEditor = false;
    public string $workspaceMode = 'index';

    public array $feedSettings = [];
    public array $categoryForm = [];
    public array $channelForm = [];
    public string $editingCategoryKey = '';
    public string $editingChannelKey = '';

    protected $queryString = [
        'activeTab' => ['as' => 'tab', 'except' => 'overview'],
        'workspaceMode' => ['as' => 'workspace', 'except' => 'index'],
        'editingCategoryKey' => ['as' => 'category', 'except' => ''],
        'editingChannelKey' => ['as' => 'channel', 'except' => ''],
        'libraryView' => ['as' => 'library_view', 'except' => 'grid'],
        'categoryView' => ['as' => 'category_view', 'except' => 'grid'],
        'channelView' => ['as' => 'channel_view', 'except' => 'grid'],
    ];


    public function mount(): void
    {
        $this->currentApp = $this->resolveCurrentApp();
        $this->feedSettings = $this->loadFeedSettings();
        $requestedCategory = (string) request()->query('category', '');
        $requestedChannel = (string) request()->query('channel', '');
        $requestedWorkspace = (string) request()->query('workspace', '');

        $this->resetCategoryForm(false);
        $this->resetChannelForm(false);

        if ($requestedWorkspace === 'category_editor') {
            $this->workspaceMode = 'category_editor';
            $this->activeTab = 'categories';
            if ($requestedCategory !== '') {
                $this->editCategory($requestedCategory);
            } else {
                $this->createCategory();
            }
        } elseif ($requestedWorkspace === 'channel_editor') {
            $this->workspaceMode = 'channel_editor';
            $this->activeTab = 'short_channels';
            if ($requestedChannel !== '') {
                $this->editChannel($requestedChannel);
            } else {
                $this->createChannel();
            }
        }

        $requestedTab = (string) request()->query('tab', '');
        if (in_array($requestedTab, ['overview', 'library', 'categories', 'short_channels', 'feed_settings', 'preview'], true)) {
            $this->activeTab = $requestedTab;
        }
    }

    public function selectTab(string $tab): void
    {
        $allowed = ['overview', 'library', 'categories', 'short_channels', 'feed_settings', 'preview'];
        $this->activeTab = in_array($tab, $allowed, true) ? $tab : 'overview';
        $this->workspaceMode = 'index';
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->statusFilter = 'all';
        $this->categoryFilter = 'all';
        $this->featuredFilter = 'all';
    }

    public function setLibraryView(string $view): void
    {
        $this->libraryView = in_array($view, ['grid', 'list'], true) ? $view : 'grid';
    }

    public function setOverviewView(string $view): void
    {
        $this->overviewView = in_array($view, ['grid', 'list'], true) ? $view : 'grid';
    }

    public function setCategoryView(string $view): void
    {
        $this->categoryView = in_array($view, ['grid', 'list'], true) ? $view : 'grid';
    }

    public function setChannelView(string $view): void
    {
        $this->channelView = in_array($view, ['grid', 'list'], true) ? $view : 'grid';
    }

    public function createChannel(): void
    {
        $this->resetChannelForm(false);
        $this->showChannelEditor = true;
        $this->workspaceMode = 'channel_editor';
        $this->activeTab = 'short_channels';
    }

    public function closeChannelEditor(): void
    {
        $this->showChannelEditor = false;
        $this->workspaceMode = 'index';
        $this->activeTab = 'short_channels';
        $this->resetChannelForm(false);
    }

    public function createCategory(): void
    {
        $this->resetCategoryForm(false);
        $this->showCategoryEditor = true;
        $this->workspaceMode = 'category_editor';
        $this->activeTab = 'categories';
    }

    public function closeCategoryEditor(): void
    {
        $this->showCategoryEditor = false;
        $this->workspaceMode = 'index';
        $this->activeTab = 'categories';
        $this->resetCategoryForm(false);
    }


    public function resetChannelForm(bool $notify = false): void
    {
        $this->editingChannelKey = '';
        $this->channelForm = [
            'label' => '',
            'key' => '',
            'placement' => 'Custom Placement',
            'description' => '',
            'icon' => '▶',
            'color' => '#06b6d4',
            'display_style' => 'vertical_feed',
            'sort_order' => count($this->loadManagedChannels()) + 1,
            'is_active' => true,
            'show_on_frontend' => true,
            'video_ids' => [],
        ];

        if ($notify) {
            $this->showChannelEditor = true;
            $this->workspaceMode = 'channel_editor';
            Notification::make()->title('Short Channel form reset')->success()->send();
        }
    }

    public function editChannel(string $key): void
    {
        $channel = $this->channelByKey($key);

        if (! $channel) {
            Notification::make()->title('Short Channel not found')->danger()->send();
            return;
        }

        $this->editingChannelKey = (string) $channel['key'];
        $this->showChannelEditor = true;
        $this->workspaceMode = 'channel_editor';
        $this->activeTab = 'short_channels';
        $this->channelForm = [
            'label' => (string) ($channel['label'] ?? ''),
            'key' => (string) ($channel['key'] ?? ''),
            'placement' => (string) ($channel['placement'] ?? 'Custom Placement'),
            'description' => (string) ($channel['description'] ?? ''),
            'icon' => (string) ($channel['icon'] ?? '▶'),
            'color' => (string) ($channel['color'] ?? '#06b6d4'),
            'display_style' => (string) ($channel['display_style'] ?? 'vertical_feed'),
            'sort_order' => (int) ($channel['sort_order'] ?? 0),
            'is_active' => (bool) ($channel['is_active'] ?? true),
            'show_on_frontend' => (bool) ($channel['show_on_frontend'] ?? true),
            'video_ids' => array_values(array_map('intval', $channel['video_ids'] ?? [])),
        ];
    }

    public function saveChannel(): void
    {
        $app = $this->resolveCurrentApp();

        if (! $app) {
            Notification::make()->title('No active app found')->danger()->send();
            return;
        }

        $label = trim((string) ($this->channelForm['label'] ?? ''));
        $keyInput = trim((string) ($this->channelForm['key'] ?? ''));
        $key = ShortVideoPayload::normalizeCategoryKey($keyInput !== '' ? $keyInput : $label);

        if ($label === '') {
            Notification::make()->title('Short Channel name is required')->danger()->send();
            return;
        }

        $managed = $this->loadManagedChannels();
        $oldKey = $this->editingChannelKey !== '' ? $this->editingChannelKey : $key;

        if ($oldKey !== $key && isset($managed[$key])) {
            Notification::make()->title('Short Channel key already exists')->body('Use a different slug/key for this channel.')->danger()->send();
            return;
        }

        if ($oldKey !== $key && isset($managed[$oldKey])) {
            unset($managed[$oldKey]);
        }

        $managed[$key] = $this->sanitizeChannelRow([
            'key' => $key,
            'label' => $label,
            'placement' => $this->channelForm['placement'] ?? 'Custom Placement',
            'description' => $this->channelForm['description'] ?? '',
            'icon' => $this->channelForm['icon'] ?? '▶',
            'color' => $this->channelForm['color'] ?? '#06b6d4',
            'display_style' => $this->channelForm['display_style'] ?? 'vertical_feed',
            'sort_order' => $this->channelForm['sort_order'] ?? 0,
            'is_active' => $this->channelForm['is_active'] ?? true,
            'show_on_frontend' => $this->channelForm['show_on_frontend'] ?? true,
            'video_ids' => $this->channelForm['video_ids'] ?? [],
            'source' => 'custom',
        ]);

        $this->saveManagedChannels($managed);
        $this->resetChannelForm(false);
        $this->showChannelEditor = false;
        $this->workspaceMode = 'index';
        $this->activeTab = 'short_channels';

        Notification::make()->title('Short Channel saved')->body('Frontend output list updated for the active app.')->success()->send();
    }

    public function toggleChannelActive(string $key): void
    {
        $channel = $this->channelByKey($key);

        if (! $channel) {
            Notification::make()->title('Short Channel not found')->danger()->send();
            return;
        }

        $managed = $this->loadManagedChannels();
        $managed[$key] = $this->sanitizeChannelRow(array_merge($channel, [
            'is_active' => ! (bool) ($channel['is_active'] ?? true),
            'source' => $channel['source'] ?? 'custom',
        ]));

        $this->saveManagedChannels($managed);
        Notification::make()->title('Short Channel visibility updated')->success()->send();
    }

    public function deleteChannel(string $key): void
    {
        $managed = $this->loadManagedChannels();

        if (! isset($managed[$key])) {
            Notification::make()->title('Only saved custom Short Channels can be removed')->warning()->send();
            return;
        }

        unset($managed[$key]);
        $this->saveManagedChannels($managed);

        if ($this->editingChannelKey === $key) {
            $this->resetChannelForm(false);
        }

        Notification::make()->title('Short Channel removed')->body('Videos are not deleted. Only the output assignment was removed.')->success()->send();
    }

    public function seedDefaultChannels(): void
    {
        $managed = $this->loadManagedChannels();

        foreach ($this->defaultChannelRows() as $row) {
            $key = (string) $row['key'];
            $managed[$key] = $this->sanitizeChannelRow(array_merge($row, $managed[$key] ?? [], [
                'source' => $managed[$key]['source'] ?? 'default',
                'is_active' => $managed[$key]['is_active'] ?? true,
                'show_on_frontend' => $managed[$key]['show_on_frontend'] ?? true,
            ]));
        }

        $this->saveManagedChannels($managed);
        Notification::make()->title('Default Short Channels prepared')->success()->send();
    }

    public function resetCategoryForm(bool $notify = false): void
    {
        $this->editingCategoryKey = '';
        $this->categoryForm = [
            'label' => '',
            'key' => '',
            'description' => '',
            'icon' => '▶',
            'color' => '#7c3aed',
            'cover_url' => '',
            'sort_order' => count($this->loadManagedCategories()) + 1,
            'is_active' => true,
            'show_on_frontend' => true,
        ];

        if ($notify) {
            $this->showCategoryEditor = true;
            $this->workspaceMode = 'category_editor';
            Notification::make()->title('Category form reset')->success()->send();
        }
    }

    public function editCategory(string $key): void
    {
        $category = $this->categoryByKey($key);

        if (! $category) {
            Notification::make()->title('Category not found')->danger()->send();
            return;
        }

        $this->editingCategoryKey = (string) $category['key'];
        $this->showCategoryEditor = true;
        $this->workspaceMode = 'category_editor';
        $this->activeTab = 'categories';
        $this->categoryForm = [
            'label' => (string) ($category['label'] ?? ''),
            'key' => (string) ($category['key'] ?? ''),
            'description' => (string) ($category['description'] ?? ''),
            'icon' => (string) ($category['icon'] ?? '▶'),
            'color' => (string) ($category['color'] ?? '#7c3aed'),
            'cover_url' => (string) ($category['cover_url'] ?? ''),
            'sort_order' => (int) ($category['sort_order'] ?? 0),
            'is_active' => (bool) ($category['is_active'] ?? true),
            'show_on_frontend' => (bool) ($category['show_on_frontend'] ?? true),
        ];
    }

    public function saveCategory(): void
    {
        $app = $this->resolveCurrentApp();

        if (! $app) {
            Notification::make()->title('No active app found')->danger()->send();
            return;
        }

        $label = trim((string) ($this->categoryForm['label'] ?? ''));
        $keyInput = trim((string) ($this->categoryForm['key'] ?? ''));
        $key = ShortVideoPayload::normalizeCategoryKey($keyInput !== '' ? $keyInput : $label);

        if ($label === '') {
            Notification::make()->title('Category name is required')->danger()->send();
            return;
        }

        $managed = $this->loadManagedCategories();
        $oldKey = $this->editingCategoryKey !== '' ? $this->editingCategoryKey : $key;

        if ($oldKey !== $key && isset($managed[$key])) {
            Notification::make()->title('Category key already exists')->body('Use a different slug/key for this category.')->danger()->send();
            return;
        }

        if ($oldKey !== $key && isset($managed[$oldKey])) {
            unset($managed[$oldKey]);
        }

        $managed[$key] = $this->sanitizeCategoryRow([
            'key' => $key,
            'label' => $label,
            'description' => $this->categoryForm['description'] ?? '',
            'icon' => $this->categoryForm['icon'] ?? '▶',
            'color' => $this->categoryForm['color'] ?? '#7c3aed',
            'cover_url' => $this->categoryForm['cover_url'] ?? '',
            'sort_order' => $this->categoryForm['sort_order'] ?? 0,
            'is_active' => $this->categoryForm['is_active'] ?? true,
            'show_on_frontend' => $this->categoryForm['show_on_frontend'] ?? true,
            'source' => 'custom',
        ]);

        $this->saveManagedCategories($managed);
        $this->resetCategoryForm(false);
        $this->showCategoryEditor = false;
        $this->workspaceMode = 'index';
        $this->activeTab = 'categories';

        Notification::make()->title('Short video category saved')->success()->send();
    }

    public function toggleCategoryActive(string $key): void
    {
        $category = $this->categoryByKey($key);

        if (! $category) {
            Notification::make()->title('Category not found')->danger()->send();
            return;
        }

        $managed = $this->loadManagedCategories();
        $managed[$key] = $this->sanitizeCategoryRow(array_merge($category, [
            'is_active' => ! (bool) ($category['is_active'] ?? true),
            'source' => $category['source'] ?? 'custom',
        ]));

        $this->saveManagedCategories($managed);
        Notification::make()->title('Category visibility updated')->success()->send();
    }

    public function deleteCategory(string $key): void
    {
        $managed = $this->loadManagedCategories();

        if (! isset($managed[$key])) {
            Notification::make()->title('Only saved custom categories can be removed')->warning()->send();
            return;
        }

        unset($managed[$key]);
        $this->saveManagedCategories($managed);

        if ($this->editingCategoryKey === $key) {
            $this->resetCategoryForm(false);
        }

        Notification::make()->title('Category removed from manager')->body('Existing short videos are not deleted.')->success()->send();
    }

    public function seedDefaultCategories(): void
    {
        $managed = $this->loadManagedCategories();

        foreach ($this->defaultCategoryRows() as $row) {
            $key = (string) $row['key'];
            $managed[$key] = $this->sanitizeCategoryRow(array_merge($row, $managed[$key] ?? [], [
                'source' => $managed[$key]['source'] ?? 'default',
                'is_active' => $managed[$key]['is_active'] ?? true,
                'show_on_frontend' => $managed[$key]['show_on_frontend'] ?? true,
            ]));
        }

        $this->saveManagedCategories($managed);
        Notification::make()->title('Default short video categories prepared')->success()->send();
    }

    public function assignShortCategory(int $postId, string $categoryKey): void
    {
        $post = $this->baseShortsQuery()->whereKey($postId)->first();

        if (! $post) {
            Notification::make()->title('Short video not found')->danger()->send();
            return;
        }

        $category = $this->categoryByKey($categoryKey) ?: [
            'key' => ShortVideoPayload::normalizeCategoryKey($categoryKey),
            'label' => ShortVideoPayload::normalizeCategoryLabel(null, $categoryKey),
        ];

        $meta = ShortVideoPayload::safeArray($post->meta_json ?? []);
        $meta['content_engine'] = 'short_video_feed';
        $meta['category'] = (string) $category['key'];
        $meta['category_key'] = (string) $category['key'];
        $meta['category_slug'] = (string) $category['key'];
        $meta['category_label'] = (string) $category['label'];
        $meta['category_name'] = (string) $category['label'];
        $meta['category_title'] = (string) $category['label'];

        $post->forceFill(['meta_json' => $meta])->save();

        Notification::make()->title('Short category updated')->body($post->title . ' → ' . $category['label'])->success()->send();
    }

    public function saveFeedSettings(): void
    {
        $app = $this->resolveCurrentApp();

        if (! $app) {
            Notification::make()->title('No active app found')->danger()->send();
            return;
        }

        $settings = $this->sanitizeFeedSettings($this->feedSettings);
        $branding = is_array($app->branding_json) ? $app->branding_json : [];
        $branding['short_video_settings'] = $settings;

        $app->forceFill(['branding_json' => $branding])->save();

        $this->currentApp = $app->fresh();
        $this->feedSettings = $settings;

        Notification::make()
            ->title('Short video feed settings saved')
            ->body('Frontend behavior settings are now stored for the active app.')
            ->success()
            ->send();
    }

    public function syncMissingDurations(): void
    {
        if (! $this->hasContentPostsTable()) {
            Notification::make()->title('Short videos table not available')->warning()->send();
            return;
        }

        $checked = 0;
        $updated = 0;

        $this->baseShortsQuery()->get()->each(function (ContentPost $post) use (&$checked, &$updated): void {
            $checked++;
            $meta = ShortVideoPayload::safeArray($post->meta_json ?? []);
            $current = $this->normalizeDurationValue(
                $meta['video_duration']
                ?? $meta['duration']
                ?? $meta['duration_label']
                ?? $meta['video_length']
                ?? $meta['runtime']
                ?? ''
            );

            if ($current !== '') {
                return;
            }

            $duration = $this->detectVideoDuration($meta);

            if ($duration === '') {
                return;
            }

            $meta['video_duration'] = $duration;
            $meta['duration'] = $duration;
            $seconds = $this->durationToSeconds($duration);
            if ($seconds > 0) {
                $meta['duration_seconds'] = $seconds;
            }

            $post->forceFill(['meta_json' => $meta])->save();
            $updated++;
        });

        Notification::make()
            ->title('Short video durations checked')
            ->body($updated . ' of ' . $checked . ' short video item(s) updated.')
            ->success()
            ->send();
    }

    public function stats(): array
    {
        if (! $this->hasContentPostsTable()) {
            return [
                'total' => 0,
                'published' => 0,
                'drafts' => 0,
                'featured' => 0,
                'categories' => 0,
                'channels' => 0,
                'latest' => 'None yet',
            ];
        }

        $base = $this->baseShortsQuery();
        $categories = $this->categories();
        $latest = (clone $base)->latest('updated_at')->first();

        return [
            'total' => (clone $base)->count(),
            'published' => (clone $base)->where('status', 'published')->count(),
            'drafts' => (clone $base)->where('status', 'draft')->count(),
            'featured' => (clone $base)->where('is_featured', true)->count(),
            'categories' => count($categories),
            'channels' => count($this->channelRows()),
            'latest' => $latest ? $this->formatDate($latest->updated_at ?? $latest->created_at) : 'None yet',
        ];
    }

    public function shortRows(int $limit = 60): array
    {
        if (! $this->hasContentPostsTable()) {
            return [];
        }

        $query = $this->baseShortsQuery();

        $search = trim($this->search);
        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('title', 'like', '%' . $search . '%')
                    ->orWhere('subtitle', 'like', '%' . $search . '%')
                    ->orWhere('body_html', 'like', '%' . $search . '%');
            });
        }

        if ($this->statusFilter !== 'all') {
            $query->where('status', $this->statusFilter);
        }

        if ($this->featuredFilter === 'featured') {
            $query->where('is_featured', true);
        } elseif ($this->featuredFilter === 'normal') {
            $query->where(function ($q): void {
                $q->where('is_featured', false)->orWhereNull('is_featured');
            });
        }

        $rows = $query
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->latest('updated_at')
            ->limit($limit)
            ->get();

        $mapped = $rows->map(fn (ContentPost $post): array => $this->mapShort($post))->all();

        if ($this->categoryFilter !== 'all') {
            $mapped = array_values(array_filter($mapped, function (array $row): bool {
                return ($row['category_key'] ?? 'general') === $this->categoryFilter;
            }));
        }

        return $mapped;
    }

    public function categories(): array
    {
        return $this->categoryRows();
    }

    public function categoryRows(bool $frontendOnly = false): array
    {
        $detected = $this->detectedCategoryRows();
        $managed = $this->loadManagedCategories();
        $defaults = [];

        foreach ($this->defaultCategoryRows() as $row) {
            $defaults[$row['key']] = $row;
        }

        $merged = [];

        foreach ($defaults as $key => $row) {
            $merged[$key] = $this->sanitizeCategoryRow(array_merge($row, ['source' => 'default']));
        }

        foreach ($detected as $row) {
            $key = (string) $row['key'];
            $merged[$key] = $this->sanitizeCategoryRow(array_merge($merged[$key] ?? [], $row, ['source' => $merged[$key]['source'] ?? 'detected']));
        }

        foreach ($managed as $key => $row) {
            $merged[$key] = $this->sanitizeCategoryRow(array_merge($merged[$key] ?? [], $row, ['source' => $row['source'] ?? 'custom']));
        }

        if ($frontendOnly) {
            $merged = array_filter($merged, fn (array $row): bool => (bool) ($row['is_active'] ?? true) && (bool) ($row['show_on_frontend'] ?? true));
        }

        uasort($merged, function (array $a, array $b): int {
            $order = ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0));
            return $order !== 0 ? $order : strcasecmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
        });

        return array_values($merged);
    }

    public function categoryOptions(): array
    {
        $options = [];

        foreach ($this->categoryRows(true) as $row) {
            $options[(string) $row['key']] = (string) $row['label'];
        }

        if ($options === []) {
            $options['general'] = 'General';
        }

        return $options;
    }

    protected function detectedCategoryRows(): array
    {
        if (! $this->hasContentPostsTable()) {
            return [];
        }

        $rows = $this->baseShortsQuery()->get();
        $categories = [];

        foreach ($rows as $post) {
            $mapped = $this->mapShort($post);
            $key = $mapped['category_key'] ?: 'general';
            $label = $mapped['category_label'] ?: ShortVideoPayload::normalizeCategoryLabel(null, $key);

            if (! isset($categories[$key])) {
                $categories[$key] = $this->sanitizeCategoryRow([
                    'key' => $key,
                    'label' => $label,
                    'count' => 0,
                    'published' => 0,
                    'featured' => 0,
                    'sample_thumbnail' => $mapped['thumbnail_url'] ?? null,
                    'source' => 'detected',
                ]);
            }

            $categories[$key]['count']++;
            if (($mapped['status'] ?? '') === 'published') {
                $categories[$key]['published']++;
            }
            if ((bool) ($mapped['is_featured'] ?? false)) {
                $categories[$key]['featured']++;
            }
            if (empty($categories[$key]['sample_thumbnail']) && ! empty($mapped['thumbnail_url'])) {
                $categories[$key]['sample_thumbnail'] = $mapped['thumbnail_url'];
            }
        }

        return $categories;
    }

    public function previewPayload(): array
    {
        $shorts = $this->shortRows(8);
        $settings = $this->sanitizeFeedSettings($this->feedSettings);

        return [
            'engine' => 'short_video_feed',
            'bucket' => ShortVideoPayload::DEFAULT_BUCKET,
            'app' => [
                'id' => $this->currentApp?->id,
                'name' => $this->currentApp?->name,
                'slug' => $this->currentApp?->slug,
            ],
            'settings' => $settings,
            'categories' => $this->categoryRows(true),
            'all_categories' => $this->categories(),
            'short_channels' => $this->channelRows(true),
            'channel_previews' => $this->channelPreviews(),
            'items' => array_slice($shorts, 0, 5),
            'behavior_rule' => $this->autoScrollRule($settings),
        ];
    }

    public function channelRows(bool $frontendOnly = false): array
    {
        $defaults = [];
        foreach ($this->defaultChannelRows() as $row) {
            $defaults[$row['key']] = $row;
        }

        $managed = $this->loadManagedChannels();
        $merged = [];

        foreach ($defaults as $key => $row) {
            $merged[$key] = $this->sanitizeChannelRow(array_merge($row, ['source' => 'default']));
        }

        foreach ($managed as $key => $row) {
            $merged[$key] = $this->sanitizeChannelRow(array_merge($merged[$key] ?? [], $row, ['source' => $row['source'] ?? 'custom']));
        }

        if ($frontendOnly) {
            $merged = array_filter($merged, fn (array $row): bool => (bool) ($row['is_active'] ?? true) && (bool) ($row['show_on_frontend'] ?? true));
        }

        uasort($merged, function (array $a, array $b): int {
            $order = ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0));
            return $order !== 0 ? $order : strcasecmp((string) ($a['label'] ?? ''), (string) ($b['label'] ?? ''));
        });

        return array_values(array_map(function (array $row): array {
            $row['count'] = count($row['video_ids'] ?? []);
            return $row;
        }, $merged));
    }

    public function channelOptions(): array
    {
        $options = [];

        foreach ($this->channelRows(true) as $row) {
            $options[(string) $row['key']] = (string) $row['label'];
        }

        return $options;
    }

    public function displayStyleOptions(): array
    {
        return [
            'vertical_feed' => 'Vertical Feed',
            'grid_preview' => 'Grid Preview',
            'carousel' => 'Carousel',
            'compact_strip' => 'Compact Strip',
        ];
    }

    public function shortChannelLabelsForPost(int $postId): array
    {
        $labels = [];

        foreach ($this->channelRows(false) as $channel) {
            if (in_array($postId, array_map('intval', $channel['video_ids'] ?? []), true)) {
                $labels[] = (string) ($channel['label'] ?? $channel['key'] ?? 'Short Channel');
            }
        }

        return $labels;
    }

    public function channelPreviews(): array
    {
        $allShorts = collect($this->shortRows(120))->keyBy('id');

        return array_map(function (array $channel) use ($allShorts): array {
            $ids = array_values(array_map('intval', $channel['video_ids'] ?? []));
            $items = [];

            foreach ($ids as $id) {
                if ($allShorts->has($id)) {
                    $items[] = $allShorts->get($id);
                }
            }

            return [
                'key' => $channel['key'],
                'label' => $channel['label'],
                'placement' => $channel['placement'] ?? '',
                'display_style' => $channel['display_style'] ?? 'vertical_feed',
                'items_count' => count($items),
                'items' => array_slice($items, 0, 5),
            ];
        }, $this->channelRows(true));
    }

    public function defaultChannelRows(): array
    {
        $rows = [
            ['label' => 'Home Shorts', 'key' => 'home-shorts', 'placement' => 'Home Tab', 'icon' => '🏠', 'color' => '#06b6d4', 'description' => 'Short videos dedicated to the Home tab.'],
            ['label' => 'Inspire Shorts', 'key' => 'inspire-shorts', 'placement' => 'Inspire Tab', 'icon' => '✨', 'color' => '#7c3aed', 'description' => 'Short videos dedicated to the Inspire tab.'],
            ['label' => 'Motivational Shorts', 'key' => 'motivational-shorts', 'placement' => 'Motivation Page', 'icon' => '⚡', 'color' => '#db2777', 'description' => 'Motivational short videos for a dedicated motivation feed.'],
            ['label' => 'Prayer Shorts', 'key' => 'prayer-shorts', 'placement' => 'Prayer Page', 'icon' => '🙏', 'color' => '#4f46e5', 'description' => 'Prayer clips and prayer-focused shorts.'],
        ];

        return array_map(function (array $row, int $index): array {
            return array_merge($row, [
                'display_style' => 'vertical_feed',
                'sort_order' => $index + 1,
                'is_active' => true,
                'show_on_frontend' => true,
                'video_ids' => [],
                'source' => 'default',
            ]);
        }, $rows, array_keys($rows));
    }

    protected function channelByKey(string $key): ?array
    {
        $normalized = ShortVideoPayload::normalizeCategoryKey($key);

        foreach ($this->channelRows(false) as $row) {
            if (($row['key'] ?? '') === $normalized) {
                return $row;
            }
        }

        return null;
    }

    protected function loadManagedChannels(): array
    {
        $app = $this->currentApp ?: $this->resolveCurrentApp();
        $branding = is_array($app?->branding_json) ? $app->branding_json : [];
        $stored = ShortVideoPayload::safeArray($branding['short_video_channels'] ?? []);
        $rows = [];

        foreach ($stored as $key => $row) {
            if (! is_array($row)) {
                continue;
            }

            $clean = $this->sanitizeChannelRow(array_merge($row, ['key' => $row['key'] ?? $key]));
            $rows[$clean['key']] = $clean;
        }

        return $rows;
    }

    protected function saveManagedChannels(array $channels): void
    {
        $app = $this->resolveCurrentApp();

        if (! $app) {
            return;
        }

        $clean = [];
        foreach ($channels as $key => $row) {
            if (! is_array($row)) {
                continue;
            }

            $channel = $this->sanitizeChannelRow(array_merge($row, ['key' => $row['key'] ?? $key]));
            $clean[$channel['key']] = $channel;
        }

        uasort($clean, fn (array $a, array $b): int => ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0)));

        $branding = is_array($app->branding_json) ? $app->branding_json : [];
        $branding['short_video_channels'] = $clean;

        $app->forceFill(['branding_json' => $branding])->save();
        $this->currentApp = $app->fresh();
    }

    protected function sanitizeChannelRow(array $row): array
    {
        $label = trim((string) ($row['label'] ?? $row['name'] ?? 'Short Channel'));
        $key = ShortVideoPayload::normalizeCategoryKey($row['key'] ?? $row['slug'] ?? $label);
        $color = trim((string) ($row['color'] ?? '#06b6d4'));
        $style = (string) ($row['display_style'] ?? 'vertical_feed');

        if (! preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#06b6d4';
        }

        if (! array_key_exists($style, $this->displayStyleOptions())) {
            $style = 'vertical_feed';
        }

        $ids = $row['video_ids'] ?? [];
        if (! is_array($ids)) {
            $ids = [];
        }

        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn (int $id): bool => $id > 0)));

        return [
            'key' => $key,
            'label' => $label !== '' ? $label : ShortVideoPayload::normalizeCategoryLabel(null, $key),
            'placement' => trim((string) ($row['placement'] ?? 'Custom Placement')) ?: 'Custom Placement',
            'description' => trim((string) ($row['description'] ?? '')),
            'icon' => trim((string) ($row['icon'] ?? '▶')) ?: '▶',
            'color' => $color,
            'display_style' => $style,
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'is_active' => (bool) ($row['is_active'] ?? true),
            'show_on_frontend' => (bool) ($row['show_on_frontend'] ?? true),
            'video_ids' => $ids,
            'source' => (string) ($row['source'] ?? 'custom'),
        ];
    }

    public function defaultCategoryRows(): array
    {
        $labels = [
            'Motivational' => ['icon' => '⚡', 'color' => '#7c3aed'],
            'Teachings' => ['icon' => '📖', 'color' => '#2563eb'],
            'Music' => ['icon' => '🎵', 'color' => '#db2777'],
            'Worship' => ['icon' => '🙌', 'color' => '#9333ea'],
            'Leadership' => ['icon' => '👑', 'color' => '#0f766e'],
            'Testimonies' => ['icon' => '✨', 'color' => '#ea580c'],
            'Announcements' => ['icon' => '📣', 'color' => '#0891b2'],
            'Events' => ['icon' => '📅', 'color' => '#16a34a'],
            'Youth' => ['icon' => '🔥', 'color' => '#dc2626'],
            'Prayer' => ['icon' => '🙏', 'color' => '#4f46e5'],
        ];

        $index = 1;

        return array_map(function (string $label, array $style) use (&$index): array {
            return [
                'key' => ShortVideoPayload::normalizeCategoryKey($label),
                'label' => $label,
                'description' => '',
                'icon' => $style['icon'],
                'color' => $style['color'],
                'cover_url' => '',
                'count' => 0,
                'published' => 0,
                'featured' => 0,
                'sample_thumbnail' => null,
                'sort_order' => $index++,
                'is_active' => true,
                'show_on_frontend' => true,
                'source' => 'default',
            ];
        }, array_keys($labels), array_values($labels));
    }


    protected function categoryByKey(string $key): ?array
    {
        $normalized = ShortVideoPayload::normalizeCategoryKey($key);

        foreach ($this->categoryRows(false) as $row) {
            if (($row['key'] ?? '') === $normalized) {
                return $row;
            }
        }

        return null;
    }

    protected function loadManagedCategories(): array
    {
        $app = $this->currentApp ?: $this->resolveCurrentApp();
        $branding = is_array($app?->branding_json) ? $app->branding_json : [];
        $stored = ShortVideoPayload::safeArray($branding['short_video_categories'] ?? []);
        $rows = [];

        foreach ($stored as $key => $row) {
            if (! is_array($row)) {
                continue;
            }

            $clean = $this->sanitizeCategoryRow(array_merge($row, ['key' => $row['key'] ?? $key]));
            $rows[$clean['key']] = $clean;
        }

        return $rows;
    }

    protected function saveManagedCategories(array $categories): void
    {
        $app = $this->resolveCurrentApp();

        if (! $app) {
            return;
        }

        $clean = [];
        foreach ($categories as $key => $row) {
            if (! is_array($row)) {
                continue;
            }

            $category = $this->sanitizeCategoryRow(array_merge($row, ['key' => $row['key'] ?? $key]));
            $clean[$category['key']] = $category;
        }

        uasort($clean, fn (array $a, array $b): int => ((int) ($a['sort_order'] ?? 0)) <=> ((int) ($b['sort_order'] ?? 0)));

        $branding = is_array($app->branding_json) ? $app->branding_json : [];
        $branding['short_video_categories'] = $clean;

        $app->forceFill(['branding_json' => $branding])->save();
        $this->currentApp = $app->fresh();
    }

    protected function sanitizeCategoryRow(array $row): array
    {
        $label = trim((string) ($row['label'] ?? $row['name'] ?? 'General'));
        $key = ShortVideoPayload::normalizeCategoryKey($row['key'] ?? $row['slug'] ?? $label);
        $color = trim((string) ($row['color'] ?? '#7c3aed'));

        if (! preg_match('/^#[0-9a-fA-F]{6}$/', $color)) {
            $color = '#7c3aed';
        }

        return [
            'key' => $key,
            'label' => $label !== '' ? $label : ShortVideoPayload::normalizeCategoryLabel(null, $key),
            'description' => trim((string) ($row['description'] ?? '')),
            'icon' => trim((string) ($row['icon'] ?? '▶')) ?: '▶',
            'color' => $color,
            'cover_url' => trim((string) ($row['cover_url'] ?? $row['image_url'] ?? '')),
            'count' => (int) ($row['count'] ?? 0),
            'published' => (int) ($row['published'] ?? 0),
            'featured' => (int) ($row['featured'] ?? 0),
            'sample_thumbnail' => $row['sample_thumbnail'] ?? null,
            'sort_order' => (int) ($row['sort_order'] ?? 0),
            'is_active' => (bool) ($row['is_active'] ?? true),
            'show_on_frontend' => (bool) ($row['show_on_frontend'] ?? true),
            'source' => (string) ($row['source'] ?? 'custom'),
        ];
    }

    public function statusOptions(): array
    {
        return ['all' => 'All Status', 'published' => 'Published', 'draft' => 'Draft', 'scheduled' => 'Scheduled', 'archived' => 'Archived'];
    }

    public function feedSpeedOptions(): array
    {
        return ['fast' => 'Fast', 'normal' => 'Normal', 'slow' => 'Slow'];
    }

    public function addShortUrl(): string
    {
        return url('/admin/beginner/content-posts/create?bucket=' . ShortVideoPayload::DEFAULT_BUCKET . '&return=short-video-engine');
    }

    public function libraryUrl(): string
    {
        return url('/admin/beginner/content-posts/channel/' . ShortVideoPayload::DEFAULT_BUCKET);
    }

    public function mediaLibraryUrl(): string
    {
        return url('/admin/media-library');
    }

    public function editUrl(int|string $id): string
    {
        return url('/admin/beginner/content-posts/' . $id . '/edit?bucket=' . ShortVideoPayload::DEFAULT_BUCKET . '&return=short-video-engine');
    }

    protected function resolveCurrentApp(): ?App
    {
        $id = ActiveApp::ensureId();

        if (! $id) {
            return App::query()->where('is_active', true)->orderBy('id')->first();
        }

        return App::query()->find($id);
    }

    protected function baseShortsQuery()
    {
        $appId = (int) ($this->currentApp?->id ?? ActiveApp::ensureId() ?? 0);

        return ContentPost::query()
            ->where('bucket', ShortVideoPayload::DEFAULT_BUCKET)
            ->when($appId > 0, fn ($query) => $query->where('app_id', $appId));
    }

    protected function mapShort(ContentPost $post): array
    {
        $meta = ShortVideoPayload::safeArray($post->meta_json ?? []);
        $categoryKey = ShortVideoPayload::normalizeCategoryKey($meta['category_key'] ?? $meta['category_slug'] ?? $meta['category'] ?? 'general');
        $categoryLabel = ShortVideoPayload::normalizeCategoryLabel($meta['category_label'] ?? $meta['category_name'] ?? null, $categoryKey);
        $videoUrl = trim((string) ($meta['video_url'] ?? $meta['url'] ?? ''));
        $thumbnail = $this->assetUrl($post->cover_image_url ?? null);
        $tags = ShortVideoPayload::normalizeTags($meta['tags'] ?? []);
        $duration = $this->resolveDurationLabel($meta);

        return [
            'id' => (int) $post->id,
            'title' => (string) $post->title,
            'subtitle' => (string) ($post->subtitle ?? ''),
            'slug' => (string) ($post->slug ?? ''),
            'status' => (string) ($post->status ?? 'draft'),
            'is_featured' => (bool) ($post->is_featured ?? false),
            'sort_order' => (int) ($post->sort_order ?? 0),
            'category_key' => $categoryKey,
            'category_label' => $categoryLabel,
            'tags' => $tags,
            'video_url' => $videoUrl,
            'video_engine' => (string) ($meta['video_engine'] ?? 'mp4'),
            'video_duration' => $duration,
            'duration' => $duration,
            'short_channels' => $this->shortChannelLabelsForPost((int) $post->id),
            'thumbnail_url' => $thumbnail,
            'publish_at' => $this->formatDate($post->publish_at),
            'published_at' => $this->formatDate($post->published_at),
            'updated_at' => $this->formatDate($post->updated_at),
            'edit_url' => $this->editUrl($post->id),
        ];
    }

    protected function resolveDurationLabel(array $meta): string
    {
        $direct = $this->normalizeDurationValue(
            $meta['video_duration']
            ?? $meta['duration']
            ?? $meta['duration_label']
            ?? $meta['video_length']
            ?? $meta['runtime']
            ?? $meta['length']
            ?? ''
        );

        if ($direct !== '') {
            return $direct;
        }

        return $this->detectVideoDuration($meta);
    }

    protected function normalizeDurationValue(mixed $value): string
    {
        if (is_numeric($value)) {
            return $this->formatDurationSeconds((int) round((float) $value));
        }

        $text = trim((string) $value);

        if ($text === '') {
            return '';
        }

        if (preg_match('/^\d{1,2}:\d{2}(?::\d{2})?$/', $text)) {
            return $text;
        }

        if (preg_match('/^(\d+)\s*(s|sec|secs|second|seconds)$/i', $text, $m)) {
            return $this->formatDurationSeconds((int) $m[1]);
        }

        if (preg_match('/^(\d+)\s*(m|min|mins|minute|minutes)$/i', $text, $m)) {
            return $this->formatDurationSeconds(((int) $m[1]) * 60);
        }

        return $text;
    }

    protected function detectVideoDuration(array $meta): string
    {
        $path = trim((string) ($meta['video_path'] ?? ''));

        if ($path === '') {
            return '';
        }

        try {
            $fullPath = Storage::disk('public')->path($path);
        } catch (\Throwable) {
            return '';
        }

        if (! is_file($fullPath) || ! is_readable($fullPath)) {
            return '';
        }

        $ffprobe = trim((string) shell_exec('command -v ffprobe 2>/dev/null'));
        if ($ffprobe === '') {
            return '';
        }

        $command = escapeshellcmd($ffprobe) . ' -v error -show_entries format=duration -of default=noprint_wrappers=1:nokey=1 ' . escapeshellarg($fullPath) . ' 2>/dev/null';
        $output = trim((string) shell_exec($command));

        if ($output === '' || ! is_numeric($output)) {
            return '';
        }

        return $this->formatDurationSeconds((int) round((float) $output));
    }

    protected function formatDurationSeconds(int $seconds): string
    {
        if ($seconds <= 0) {
            return '';
        }

        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remaining = $seconds % 60;

        if ($hours > 0) {
            return sprintf('%d:%02d:%02d', $hours, $minutes, $remaining);
        }

        return sprintf('%d:%02d', $minutes, $remaining);
    }

    protected function durationToSeconds(string $duration): int
    {
        $parts = array_map('intval', explode(':', $duration));

        if (count($parts) === 2) {
            return ($parts[0] * 60) + $parts[1];
        }

        if (count($parts) === 3) {
            return ($parts[0] * 3600) + ($parts[1] * 60) + $parts[2];
        }

        return 0;
    }

    protected function loadFeedSettings(): array
    {
        $app = $this->currentApp ?: $this->resolveCurrentApp();
        $branding = is_array($app?->branding_json) ? $app->branding_json : [];
        $stored = ShortVideoPayload::safeArray($branding['short_video_settings'] ?? []);

        return $this->sanitizeFeedSettings($stored);
    }

    protected function sanitizeFeedSettings(array $settings): array
    {
        $defaults = [
            'enabled' => true,
            'default_feed' => 'latest',
            'show_category_tabs' => true,
            'show_search_filter' => true,
            'autoplay' => true,
            'loop_current_video' => false,
            'auto_scroll_next' => true,
            'smooth_scroll_speed' => 'fast',
            'show_progress_bar' => true,
            'show_like_button' => true,
            'show_comment_button' => true,
            'show_share_button' => true,
            'allow_save_favorite' => true,
            'require_login_for_interactions' => true,
            'show_banner_ads' => true,
            'show_native_ads' => true,
            'native_ads_after' => 3,
            'fallback_message' => 'No short videos available yet.',
            'target_route' => '/short-videos',
        ];

        $merged = array_merge($defaults, $settings);
        $speed = in_array((string) ($merged['smooth_scroll_speed'] ?? 'fast'), ['fast', 'normal', 'slow'], true)
            ? (string) $merged['smooth_scroll_speed']
            : 'fast';

        return [
            'enabled' => (bool) ($merged['enabled'] ?? true),
            'default_feed' => in_array((string) ($merged['default_feed'] ?? 'latest'), ['all', 'featured', 'latest', 'category'], true) ? (string) $merged['default_feed'] : 'latest',
            'show_category_tabs' => (bool) ($merged['show_category_tabs'] ?? true),
            'show_search_filter' => (bool) ($merged['show_search_filter'] ?? true),
            'autoplay' => (bool) ($merged['autoplay'] ?? true),
            'loop_current_video' => (bool) ($merged['loop_current_video'] ?? false),
            'auto_scroll_next' => (bool) ($merged['auto_scroll_next'] ?? true),
            'smooth_scroll_speed' => $speed,
            'show_progress_bar' => (bool) ($merged['show_progress_bar'] ?? true),
            'show_like_button' => (bool) ($merged['show_like_button'] ?? true),
            'show_comment_button' => (bool) ($merged['show_comment_button'] ?? true),
            'show_share_button' => (bool) ($merged['show_share_button'] ?? true),
            'allow_save_favorite' => (bool) ($merged['allow_save_favorite'] ?? true),
            'require_login_for_interactions' => (bool) ($merged['require_login_for_interactions'] ?? true),
            'show_banner_ads' => (bool) ($merged['show_banner_ads'] ?? true),
            'show_native_ads' => (bool) ($merged['show_native_ads'] ?? true),
            'native_ads_after' => max(1, min(20, (int) ($merged['native_ads_after'] ?? 3))),
            'fallback_message' => trim((string) ($merged['fallback_message'] ?? 'No short videos available yet.')) ?: 'No short videos available yet.',
            'target_route' => trim((string) ($merged['target_route'] ?? '/short-videos')) ?: '/short-videos',
        ];
    }

    protected function autoScrollRule(array $settings): string
    {
        if ((bool) ($settings['loop_current_video'] ?? false)) {
            return 'Loop is ON, so the frontend should replay the current video and should not auto-scroll.';
        }

        if ((bool) ($settings['auto_scroll_next'] ?? false)) {
            return 'When a video ends, the frontend should smoothly and quickly advance upward to the next short.';
        }

        return 'When a video ends, the frontend should stay on the current short until the user manually scrolls.';
    }

    public function assetUrl(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (Str::startsWith($value, ['http://', 'https://'])) {
            return $value;
        }

        try {
            return Storage::disk('public')->url($value);
        } catch (\Throwable) {
            return $value;
        }
    }

    protected function formatDate(mixed $value): string
    {
        if (! $value) {
            return '—';
        }

        try {
            return Carbon::parse($value)->format('M j, Y g:ia');
        } catch (\Throwable) {
            return (string) $value;
        }
    }

    protected function hasContentPostsTable(): bool
    {
        return Schema::hasTable('content_posts');
    }
}
