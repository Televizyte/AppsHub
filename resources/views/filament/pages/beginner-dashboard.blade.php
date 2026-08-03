<x-filament::page>
    @php
        $activeAppId = (int) (\App\Support\ActiveApp::ensureId() ?? 0);
        $activeApp = $activeAppId > 0 ? \App\Models\App::query()->find($activeAppId) : null;
        $isSuperAdmin = \App\Support\AdminAccess::super();
        $allowedAppIds = \App\Support\AdminAccess::allowedAppIds();
        $assignedApps = $allowedAppIds !== []
            ? \App\Models\App::query()->whereIn('id', $allowedAppIds)->where('is_active', true)->orderBy('name')->get()
            : collect();

        $defaultTabs = [
            'home' => 'Home',
            'watch' => 'Watch',
            'inspire' => 'Inspire',
            'explore' => 'Explore',
            'more' => 'More',
        ];

        $rawSections = $activeAppId > 0
            ? \App\Models\AppSection::query()
                ->withCount(['items'])
                ->where('app_id', $activeAppId)
                ->orderBy('tab_key')
                ->orderBy('sort_order')
                ->get()
            : collect();

        $sectionCount = $rawSections->count();
        $enabledSectionCount = $rawSections->where('is_enabled', true)->count();
        $itemCount = (int) $rawSections->sum('items_count');

        $enabledItemCount = $activeAppId > 0
            ? \App\Models\AppItem::query()
                ->whereIn('section_id', $rawSections->pluck('id')->all())
                ->where('is_enabled', true)
                ->count()
            : 0;

        $publishedContentCount = $activeAppId > 0
            ? \App\Models\ContentPost::query()
                ->where('app_id', $activeAppId)
                ->where('status', 'published')
                ->count()
            : 0;

        $watchLinkCount = $activeAppId > 0
            ? \App\Models\WatchLink::query()
                ->where('app_id', $activeAppId)
                ->count()
            : 0;

        $shortVideoCount = $activeAppId > 0
            ? \App\Models\ContentPost::query()
                ->where('app_id', $activeAppId)
                ->where('bucket', 'short_videos')
                ->count()
            : 0;

        $bookCount = $activeAppId > 0
            ? \App\Models\Book::query()
                ->where('app_id', $activeAppId)
                ->count()
            : 0;

        $mediaCount = $activeAppId > 0
            ? \App\Models\MediaAsset::query()
                ->where(function ($query) use ($activeAppId) {
                    $query->whereNull('app_id')->orWhere('app_id', $activeAppId);
                })
                ->where('is_active', true)
                ->count()
            : 0;

        $tabCounts = [];
        foreach ($defaultTabs as $tabKey => $label) {
            $tabCounts[$tabKey] = $rawSections->where('tab_key', $tabKey)->count();
        }

        $recentSections = $rawSections
            ->sortByDesc('updated_at')
            ->take(5)
            ->values();

        $appFeatureCards = collect([
            [
                'title' => 'Destination Builder',
                'description' => 'Build tabs, pages, sections, insert blocks, cards, layout, and preview payloads.',
                'icon' => '◎',
                'url' => url('/admin/destination-builder?tab=home'),
                'status' => $sectionCount . ' sections',
                'show' => \App\Support\AdminAccess::page('destination_builder') && \App\Support\AppCapabilities::enabled($activeApp, 'destination_builder'),
                'tone' => 'primary',
            ],
            [
                'title' => 'Template Library',
                'description' => 'Apply safe reusable templates for tabs, pages, sections, items, media blocks, widgets, and cards.',
                'icon' => '▣',
                'url' => url('/admin/template-library?tab=tabs'),
                'status' => 'Append only',
                'show' => \App\Support\AdminAccess::page('template_library'),
                'tone' => 'primary',
            ],
            [
                'title' => 'Content Channels',
                'description' => 'Manage articles, SOD, motivation, wordification, highlights, and related content.',
                'icon' => '▦',
                'url' => url('/admin/content-channels'),
                'status' => $publishedContentCount . ' published',
                'show' => \App\Support\AdminAccess::page('content_channels') && \App\Support\AppCapabilities::enabled($activeApp, 'content_channels'),
                'tone' => 'default',
            ],
            [
                'title' => 'Watch Links',
                'description' => 'Manage live streams, YouTube links, video links, and broadcast entries.',
                'icon' => '▻',
                'url' => url('/admin/watch-builder'),
                'status' => $watchLinkCount . ' links',
                'show' => \App\Support\AdminAccess::page('watch_builder') && \App\Support\AppCapabilities::enabled($activeApp, 'watch_links'),
                'tone' => 'default',
            ],
            [
                'title' => 'Short Videos',
                'description' => 'Manage short/reel-style video content used by this selected app.',
                'icon' => '▶',
                'url' => route('admin.beginner.content-posts.channel', ['bucket' => 'short_videos']),
                'status' => $shortVideoCount . ' videos',
                'show' => \App\Support\AdminAccess::page('short_video_engine') && \App\Support\AppCapabilities::enabled($activeApp, 'short_videos'),
                'tone' => 'default',
            ],
            [
                'title' => 'Books & Library',
                'description' => 'Manage app books, chapters, covers, and reading library content.',
                'icon' => '▤',
                'url' => route('admin.beginner.books.index'),
                'status' => $bookCount . ' books',
                'show' => \App\Support\AdminAccess::page('books') && \App\Support\AppCapabilities::enabled($activeApp, 'books'),
                'tone' => 'default',
            ],
            [
                'title' => 'Daily Scripture',
                'description' => 'Edit the current scripture card for this selected app.',
                'icon' => '✚',
                'url' => route('admin.beginner.daily.edit', ['kind' => 'scripture']),
                'status' => 'Manual ready',
                'show' => \App\Support\AdminAccess::page('content_channels') && \App\Support\AppCapabilities::enabled($activeApp, 'daily_scripture'),
                'tone' => 'default',
            ],
            [
                'title' => 'Daily Quote',
                'description' => 'Edit the current quote card for this selected app.',
                'icon' => '❝',
                'url' => route('admin.beginner.daily.edit', ['kind' => 'quote']),
                'status' => 'Manual ready',
                'show' => \App\Support\AdminAccess::page('content_channels') && \App\Support\AppCapabilities::enabled($activeApp, 'daily_quote'),
                'tone' => 'default',
            ],
            [
                'title' => 'Media Library',
                'description' => 'Manage images, logos, banners, thumbnails, videos, and other files.',
                'icon' => '▧',
                'url' => url('/admin/media-assets'),
                'status' => $mediaCount . ' assets',
                'show' => \App\Support\AdminAccess::page('media_library') && \App\Support\AppCapabilities::enabled($activeApp, 'media'),
                'tone' => 'default',
            ],
            [
                'title' => 'App Settings',
                'description' => 'Edit name, branding, logo, splash, theme, store metadata, and API settings.',
                'icon' => '⚙',
                'url' => $activeApp ? url('/admin/apps/' . $activeApp->id . '/edit') : url('/admin/apps'),
                'status' => 'Branding',
                'show' => \App\Support\AdminAccess::page('app_settings') && \App\Support\AppCapabilities::enabled($activeApp, 'app_settings'),
                'tone' => 'default',
            ],
            [
                'title' => 'App Capabilities',
                'description' => 'Enable or disable the tools this selected app should use.',
                'icon' => '☷',
                'url' => url('/admin/app-capabilities'),
                'status' => 'Switchboard',
                'show' => \App\Support\AdminAccess::page('app_capabilities'),
                'tone' => 'default',
            ],
        ])->where('show', true)->values();
    @endphp


    <style>
        /* AppsHub staff app switcher cleanup v2: assigned-app switching now lives in sidebar. */
    </style>

    <div class="dxm-dashboard-overview" data-dxm-page-key="beginner-dashboard-overview">

        <section class="dxm-overview-hero">
            <div class="dxm-overview-hero__main">
                <div class="dxm-kicker">Selected App Workspace</div>
                <h1>{{ $activeApp?->name ?? 'No App Selected' }}</h1>
                <p>
                    This dashboard is now an overview only. Use it to enter app tools quickly.
                    Actual page/section/item editing now lives inside the official Destination Builder, with templates and preview-first controls.
                </p>

                <div class="dxm-chip-row">
                    @if($isSuperAdmin || \App\Support\AdminAccess::page('control_center'))
                        <a class="dxm-chip" href="{{ url('/admin/control-center') }}">← Main Control Center</a>
                    @endif
                    @if(\App\Support\AdminAccess::page('destination_builder'))
                        <a class="dxm-chip" href="{{ url('/admin/destination-builder?tab=home') }}">Open Destination Builder</a>
                    <a class="dxm-chip" href="{{ url('/admin/template-library?tab=tabs') }}">Template Library</a>
                    @endif
                    @if(\App\Support\AdminAccess::page('app_capabilities'))
                        <a class="dxm-chip" href="{{ url('/admin/app-capabilities') }}">App Capabilities</a>
                    @endif
                    <span class="dxm-chip">App ID: {{ $activeAppId ?: 'Not selected' }}</span>
                </div>
            </div>

            <div class="dxm-overview-stats">
                <div><strong>{{ $enabledSectionCount }}/{{ $sectionCount }}</strong><span>Enabled Sections</span></div>
                <div><strong>{{ $enabledItemCount }}/{{ $itemCount }}</strong><span>Enabled Items</span></div>
                <div><strong>{{ $publishedContentCount }}</strong><span>Published Posts</span></div>
                <div><strong>{{ $mediaCount }}</strong><span>Media Assets</span></div>
            </div>
        </section>

        <section class="dxm-dashboard-panel">
            <div class="dxm-panel-heading">
                <div>
                    <h2>App Workspace Tools</h2>
                    <p>Only tools enabled for this selected app are shown here.</p>
                </div>
                @if(\App\Support\AdminAccess::page('destination_builder'))
                    <a href="{{ url('/admin/destination-builder?tab=home') }}" class="dxm-mini-btn dxm-mini-btn--primary">Open Builder</a>
                @endif
            </div>

            <div class="dxm-feature-grid-clean">
                @forelse ($appFeatureCards as $card)
                    <a href="{{ $card['url'] }}" class="dxm-feature-card-clean {{ $card['tone'] === 'primary' ? 'is-primary' : '' }}">
                        <div class="dxm-feature-icon-clean">{{ $card['icon'] }}</div>
                        <div>
                            <strong>{{ $card['title'] }}</strong>
                            <small>{{ $card['description'] }}</small>
                            <em>{{ $card['status'] }}</em>
                        </div>
                    </a>
                @empty
                    <div class="dxm-empty-note-clean">
                        No app-specific feature is enabled yet. Open App Capabilities to enable the tools needed by this app.
                    </div>
                @endforelse
            </div>
        </section>

        <section class="dxm-dashboard-panel">
            <div class="dxm-panel-heading">
                <div>
                    <h2>Destination Summary</h2>
                    <p>Quick view of how many backend sections each frontend tab currently has.</p>
                </div>
            </div>

            <div class="dxm-destination-summary-grid">
                @foreach ($defaultTabs as $tabKey => $label)
                    <a href="{{ url('/admin/destination-builder?tab=' . $tabKey) }}" class="dxm-destination-summary-card">
                        <strong>{{ $label }}</strong>
                        <span>{{ $tabCounts[$tabKey] ?? 0 }} sections</span>
                    </a>
                @endforeach
            </div>
        </section>

        <section class="dxm-dashboard-panel">
            <div class="dxm-panel-heading">
                <div>
                    <h2>Recently Updated Blocks</h2>
                    <p>Quickly return to recent sections without scrolling through a long builder page.</p>
                </div>
            </div>

            <div class="dxm-recent-list">
                @forelse ($recentSections as $section)
                    <div class="dxm-recent-row">
                        <div>
                            <strong>{{ $section->title ?: $section->key }}</strong>
                            <small>{{ strtoupper((string) $section->tab_key) }} · {{ $section->template ?: 'default' }} · {{ $section->items_count }} items</small>
                        </div>
                        @if(\App\Support\AdminAccess::page('destination_builder'))
                            <div class="dxm-recent-actions">
                                <a href="{{ url('/admin/destination-builder?tab=' . urlencode((string) $section->tab_key) . '#section-' . $section->id) }}" class="dxm-mini-btn">Open</a>
                                <a href="{{ route('admin.beginner.sections.edit', $section) }}?return=destination&tab={{ urlencode((string) $section->tab_key) }}" class="dxm-mini-btn">Edit</a>
                            </div>
                        @endif
                    </div>
                @empty
                    <div class="dxm-empty-note-clean">No sections have been created for this app yet.</div>
                @endforelse
            </div>
        </section>
    </div>
</x-filament::page>

