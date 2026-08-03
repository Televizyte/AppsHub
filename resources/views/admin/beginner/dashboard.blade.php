@extends('layouts.beginner')

@section('title', 'App Workspace')
@section('eyebrow', 'AppsHub App Workspace')
@section('page_title', 'App Workspace')
@section('page_description', 'Manage the selected app in one simple beginner-friendly control panel.')

@php
    $tabs = [
        'home' => 'Home',
        'watch' => 'Watch',
        'inspire' => 'Inspire',
        'explore' => 'Explore',
        'more' => 'More',
    ];

    $activeTab = request('tab', 'home');

    if (! array_key_exists($activeTab, $tabs)) {
        $activeTab = 'home';
    }

    $activeAppId = 0;
    $activeApp = null;

    try {
        $activeAppId = (int) (\App\Support\ActiveApp::ensureId() ?? 0);

        if ($activeAppId > 0) {
            $activeApp = \App\Models\App::query()->find($activeAppId);
        }
    } catch (\Throwable $e) {
        $activeAppId = 0;
        $activeApp = null;
    }

    $safeSections = collect($sections ?? []);

    $sectionsForActiveTab = $safeSections
        ->where('tab_key', $activeTab)
        ->sortBy([
            ['sort_order', 'asc'],
            ['id', 'asc'],
        ]);

    $allItems = $safeSections->flatMap(function ($section) {
        return $section->items ?? collect();
    });

    $enabledSectionsCount = $safeSections->where('is_enabled', true)->count();
    $totalSectionsCount = $safeSections->count();
    $enabledItemsCount = $allItems->where('is_enabled', true)->count();
    $totalItemsCount = $allItems->count();

    $tabCounts = [];

    foreach ($tabs as $key => $label) {
        $tabCounts[$key] = $safeSections->where('tab_key', $key)->count();
    }

    $workspaceCards = [
        [
            'title' => 'Home Manager',
            'subtitle' => 'Control homepage sections, quick tools, daily cards, banners, and featured blocks.',
            'icon' => '🏠',
            'url' => '/admin/beginner-dashboard?tab=home',
            'status' => 'Ready',
            'kind' => 'live',
        ],
        [
            'title' => 'Watch Manager',
            'subtitle' => 'Manage live stream, TV links, broadcast cards, and watch tab arrangement.',
            'icon' => '📺',
            'url' => '/admin/beginner-dashboard?tab=watch',
            'status' => 'Ready',
            'kind' => 'live',
        ],
        [
            'title' => 'Inspire Manager',
            'subtitle' => 'Manage SOD, articles, motivation, wordification, highlights, and featured videos.',
            'icon' => '✨',
            'url' => '/admin/beginner-dashboard?tab=inspire',
            'status' => 'Ready',
            'kind' => 'live',
        ],
        [
            'title' => 'Explore Manager',
            'subtitle' => 'Control Bible, Notes, Quote Creator, Books, games, quizzes, and tool shortcuts.',
            'icon' => '🧭',
            'url' => '/admin/beginner-dashboard?tab=explore',
            'status' => 'Ready',
            'kind' => 'live',
        ],
        [
            'title' => 'Short Videos',
            'subtitle' => 'Upload, edit, publish, and arrange short videos used on Home and Inspire.',
            'icon' => '🎬',
            'url' => route('admin.beginner.content-posts.channel', ['bucket' => 'short_videos']),
            'status' => 'Engine Active',
            'kind' => 'highlight',
        ],
        [
            'title' => 'Books & Library',
            'subtitle' => 'Create books, manage chapters, covers, reading content, and library publishing.',
            'icon' => '📚',
            'url' => route('admin.beginner.books.index'),
            'status' => 'Engine Active',
            'kind' => 'highlight',
        ],
        [
            'title' => 'Daily Scripture',
            'subtitle' => 'Edit today’s scripture card and prepare the future auto-rotation engine.',
            'icon' => '📖',
            'url' => route('admin.beginner.daily.edit', ['kind' => 'scripture']),
            'status' => 'Manual Ready',
            'kind' => 'live',
        ],
        [
            'title' => 'Daily Quote',
            'subtitle' => 'Edit today’s quote card and prepare future scheduled/automatic publishing.',
            'icon' => '💬',
            'url' => route('admin.beginner.daily.edit', ['kind' => 'quote']),
            'status' => 'Manual Ready',
            'kind' => 'live',
        ],
        [
            'title' => 'Quiz Manager',
            'subtitle' => 'Bible Quiz, SOD Quiz, Sermon Quiz, and article-based quiz engine.',
            'icon' => '🧠',
            'url' => null,
            'status' => 'Queued',
            'kind' => 'queued',
        ],
        [
            'title' => 'Ads Placements',
            'subtitle' => 'Backend control for banner, native, interstitial, and per-page ad settings.',
            'icon' => '📢',
            'url' => null,
            'status' => 'Queued',
            'kind' => 'queued',
        ],
        [
            'title' => 'Notifications',
            'subtitle' => 'Instant, scheduled, and repeating push notifications with deep links.',
            'icon' => '🔔',
            'url' => null,
            'status' => 'Queued',
            'kind' => 'queued',
        ],
        [
            'title' => 'Usage Monitor',
            'subtitle' => 'Future owner view for API, AI, media, notifications, and engine usage per app.',
            'icon' => '📊',
            'url' => null,
            'status' => 'Owner Tool',
            'kind' => 'queued',
        ],
    ];
