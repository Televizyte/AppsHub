<x-filament::page>
    @php
        $items = collect($channels)->concat($modules ?? [])->values();
        $itemsByGroup = $items->groupBy('group')->toArray();
        $defaultGroup = request('group', $groups[0]['key'] ?? 'reading');
        $defaultChannel = request('channel', ($items->firstWhere('group', $defaultGroup)['key'] ?? $items->firstWhere('group', $defaultGroup)['bucket'] ?? ($items[0]['key'] ?? $items[0]['bucket'] ?? 'articles')));

        $firstChannelByGroup = [];
        foreach ($groups as $group) {
            $first = $items->firstWhere('group', $group['key']);
            $firstChannelByGroup[$group['key']] = $first['key'] ?? $first['bucket'] ?? $defaultChannel;
        }

        $quoteStyle = function (array $design, string $size = 'thumb') {
            $fontSize = $size === 'large' ? max(20, min(54, (int) ($design['font_size'] ?? 28))) : max(16, min(34, (int) ($design['font_size'] ?? 24)));
            $overlay = ((int) ($design['overlay_strength'] ?? 48)) / 100;
            $css = '--dxm-quote-text:' . e($design['text_color'] ?? '#ffffff') . ';';
            $css .= '--dxm-quote-accent:' . e($design['accent_color'] ?? '#38bdf8') . ';';
            $css .= '--dxm-quote-font:' . $fontSize . 'px;';
            $css .= '--dxm-quote-weight:' . e($design['font_weight'] ?? '900') . ';';
            $css .= '--dxm-quote-align:' . e($design['text_align'] ?? 'center') . ';';
            $css .= '--dxm-quote-source:' . e(($design['source_size'] ?? 14) . 'px') . ';';
            $css .= '--dxm-quote-line:' . e($design['line_height'] ?? '1.35') . ';';

            if (($design['background_mode'] ?? '') === 'image' && ! empty($design['background_image'])) {
                $css .= 'background-image:linear-gradient(135deg,rgba(0,0,0,' . e($overlay) . '),rgba(0,0,0,' . e($overlay) . ')),url(' . e($design['background_image']) . ');background-size:cover;background-position:center;';
            } else {
                $css .= 'background:linear-gradient(135deg,' . e($design['bg_color'] ?? '#160042') . ',' . e($design['bg_color_2'] ?? '#e2388a') . ');';
            }

            return $css;
        };
    @endphp

    <div
        x-data='{
            activeGroup: @json($defaultGroup),
            activeChannel: @json($defaultChannel),
            firstChannelByGroup: @json($firstChannelByGroup),
            previewOpen: false,
            preview: { mode: "image", title: "", subtitle: "", src: "", body: "", quote: "", quoteSource: "", design: {} },

            init() {
                const url = new URL(window.location.href);
                const groupFromUrl = url.searchParams.get("group");
                const channelFromUrl = url.searchParams.get("channel");
                const savedGroup = localStorage.getItem("dxm_content_group");
                const savedChannel = localStorage.getItem("dxm_content_channel");

                if (groupFromUrl && this.firstChannelByGroup[groupFromUrl]) {
                    this.activeGroup = groupFromUrl;
                    this.activeChannel = channelFromUrl || this.firstChannelByGroup[groupFromUrl];
                } else if (savedGroup && this.firstChannelByGroup[savedGroup]) {
                    this.activeGroup = savedGroup;
                    this.activeChannel = savedChannel || this.firstChannelByGroup[savedGroup];
                }

                if (!this.activeChannel) {
                    this.activeChannel = this.firstChannelByGroup[this.activeGroup] || @json($defaultChannel);
                }

                this.persist(false);
            },

            persist(replace = true) {
                localStorage.setItem("dxm_content_group", this.activeGroup);
                localStorage.setItem("dxm_content_channel", this.activeChannel);

                if (!replace) return;

                const url = new URL(window.location.href);
                url.searchParams.set("group", this.activeGroup);
                url.searchParams.set("channel", this.activeChannel);
                window.history.replaceState({}, "", url.toString());
            },

            setGroup(group) {
                this.activeGroup = group;
                this.activeChannel = this.firstChannelByGroup[group] || @json($defaultChannel);
                this.persist();
            },

            setChannel(channel) {
                this.activeChannel = channel;
                this.persist();
            },

            openPreview(payload) {
                this.preview = payload || { mode: "image", title: "Preview", subtitle: "", src: "", body: "", quote: "", quoteSource: "", design: {} };
                this.previewOpen = true;
            },

            closePreview() {
                this.previewOpen = false;
            },

            quotePreviewStyle() {
                const d = this.preview.design || {};
                const overlay = ((d.overlay_strength ?? 48) / 100);
                let css = `--dxm-quote-text:${d.text_color || "#ffffff"};--dxm-quote-accent:${d.accent_color || "#38bdf8"};--dxm-quote-font:${Math.max(22, Math.min(58, d.font_size || 32))}px;--dxm-quote-weight:${d.font_weight || "900"};--dxm-quote-align:${d.text_align || "center"};`;
                if (d.background_mode === "image" && d.background_image) {
                    css += `background-image:linear-gradient(135deg,rgba(0,0,0,${overlay}),rgba(0,0,0,${overlay})),url(${d.background_image});background-size:cover;background-position:center;`;
                } else {
                    css += `background:linear-gradient(135deg,${d.bg_color || "#160042"},${d.bg_color_2 || "#e2388a"});`;
                }
                return css;
            }
        }'
        x-init="init()"
        class="dxm-content-studio"
    >
        <section class="dxm-hero">
            <div>
                <div class="dxm-kicker">Beginner Content Studio</div>
                <h1>{{ $currentApp?->name ?? 'Selected App' }} Content Hub</h1>
                <p>Manage reusable content channels and content engines for the selected app. Previews now show actual content meaning, not only media thumbnails.</p>
                <div class="dxm-hero-chips">
                    <span>{{ $summary['channels'] ?? 0 }} channels</span>
                    <span>{{ $summary['modules'] ?? 0 }} modules</span>
                    <span>{{ $summary['total'] ?? 0 }} total items</span>
                    <span>{{ $summary['published'] ?? 0 }} published</span>
                    <span>{{ $summary['draft'] ?? 0 }} drafts</span>
                </div>
            </div>

            <div class="dxm-hero-actions">
                <a href="{{ url('/admin/destination-builder') }}" class="dxm-btn dxm-btn-muted">Destination Builder</a>
                <a href="{{ route('admin.beginner.content-posts.create') }}" class="dxm-btn dxm-btn-primary">+ New Content</a>
            </div>
        </section>

        <section class="dxm-overview-grid">
            <div class="dxm-summary-card"><span>Total</span><strong>{{ $summary['total'] ?? 0 }}</strong><small>All content posts</small></div>
            <div class="dxm-summary-card"><span>Published</span><strong>{{ $summary['published'] ?? 0 }}</strong><small>Visible to app users</small></div>
            <div class="dxm-summary-card"><span>Draft</span><strong>{{ $summary['draft'] ?? 0 }}</strong><small>Still being prepared</small></div>
            <div class="dxm-summary-card"><span>Visuals</span><strong>{{ $summary['visuals'] ?? 0 }}</strong><small>Quote/scripture designs</small></div>
            <div class="dxm-summary-card"><span>Library</span><strong>{{ $summary['library'] ?? 0 }}</strong><small>Book resources</small></div>
        </section>

        <section class="dxm-group-tabs" aria-label="Content groups">
            @foreach ($groups as $group)
                <button type="button" class="dxm-group-tab" :class="{ 'active': activeGroup === '{{ $group['key'] }}' }" x-on:click="setGroup('{{ $group['key'] }}')">
                    <span class="dxm-svg">{!! $group['icon_svg'] !!}</span>
                    <strong>{{ $group['label'] }}</strong>
                    <small>{{ $group['description'] }}</small>
                </button>
            @endforeach
        </section>

        @foreach ($groups as $group)
            <section x-show="activeGroup === '{{ $group['key'] }}'" x-cloak class="dxm-channel-row-wrap">
                <div class="dxm-channel-row">
                    @foreach (($itemsByGroup[$group['key']] ?? []) as $item)
                        @php $itemKey = $item['key'] ?? $item['bucket']; @endphp
                        <button type="button" class="dxm-channel-chip" :class="{ 'active': activeChannel === '{{ $itemKey }}' }" x-on:click="setChannel('{{ $itemKey }}')">
                            <span class="dxm-chip-icon">{!! $item['icon_svg'] !!}</span>
                            <span>{{ $item['label'] }}</span>
                            <em>{{ $item['total'] ?? 0 }}</em>
                        </button>
                    @endforeach
                </div>
            </section>
        @endforeach

        @foreach ($items as $item)
            @php
                $itemKey = $item['key'] ?? $item['bucket'];
                $isModule = (bool) ($item['is_module'] ?? false);
            @endphp

            <section x-show="activeChannel === '{{ $itemKey }}'" x-cloak class="dxm-channel-panel">
                <div class="dxm-channel-head">
                    <div class="dxm-channel-title">
                        <div class="dxm-channel-avatar">{!! $item['icon_svg'] !!}</div>
                        <div>
                            <span>{{ $item['tone'] }}</span>
                            <h2>{{ $item['label'] }}</h2>
                            <p>{{ $item['description'] }}</p>
                        </div>
                    </div>
                    <div class="dxm-channel-actions">
                        @if (! empty($item['manage_url']))
                            <a href="{{ $item['manage_url'] }}" class="dxm-btn dxm-btn-muted">{{ $isModule ? 'Open Module' : 'Manage Library' }}</a>
                        @else
                            <span class="dxm-btn dxm-btn-disabled">Coming Soon</span>
                        @endif

                        @if (! empty($item['create_url']) && ! $isModule)
                            <a href="{{ $item['create_url'] }}" class="dxm-btn dxm-btn-primary">+ Create</a>
                        @elseif (! empty($item['create_url']) && $isModule)
                            <a href="{{ $item['create_url'] }}" class="dxm-btn dxm-btn-primary">Open Editor</a>
                        @endif
                    </div>
                </div>

                <div class="dxm-channel-metrics">
                    <div><span>Total</span><strong>{{ $item['total'] ?? 0 }}</strong></div>
                    <div><span>Published</span><strong>{{ $item['published'] ?? 0 }}</strong></div>
                    <div><span>Draft</span><strong>{{ $item['draft'] ?? 0 }}</strong></div>
                    <div><span>Status</span><strong>{{ $item['status'] ?? (($item['featured'] ?? 0) . ' featured') }}</strong></div>
                </div>

                @if ($isModule)
                    <div class="dxm-module-note">
                        <strong>{{ $item['label'] }} is a connected content module.</strong>
                        <p>Modules stay separate, scalable, and reusable. They do not need to be forced inside the article table.</p>
                    </div>

                    @if (in_array(($item['key'] ?? ''), ['daily_scripture', 'daily_quote'], true))
                        @php $daily = $item['daily_preview'] ?? []; @endphp
                        <div class="dxm-daily-preview-wrap">
                            <div
                                class="dxm-quote-card dxm-quote-card-large"
                                style="{{ $quoteStyle($daily['design'] ?? [], 'large') }}"
                                x-on:click="openPreview({
                                    mode: 'quote_card',
                                    title: @js($daily['title'] ?? $item['label']),
                                    subtitle: @js($daily['sub'] ?? ''),
                                    quote: @js($daily['main'] ?? ''),
                                    quoteSource: @js($daily['sub'] ?? ''),
                                    body: @js($daily['note'] ?? ''),
                                    design: @js($daily['design'] ?? [])
                                })"
                            >
                                <div class="dxm-quote-mark">“</div>
                                <div class="dxm-quote-main">{{ $daily['main'] ?? '' }}</div>
                                @if (! empty($daily['sub']))
                                    <div class="dxm-quote-source">{{ $daily['sub'] }}</div>
                                @endif
                                @if (! empty($daily['note']))
                                    <div class="dxm-quote-note">{{ $daily['note'] }}</div>
                                @endif
                            </div>
                        </div>
                    @elseif (($item['key'] ?? '') === 'books_library')
                        @if (! empty($item['latest']))
                            <div class="dxm-book-grid">
                                @foreach ($item['latest'] as $book)
                                    <article class="dxm-book-card">
                                        <div class="dxm-book-cover">
                                            @if (! empty($book['cover']))
                                                <img src="{{ $book['cover'] }}" alt="">
                                            @else
                                                {!! $item['icon_svg'] !!}
                                            @endif
                                        </div>
                                        <div><strong>{{ $book['title'] }}</strong><small>{{ $book['subtitle'] ?: 'No subtitle yet' }}</small><span>{{ $book['status'] }}</span></div>
                                        @if (! empty($book['edit_url']))
                                            <a href="{{ $book['edit_url'] }}">Edit</a>
                                        @endif
                                    </article>
                                @endforeach
                            </div>
                        @else
                            <div class="dxm-empty">
                                <div class="dxm-empty-icon">{!! $item['icon_svg'] !!}</div>
                                <strong>No books yet.</strong>
                                <p>Open the book module to create books, chapters, and reader content.</p>
                            </div>
                        @endif
                    @elseif (($item['key'] ?? '') === 'quiz_builder')
                        <div class="dxm-empty">
                            <div class="dxm-empty-icon">{!! $item['icon_svg'] !!}</div>
                            <strong>Quiz Builder is queued.</strong>
                            <p>This will later manage devotional quiz, article quiz, Bible quiz, and future quiz-linked content.</p>
                        </div>
                    @endif
                @else
                    <div class="dxm-section-bar">
                        <div><strong>Recent Items</strong><small>Preview the actual content format, not only the image file.</small></div>
                        <a href="{{ $item['manage_url'] }}">Full manager →</a>
                    </div>

                    @if (! empty($item['latest']))
                        <div class="dxm-content-grid">
                            @foreach ($item['latest'] as $post)
                                @php
                                    $mode = $post['preview_mode'] ?? $item['preview_mode'] ?? 'article';
                                    $previewSrc = $mode === 'video' ? ($post['video_url'] ?? '') : ($post['cover'] ?? '');
                                @endphp

                                <article class="dxm-content-card dxm-content-card-{{ $mode }}">
                                    @if ($mode === 'quote_card')
                                        <button
                                            type="button"
                                            class="dxm-quote-card"
                                            style="{{ $quoteStyle($post['design'] ?? []) }}"
                                            x-on:click="openPreview({
                                                mode: 'quote_card',
                                                title: @js($post['title']),
                                                subtitle: @js($post['subtitle']),
                                                quote: @js($post['quote']),
                                                quoteSource: @js($post['quote_source']),
                                                src: @js($post['cover']),
                                                body: @js($post['body_preview']),
                                                design: @js($post['design'] ?? [])
                                            })"
                                        >
                                            <div class="dxm-quote-mark">“</div>
                                            <div class="dxm-quote-main">{{ $post['quote'] }}</div>
                                            @if (! empty($post['quote_source']))
                                                <div class="dxm-quote-source">{{ $post['quote_source'] }}</div>
                                            @endif
                                        </button>
                                    @else
                                        <button
                                            type="button"
                                            class="dxm-card-thumb"
                                            x-on:click="openPreview({
                                                mode: @js($mode),
                                                title: @js($post['title']),
                                                subtitle: @js($post['subtitle']),
                                                src: @js($previewSrc),
                                                body: @js($post['body_preview']),
                                                quote: @js($post['quote'] ?? ''),
                                                quoteSource: @js($post['quote_source'] ?? ''),
                                                design: @js($post['design'] ?? [])
                                            })"
                                        >
                                            @if (! empty($post['cover']))
                                                <img src="{{ $post['cover'] }}" alt="">
                                            @else
                                                <span class="dxm-thumb-placeholder">{!! $item['icon_svg'] !!}</span>
                                            @endif

                                            @if ($mode === 'video')
                                                <em class="dxm-video-badge">▶</em>
                                            @endif
                                        </button>
                                    @endif

                                    <div class="dxm-card-body">
                                        <strong>{{ $post['title'] }}</strong>
                                        <small>{{ $post['subtitle'] ?: ($post['body_preview'] ?: 'No subtitle yet') }}</small>
                                        @if ($mode === 'article' && ! empty($post['body_preview']))
                                            <p>{{ $post['body_preview'] }}</p>
                                        @endif
                                        <div class="dxm-card-tags">
                                            <span>{{ ucfirst($post['status']) }}</span>
                                            @if ($post['is_featured']) <span>Featured</span> @endif
                                            @if ($post['duration']) <span>{{ $post['duration'] }}</span> @endif
                                            @if ($post['updated_human']) <span>{{ $post['updated_human'] }}</span> @endif
                                        </div>
                                    </div>

                                    <div class="dxm-card-actions">
                                        <button type="button" x-on:click="openPreview({
                                            mode: @js($mode),
                                            title: @js($post['title']),
                                            subtitle: @js($post['subtitle']),
                                            src: @js($previewSrc),
                                            body: @js($post['body_preview']),
                                            quote: @js($post['quote'] ?? ''),
                                            quoteSource: @js($post['quote_source'] ?? ''),
                                            design: @js($post['design'] ?? [])
                                        })">Preview</button>
                                        <a href="{{ $post['edit_url'] }}">Edit</a>
                                    </div>
                                </article>
                            @endforeach
                        </div>
                    @else
                        <div class="dxm-empty">
                            <div class="dxm-empty-icon">{!! $item['icon_svg'] !!}</div>
                            <strong>No {{ strtolower($item['label']) }} yet.</strong>
                            <p>Create the first item or open the full manager to review this channel.</p>
                            <div>
                                <a href="{{ $item['create_url'] }}" class="dxm-btn dxm-btn-primary">Create First</a>
                                <a href="{{ $item['manage_url'] }}" class="dxm-btn dxm-btn-muted">Open Manager</a>
                            </div>
                        </div>
                    @endif
                @endif
            </section>
        @endforeach

        <div class="dxm-preview-modal" x-show="previewOpen" x-cloak x-on:keydown.escape.window="closePreview()">
            <button type="button" class="dxm-preview-backdrop" x-on:click="closePreview()"></button>
            <div class="dxm-preview-box" role="dialog" aria-modal="true">
                <div class="dxm-preview-head">
                    <div><strong x-text="preview.title"></strong><small x-text="preview.subtitle"></small></div>
                    <button type="button" x-on:click="closePreview()">×</button>
                </div>
                <div class="dxm-preview-body">
                    <template x-if="preview.mode === 'video' && preview.src">
                        <video x-bind:src="preview.src" controls playsinline style="width:100%;max-height:70vh;border-radius:18px;background:#000;"></video>
                    </template>

                    <template x-if="preview.mode === 'quote_card'">
                        <div class="dxm-quote-card dxm-quote-card-modal" x-bind:style="quotePreviewStyle()">
                            <div class="dxm-quote-mark">“</div>
                            <div class="dxm-quote-main" x-text="preview.quote"></div>
                            <div class="dxm-quote-source" x-show="preview.quoteSource" x-text="preview.quoteSource"></div>
                            <div class="dxm-quote-note" x-show="preview.body" x-text="preview.body"></div>
                        </div>
                    </template>

                    <template x-if="preview.mode === 'article'">
                        <div class="dxm-article-preview">
                            <template x-if="preview.src">
                                <img x-bind:src="preview.src" alt="">
                            </template>
                            <h2 x-text="preview.title"></h2>
                            <h3 x-show="preview.subtitle" x-text="preview.subtitle"></h3>
                            <p x-text="preview.body || 'No body preview available yet.'"></p>
                        </div>
                    </template>

                    <template x-if="preview.mode !== 'video' && preview.mode !== 'quote_card' && preview.mode !== 'article' && preview.src">
                        <img x-bind:src="preview.src" alt="" style="max-width:100%;max-height:70vh;border-radius:18px;object-fit:contain;">
                    </template>

                    <template x-if="preview.mode !== 'quote_card' && preview.mode !== 'article' && !preview.src">
                        <div class="dxm-preview-empty">No media preview available for this item.</div>
                    </template>
                </div>
            </div>
        </div>
    </div>

    <style>
        [x-cloak]{display:none!important}.dxm-content-studio{display:grid;gap:18px;max-width:1220px}.dxm-hero{display:flex;align-items:center;justify-content:space-between;gap:18px;border:1px solid rgba(34,211,238,.20);border-radius:28px;padding:22px;background:radial-gradient(circle at 10% 0%,rgba(34,211,238,.18),transparent 34%),radial-gradient(circle at 100% 0%,rgba(168,85,247,.16),transparent 36%),linear-gradient(135deg,rgba(2,6,23,.94),rgba(15,23,42,.90))}.dxm-kicker{display:inline-flex;padding:7px 11px;border-radius:999px;border:1px solid rgba(34,211,238,.35);background:rgba(34,211,238,.10);color:#cffafe;font-size:11px;font-weight:950;letter-spacing:.08em;text-transform:uppercase}.dxm-hero h1{margin:11px 0 0;color:#fff;font-size:clamp(29px,4vw,43px);line-height:1.05;font-weight:950;letter-spacing:-.05em}.dxm-hero p{max-width:780px;margin:10px 0 0;color:rgba(255,255,255,.68);font-size:14px;line-height:1.6}.dxm-hero-actions,.dxm-channel-actions,.dxm-card-actions{display:flex;gap:9px;flex-wrap:wrap;justify-content:flex-end}.dxm-hero-chips{display:flex;gap:8px;flex-wrap:wrap;margin-top:14px}.dxm-hero-chips span,.dxm-card-tags span{display:inline-flex;align-items:center;min-height:25px;border-radius:999px;border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.06);padding:5px 8px;color:rgba(255,255,255,.72);font-size:10.5px;font-weight:900;text-transform:uppercase;letter-spacing:.04em}.dxm-btn,.dxm-card-actions button,.dxm-card-actions a{display:inline-flex;align-items:center;justify-content:center;min-height:39px;border-radius:13px;padding:9px 13px;border:1px solid rgba(255,255,255,.12);text-decoration:none;font-size:12px;font-weight:950;transition:.18s ease;cursor:pointer}.dxm-btn:hover,.dxm-card-actions button:hover,.dxm-card-actions a:hover{transform:translateY(-1px);filter:brightness(1.08)}.dxm-btn-primary{color:#fff;background:linear-gradient(135deg,rgba(34,211,238,.34),rgba(59,130,246,.22));border-color:rgba(34,211,238,.48)}.dxm-btn-muted,.dxm-card-actions button,.dxm-card-actions a{color:#fff;background:rgba(255,255,255,.055);border-color:rgba(255,255,255,.12)}.dxm-btn-disabled{color:rgba(255,255,255,.48);background:rgba(255,255,255,.035);border-color:rgba(255,255,255,.08);cursor:not-allowed}.dxm-overview-grid{display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px}.dxm-summary-card{border:1px solid rgba(255,255,255,.09);border-radius:20px;background:rgba(255,255,255,.035);padding:14px}.dxm-summary-card span{display:block;color:rgba(255,255,255,.55);font-size:11px;font-weight:900;text-transform:uppercase}.dxm-summary-card strong{display:block;color:#fff;font-size:25px;margin-top:5px;letter-spacing:-.04em}.dxm-summary-card small{display:block;color:rgba(255,255,255,.45);font-size:11px;margin-top:4px}.dxm-group-tabs{display:grid;grid-template-columns:repeat(auto-fit,minmax(190px,1fr));gap:12px}.dxm-group-tab{display:grid;grid-template-columns:46px minmax(0,1fr);gap:12px;align-items:center;text-align:left;border:1px solid rgba(255,255,255,.09);border-radius:22px;background:rgba(2,6,23,.38);padding:15px;color:#fff;cursor:pointer}.dxm-group-tab.active{border-color:rgba(34,211,238,.55);background:linear-gradient(135deg,rgba(14,116,144,.26),rgba(88,28,135,.18))}.dxm-group-tab strong{display:block;font-size:15px;font-weight:950}.dxm-group-tab small{display:block;color:rgba(255,255,255,.58);font-size:11.5px;line-height:1.4;margin-top:4px}.dxm-svg,.dxm-chip-icon,.dxm-channel-avatar,.dxm-empty-icon,.dxm-thumb-placeholder{display:grid;place-items:center;color:#e0faff}.dxm-svg{width:46px;height:46px;border-radius:16px;background:rgba(255,255,255,.07)}.dxm-svg svg,.dxm-chip-icon svg,.dxm-channel-avatar svg,.dxm-empty-icon svg,.dxm-thumb-placeholder svg{width:24px;height:24px}.dxm-channel-row-wrap{border:1px solid rgba(255,255,255,.08);border-radius:18px;background:rgba(255,255,255,.035);overflow:hidden}.dxm-channel-row{display:flex;gap:9px;padding:10px;overflow-x:auto;scrollbar-width:thin}.dxm-channel-chip{flex:0 0 auto;display:inline-flex;align-items:center;gap:8px;min-height:43px;border-radius:999px;border:1px solid rgba(255,255,255,.10);background:rgba(2,6,23,.55);color:rgba(255,255,255,.76);padding:9px 12px;font-weight:900;cursor:pointer}.dxm-channel-chip.active{border-color:rgba(34,211,238,.58);background:rgba(34,211,238,.12);color:#fff}.dxm-chip-icon{width:24px;height:24px}.dxm-chip-icon svg{width:18px;height:18px}.dxm-channel-chip em{display:grid;place-items:center;min-width:22px;height:22px;border-radius:999px;background:rgba(255,255,255,.09);font-style:normal;font-size:11px}.dxm-channel-panel{border:1px solid rgba(255,255,255,.09);border-radius:26px;padding:18px;background:linear-gradient(180deg,rgba(15,23,42,.70),rgba(2,6,23,.78));box-shadow:0 18px 60px rgba(0,0,0,.24)}.dxm-channel-head{display:flex;justify-content:space-between;gap:16px;align-items:flex-start}.dxm-channel-title{display:flex;gap:13px}.dxm-channel-avatar{width:54px;height:54px;border-radius:18px;background:linear-gradient(135deg,#0B1F4D,#1D5CFF,#E2388A);box-shadow:0 16px 34px rgba(0,0,0,.28)}.dxm-channel-title span{display:block;color:#67e8f9;font-size:11px;font-weight:950;letter-spacing:.07em;text-transform:uppercase}.dxm-channel-title h2{margin:3px 0 0;color:#fff;font-size:23px;font-weight:950}.dxm-channel-title p{margin:6px 0 0;color:rgba(255,255,255,.62);line-height:1.5;max-width:760px}.dxm-channel-metrics{display:grid;grid-template-columns:repeat(4,minmax(0,1fr));gap:10px;margin-top:16px}.dxm-channel-metrics div{border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.045);border-radius:18px;padding:13px}.dxm-channel-metrics span{display:block;color:rgba(255,255,255,.54);font-size:11px;font-weight:900;text-transform:uppercase}.dxm-channel-metrics strong{display:block;color:#fff;font-size:24px;margin-top:4px}.dxm-section-bar{display:flex;justify-content:space-between;align-items:flex-end;gap:12px;margin:18px 0 10px}.dxm-section-bar strong{display:block;color:#fff;font-size:16px}.dxm-section-bar small{display:block;color:rgba(255,255,255,.54);font-size:12px;margin-top:3px}.dxm-section-bar a{color:#93c5fd;text-decoration:none;font-weight:900;font-size:12px}.dxm-module-note{margin-top:16px;border:1px solid rgba(34,211,238,.18);border-radius:20px;background:rgba(34,211,238,.07);padding:14px}.dxm-module-note strong{display:block;color:#fff}.dxm-module-note p{margin:7px 0 0;color:rgba(255,255,255,.63);line-height:1.55;font-size:13px}.dxm-content-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(255px,1fr));gap:12px}.dxm-content-card{border:1px solid rgba(255,255,255,.08);border-radius:20px;background:rgba(2,6,23,.36);overflow:hidden;transition:.18s ease}.dxm-content-card:hover{border-color:rgba(34,211,238,.32);transform:translateY(-1px)}.dxm-card-thumb{position:relative;width:100%;height:140px;border:0;padding:0;background:linear-gradient(135deg,#0B1F4D,#1D5CFF,#E2388A);display:grid;place-items:center;overflow:hidden;cursor:pointer;color:#fff}.dxm-card-thumb img{width:100%;height:100%;object-fit:cover}.dxm-thumb-placeholder svg{width:46px;height:46px}.dxm-video-badge{position:absolute;right:12px;bottom:12px;width:42px;height:42px;border-radius:999px;display:grid;place-items:center;background:rgba(0,0,0,.58);color:#fff;font-style:normal}.dxm-card-body{padding:13px}.dxm-card-body strong{display:block;color:#fff;font-size:14.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.dxm-card-body small{display:block;color:rgba(255,255,255,.56);font-size:12px;margin-top:5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.dxm-card-body p{margin:8px 0 0;color:rgba(255,255,255,.62);font-size:12px;line-height:1.5;display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}.dxm-card-tags{display:flex;gap:6px;flex-wrap:wrap;margin-top:10px}.dxm-card-actions{padding:0 13px 13px;justify-content:flex-start}.dxm-quote-card{position:relative;width:100%;min-height:205px;border:0;padding:24px;display:grid;align-content:center;gap:10px;overflow:hidden;cursor:pointer;color:var(--dxm-quote-text);text-align:var(--dxm-quote-align);font-weight:var(--dxm-quote-weight);isolation:isolate}.dxm-quote-card:after{content:"";position:absolute;inset:0;border:1px solid rgba(255,255,255,.14);border-radius:inherit;pointer-events:none}.dxm-quote-card .dxm-quote-mark{font-size:42px;line-height:.8;color:var(--dxm-quote-accent);font-weight:950}.dxm-quote-card .dxm-quote-main{font-size:var(--dxm-quote-font);line-height:1.18;text-shadow:0 2px 12px rgba(0,0,0,.32);white-space:normal}.dxm-quote-card .dxm-quote-source{font-size:12px;letter-spacing:.08em;text-transform:uppercase;color:rgba(255,255,255,.78);font-weight:900}.dxm-quote-card .dxm-quote-note{font-size:13px;line-height:1.45;color:rgba(255,255,255,.76);font-weight:650}.dxm-quote-card-large{border-radius:24px;min-height:330px;margin-top:16px}.dxm-quote-card-modal{border-radius:24px;width:min(760px,90vw);min-height:min(70vh,720px)}.dxm-book-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px;margin-top:16px}.dxm-book-card{border:1px solid rgba(255,255,255,.08);background:rgba(2,6,23,.35);border-radius:18px;padding:12px;display:grid;grid-template-columns:72px minmax(0,1fr);gap:12px}.dxm-book-cover{width:72px;height:96px;border-radius:12px;display:grid;place-items:center;background:linear-gradient(135deg,#0B1F4D,#1D5CFF,#E2388A);overflow:hidden;color:#fff}.dxm-book-cover img{width:100%;height:100%;object-fit:cover}.dxm-book-cover svg{width:30px;height:30px}.dxm-book-card strong{display:block;color:#fff;font-size:13px}.dxm-book-card small{display:block;color:rgba(255,255,255,.58);font-size:11px;margin-top:4px}.dxm-book-card span{display:inline-flex;margin-top:8px;border-radius:999px;padding:4px 7px;background:rgba(255,255,255,.07);font-size:10px;color:#fff}.dxm-book-card a{grid-column:1/-1;color:#93c5fd;text-decoration:none;font-size:12px;font-weight:900}.dxm-empty{border:1px dashed rgba(255,255,255,.18);border-radius:22px;background:rgba(2,6,23,.24);padding:24px;text-align:center;color:#fff;margin-top:16px}.dxm-empty-icon{width:58px;height:58px;border-radius:20px;margin:0 auto 12px;background:rgba(255,255,255,.07)}.dxm-empty p{color:rgba(255,255,255,.60);margin:7px 0 14px}.dxm-preview-modal{position:fixed;inset:0;z-index:9999;display:grid;place-items:center;padding:24px}.dxm-preview-backdrop{position:absolute;inset:0;border:0;background:rgba(0,0,0,.72);backdrop-filter:blur(8px);cursor:pointer}.dxm-preview-box{position:relative;width:min(920px,96vw);border:1px solid rgba(34,211,238,.24);border-radius:26px;background:#070b18;box-shadow:0 30px 90px rgba(0,0,0,.55);overflow:hidden}.dxm-preview-head{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:15px 17px;border-bottom:1px solid rgba(255,255,255,.08)}.dxm-preview-head strong{display:block;color:#fff}.dxm-preview-head small{display:block;color:rgba(255,255,255,.58);font-size:12px;margin-top:3px}.dxm-preview-head button{width:38px;height:38px;border-radius:999px;border:1px solid rgba(255,255,255,.13);background:rgba(255,255,255,.08);color:#fff;font-size:24px;cursor:pointer}.dxm-preview-body{display:grid;place-items:center;min-height:260px;padding:18px}.dxm-preview-empty{color:rgba(255,255,255,.65);border:1px dashed rgba(255,255,255,.16);border-radius:18px;padding:24px;text-align:center}.dxm-article-preview{width:100%;max-width:760px;max-height:72vh;overflow:auto;border:1px solid rgba(255,255,255,.09);border-radius:22px;background:rgba(255,255,255,.04);padding:18px}.dxm-article-preview img{width:100%;max-height:280px;object-fit:cover;border-radius:16px;margin-bottom:16px}.dxm-article-preview h2{margin:0;color:#fff;font-size:28px;font-weight:950}.dxm-article-preview h3{margin:7px 0 0;color:rgba(255,255,255,.70);font-size:15px}.dxm-article-preview p{margin:16px 0 0;color:rgba(255,255,255,.78);line-height:1.75;white-space:pre-line}@media(max-width:980px){.dxm-hero,.dxm-channel-head,.dxm-section-bar{display:block}.dxm-hero-actions,.dxm-channel-actions{justify-content:flex-start;margin-top:14px}.dxm-overview-grid,.dxm-group-tabs,.dxm-channel-metrics{grid-template-columns:1fr}}@media(max-width:640px){.dxm-content-grid{grid-template-columns:1fr}.dxm-hero,.dxm-channel-panel{padding:14px}.dxm-btn{width:100%}.dxm-group-tab{grid-template-columns:40px minmax(0,1fr)}.dxm-svg{width:40px;height:40px}}
    </style>
</x-filament::page>
