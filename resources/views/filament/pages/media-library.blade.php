<x-filament::page>
    @php
        $activeAppId = (int) (\App\Support\ActiveApp::ensureId() ?? 0);
        $activeApp = $activeAppId > 0 ? \App\Models\App::query()->find($activeAppId) : null;

        $tabs = [
            'all' => ['label' => 'All', 'description' => 'Everything available to this app.'],
            'images' => ['label' => 'Images', 'description' => 'All image assets.'],
            'banners' => ['label' => 'Banners', 'description' => 'Homepage, watch, article, and promo banners.'],
            'logos' => ['label' => 'Logos', 'description' => 'App logos and brand identity files.'],
            'icons' => ['label' => 'Icons', 'description' => 'Icon graphics and reusable visual marks.'],
            'videos' => ['label' => 'Videos', 'description' => 'Video files and video-related media.'],
            'documents' => ['label' => 'Documents', 'description' => 'PDFs, files, and document assets.'],
            'shared' => ['label' => 'Shared', 'description' => 'Global assets available to all apps.'],
        ];

        $activeTab = strtolower((string) request('tab', 'all'));
        if (! array_key_exists($activeTab, $tabs)) {
            $activeTab = 'all';
        }

        $search = trim((string) request('q', ''));

        $baseQuery = \App\Models\MediaAsset::query()
            ->where(function ($query) use ($activeAppId, $activeTab) {
                if ($activeTab === 'shared') {
                    $query->whereNull('app_id');
                    return;
                }

                if ($activeAppId > 0) {
                    $query->whereNull('app_id')->orWhere('app_id', $activeAppId);
                    return;
                }

                $query->whereNull('app_id');
            });

        $tabQuery = clone $baseQuery;

        if ($activeTab === 'images') {
            $tabQuery->where(function ($query) {
                $query->where('type', 'image')->orWhere('mime', 'like', 'image/%');
            });
        } elseif ($activeTab === 'banners') {
            $tabQuery->where('bucket', 'banners');
        } elseif ($activeTab === 'logos') {
            $tabQuery->whereIn('bucket', ['logos', 'branding']);
        } elseif ($activeTab === 'icons') {
            $tabQuery->whereIn('bucket', ['icons', 'icon-presets']);
        } elseif ($activeTab === 'videos') {
            $tabQuery->where(function ($query) {
                $query->where('type', 'video')->orWhere('mime', 'like', 'video/%');
            });
        } elseif ($activeTab === 'documents') {
            $tabQuery->where(function ($query) {
                $query->whereIn('type', ['file', 'pdf', 'document'])
                    ->orWhere('mime', 'like', 'application/%')
                    ->orWhere('mime', 'like', 'text/%');
            });
        }

        if ($search !== '') {
            $tabQuery->where(function ($query) use ($search) {
                $query->where('label', 'like', '%' . $search . '%')
                    ->orWhere('bucket', 'like', '%' . $search . '%')
                    ->orWhere('type', 'like', '%' . $search . '%')
                    ->orWhere('url', 'like', '%' . $search . '%')
                    ->orWhere('path', 'like', '%' . $search . '%');
            });
        }

        $assets = $tabQuery
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(36)
            ->withQueryString();

        $countFor = function (string $tabKey) use ($baseQuery, $activeAppId) {
            $query = clone $baseQuery;

            if ($tabKey === 'shared') {
                $query = \App\Models\MediaAsset::query()->whereNull('app_id');
            } elseif ($tabKey === 'images') {
                $query->where(function ($inner) {
                    $inner->where('type', 'image')->orWhere('mime', 'like', 'image/%');
                });
            } elseif ($tabKey === 'banners') {
                $query->where('bucket', 'banners');
            } elseif ($tabKey === 'logos') {
                $query->whereIn('bucket', ['logos', 'branding']);
            } elseif ($tabKey === 'icons') {
                $query->whereIn('bucket', ['icons', 'icon-presets']);
            } elseif ($tabKey === 'videos') {
                $query->where(function ($inner) {
                    $inner->where('type', 'video')->orWhere('mime', 'like', 'video/%');
                });
            } elseif ($tabKey === 'documents') {
                $query->where(function ($inner) {
                    $inner->whereIn('type', ['file', 'pdf', 'document'])
                        ->orWhere('mime', 'like', 'application/%')
                        ->orWhere('mime', 'like', 'text/%');
                });
            }

            return (int) $query->count();
        };

        $publicUrlFor = function ($asset) {
            $url = trim((string) ($asset->url ?? ''));
            if ($url !== '') {
                return $url;
            }

            $disk = (string) ($asset->disk ?? 'public');
            $path = trim((string) ($asset->path ?? ''));

            if ($path === '') {
                return '';
            }

            try {
                return (string) \Illuminate\Support\Facades\Storage::disk($disk)->url($path);
            } catch (\Throwable $e) {
                return '';
            }
        };

        $isImage = function ($asset) {
            $mime = strtolower((string) ($asset->mime ?? ''));
            $type = strtolower((string) ($asset->type ?? ''));

            return $type === 'image' || str_starts_with($mime, 'image/');
        };

        $isVideo = function ($asset) {
            $mime = strtolower((string) ($asset->mime ?? ''));
            $type = strtolower((string) ($asset->type ?? ''));

            return $type === 'video' || str_starts_with($mime, 'video/');
        };

        $humanSize = function ($bytes) {
            $bytes = (int) ($bytes ?? 0);
            if ($bytes <= 0) {
                return '-';
            }

            $units = ['B', 'KB', 'MB', 'GB'];
            $i = 0;
            $value = (float) $bytes;

            while ($value >= 1024 && $i < count($units) - 1) {
                $value /= 1024;
                $i++;
            }

            return rtrim(rtrim(number_format($value, 2), '0'), '.') . ' ' . $units[$i];
        };

        $stats = [
            'total' => $countFor('all'),
            'images' => $countFor('images'),
            'banners' => $countFor('banners'),
            'videos' => $countFor('videos'),
        ];
    @endphp

    <style>
        .dxm-media-shell{display:grid;gap:16px;padding-bottom:28px}.dxm-media-hero{border:1px solid rgba(34,211,238,.18);border-radius:26px;padding:20px;background:radial-gradient(circle at top left,rgba(34,211,238,.18),transparent 34%),radial-gradient(circle at top right,rgba(168,85,247,.16),transparent 34%),linear-gradient(135deg,rgba(2,6,23,.96),rgba(15,23,42,.88))}.dxm-media-hero-inner{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:18px;align-items:center}.dxm-kicker{display:inline-flex;align-items:center;min-height:28px;padding:6px 11px;border-radius:999px;border:1px solid rgba(34,211,238,.34);background:rgba(34,211,238,.10);color:rgba(207,250,254,.96);font-size:11px;font-weight:950;letter-spacing:.08em;text-transform:uppercase}.dxm-title{margin:11px 0 0;color:#fff;font-size:clamp(28px,4vw,42px);line-height:1.03;font-weight:950;letter-spacing:-.055em}.dxm-sub{margin-top:9px;max-width:900px;color:rgba(255,255,255,.66);font-size:13px;line-height:1.6}.dxm-stat-grid{display:grid;grid-template-columns:repeat(2,minmax(120px,1fr));gap:10px;min-width:280px}.dxm-stat{padding:14px;border:1px solid rgba(255,255,255,.10);border-radius:18px;background:rgba(2,6,23,.48)}.dxm-stat strong{display:block;color:#fff;font-size:22px;line-height:1}.dxm-stat span{display:block;margin-top:6px;color:rgba(255,255,255,.58);font-size:11px;font-weight:850}.dxm-media-tabs{position:sticky;top:72px;z-index:20;display:flex;gap:8px;flex-wrap:wrap;border:1px solid rgba(255,255,255,.08);border-radius:20px;padding:9px;background:rgba(3,7,18,.88);backdrop-filter:blur(14px)}.dxm-media-tab{display:inline-flex;gap:7px;align-items:center;min-height:36px;border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.045);color:rgba(255,255,255,.76);border-radius:999px;padding:8px 12px;font-size:12px;font-weight:900;text-decoration:none}.dxm-media-tab:hover,.dxm-media-tab.active{border-color:rgba(34,211,238,.45);background:rgba(34,211,238,.12);color:#fff}.dxm-media-tab span{display:inline-grid;place-items:center;min-width:21px;height:21px;border-radius:999px;background:rgba(255,255,255,.08);font-size:10px;color:rgba(255,255,255,.72)}.dxm-panel{border:1px solid rgba(255,255,255,.09);border-radius:22px;padding:15px;background:rgba(255,255,255,.035)}.dxm-toolbar{display:flex;gap:10px;flex-wrap:wrap;align-items:center;justify-content:space-between}.dxm-search{min-width:min(420px,100%);flex:1}.dxm-search input{width:100%;min-height:42px;border-radius:14px;border:1px solid rgba(255,255,255,.12);background:rgba(2,6,23,.45);color:#fff;padding:10px 13px}.dxm-actions{display:flex;gap:8px;flex-wrap:wrap}.dxm-btn{display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:8px 12px;border-radius:12px;border:1px solid rgba(255,255,255,.11);background:rgba(255,255,255,.055);color:rgba(255,255,255,.84);font-size:12px;font-weight:900;text-decoration:none;cursor:pointer}.dxm-btn.primary{border-color:rgba(34,211,238,.30);background:rgba(34,211,238,.10);color:#e0faff}.dxm-media-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(220px,1fr));gap:12px}.dxm-asset-card{border:1px solid rgba(255,255,255,.09);border-radius:20px;background:rgba(2,6,23,.42);overflow:hidden;min-height:280px;display:grid;grid-template-rows:150px auto;transition:.16s ease}.dxm-asset-card:hover{transform:translateY(-1px);border-color:rgba(34,211,238,.32)}.dxm-preview{position:relative;background:linear-gradient(135deg,rgba(15,23,42,.9),rgba(30,41,59,.72));display:grid;place-items:center;overflow:hidden}.dxm-preview img{width:100%;height:100%;object-fit:cover}.dxm-preview video{width:100%;height:100%;object-fit:cover}.dxm-file-glyph{width:74px;height:74px;border-radius:24px;background:rgba(255,255,255,.08);display:grid;place-items:center;color:#fff;font-size:28px;font-weight:950}.dxm-scope-badge{position:absolute;left:10px;top:10px;display:inline-flex;min-height:25px;align-items:center;border-radius:999px;padding:5px 8px;background:rgba(0,0,0,.45);backdrop-filter:blur(8px);color:#fff;font-size:10px;font-weight:950;text-transform:uppercase}.dxm-asset-body{padding:13px;display:grid;gap:10px}.dxm-asset-title{color:#fff;font-size:14px;font-weight:950;line-height:1.25;word-break:break-word}.dxm-asset-meta{display:flex;gap:6px;flex-wrap:wrap}.dxm-pill{display:inline-flex;align-items:center;min-height:23px;padding:5px 8px;border-radius:999px;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.055);color:rgba(255,255,255,.68);font-size:10px;font-weight:950;text-transform:uppercase;letter-spacing:.04em}.dxm-url{font-size:10.5px;color:rgba(255,255,255,.48);line-height:1.35;word-break:break-all;max-height:42px;overflow:hidden}.dxm-card-actions{display:flex;gap:7px;flex-wrap:wrap}.dxm-empty{border:1px dashed rgba(255,255,255,.18);border-radius:20px;padding:22px;color:rgba(255,255,255,.65);background:rgba(2,6,23,.25);text-align:center;line-height:1.6}.dxm-pagination{margin-top:14px}.dxm-modal-backdrop{position:fixed;inset:0;z-index:9998;background:rgba(0,0,0,.72);backdrop-filter:blur(10px);display:grid;place-items:center;padding:22px}.dxm-modal-card{width:min(980px,96vw);max-height:88vh;border:1px solid rgba(34,211,238,.30);border-radius:24px;background:linear-gradient(135deg,rgba(2,6,23,.98),rgba(15,23,42,.96));box-shadow:0 24px 90px rgba(0,0,0,.55);overflow:hidden;display:grid;grid-template-rows:auto minmax(0,1fr) auto}.dxm-modal-head{display:flex;justify-content:space-between;align-items:center;gap:12px;padding:15px 17px;border-bottom:1px solid rgba(255,255,255,.08)}.dxm-modal-head strong{color:#fff;font-size:15px;font-weight:950}.dxm-modal-close{width:40px;height:40px;border-radius:999px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.07);color:#fff;font-size:22px;line-height:1;cursor:pointer}.dxm-modal-body{min-height:280px;display:grid;place-items:center;background:rgba(0,0,0,.20);overflow:auto}.dxm-modal-body img{max-width:100%;max-height:70vh;object-fit:contain}.dxm-modal-body video{max-width:100%;max-height:70vh}.dxm-modal-file{padding:34px;text-align:center;color:rgba(255,255,255,.72)}.dxm-modal-foot{display:flex;justify-content:flex-end;gap:8px;padding:13px 17px;border-top:1px solid rgba(255,255,255,.08)}.dxm-toast{position:fixed;right:18px;bottom:18px;z-index:9999;border:1px solid rgba(34,211,238,.30);border-radius:14px;background:rgba(3,7,18,.92);color:#e0faff;padding:10px 12px;font-size:12px;font-weight:900;box-shadow:0 18px 60px rgba(0,0,0,.35)}@media(max-width:980px){.dxm-media-hero-inner{grid-template-columns:1fr}.dxm-stat-grid{min-width:0}}@media(max-width:760px){.dxm-media-tabs{top:56px;overflow-x:auto;flex-wrap:nowrap}.dxm-media-tab{flex:0 0 auto}.dxm-stat-grid{grid-template-columns:1fr}.dxm-media-grid{grid-template-columns:repeat(auto-fill,minmax(165px,1fr))}.dxm-asset-card{grid-template-rows:125px auto}}
    </style>

    <div class="dxm-media-shell" x-data="{ copied: false, previewOpen: false, previewType: null, previewUrl: null, previewTitle: null, copy(text) { navigator.clipboard.writeText(text); this.copied = true; setTimeout(() => this.copied = false, 1700); }, openPreview(type, url, title) { this.previewType = type; this.previewUrl = url; this.previewTitle = title; this.previewOpen = true; document.body.style.overflow = 'hidden'; }, closePreview() { this.previewOpen = false; this.previewType = null; this.previewUrl = null; this.previewTitle = null; document.body.style.overflow = ''; } }" x-on:keydown.escape.window="closePreview()">
        <section class="dxm-media-hero">
            <div class="dxm-media-hero-inner">
                <div>
                    <div class="dxm-kicker">Beginner Media Library</div>
                    <h1 class="dxm-title">{{ $activeApp?->name ?? 'Selected App' }} Media Library</h1>
                    <div class="dxm-sub">
                        Manage app-scoped and shared media visually. Use this page for banners, logos, thumbnails, videos, documents, and files without opening the technical table first.
                    </div>
                </div>
                <div class="dxm-stat-grid">
                    <div class="dxm-stat"><strong>{{ $stats['total'] }}</strong><span>Available Assets</span></div>
                    <div class="dxm-stat"><strong>{{ $stats['images'] }}</strong><span>Images</span></div>
                    <div class="dxm-stat"><strong>{{ $stats['banners'] }}</strong><span>Banners</span></div>
                    <div class="dxm-stat"><strong>{{ $stats['videos'] }}</strong><span>Videos</span></div>
                </div>
            </div>
        </section>

        <nav class="dxm-media-tabs" aria-label="Media library categories">
            @foreach ($tabs as $key => $data)
                <a class="dxm-media-tab {{ $activeTab === $key ? 'active' : '' }}" href="{{ url('/admin/media-library') }}?tab={{ $key }}">{{ $data['label'] }} <span>{{ $countFor($key) }}</span></a>
            @endforeach
        </nav>

        <section class="dxm-panel">
            <div class="dxm-toolbar">
                <div>
                    <div class="dxm-kicker">{{ $tabs[$activeTab]['label'] }}</div>
                    <div class="dxm-sub">{{ $tabs[$activeTab]['description'] }}</div>
                </div>

                <form class="dxm-search" method="GET" action="{{ url('/admin/media-library') }}">
                    <input type="hidden" name="tab" value="{{ $activeTab }}">
                    <input type="search" name="q" value="{{ $search }}" placeholder="Search by label, bucket, type, URL, or path...">
                </form>

                <div class="dxm-actions">
                    <a href="{{ url('/admin/media-assets/create') }}" class="dxm-btn primary">+ Upload Media</a>
                    <a href="{{ url('/admin/media-assets') }}" class="dxm-btn">Advanced Table</a>
                </div>
            </div>
        </section>

        @if ($assets->count() > 0)
            <section class="dxm-media-grid">
                @foreach ($assets as $asset)
                    @php
                        $url = $publicUrlFor($asset);
                        $title = trim((string) ($asset->label ?? '')) ?: ('Media Asset #' . $asset->id);
                        $scope = $asset->app_id ? 'App' : 'Shared';
                        $glyph = $isVideo($asset) ? '▶' : (($asset->type === 'pdf') ? 'PDF' : 'FILE');
                    @endphp

                    <article class="dxm-asset-card">
                        <div class="dxm-preview">
                            <span class="dxm-scope-badge">{{ $scope }}</span>

                            @if ($isImage($asset) && $url)
                                <img src="{{ $url }}" alt="{{ $title }}">
                            @elseif ($isVideo($asset) && $url)
                                <video src="{{ $url }}" muted preload="metadata"></video>
                            @else
                                <div class="dxm-file-glyph">{{ $glyph }}</div>
                            @endif
                        </div>

                        <div class="dxm-asset-body">
                            <div>
                                <div class="dxm-asset-title">{{ $title }}</div>
                                <div class="dxm-url">{{ $url ?: ($asset->path ?: 'No URL/path available') }}</div>
                            </div>

                            <div class="dxm-asset-meta">
                                <span class="dxm-pill">{{ $asset->bucket ?: 'misc' }}</span>
                                <span class="dxm-pill">{{ $asset->type ?: 'file' }}</span>
                                <span class="dxm-pill">{{ $asset->is_active ? 'Active' : 'Inactive' }}</span>
                                @if ($asset->width && $asset->height)
                                    <span class="dxm-pill">{{ $asset->width }}×{{ $asset->height }}</span>
                                @endif
                                @if ($asset->size)
                                    <span class="dxm-pill">{{ $humanSize($asset->size) }}</span>
                                @endif
                            </div>

                            <div class="dxm-card-actions">
                                @if ($url)
                                    <button type="button" class="dxm-btn primary" x-on:click="copy(@js($url))">Copy URL</button>
                                    <button type="button" class="dxm-btn" x-on:click="openPreview(@js($isVideo($asset) ? 'video' : ($isImage($asset) ? 'image' : 'file')), @js($url), @js($title))">Preview</button>
                                @endif
                                <a href="{{ url('/admin/media-assets/' . $asset->id . '/edit') }}" class="dxm-btn">Edit</a>
                            </div>
                        </div>
                    </article>
                @endforeach
            </section>

            <div class="dxm-pagination">{{ $assets->links() }}</div>
        @else
            <div class="dxm-empty">
                No media found for this tab yet. Upload new media or switch to another category.
                <br><br>
                <a href="{{ url('/admin/media-assets/create') }}" class="dxm-btn primary">+ Upload Media</a>
            </div>
        @endif

        <template x-if="previewOpen">
            <div class="dxm-modal-backdrop" x-on:click.self="closePreview()" x-transition>
                <div class="dxm-modal-card">
                    <div class="dxm-modal-head">
                        <strong x-text="previewTitle || 'Media Preview'"></strong>
                        <button type="button" class="dxm-modal-close" x-on:click="closePreview()">×</button>
                    </div>

                    <div class="dxm-modal-body">
                        <template x-if="previewType === 'image'">
                            <img :src="previewUrl" :alt="previewTitle || 'Media preview'">
                        </template>

                        <template x-if="previewType === 'video'">
                            <video :src="previewUrl" controls autoplay></video>
                        </template>

                        <template x-if="previewType === 'file'">
                            <div class="dxm-modal-file">
                                <div style="font-size:44px;font-weight:950;margin-bottom:10px;">FILE</div>
                                <div>This file cannot be rendered directly inside the preview modal.</div>
                                <div style="margin-top:12px;word-break:break-all;" x-text="previewUrl"></div>
                            </div>
                        </template>
                    </div>

                    <div class="dxm-modal-foot">
                        <button type="button" class="dxm-btn" x-on:click="copy(previewUrl)">Copy URL</button>
                        <button type="button" class="dxm-btn primary" x-on:click="closePreview()">Close Preview</button>
                    </div>
                </div>
            </div>
        </template>

        <div x-show="copied" x-transition class="dxm-toast">URL copied</div>
    </div>
</x-filament::page>