@endphp

@section('form_title', 'Selected App Control Panel')
@section('form_description', 'Beginner-friendly workspace for managing the active app safely.')
@section('preview_description', 'Quick overview of app structure, active sections, and future engine readiness.')

@section('dock_actions')
    <a href="{{ route('admin.beginner.sections.create') }}?tab={{ $activeTab }}&return=dashboard" class="dxm-btn dxm-btn--primary">
        + Add Section
    </a>
@endsection

@section('form')
    @if (session('status'))
        <div class="dxm-alert">{{ session('status') }}</div>
    @endif

    <div class="workspace-hero">
        <div>
            <span class="workspace-eyebrow">Currently Editing</span>
            <h2>{{ $activeApp?->name ?? $activeApp?->title ?? 'Selected App' }}</h2>
            <p>
                This workspace controls only the selected app. Content, media, sections, videos, books,
                daily cards, ads, notifications, and future engines should remain app-scoped.
            </p>
        </div>

        <div class="workspace-hero__meta">
            <div>
                <strong>{{ $totalSectionsCount }}</strong>
                <span>Sections</span>
            </div>
            <div>
                <strong>{{ $totalItemsCount }}</strong>
                <span>Items</span>
            </div>
            <div>
                <strong>{{ $activeAppId > 0 ? 'App #' . $activeAppId : 'No App' }}</strong>
                <span>Scope</span>
            </div>
        </div>
    </div>

    <div class="workspace-grid">
        @foreach ($workspaceCards as $card)
            @if ($card['url'])
                <a href="{{ $card['url'] }}" class="workspace-card workspace-card--{{ $card['kind'] }}">
                    <div class="workspace-card__icon">{{ $card['icon'] }}</div>
                    <div>
                        <strong>{{ $card['title'] }}</strong>
                        <small>{{ $card['subtitle'] }}</small>
                        <span>{{ $card['status'] }}</span>
                    </div>
                </a>
            @else
                <div class="workspace-card workspace-card--{{ $card['kind'] }} is-disabled">
                    <div class="workspace-card__icon">{{ $card['icon'] }}</div>
                    <div>
                        <strong>{{ $card['title'] }}</strong>
                        <small>{{ $card['subtitle'] }}</small>
                        <span>{{ $card['status'] }}</span>
                    </div>
                </div>
            @endif
        @endforeach
    </div>

    <div class="workspace-divider">
        <div>
            <span class="workspace-eyebrow">Destination Builder</span>
            <h3>Frontend Tab Sections</h3>
            <p>
                These are the live sections that the Flutter render engine can consume from AppsHub.
                Keep using this builder for Home, Watch, Inspire, Explore, and More.
            </p>
        </div>
    </div>

    <div class="dxm-tabs">
        @foreach ($tabs as $key => $label)
            <a href="?tab={{ $key }}" class="dxm-tab {{ $activeTab === $key ? 'active' : '' }}">
                {{ $label }}
                <span>{{ $tabCounts[$key] ?? 0 }}</span>
            </a>
        @endforeach
    </div>

    <div class="dxm-section-list">
        @forelse ($sectionsForActiveTab as $section)
            <div class="dxm-section-card">
                <div class="dxm-section-header">
                    <div>
                        <h3>{{ $section->title }}</h3>
                        <small>
                            {{ $section->subtitle ?: 'No subtitle' }}
                            · Key: {{ $section->key }}
                            · Template: {{ $section->template }}
                            · Order: {{ $section->sort_order }}
                            · {{ $section->is_enabled ? 'Enabled' : 'Disabled' }}
                        </small>
                    </div>

                    <div class="dxm-action-row">
                        <form method="POST" action="{{ route('admin.beginner.sections.move', [$section, 'up']) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="dxm-btn dxm-btn--small">↑</button>
                        </form>

                        <form method="POST" action="{{ route('admin.beginner.sections.move', [$section, 'down']) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit" class="dxm-btn dxm-btn--small">↓</button>
                        </form>

                        <a href="{{ route('admin.beginner.sections.edit', $section) }}?return=dashboard&tab={{ $activeTab }}"
                           class="dxm-btn dxm-btn--small">Edit Section</a>

                        <form method="POST"
                              action="{{ route('admin.beginner.sections.destroy', $section) }}"
                              onsubmit="return confirm('Delete this section and all its items?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="dxm-btn dxm-btn--danger dxm-btn--small">Delete</button>
                        </form>
                    </div>
                </div>

                <div class="dxm-item-row">
                    @forelse ($section->items as $item)
                        <div class="dxm-item-card">
                            <div class="dxm-item-title">
                                {{ $item->title }}
                                <small>
                                    Order: {{ $item->sort_order }}
                                    · Type: {{ $item->type ?: 'link' }}
                                    · {{ $item->is_enabled ? 'Enabled' : 'Disabled' }}
                                </small>
                            </div>

                            <div class="dxm-item-actions">
                                <form method="POST" action="{{ route('admin.beginner.items.move', [$item, 'up']) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="dxm-btn dxm-btn--small">↑</button>
                                </form>

                                <form method="POST" action="{{ route('admin.beginner.items.move', [$item, 'down']) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="dxm-btn dxm-btn--small">↓</button>
                                </form>

                                <a href="{{ route('admin.beginner.items.edit', $item) }}?return=dashboard&tab={{ $activeTab }}"
                                   class="dxm-btn dxm-btn--small">Edit</a>

                                <form method="POST"
                                      action="{{ route('admin.beginner.items.destroy', $item) }}"
                                      onsubmit="return confirm('Delete this item?')">
                                    @csrf
                                    @method('DELETE')
                                    <input type="hidden" name="current_tab" value="{{ $activeTab }}">
                                    <input type="hidden" name="return" value="dashboard">
                                    <button type="submit" class="dxm-btn dxm-btn--danger dxm-btn--small">
                                        Delete
                                    </button>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="dxm-empty-card">
                            <strong>No items yet</strong>
                            <small>Add the first card, shortcut, video entry, tool link, or content item for this section.</small>
                        </div>
                    @endforelse

                    <a href="{{ route('admin.beginner.items.create') }}?section_id={{ $section->id }}&return=dashboard&tab={{ $activeTab }}"
                       class="dxm-add-card">+ Add Item</a>
                </div>
            </div>
        @empty
            <div class="empty-workspace-state">
                <strong>No sections yet for {{ $tabs[$activeTab] }}</strong>
                <p>Create the first section for this tab. The frontend can still use fallbacks, but production control should come from AppsHub.</p>
                <a href="{{ route('admin.beginner.sections.create') }}?tab={{ $activeTab }}&return=dashboard" class="dxm-btn dxm-btn--primary">
                    + Create {{ $tabs[$activeTab] }} Section
                </a>
            </div>
        @endforelse
    </div>
