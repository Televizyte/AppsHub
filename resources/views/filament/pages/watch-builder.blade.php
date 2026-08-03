<x-filament::page>
    @php
        $tabs = [
            'primary' => 'Main Live',
            'videos' => 'Videos',
            'channels' => 'Other Channels',
            'fallback' => 'Fallback',
            'other' => 'Other',
        ];

        $defaultTab = request('tab', 'primary');
        if (! array_key_exists($defaultTab, $tabs)) {
            $defaultTab = 'primary';
        }

        $typeOptions = [
            'live_hls' => 'Live / HLS Stream',
            'live_youtube' => 'YouTube Live / Service',
            'commanding_day' => 'Prayer Broadcast',
            'video' => 'Video / Playlist',
            'channel' => 'TV Channel',
            'web' => 'Web / Embed',
        ];

        $playerOptions = [
            'web' => 'Web / Embed',
            'youtube' => 'YouTube',
            'hls' => 'HLS',
        ];

        $groupOptions = [
            '' => 'Main Watch Area',
            'video' => 'Video Library',
            'other_channels' => 'Other Channels',
            'fallback' => 'Fallback / Backup',
            'sermons' => 'Sermons',
            'programs' => 'Programs',
            'events' => 'Events',
        ];

        $svg = [
            'tv' => '<svg viewBox="0 0 24 24" fill="none"><path d="M4 6h16v11H4V6Z" stroke="currentColor" stroke-width="1.8"/><path d="M9 21h6M12 17v4" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
            'image' => '<svg viewBox="0 0 24 24" fill="none"><rect x="4" y="5" width="16" height="14" rx="2" stroke="currentColor" stroke-width="1.8"/><path d="M5 17l4-4a1.4 1.4 0 0 1 2 0l2 2 1.5-1.5a1.4 1.4 0 0 1 2 0L20 17" stroke="currentColor" stroke-width="1.8"/><circle cx="16" cy="9" r="1.2" fill="currentColor"/></svg>',
            'power' => '<svg viewBox="0 0 24 24" fill="none"><path d="M12 3v9M7.1 6.8a7 7 0 1 0 9.8 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
            'trash' => '<svg viewBox="0 0 24 24" fill="none"><path d="M5 7h14M10 11v6M14 11v6M8 7l1-3h6l1 3M7 7l1 14h8l1-14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'edit' => '<svg viewBox="0 0 24 24" fill="none"><path d="m4 16.8-.7 3.9 3.9-.7L18.5 8.7 15.3 5.5 4 16.8Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="m14.5 6.3 3.2 3.2" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
            'up' => '<svg viewBox="0 0 24 24" fill="none"><path d="m6 15 6-6 6 6" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'down' => '<svg viewBox="0 0 24 24" fill="none"><path d="m6 9 6 6 6-6" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            'map' => '<svg viewBox="0 0 24 24" fill="none"><path d="M9 18 4 20V6l5-2 6 2 5-2v14l-5 2-6-2Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9 4v14M15 6v14" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        ];

        $formId = fn ($id) => 'watch-edit-' . $id;
    @endphp

    <div
        class="dxm-watch"
        x-data='{
            activeTab: @json($defaultTab),
            preview: @json($links[0] ?? null),
            createOpen: false,
            pickerTarget: "",
            imageAssets: @json($imageAssets),
            setTab(tab) {
                this.activeTab = tab;
                const url = new URL(window.location.href);
                url.searchParams.set("tab", tab);
                history.replaceState({}, "", url.toString());
                localStorage.setItem("dxm_watch_tab", tab);
            },
            init() {
                const saved = localStorage.getItem("dxm_watch_tab");
                if (saved && @json(array_keys($tabs)).includes(saved) && !new URL(window.location.href).searchParams.get("tab")) {
                    this.activeTab = saved;
                }
            },
            setPreview(link) { this.preview = link; },
            openPicker(target) {
                this.pickerTarget = target;
                const dialog = document.getElementById("dxm-watch-image-picker");
                if (dialog && !dialog.open) {
                    dialog.showModal();
                }
            },
            closePicker() {
                const dialog = document.getElementById("dxm-watch-image-picker");
                if (dialog && dialog.open) {
                    dialog.close();
                }
            },
            chooseImage(url) {
                const input = document.querySelector(this.pickerTarget);
                if (input) {
                    input.value = url;
                    input.dispatchEvent(new Event("input", { bubbles: true }));
                    input.dispatchEvent(new Event("change", { bubbles: true }));
                }
                this.closePicker();
            }
        }'
        x-init="init()"
    >
        @if (session('status'))
            <div class="dxm-notice">{{ session('status') }}</div>
        @endif

        <section class="dxm-watch-hero">
            <div>
                <div class="dxm-kicker">Beginner Watch Builder</div>
                <h1>{{ $currentApp?->name ?? 'Selected App' }} Watch Links</h1>
                <p>Manage live streams, YouTube services, playlist cards, embedded TV channels, and backup watch options. Each card also shows where it is used in Destination Builder when detected.</p>
                <div class="dxm-hero-chips">
                    <span>{{ $stats['enabled'] }}/{{ $stats['total'] }} enabled</span>
                    <span>{{ $stats['live'] }} live links</span>
                    <span>{{ $stats['videos'] }} video links</span>
                    <span>{{ $stats['channels'] }} other channels</span>
                    <span>{{ $stats['placed'] ?? 0 }} placed</span>
                </div>
            </div>

            <div class="dxm-hero-actions">
                <button type="button" class="dxm-btn dxm-btn-primary" x-on:click="createOpen = !createOpen">+ Add Watch Link</button>
                <a href="{{ url('/admin/watch-links') }}" class="dxm-btn dxm-btn-muted">Advanced Table</a>
            </div>
        </section>

        <section class="dxm-create-panel" x-show="createOpen" x-cloak>
            <div class="dxm-panel-title">
                <div><strong>Create Watch Link</strong><small>Add a new stream, YouTube playlist, channel, or fallback card.</small></div>
                <button type="button" x-on:click="createOpen = false">×</button>
            </div>

            <form method="POST" action="{{ route('admin.beginner.watch-links.store') }}" class="dxm-watch-form">
                @csrf
                <input type="hidden" name="return_tab" x-bind:value="activeTab">

                <label>Title <input name="title" required placeholder="Live Service"></label>

                <label>Type
                    <select name="type" required>
                        @foreach ($typeOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label class="dxm-wide">Stream / Link URL <textarea name="url" rows="2" placeholder="https://youtube.com/... or https://...m3u8"></textarea></label>

                <label>Subtitle <input name="subtitle" placeholder="Short description shown on the card"></label>
                <label>Badge Label <input name="label" placeholder="LIVE, SERVICE, CHANNEL"></label>

                <label>Player
                    <select name="player">
                        @foreach ($playerOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label>Placement Group
                    <select name="group">
                        @foreach ($groupOptions as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </label>

                <label>Sort Order <input name="sort_order" type="number" min="0" value="{{ ($stats['total'] ?? 0) + 1 }}"></label>

                <label class="dxm-wide dxm-image-field">
                    Image URL
                    <div class="dxm-image-input">
                        <input id="watch-create-image" name="image_url" placeholder="Choose from library or paste image URL">
                        <button type="button" x-on:click="openPicker('#watch-create-image')">{!! $svg['image'] !!} Library</button>
                    </div>
                </label>

                <label class="dxm-toggle"><input name="is_enabled" type="checkbox" value="1" checked> Enabled</label>

                <div class="dxm-form-actions">
                    <button type="submit" class="dxm-btn dxm-btn-primary">Save Watch Link</button>
                    <button type="button" class="dxm-btn dxm-btn-muted" x-on:click="createOpen = false">Cancel</button>
                </div>
            </form>
        </section>

        <section class="dxm-watch-grid">
            <div class="dxm-watch-main">
                <div class="dxm-tabs">
                    @foreach ($tabs as $key => $label)
                        <button type="button" class="dxm-tab" :class="{ 'active': activeTab === '{{ $key }}' }" x-on:click="setTab('{{ $key }}')">
                            {{ $label }}
                            <span>{{ count($groups[$key]['items'] ?? []) }}</span>
                        </button>
                    @endforeach
                </div>

                @foreach ($tabs as $key => $label)
                    <section x-show="activeTab === '{{ $key }}'" x-cloak class="dxm-tab-panel">
                        <div class="dxm-section-head">
                            <div>
                                <strong>{{ $groups[$key]['label'] ?? $label }}</strong>
                                <small>{{ $groups[$key]['description'] ?? '' }}</small>
                            </div>
                            <button type="button" class="dxm-btn dxm-btn-small" x-on:click="createOpen = true">+ Add Here</button>
                        </div>

                        <div class="dxm-link-list">
                            @forelse (($groups[$key]['items'] ?? []) as $link)
                                <article class="dxm-watch-card" x-on:click="setPreview(@js($link))">
                                    <div class="dxm-watch-thumb">
                                        @if (! empty($link['image_url']))
                                            <img src="{{ $link['image_url'] }}" alt="">
                                        @else
                                            <div class="dxm-thumb-icon">{!! $svg['tv'] !!}</div>
                                        @endif
                                        <span class="dxm-watch-badge">{{ $link['label'] ?: 'WATCH' }}</span>
                                    </div>

                                    <div class="dxm-watch-card-body">
                                        <div class="dxm-watch-card-top">
                                            <div>
                                                <h3>{{ $link['title'] }}</h3>
                                                <p>{{ $link['subtitle'] ?: 'No subtitle yet' }}</p>
                                            </div>
                                            <span class="dxm-status {{ $link['is_enabled'] ? 'on' : 'off' }}">{{ $link['is_enabled'] ? 'ON' : 'OFF' }}</span>
                                        </div>

                                        <div class="dxm-meta-row">
                                            <span>{{ $link['kind_label'] }}</span>
                                            <span>{{ $link['player'] ?: 'auto' }}</span>
                                            <span>Order {{ $link['sort_order'] }}</span>
                                            <span>{{ $link['health'] }}</span>
                                        </div>

                                        <div class="dxm-url-line">{{ $link['url'] ?: 'No URL set yet' }}</div>

                                        <div class="dxm-usage-box">
                                            <div class="dxm-usage-title">{!! $svg['map'] !!}<strong>Used in</strong><span>{{ $link['usage_label'] }}</span></div>
                                            @if (! empty($link['usage']))
                                                <div class="dxm-usage-list">
                                                    @foreach ($link['usage'] as $usage)
                                                        <a href="{{ $usage['edit_url'] }}" class="dxm-usage-chip">
                                                            {{ ucfirst($usage['tab']) }} → {{ $usage['section'] }}
                                                        </a>
                                                    @endforeach
                                                </div>
                                            @else
                                                <small class="dxm-muted">Not detected inside Destination Builder yet. You can still assign it through a section item.</small>
                                            @endif
                                        </div>

                                        <div class="dxm-card-actions" x-on:click.stop>
                                            <form method="POST" action="{{ route('admin.beginner.watch-links.move', [$link['id'], 'up']) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="return_tab" value="{{ $key }}">
                                                <button title="Move up">{!! $svg['up'] !!}</button>
                                            </form>

                                            <form method="POST" action="{{ route('admin.beginner.watch-links.move', [$link['id'], 'down']) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="return_tab" value="{{ $key }}">
                                                <button title="Move down">{!! $svg['down'] !!}</button>
                                            </form>

                                            <form method="POST" action="{{ route('admin.beginner.watch-links.toggle', $link['id']) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="return_tab" value="{{ $key }}">
                                                <button title="Toggle on/off">{!! $svg['power'] !!}</button>
                                            </form>

                                            <button type="button" title="Quick edit" x-on:click="document.getElementById('{{ $formId($link['id']) }}').showModal()">{!! $svg['edit'] !!}</button>

                                            <form method="POST" action="{{ route('admin.beginner.watch-links.delete', $link['id']) }}" onsubmit="return confirm('Delete this watch link?');">
                                                @csrf
                                                @method('DELETE')
                                                <input type="hidden" name="return_tab" value="{{ $key }}">
                                                <button title="Delete">{!! $svg['trash'] !!}</button>
                                            </form>
                                        </div>
                                    </div>
                                </article>

                                <dialog id="{{ $formId($link['id']) }}" class="dxm-edit-dialog">
                                    <form method="POST" action="{{ route('admin.beginner.watch-links.update', $link['id']) }}" class="dxm-watch-form">
                                        @csrf
                                        @method('PATCH')
                                        <input type="hidden" name="return_tab" value="{{ $key }}">

                                        <div class="dxm-panel-title dxm-wide">
                                            <div>
                                                <strong>Edit Watch Link</strong>
                                                <small>{{ $link['title'] }} · {{ $link['kind_label'] }}</small>
                                            </div>
                                            <button type="button" onclick="this.closest('dialog').close()">×</button>
                                        </div>

                                        <div class="dxm-edit-preview dxm-wide">
                                            <div class="dxm-edit-preview-img">
                                                @if (! empty($link['image_url']))
                                                    <img src="{{ $link['image_url'] }}" alt="">
                                                @else
                                                    <div class="dxm-thumb-icon">{!! $svg['tv'] !!}</div>
                                                @endif
                                                <span>{{ $link['label'] ?: 'WATCH' }}</span>
                                            </div>
                                            <div>
                                                <strong>{{ $link['title'] }}</strong>
                                                <p>{{ $link['subtitle'] ?: 'No subtitle yet' }}</p>
                                                <div class="dxm-meta-row">
                                                    <span>{{ $link['kind_label'] }}</span>
                                                    <span>{{ $link['usage_label'] }}</span>
                                                </div>
                                            </div>
                                        </div>

                                        <label>Title <input name="title" required value="{{ $link['title'] }}"></label>

                                        <label>Type
                                            <select name="type" required>
                                                @foreach ($typeOptions as $value => $optionLabel)
                                                    <option value="{{ $value }}" @selected($link['type'] === $value)>{{ $optionLabel }}</option>
                                                @endforeach
                                            </select>
                                        </label>

                                        <label class="dxm-wide">Stream / Link URL <textarea name="url" rows="2">{{ $link['url'] }}</textarea></label>
                                        <label>Subtitle <input name="subtitle" value="{{ $link['subtitle'] }}"></label>
                                        <label>Badge Label <input name="label" value="{{ $link['label'] }}"></label>

                                        <label>Player
                                            <select name="player">
                                                @foreach ($playerOptions as $value => $optionLabel)
                                                    <option value="{{ $value }}" @selected($link['player'] === $value)>{{ $optionLabel }}</option>
                                                @endforeach
                                            </select>
                                        </label>

                                        <label>Placement Group
                                            <select name="group">
                                                @foreach ($groupOptions as $value => $optionLabel)
                                                    <option value="{{ $value }}" @selected($link['group'] === $value)>{{ $optionLabel }}</option>
                                                @endforeach
                                            </select>
                                        </label>

                                        <label>Sort Order <input name="sort_order" type="number" min="0" value="{{ $link['sort_order'] }}"></label>

                                        <label class="dxm-wide dxm-image-field">
                                            Image URL
                                            <div class="dxm-image-input">
                                                <input id="watch-image-{{ $link['id'] }}" name="image_url" value="{{ $link['image_url'] }}">
                                                <button type="button" x-on:click="openPicker('#watch-image-{{ $link['id'] }}')">{!! $svg['image'] !!} Library</button>
                                            </div>
                                        </label>

                                        <label class="dxm-toggle"><input name="is_enabled" type="checkbox" value="1" @checked($link['is_enabled'])> Enabled</label>

                                        <div class="dxm-form-actions dxm-wide">
                                            <button type="submit" class="dxm-btn dxm-btn-primary">Save Changes</button>
                                            <button type="button" class="dxm-btn dxm-btn-muted" onclick="this.closest('dialog').close()">Cancel</button>
                                        </div>
                                    </form>
                                </dialog>
                            @empty
                                <div class="dxm-empty">
                                    <div>{!! $svg['tv'] !!}</div>
                                    <strong>No {{ strtolower($label) }} links yet.</strong>
                                    <p>Add a watch link for this group. Existing advanced data remains safe.</p>
                                    <button type="button" class="dxm-btn dxm-btn-primary" x-on:click="createOpen = true">+ Add Watch Link</button>
                                </div>
                            @endforelse
                        </div>
                    </section>
                @endforeach
            </div>

            <aside class="dxm-preview-panel">
                <div class="dxm-preview-head">
                    <strong>Live Preview</strong>
                    <small>Click any card to preview it here.</small>
                </div>

                <template x-if="preview">
                    <div class="dxm-preview-card">
                        <div class="dxm-preview-thumb">
                            <template x-if="preview.image_url">
                                <img x-bind:src="preview.image_url" alt="">
                            </template>
                            <template x-if="!preview.image_url">
                                <div class="dxm-thumb-icon">{!! $svg['tv'] !!}</div>
                            </template>
                            <span x-text="preview.label || 'WATCH'"></span>
                        </div>
                        <div class="dxm-preview-body">
                            <h2 x-text="preview.title"></h2>
                            <p x-text="preview.subtitle || 'No subtitle yet'"></p>
                            <div class="dxm-meta-row">
                                <span x-text="preview.kind_label"></span>
                                <span x-text="preview.player || 'auto'"></span>
                                <span x-text="preview.is_enabled ? 'Enabled' : 'Disabled'"></span>
                                <span x-text="preview.usage_label || 'Placement unknown'"></span>
                            </div>
                            <div class="dxm-preview-url" x-text="preview.url || 'No URL set yet'"></div>
                            <template x-if="preview.usage && preview.usage.length">
                                <div class="dxm-preview-usage">
                                    <strong>Destination placement</strong>
                                    <template x-for="place in preview.usage" :key="place.tab + place.section + place.item">
                                        <a x-bind:href="place.edit_url" x-text="place.tab.charAt(0).toUpperCase() + place.tab.slice(1) + ' → ' + place.section"></a>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </div>
                </template>
            </aside>
        </section>

        <dialog id="dxm-watch-image-picker" class="dxm-picker-dialog">
            <div class="dxm-panel-title">
                <div><strong>Select Watch Image</strong><small>Choose an existing media image for this watch card.</small></div>
                <button type="button" x-on:click="closePicker()">×</button>
            </div>

            <div class="dxm-picker-grid">
                <template x-for="asset in imageAssets" :key="asset.url">
                    <button type="button" class="dxm-picker-item" x-on:click="chooseImage(asset.url)">
                        <img x-bind:src="asset.url" alt="">
                        <strong x-text="asset.label"></strong>
                        <small x-text="asset.bucket || 'media'"></small>
                    </button>
                </template>
            </div>

            <template x-if="imageAssets.length === 0">
                <div class="dxm-empty">
                    <strong>No image assets found.</strong>
                    <p>Upload images in Media Library first, or paste an image URL directly.</p>
                </div>
            </template>
        </dialog>
    </div>

    <style>
        [x-cloak]{display:none!important}.dxm-watch{display:grid;gap:18px;max-width:1320px}.dxm-notice{border:1px solid rgba(34,197,94,.28);background:rgba(34,197,94,.10);color:#dcfce7;padding:12px 14px;border-radius:16px;font-size:13px;font-weight:850}.dxm-watch-hero{display:flex;justify-content:space-between;gap:18px;align-items:center;border:1px solid rgba(34,211,238,.20);border-radius:28px;padding:22px;background:radial-gradient(circle at 10% 0%,rgba(34,211,238,.18),transparent 34%),radial-gradient(circle at 100% 0%,rgba(168,85,247,.16),transparent 36%),linear-gradient(135deg,rgba(2,6,23,.94),rgba(15,23,42,.90))}.dxm-kicker{display:inline-flex;padding:7px 11px;border-radius:999px;border:1px solid rgba(34,211,238,.35);background:rgba(34,211,238,.10);color:#cffafe;font-size:11px;font-weight:950;letter-spacing:.08em;text-transform:uppercase}.dxm-watch-hero h1{margin:11px 0 0;color:#fff;font-size:clamp(29px,4vw,43px);line-height:1.05;font-weight:950;letter-spacing:-.05em}.dxm-watch-hero p{max-width:860px;margin:10px 0 0;color:rgba(255,255,255,.68);font-size:14px;line-height:1.6}.dxm-hero-chips,.dxm-meta-row{display:flex;gap:7px;flex-wrap:wrap;margin-top:13px}.dxm-hero-chips span,.dxm-meta-row span{display:inline-flex;align-items:center;min-height:25px;border-radius:999px;border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.06);padding:5px 8px;color:rgba(255,255,255,.72);font-size:10.5px;font-weight:900;text-transform:uppercase;letter-spacing:.04em}.dxm-hero-actions,.dxm-form-actions,.dxm-card-actions{display:flex;gap:9px;flex-wrap:wrap}.dxm-btn{display:inline-flex;align-items:center;justify-content:center;min-height:39px;border-radius:13px;padding:9px 13px;border:1px solid rgba(255,255,255,.12);text-decoration:none;font-size:12px;font-weight:950;transition:.18s ease;cursor:pointer}.dxm-btn-primary{color:#fff;background:linear-gradient(135deg,rgba(34,211,238,.34),rgba(59,130,246,.22));border-color:rgba(34,211,238,.48)}.dxm-btn-muted{color:#fff;background:rgba(255,255,255,.055);border-color:rgba(255,255,255,.12)}.dxm-btn-small{min-height:33px;padding:7px 10px}.dxm-create-panel,.dxm-tab-panel,.dxm-preview-panel{border:1px solid rgba(255,255,255,.09);border-radius:24px;background:rgba(2,6,23,.42);overflow:hidden}.dxm-panel-title{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.08)}.dxm-panel-title strong{display:block;color:#fff;font-size:15px}.dxm-panel-title small{display:block;color:rgba(255,255,255,.55);font-size:12px;margin-top:3px}.dxm-panel-title button{width:34px;height:34px;border-radius:999px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.08);color:#fff;font-size:19px;cursor:pointer}.dxm-watch-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;padding:15px}.dxm-watch-form label{display:grid;gap:7px;color:#fff;font-weight:900;font-size:12px}.dxm-watch-form input,.dxm-watch-form select,.dxm-watch-form textarea{width:100%;border:1px solid rgba(255,255,255,.10);border-radius:13px;background:rgba(15,23,42,.80);color:#fff;padding:11px 12px;outline:none}.dxm-watch-form textarea{resize:vertical}.dxm-wide{grid-column:1/-1}.dxm-toggle{display:flex!important;grid-template-columns:auto 1fr!important;align-items:center;gap:9px}.dxm-toggle input{width:auto}.dxm-image-input{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:8px}.dxm-image-input button{display:inline-flex;gap:6px;align-items:center;border:1px solid rgba(34,211,238,.30);background:rgba(34,211,238,.08);color:#e0faff;border-radius:13px;padding:9px 11px;font-weight:950;cursor:pointer}.dxm-image-input svg{width:17px;height:17px}.dxm-watch-grid{display:grid;grid-template-columns:minmax(0,1fr) 370px;gap:16px;align-items:start}.dxm-watch-main{display:grid;gap:14px}.dxm-tabs{display:flex;gap:8px;overflow-x:auto;border:1px solid rgba(255,255,255,.08);border-radius:18px;padding:9px;background:rgba(255,255,255,.035)}.dxm-tab{flex:0 0 auto;display:inline-flex;align-items:center;gap:7px;border:1px solid rgba(255,255,255,.10);background:rgba(15,23,42,.75);color:rgba(255,255,255,.75);border-radius:14px;padding:10px 12px;font-weight:950;cursor:pointer}.dxm-tab.active{border-color:rgba(34,211,238,.55);background:rgba(34,211,238,.12);color:#fff}.dxm-tab span{display:grid;place-items:center;min-width:22px;height:22px;border-radius:999px;background:rgba(255,255,255,.09);font-size:11px}.dxm-section-head{display:flex;justify-content:space-between;gap:12px;padding:15px;border-bottom:1px solid rgba(255,255,255,.08)}.dxm-section-head strong{display:block;color:#fff;font-size:17px}.dxm-section-head small{display:block;color:rgba(255,255,255,.58);margin-top:4px;line-height:1.45}.dxm-link-list{display:grid;gap:12px;padding:14px}.dxm-watch-card{display:grid;grid-template-columns:210px minmax(0,1fr);gap:0;border:1px solid rgba(255,255,255,.08);border-radius:22px;background:rgba(15,23,42,.55);overflow:hidden;cursor:pointer;transition:.18s ease}.dxm-watch-card:hover{border-color:rgba(34,211,238,.34);transform:translateY(-1px)}.dxm-watch-thumb,.dxm-preview-thumb{position:relative;min-height:145px;background:linear-gradient(135deg,#0B1F4D,#1D5CFF,#E2388A);display:grid;place-items:center;overflow:hidden}.dxm-watch-thumb img,.dxm-preview-thumb img,.dxm-edit-preview-img img{width:100%;height:100%;object-fit:cover}.dxm-thumb-icon{width:58px;height:58px;border-radius:20px;background:rgba(255,255,255,.10);display:grid;place-items:center;color:#fff}.dxm-thumb-icon svg{width:29px;height:29px}.dxm-watch-badge,.dxm-preview-thumb span,.dxm-edit-preview-img span{position:absolute;top:10px;left:10px;display:inline-flex;border-radius:999px;background:rgba(0,0,0,.55);color:#fff;padding:6px 9px;font-size:10px;font-weight:950;letter-spacing:.06em}.dxm-watch-card-body{padding:13px;display:grid;gap:10px}.dxm-watch-card-top{display:flex;justify-content:space-between;gap:10px}.dxm-watch-card h3{margin:0;color:#fff;font-size:17px;font-weight:950}.dxm-watch-card p{margin:5px 0 0;color:rgba(255,255,255,.58);font-size:12px;line-height:1.4}.dxm-status{display:inline-flex;height:27px;align-items:center;border-radius:999px;padding:5px 8px;font-size:10px;font-weight:950}.dxm-status.on{background:rgba(34,197,94,.13);color:#bbf7d0;border:1px solid rgba(34,197,94,.28)}.dxm-status.off{background:rgba(239,68,68,.12);color:#fecaca;border:1px solid rgba(239,68,68,.24)}.dxm-url-line,.dxm-preview-url{border:1px solid rgba(255,255,255,.08);background:rgba(2,6,23,.38);border-radius:12px;padding:9px;color:rgba(255,255,255,.55);font-size:11px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.dxm-card-actions button{width:34px;height:34px;display:grid;place-items:center;border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.055);border-radius:11px;color:#fff;cursor:pointer}.dxm-card-actions svg{width:17px;height:17px}.dxm-usage-box{border:1px solid rgba(34,211,238,.13);background:rgba(34,211,238,.055);border-radius:16px;padding:10px}.dxm-usage-title{display:flex;align-items:center;gap:8px;color:#fff}.dxm-usage-title svg{width:16px;height:16px;color:#67e8f9}.dxm-usage-title strong{font-size:12px}.dxm-usage-title span{margin-left:auto;color:rgba(255,255,255,.55);font-size:11px;font-weight:900}.dxm-usage-list{display:flex;flex-wrap:wrap;gap:6px;margin-top:8px}.dxm-usage-chip{display:inline-flex;padding:6px 8px;border-radius:999px;border:1px solid rgba(34,211,238,.20);background:rgba(34,211,238,.08);color:#e0faff;text-decoration:none;font-size:11px;font-weight:850}.dxm-muted{display:block;color:rgba(255,255,255,.48);font-size:11.5px;line-height:1.4;margin-top:6px}.dxm-preview-panel{position:sticky;top:92px}.dxm-preview-head{padding:15px;border-bottom:1px solid rgba(255,255,255,.08)}.dxm-preview-head strong{display:block;color:#fff}.dxm-preview-head small{display:block;color:rgba(255,255,255,.55);font-size:12px;margin-top:3px}.dxm-preview-card{padding:14px}.dxm-preview-thumb{border-radius:20px;height:210px}.dxm-preview-body{padding:13px}.dxm-preview-body h2{margin:0;color:#fff;font-size:24px;font-weight:950;line-height:1.1}.dxm-preview-body p{color:rgba(255,255,255,.62);line-height:1.5}.dxm-preview-usage{margin-top:12px;border:1px solid rgba(34,211,238,.16);background:rgba(34,211,238,.07);border-radius:16px;padding:12px}.dxm-preview-usage strong{display:block;color:#fff;margin-bottom:8px}.dxm-preview-usage a{display:block;color:#a5f3fc;text-decoration:none;font-size:12px;margin-top:5px}.dxm-empty{border:1px dashed rgba(255,255,255,.18);border-radius:22px;background:rgba(2,6,23,.24);padding:28px;text-align:center;color:#fff}.dxm-empty svg{width:42px;height:42px}.dxm-empty p{color:rgba(255,255,255,.60)}.dxm-edit-dialog{width:min(900px,96vw);border:1px solid rgba(34,211,238,.22);border-radius:24px;background:#070b18;color:#fff;padding:0;box-shadow:0 30px 90px rgba(0,0,0,.55)}.dxm-edit-dialog::backdrop{background:rgba(0,0,0,.72);backdrop-filter:blur(8px)}.dxm-edit-preview{display:grid;grid-template-columns:190px minmax(0,1fr);gap:14px;border:1px solid rgba(34,211,238,.15);background:linear-gradient(135deg,rgba(34,211,238,.08),rgba(168,85,247,.08));border-radius:20px;padding:12px}.dxm-edit-preview-img{position:relative;border-radius:16px;min-height:120px;background:linear-gradient(135deg,#0B1F4D,#1D5CFF,#E2388A);overflow:hidden;display:grid;place-items:center}.dxm-edit-preview strong{display:block;color:#fff;font-size:18px}.dxm-edit-preview p{color:rgba(255,255,255,.60)}.dxm-picker-dialog{width:min(1050px,96vw);max-height:88vh;border:1px solid rgba(34,211,238,.22);border-radius:24px;background:#070b18;color:#fff;padding:0;box-shadow:0 30px 90px rgba(0,0,0,.70)}.dxm-picker-dialog::backdrop{background:rgba(0,0,0,.78);backdrop-filter:blur(10px)}.dxm-picker-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:12px;padding:14px;max-height:70vh;overflow:auto}.dxm-picker-item{border:1px solid rgba(255,255,255,.08);border-radius:18px;background:rgba(15,23,42,.7);overflow:hidden;text-align:left;color:#fff;cursor:pointer;padding:0}.dxm-picker-item:hover{border-color:rgba(34,211,238,.38);transform:translateY(-1px)}.dxm-picker-item img{width:100%;height:110px;object-fit:cover;display:block}.dxm-picker-item strong{display:block;padding:10px 10px 0;font-size:12px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.dxm-picker-item small{display:block;padding:3px 10px 10px;color:rgba(255,255,255,.55);font-size:11px}@media(max-width:1180px){.dxm-watch-grid{grid-template-columns:1fr}.dxm-preview-panel{position:relative;top:auto}.dxm-watch-card{grid-template-columns:180px minmax(0,1fr)}}@media(max-width:760px){.dxm-watch-hero,.dxm-section-head,.dxm-watch-card-top{display:block}.dxm-hero-actions{margin-top:14px}.dxm-watch-form{grid-template-columns:1fr}.dxm-watch-card{grid-template-columns:1fr}.dxm-watch-thumb{height:170px}.dxm-image-input{grid-template-columns:1fr}.dxm-btn{width:100%}.dxm-edit-preview{grid-template-columns:1fr}}
    </style>
</x-filament::page>
