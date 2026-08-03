<x-filament-panels::page>
    @php
        $stats = $this->stats();
        $buckets = $this->bucketOptions();
        $types = $this->postTypeOptions();
        $recentPosts = $this->posts('recent', 8);
        $publishedPosts = $this->posts('published', 12);
        $pendingPosts = $this->posts('pending', 12);
    @endphp

    <style>
        .fe-shell{display:flex;flex-direction:column;gap:14px;color:#e5e7eb}.fe-hero{border:1px solid rgba(14,165,233,.25);border-radius:26px;padding:18px;background:linear-gradient(135deg,rgba(88,28,135,.34),rgba(15,23,42,.96) 50%,rgba(14,165,233,.15));box-shadow:0 18px 50px rgba(2,6,23,.25);position:relative;overflow:hidden}.fe-hero:after{content:"";position:absolute;right:-70px;top:-80px;width:230px;height:230px;border-radius:999px;background:radial-gradient(circle,rgba(14,165,233,.34),transparent 68%)}.fe-hero-top{display:flex;justify-content:space-between;gap:14px;align-items:flex-start;position:relative;z-index:1}.fe-kicker{display:inline-flex;align-items:center;gap:7px;padding:6px 10px;border-radius:999px;font-size:11px;font-weight:900;letter-spacing:.08em;text-transform:uppercase;color:#a5f3fc;background:rgba(8,145,178,.16);border:1px solid rgba(103,232,249,.22)}.fe-title{margin-top:9px;font-size:25px;line-height:1.05;font-weight:950;color:#fff;letter-spacing:-.03em}.fe-desc{margin-top:8px;max-width:820px;color:#cbd5e1;font-size:12.5px;line-height:1.55}.fe-app-pill{min-width:178px;padding:11px 13px;border-radius:18px;background:rgba(15,23,42,.75);border:1px solid rgba(148,163,184,.18);text-align:right}.fe-app-pill span{display:block;font-size:10px;color:#94a3b8;text-transform:uppercase;letter-spacing:.08em;font-weight:900}.fe-app-pill strong{display:block;color:#fff;font-size:13px}.fe-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px;position:relative;z-index:1}.fe-btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;min-height:34px;padding:8px 12px;border-radius:12px;border:1px solid rgba(148,163,184,.22);background:rgba(15,23,42,.72);color:#e2e8f0;font-size:12px;font-weight:900;text-decoration:none;transition:.18s ease;cursor:pointer}.fe-btn:hover{transform:translateY(-1px);border-color:rgba(103,232,249,.45);color:#fff}.fe-btn.primary{background:linear-gradient(135deg,#7c3aed,#06b6d4);border-color:transparent;color:#fff}.fe-btn.danger{color:#fecaca;border-color:rgba(248,113,113,.3);background:rgba(127,29,29,.2)}.fe-btn.small{min-height:30px;padding:6px 10px;font-size:11px}.fe-tabs{display:flex;flex-wrap:wrap;gap:8px;padding:7px;border-radius:18px;background:rgba(15,23,42,.72);border:1px solid rgba(148,163,184,.16)}.fe-tab{border:0;cursor:pointer;border-radius:13px;padding:9px 12px;background:transparent;color:#94a3b8;font-size:12px;font-weight:950}.fe-tab.active{background:linear-gradient(135deg,rgba(124,58,237,.95),rgba(14,165,233,.72));color:#fff;box-shadow:0 10px 24px rgba(14,165,233,.16)}.fe-card{border:1px solid rgba(148,163,184,.16);border-radius:22px;background:rgba(15,23,42,.78);box-shadow:0 14px 40px rgba(2,6,23,.15);overflow:hidden}.fe-card-pad{padding:14px}.fe-card-head{display:flex;justify-content:space-between;gap:10px;align-items:flex-start;margin-bottom:12px}.fe-card-title{font-size:15px;font-weight:950;color:#fff}.fe-card-note{margin-top:3px;font-size:12px;line-height:1.45;color:#94a3b8}.fe-stats{display:grid;grid-template-columns:repeat(6,minmax(0,1fr));gap:10px}.fe-stat{border:1px solid rgba(148,163,184,.14);border-radius:18px;padding:12px;background:linear-gradient(180deg,rgba(30,41,59,.72),rgba(15,23,42,.88));min-height:86px}.fe-stat span{font-size:10px;letter-spacing:.08em;text-transform:uppercase;color:#94a3b8;font-weight:950}.fe-stat strong{display:block;margin-top:7px;font-size:24px;line-height:1;color:#fff;font-weight:950}.fe-stat small{display:block;margin-top:7px;color:#a5b4fc;font-size:11px}.fe-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:12px}.fe-two{display:grid;grid-template-columns:minmax(0,1.05fr) minmax(280px,.95fr);gap:14px;align-items:start}.fe-type-grid{display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:9px}.fe-type{cursor:pointer;text-align:left;border:1px solid rgba(148,163,184,.15);border-radius:17px;padding:11px;background:rgba(2,6,23,.35);transition:.18s ease}.fe-type.active,.fe-type:hover{border-color:rgba(34,211,238,.48);background:linear-gradient(135deg,rgba(8,145,178,.22),rgba(124,58,237,.15));transform:translateY(-1px)}.fe-type{display:grid;grid-template-columns:34px minmax(0,1fr);gap:10px;align-items:start}.fe-type-icon{width:32px;height:32px;border-radius:12px;border:1px solid rgba(34,211,238,.34);display:grid;place-items:center;color:#a5f3fc;background:rgba(8,145,178,.10)}.fe-type-icon svg{width:17px;height:17px;stroke:currentColor;fill:none;stroke-width:2;stroke-linecap:round;stroke-linejoin:round}.fe-type b{display:block;color:#fff;font-size:13px}.fe-type small{display:block;margin-top:5px;color:#94a3b8;font-size:11px;line-height:1.35}.fe-form-grid{display:grid;grid-template-columns:repeat(12,minmax(0,1fr));gap:10px}.fe-field{grid-column:span 6}.fe-field.full{grid-column:1/-1}.fe-label{display:block;font-size:11px;color:#94a3b8;font-weight:950;margin-bottom:5px}.fe-input,.fe-select,.fe-textarea{width:100%;border-radius:13px;border:1px solid rgba(148,163,184,.22);background:rgba(2,6,23,.44)!important;color:#e5e7eb;padding:9px 10px;font-size:12px;outline:none}.fe-textarea{min-height:94px;resize:vertical}.fe-select{appearance:none!important;-webkit-appearance:none!important;-moz-appearance:none!important;background-image:none!important;padding-right:30px!important}.fe-select-wrap{position:relative}.fe-select-wrap:after{content:"⌄";position:absolute;right:11px;top:50%;transform:translateY(-53%);color:#94a3b8;font-size:13px;pointer-events:none}.fe-toggle-row{display:flex;align-items:center;justify-content:space-between;gap:10px;border:1px solid rgba(148,163,184,.14);border-radius:16px;padding:11px;background:rgba(2,6,23,.32)}.fe-toggle-row strong{font-size:12.5px;color:#fff}.fe-toggle-row p{font-size:10.5px;color:#94a3b8;margin-top:3px}.fe-switch{width:44px;height:24px;border-radius:999px;border:0;background:rgba(148,163,184,.3);position:relative;cursor:pointer;flex:0 0 auto}.fe-switch:before{content:"";position:absolute;width:18px;height:18px;left:3px;top:3px;border-radius:999px;background:#fff;transition:.18s ease}.fe-switch.on{background:linear-gradient(135deg,#7c3aed,#06b6d4)}.fe-switch.on:before{left:23px}.fe-posts{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.fe-post{border:1px solid rgba(148,163,184,.14);border-radius:18px;padding:12px;background:rgba(2,6,23,.34);display:grid;grid-template-columns:72px minmax(0,1fr);gap:10px}.fe-thumb{width:72px;height:82px;border-radius:15px;overflow:hidden;border:1px solid rgba(148,163,184,.13);background:linear-gradient(135deg,rgba(124,58,237,.38),rgba(14,165,233,.20));display:flex;align-items:center;justify-content:center;color:#fff;font-size:22px;font-weight:950}.fe-thumb img{width:100%;height:100%;object-fit:cover}.fe-post-title{color:#fff;font-size:13px;font-weight:950;line-height:1.25}.fe-post-excerpt{margin-top:5px;color:#94a3b8;font-size:11px;line-height:1.4;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden}.fe-meta{display:flex;flex-wrap:wrap;gap:5px;margin-top:7px}.fe-badge{display:inline-flex;align-items:center;border-radius:999px;padding:4px 7px;font-size:10px;font-weight:950;border:1px solid rgba(148,163,184,.16);background:rgba(15,23,42,.75);color:#cbd5e1}.fe-badge.success{color:#86efac;border-color:rgba(34,197,94,.22);background:rgba(22,163,74,.1)}.fe-badge.warning{color:#fde68a;border-color:rgba(245,158,11,.25);background:rgba(245,158,11,.1)}.fe-badge.purple{color:#f0abfc;border-color:rgba(217,70,239,.24);background:rgba(168,85,247,.12)}.fe-post-actions{display:flex;flex-wrap:wrap;gap:6px;margin-top:9px}.fe-empty{border:1px dashed rgba(148,163,184,.24);border-radius:18px;padding:18px;text-align:center;color:#94a3b8;background:rgba(2,6,23,.22)}.fe-rule{border:1px solid rgba(148,163,184,.14);border-radius:18px;padding:12px;background:rgba(2,6,23,.32)}.fe-rule h4{font-size:13px;color:#fff;font-weight:950}.fe-rule p{margin-top:5px;font-size:11px;color:#94a3b8;line-height:1.4}.fe-phone{max-width:330px;margin:auto;border:1px solid rgba(148,163,184,.18);border-radius:28px;padding:10px;background:#020617}.fe-phone-screen{border-radius:22px;min-height:480px;background:linear-gradient(180deg,#111827,#020617);overflow:hidden;padding:12px}.fe-feed-card{border:1px solid rgba(148,163,184,.14);border-radius:18px;background:rgba(15,23,42,.9);padding:11px;margin-bottom:10px}.fe-feed-card h4{font-size:13px;color:#fff;font-weight:950}.fe-feed-card p{font-size:11px;color:#94a3b8;line-height:1.4;margin-top:5px}.fe-feed-bar{height:7px;border-radius:99px;background:linear-gradient(90deg,#ec4899,#06b6d4);margin-top:9px}.fe-filterbar{display:flex;flex-wrap:wrap;gap:8px;align-items:center}.fe-filterbar .fe-select-wrap{min-width:180px}.fe-link{color:#67e8f9;text-decoration:none;font-size:12px;font-weight:900}.fe-link:hover{text-decoration:underline}@media(max-width:1180px){.fe-stats{grid-template-columns:repeat(3,minmax(0,1fr))}.fe-grid,.fe-type-grid,.fe-posts{grid-template-columns:repeat(2,minmax(0,1fr))}.fe-two{grid-template-columns:1fr}}@media(max-width:760px){.fe-hero-top{flex-direction:column}.fe-app-pill{text-align:left;width:100%}.fe-stats,.fe-grid,.fe-type-grid,.fe-posts{grid-template-columns:1fr}.fe-field{grid-column:1/-1}.fe-post{grid-template-columns:62px minmax(0,1fr)}.fe-thumb{width:62px;height:76px}}
    </style>

    <div class="fe-shell">
        <section class="fe-hero">
            <div class="fe-hero-top">
                <div>
                    <span class="fe-kicker">AppsHub Engine • Controlled Community Feed</span>
                    <div class="fe-title">Feed Engine</div>
                    <div class="fe-desc">Create and manage app-specific community feed posts, pinned updates, auto-post rules, user activity approvals, and app deep links from one reusable feed workspace.</div>
                    <div class="fe-actions">
                        <a class="fe-btn primary" href="{{ $this->createPostUrl() }}">+ Create Post</a>
                        <button type="button" class="fe-btn" wire:click="selectTab('pending')">Review Pending</button>
                        <button type="button" class="fe-btn" wire:click="selectTab('auto_rules')">Auto-Post Rules</button>
                        <button type="button" class="fe-btn" wire:click="selectTab('settings')">Feed Settings</button>
                        <a class="fe-btn" href="{{ url('/admin/feed-posts') }}">Open Advanced Library</a>
                    </div>
                </div>
                <div class="fe-app-pill">
                    <span>Active App</span>
                    <strong>{{ $this->currentApp?->name ?? 'No app selected' }}</strong>
                    <span>{{ $this->currentApp?->slug ?? 'select app' }}</span>
                </div>
            </div>
        </section>

        <nav class="fe-tabs">
            @foreach([
                'overview' => 'Overview',
                'published' => 'Published',
                'pending' => 'Pending Review',
                'auto_rules' => 'Auto-Post Rules',
                'settings' => 'Settings',
                'preview' => 'Preview',
            ] as $key => $label)
                <button type="button" class="fe-tab {{ $activeTab === $key ? 'active' : '' }}" wire:click="selectTab('{{ $key }}')">{{ $label }}</button>
            @endforeach
        </nav>

        @if($activeTab === 'overview')
            <section class="fe-stats">
                <div class="fe-stat"><span>Total Posts</span><strong>{{ $stats['total'] }}</strong><small>Current app feed</small></div>
                <div class="fe-stat"><span>Published</span><strong>{{ $stats['published'] }}</strong><small>Visible in API</small></div>
                <div class="fe-stat"><span>Drafts</span><strong>{{ $stats['drafts'] }}</strong><small>Work in progress</small></div>
                <div class="fe-stat"><span>Pending</span><strong>{{ $stats['pending'] }}</strong><small>Needs review</small></div>
                <div class="fe-stat"><span>Pinned</span><strong>{{ $stats['pinned'] }}</strong><small>Top priority</small></div>
                <div class="fe-stat"><span>Reports</span><strong>{{ $stats['reports'] }}</strong><small>Moderation queue</small></div>
            </section>

            <section class="fe-two">
                <div class="fe-card"><div class="fe-card-pad">
                    <div class="fe-card-head"><div><div class="fe-card-title">Recent Feed Posts</div><div class="fe-card-note">Card-based beginner view. Use Advanced Library only for technical table management.</div></div><a class="fe-btn small primary" href="{{ $this->createPostUrl() }}">+ New</a></div>
                    @include('filament.pages.feed-engine-post-cards', ['posts' => $recentPosts, 'empty' => 'No feed posts yet. Create your first community update.'])
                </div></div>
                <div class="fe-card"><div class="fe-card-pad">
                    <div class="fe-card-title">Quick Post Types</div><div class="fe-card-note">Start with a guided post type. The feed remains controlled and app-specific.</div>
                    <div style="height:10px"></div>
                    <div class="fe-type-grid" style="grid-template-columns:1fr;">
                        @foreach($this->quickPostTypes() as $type)
                            <a class="fe-type" href="{{ $this->createPostUrl($type['type'], $type['bucket']) }}"><span class="fe-type-icon"><svg viewBox="0 0 24 24"><path d="M5 7h14M5 12h14M5 17h9"/></svg></span><span><b>{{ $type['label'] }}</b><small>{{ $type['note'] }}</small></span></a>
                        @endforeach
                    </div>
                </div></div>
            </section>
        @endif

        {{-- Create/edit moved to full beginner editor page. --}}

        @if($activeTab === 'published')
            <section class="fe-card"><div class="fe-card-pad"><div class="fe-card-head"><div><div class="fe-card-title">Published Feed</div><div class="fe-card-note">Posts visible through the feed API for the active app.</div></div><button type="button" class="fe-btn small primary" onclick="window.location.href='{{ $this->createPostUrl() }}'">+ Create</button></div>@include('filament.pages.feed-engine-post-cards', ['posts' => $publishedPosts, 'empty' => 'No published feed posts yet.'])</div></section>
        @endif

        @if($activeTab === 'pending')
            <section class="fe-card"><div class="fe-card-pad"><div class="fe-card-head"><div><div class="fe-card-title">Pending Review</div><div class="fe-card-note">Future user-submitted reflections, testimonies, quote shares, quiz scores, and achievement posts will appear here.</div></div></div>@include('filament.pages.feed-engine-post-cards', ['posts' => $pendingPosts, 'empty' => 'No pending posts or reports right now.'])</div></section>
        @endif

        @if($activeTab === 'auto_rules')
            <section class="fe-card"><div class="fe-card-pad"><div class="fe-card-title">Auto-Post Rules</div><div class="fe-card-note">These rules keep the feed alive by creating controlled feed posts from existing AppsHub engines. Full automation comes after the manual feed MVP is stable.</div><div style="height:12px"></div><div class="fe-grid">@foreach($this->autoRules() as $rule)<div class="fe-rule"><span class="fe-badge purple">{{ $rule['status'] }}</span><h4 style="margin-top:8px">{{ $rule['title'] }}</h4><p><b>{{ $rule['engine'] }}</b> • {{ $rule['note'] }}</p></div>@endforeach</div></div></section>
        @endif

        @if($activeTab === 'settings')
            <section class="fe-card"><div class="fe-card-pad"><div class="fe-card-head"><div><div class="fe-card-title">Feed Settings</div><div class="fe-card-note">Beginner controls for community behavior. External uploads remain off for safety.</div></div><button type="button" class="fe-btn small primary" wire:click="saveSettings">Save Settings</button></div><div class="fe-grid">@foreach([
                'is_enabled' => ['Enable Community Feed','Allow this app to expose the community feed.'],
                'show_home_preview' => ['Show Home Preview','Display a feed preview section on Home later.'],
                'comments_enabled' => ['Allow Comments','Users can comment where login rules allow it.'],
                'comments_require_approval' => ['Require Comment Approval','Send comments through review before display.'],
                'user_text_posts_enabled' => ['Allow User Reflections','Permit text reflections/testimonies with approval.'],
                'auto_approve_system_posts' => ['Auto-Approve System Posts','Admin/system engine posts publish without review.'],
                'auto_approve_tool_posts' => ['Auto-Approve Tool Shares','Quote/quiz/game shares can publish automatically.'],
                'external_media_uploads_enabled' => ['External Media Uploads','Keep off for MVP to prevent abuse.'],
                'allow_quote_shares' => ['Allow Quote Shares','Users can share app-created quote designs.'],
                'allow_quiz_score_shares' => ['Allow Quiz Score Shares','Users can share approved quiz achievements.'],
                'allow_game_achievement_shares' => ['Allow Game Achievements','Users can share approved game progress.'],
            ] as $key => $row)<div class="fe-toggle-row"><div><strong>{{ $row[0] }}</strong><p>{{ $row[1] }}</p></div><button type="button" class="fe-switch {{ ($settings[$key] ?? false) ? 'on' : '' }}" wire:click="toggleSetting('{{ $key }}')"></button></div>@endforeach</div></div></section>
        @endif

        @if($activeTab === 'preview')
            <section class="fe-two"><div class="fe-card"><div class="fe-card-pad"><div class="fe-card-title">Feed Preview Rules</div><div class="fe-card-note">The app feed should mix pinned posts, live reminders, latest updates, trending cards, and inserted carousels.</div><div style="height:12px"></div><div class="fe-grid"><div class="fe-rule"><span class="fe-badge success">Top</span><h4>Pinned / Live</h4><p>Live service and urgent posts stay near the top and open the Watch tab or linked content.</p></div><div class="fe-rule"><span class="fe-badge">Main Feed</span><h4>Mixed Posts</h4><p>Articles, quotes, short videos, books, testimonies, and achievements appear as feed cards.</p></div><div class="fe-rule"><span class="fe-badge purple">Insert</span><h4>Carousels</h4><p>Short videos, quotes, or quiz challenges can appear between normal posts later.</p></div></div></div></div><div class="fe-card"><div class="fe-card-pad"><div class="fe-phone"><div class="fe-phone-screen"><div class="fe-feed-card"><span class="fe-badge success">Pinned</span><h4>Live Service is On</h4><p>Join the ongoing broadcast now.</p><div class="fe-feed-bar"></div></div><div class="fe-feed-card"><span class="fe-badge">Article</span><h4>Latest Ministry Update</h4><p>Read the newest update from the app.</p></div><div class="fe-feed-card"><span class="fe-badge purple">Short Video</span><h4>Watch a fresh inspirational clip</h4><p>Tap to open the short video reel.</p></div><div class="fe-feed-card"><span class="fe-badge">Quote</span><h4>Today’s quote card</h4><p>Save, share, or open Quote Creator.</p></div></div></div></div></div></section>
        @endif
    </div>
</x-filament-panels::page>