@endsection

@section('preview')
    <div class="workspace-preview">
        <div class="preview-card preview-card--active">
            <span>Active App</span>
            <strong>{{ $activeApp?->name ?? $activeApp?->title ?? 'Selected App' }}</strong>
            <small>
                All records shown here should remain scoped to this app. Avoid mixing Dunamis TV, Celebration TV,
                or future apps.
            </small>
        </div>

        <div class="preview-card">
            <span>Workspace Health</span>
            <strong>{{ $enabledSectionsCount }} / {{ $totalSectionsCount }} sections enabled</strong>
            <small>{{ $enabledItemsCount }} / {{ $totalItemsCount }} items enabled.</small>
        </div>

        <div class="preview-card">
            <span>Live Engines Already Present</span>
            <ul>
                <li>App Sections / Items</li>
                <li>Content Channels</li>
                <li>Short Videos</li>
                <li>Books & Library</li>
                <li>Daily Scripture / Quote</li>
                <li>Media Library</li>
            </ul>
        </div>

        <div class="preview-card">
            <span>Queued Engines</span>
            <ul>
                <li>Quiz Engine</li>
                <li>Ads Engine Control</li>
                <li>Notification Campaigns</li>
                <li>Usage / API / AI Monitor</li>
                <li>User Roles Dashboard</li>
            </ul>
        </div>

        <div class="preview-card preview-card--note">
            <span>Beginner Mode Rule</span>
            <strong>Keep it simple</strong>
            <small>
                Normal users should manage the selected app through friendly cards.
                Owner-only engine monitoring can come later without disturbing working tools.
            </small>
        </div>
    </div>
@endsection

@push('styles')
    <style>
        .workspace-hero{
            display:grid;
            grid-template-columns:minmax(0,1fr) auto;
            gap:16px;
            align-items:center;
            border:1px solid rgba(34,211,238,.22);
            border-radius:24px;
            padding:18px;
            margin-bottom:14px;
            background:
                radial-gradient(circle at top left,rgba(34,211,238,.16),transparent 42%),
                linear-gradient(135deg,rgba(2,6,23,.62),rgba(15,23,42,.42));
        }

        .workspace-eyebrow{
            display:block;
            color:rgba(207,250,254,.88);
            font-size:11px;
            font-weight:950;
            letter-spacing:.08em;
            text-transform:uppercase;
            margin-bottom:7px;
        }

        .workspace-hero h2{
            margin:0;
            color:#fff;
            font-size:26px;
            letter-spacing:-.04em;
            line-height:1.1;
        }

        .workspace-hero p{
            margin:8px 0 0;
            color:rgba(255,255,255,.66);
            font-size:13px;
            line-height:1.55;
            max-width:760px;
        }

        .workspace-hero__meta{
            display:grid;
            grid-template-columns:repeat(3,minmax(90px,1fr));
            gap:10px;
        }

        .workspace-hero__meta div{
            border:1px solid rgba(255,255,255,.09);
            border-radius:18px;
            background:rgba(2,6,23,.42);
            padding:13px;
            text-align:center;
        }

        .workspace-hero__meta strong{
            display:block;
            color:#fff;
            font-size:18px;
            line-height:1.2;
        }

        .workspace-hero__meta span{
            display:block;
            color:rgba(255,255,255,.52);
            font-size:11px;
            font-weight:850;
            margin-top:4px;
        }

        .workspace-grid{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(230px,1fr));
            gap:11px;
            margin-bottom:16px;
        }

        .workspace-card{
            display:grid;
            grid-template-columns:48px minmax(0,1fr);
            gap:12px;
            align-items:flex-start;
            border:1px solid rgba(255,255,255,.09);
            border-radius:20px;
            background:rgba(2,6,23,.32);
            padding:14px;
            min-height:128px;
            color:#fff;
            transition:.18s ease;
        }

        .workspace-card:hover{
            transform:translateY(-1px);
            border-color:rgba(34,211,238,.35);
            background:rgba(8,47,73,.20);
        }

        .workspace-card.is-disabled{
            opacity:.78;
            cursor:not-allowed;
        }

        .workspace-card.is-disabled:hover{
            transform:none;
            border-color:rgba(255,255,255,.09);
            background:rgba(2,6,23,.32);
        }

        .workspace-card--highlight{
            border-color:rgba(34,211,238,.28);
            background:linear-gradient(135deg,rgba(34,211,238,.10),rgba(168,85,247,.07));
        }

        .workspace-card--queued{
            border-style:dashed;
        }

        .workspace-card__icon{
            width:48px;
            height:48px;
            border-radius:17px;
            display:grid;
            place-items:center;
            background:rgba(255,255,255,.07);
            font-size:23px;
        }

        .workspace-card strong{
            display:block;
            color:#fff;
            font-size:14px;
            line-height:1.25;
        }

        .workspace-card small{
            display:block;
            color:rgba(255,255,255,.58);
            font-size:11.5px;
            line-height:1.45;
            margin-top:5px;
        }

        .workspace-card span{
            display:inline-flex;
            align-items:center;
            margin-top:10px;
            padding:5px 8px;
            border-radius:999px;
            border:1px solid rgba(255,255,255,.08);
            background:rgba(255,255,255,.055);
            color:rgba(255,255,255,.74);
            font-size:10.5px;
            font-weight:900;
            text-transform:uppercase;
            letter-spacing:.05em;
        }

        .workspace-divider{
            border:1px solid rgba(255,255,255,.08);
            border-radius:20px;
            padding:14px;
            margin:3px 0 13px;
            background:rgba(255,255,255,.035);
        }

        .workspace-divider h3{
            margin:0;
            color:#fff;
            font-size:17px;
            line-height:1.2;
        }

        .workspace-divider p{
            margin:7px 0 0;
            color:rgba(255,255,255,.60);
            font-size:12.5px;
            line-height:1.5;
        }

        .dxm-tabs{
            display:flex;
            gap:8px;
            flex-wrap:wrap;
            margin-bottom:14px;
        }

        .dxm-tab{
            display:inline-flex;
            align-items:center;
            justify-content:center;
            gap:8px;
            min-height:36px;
            padding:9px 13px;
            border-radius:999px;
            border:1px solid rgba(255,255,255,.10);
            background:rgba(255,255,255,.04);
            color:rgba(255,255,255,.78);
            font-size:12px;
            font-weight:900;
        }

        .dxm-tab span{
            min-width:21px;
            height:21px;
            border-radius:999px;
            display:inline-flex;
            align-items:center;
            justify-content:center;
            background:rgba(255,255,255,.08);
            color:rgba(255,255,255,.72);
            font-size:10.5px;
        }

        .dxm-tab.active,
        .dxm-tab:hover{
            color:#fff;
            border-color:rgba(34,211,238,.46);
            background:rgba(34,211,238,.12);
        }

        .dxm-section-list{
            display:flex;
            flex-direction:column;
            gap:14px;
        }

        .dxm-section-card{
            border:1px solid rgba(255,255,255,.10);
            border-radius:20px;
            background:rgba(2,6,23,.35);
            overflow:hidden;
        }

        .dxm-section-header{
            display:flex;
            align-items:flex-start;
            justify-content:space-between;
            gap:12px;
            padding:14px;
            border-bottom:1px solid rgba(255,255,255,.08);
            background:linear-gradient(135deg,rgba(34,211,238,.07),rgba(168,85,247,.04));
        }

        .dxm-section-header h3{
            margin:0;
            color:#fff;
            font-size:15px;
            line-height:1.25;
        }

        .dxm-section-header small,
        .dxm-item-title small{
            display:block;
            margin-top:4px;
            color:rgba(255,255,255,.55);
            font-size:11px;
            line-height:1.35;
        }

        .dxm-action-row,
        .dxm-item-actions{
            display:flex;
            align-items:center;
            justify-content:flex-end;
            gap:6px;
            flex-wrap:wrap;
        }

        .dxm-btn--small{
            min-height:32px;
            padding:6px 10px;
            font-size:11px;
            border-radius:10px;
        }

        .dxm-btn--danger{
            border-color:rgba(248,113,113,.45);
            background:rgba(127,29,29,.20);
        }

        .dxm-btn--danger:hover{
            border-color:rgba(248,113,113,.70);
            background:rgba(127,29,29,.34);
        }

        .dxm-item-row{
            display:grid;
            grid-template-columns:repeat(auto-fit,minmax(230px,1fr));
            gap:10px;
            padding:14px;
        }

        .dxm-item-card,
        .dxm-empty-card{
            border:1px solid rgba(255,255,255,.08);
            border-radius:16px;
            background:rgba(255,255,255,.035);
            padding:12px;
            min-height:98px;
            display:flex;
            flex-direction:column;
            justify-content:space-between;
            gap:12px;
        }

        .dxm-empty-card{
            border-style:dashed;
            justify-content:center;
        }

        .dxm-empty-card strong{
            color:#fff;
            font-size:13px;
        }

        .dxm-empty-card small{
            color:rgba(255,255,255,.54);
            line-height:1.45;
        }

        .dxm-item-title{
            color:#fff;
            font-size:13px;
            font-weight:900;
            line-height:1.35;
        }

        .dxm-add-card{
            min-height:98px;
            border:1px dashed rgba(34,211,238,.38);
            border-radius:16px;
            background:rgba(34,211,238,.06);
            color:rgba(207,250,254,.95);
            display:flex;
            align-items:center;
            justify-content:center;
            font-size:13px;
            font-weight:900;
        }

        .dxm-add-card:hover{
            background:rgba(34,211,238,.10);
            border-color:rgba(34,211,238,.62);
        }

        .empty-workspace-state{
            border:1px dashed rgba(255,255,255,.18);
            border-radius:22px;
            padding:24px;
            text-align:center;
            background:rgba(2,6,23,.28);
        }

        .empty-workspace-state strong{
            display:block;
            color:#fff;
            font-size:17px;
        }

        .empty-workspace-state p{
            color:rgba(255,255,255,.60);
            font-size:13px;
            line-height:1.55;
            max-width:560px;
            margin:9px auto 16px;
        }

        .workspace-preview{
            display:flex;
            flex-direction:column;
            gap:12px;
        }

        .preview-card{
            border:1px solid rgba(255,255,255,.09);
            border-radius:18px;
            background:rgba(2,6,23,.34);
            padding:14px;
            color:#fff;
        }

        .preview-card--active{
            border-color:rgba(34,211,238,.28);
            background:linear-gradient(135deg,rgba(34,211,238,.10),rgba(2,6,23,.34));
        }

        .preview-card--note{
            border-color:rgba(168,85,247,.28);
            background:linear-gradient(135deg,rgba(168,85,247,.10),rgba(2,6,23,.34));
        }

        .preview-card span{
            display:block;
            color:rgba(255,255,255,.52);
            font-size:11px;
            font-weight:950;
            text-transform:uppercase;
            letter-spacing:.07em;
            margin-bottom:6px;
        }

        .preview-card strong{
            display:block;
            color:#fff;
            font-size:15px;
            line-height:1.25;
        }

        .preview-card small{
            display:block;
            color:rgba(255,255,255,.60);
            line-height:1.5;
            margin-top:7px;
        }

        .preview-card ul{
            margin:8px 0 0;
            padding-left:18px;
            color:rgba(255,255,255,.68);
            font-size:12.5px;
            line-height:1.7;
        }

        @media(max-width:900px){
            .workspace-hero{
                grid-template-columns:1fr;
            }

            .workspace-hero__meta{
                grid-template-columns:repeat(3,1fr);
            }
        }

        @media(max-width:760px){
            .workspace-hero__meta{
                grid-template-columns:1fr;
            }

            .dxm-section-header{
                flex-direction:column;
            }

            .dxm-action-row,
            .dxm-item-actions{
                justify-content:flex-start;
            }
        }
    </style>
@endpush
