<x-filament-panels::page>
    <style>
        .be-shell{max-width:1180px;margin:0 auto;color:#f8fafc;font-family:Inter,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif;}
        .be-stack{display:flex;flex-direction:column;gap:22px;}
        .be-hero{position:relative;overflow:hidden;border:1px solid rgba(34,211,238,.24);border-radius:28px;background:linear-gradient(135deg,#07111f 0%,#0b1020 42%,#11162c 100%);box-shadow:0 22px 60px rgba(0,0,0,.32);}
        .be-hero:before{content:"";position:absolute;inset:0;background:radial-gradient(circle at 10% 5%,rgba(34,211,238,.24),transparent 32%),radial-gradient(circle at 85% 0%,rgba(168,85,247,.28),transparent 36%),radial-gradient(circle at 80% 95%,rgba(236,72,153,.12),transparent 34%);pointer-events:none;}
        .be-hero-inner{position:relative;padding:24px;display:grid;grid-template-columns:minmax(0,1fr) 430px;gap:22px;align-items:center;}
        .be-pill{display:inline-flex;align-items:center;gap:8px;border:1px solid rgba(103,232,249,.35);background:rgba(8,145,178,.16);color:#a5f3fc;border-radius:999px;padding:7px 11px;font-size:11px;font-weight:900;text-transform:uppercase;letter-spacing:.16em;}
        .be-dot{width:8px;height:8px;border-radius:999px;background:#22d3ee;box-shadow:0 0 18px rgba(34,211,238,.8);}
        .be-title{margin:14px 0 0;font-size:36px;line-height:1.05;font-weight:950;letter-spacing:-.04em;color:#fff;}
        .be-subtitle{margin:12px 0 0;max-width:760px;color:#cbd5e1;font-size:14px;line-height:1.75;}
        .be-tags{display:flex;flex-wrap:wrap;gap:8px;margin-top:16px;}
        .be-tag{border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.06);border-radius:999px;padding:7px 10px;color:#cbd5e1;font-size:11px;font-weight:850;text-transform:uppercase;letter-spacing:.04em;}
        .be-stat-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;}
        .be-stat{border:1px solid rgba(255,255,255,.1);background:rgba(3,7,18,.46);border-radius:18px;padding:13px 10px;text-align:center;backdrop-filter:blur(10px);}
        .be-stat-value{font-size:25px;font-weight:950;line-height:1;color:#fff;}
        .be-stat-label{margin-top:7px;color:#94a3b8;font-size:10px;font-weight:900;text-transform:uppercase;letter-spacing:.08em;}
        .be-tabs{position:relative;padding:0 24px 24px;display:grid;grid-template-columns:repeat(5,minmax(0,1fr));gap:10px;}
        .be-tab{border:1px solid rgba(255,255,255,.09);background:rgba(255,255,255,.045);border-radius:18px;padding:13px 14px;text-align:left;color:#e2e8f0;transition:.18s ease;}
        .be-tab:hover{border-color:rgba(34,211,238,.35);background:rgba(34,211,238,.09);}
        .be-tab.is-active{border-color:rgba(103,232,249,.75);background:linear-gradient(135deg,#22d3ee,#8b5cf6);color:#020617;box-shadow:0 16px 34px rgba(34,211,238,.18);}
        .be-tab-title{display:block;font-size:13px;font-weight:950;}
        .be-tab-sub{display:block;margin-top:3px;font-size:11px;font-weight:800;opacity:.76;}
        .be-grid{display:grid;gap:16px;}
        .be-grid-3{grid-template-columns:repeat(3,minmax(0,1fr));}
        .be-grid-2{grid-template-columns:repeat(2,minmax(0,1fr));}
        .be-main-grid{display:grid;grid-template-columns:minmax(0,1fr) 360px;gap:18px;align-items:start;}
        .be-card{border:1px solid rgba(255,255,255,.1);background:linear-gradient(180deg,#0b1020,#070b16);border-radius:26px;padding:20px;box-shadow:0 18px 45px rgba(0,0,0,.25);}
        .be-card-cyan{border-color:rgba(34,211,238,.22);background:linear-gradient(180deg,#0a1626,#07101d);}
        .be-card-title{margin:0;color:#fff;font-size:20px;font-weight:950;letter-spacing:-.02em;}
        .be-card-sub{margin:5px 0 0;color:#94a3b8;font-size:13px;line-height:1.65;}
        .be-mini-title{font-size:11px;font-weight:950;text-transform:uppercase;letter-spacing:.15em;color:#67e8f9;}
        .be-dashboard-card{border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.035);border-radius:22px;padding:18px;}
        .be-icon{display:flex;align-items:center;justify-content:center;width:44px;height:44px;border-radius:16px;background:rgba(34,211,238,.11);font-size:22px;}
        .be-form-row{display:grid;grid-template-columns:minmax(0,1fr) 210px auto;gap:12px;align-items:end;}
        .be-label{display:block;color:#94a3b8;font-size:11px;font-weight:950;text-transform:uppercase;letter-spacing:.08em;}
        .be-input,.be-select{width:100%;margin-top:7px;border:1px solid rgba(255,255,255,.12)!important;background:#050914!important;color:#f8fafc!important;border-radius:16px!important;padding:12px 14px!important;font-size:14px!important;outline:none!important;box-shadow:none!important;}
        .be-input:focus,.be-select:focus{border-color:rgba(34,211,238,.75)!important;box-shadow:0 0 0 3px rgba(34,211,238,.12)!important;}
        .be-input::placeholder{color:#64748b!important;}
        .be-select option{background:#050914;color:#f8fafc;}
        .be-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1px solid rgba(255,255,255,.1);background:rgba(255,255,255,.07);color:#f8fafc;border-radius:14px;padding:11px 14px;font-size:13px;font-weight:950;transition:.18s ease;white-space:nowrap;}
        .be-btn:hover{background:rgba(255,255,255,.12);border-color:rgba(34,211,238,.35);}
        .be-btn-primary{border-color:transparent;background:linear-gradient(135deg,#06b6d4,#6366f1);color:#fff;box-shadow:0 14px 28px rgba(34,211,238,.16);}
        .be-btn-danger{border-color:rgba(248,113,113,.28);background:rgba(127,29,29,.2);color:#fecaca;}
        .be-topic-row{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px;}
        .be-topic{border:1px solid rgba(255,255,255,.09);background:rgba(255,255,255,.055);border-radius:999px;padding:8px 11px;color:#dbeafe;font-size:12px;font-weight:850;}
        .be-topic:hover{border-color:rgba(34,211,238,.42);background:rgba(34,211,238,.12);color:#fff;}
        .be-result-head{display:flex;align-items:center;justify-content:space-between;gap:12px;margin:18px 0 10px;}
        .be-result-list{display:grid;gap:12px;}
        .be-verse{border:1px solid rgba(255,255,255,.1);background:rgba(3,7,18,.38);border-radius:20px;padding:16px;}
        .be-verse:hover{border-color:rgba(34,211,238,.38);background:rgba(8,145,178,.07);}
        .be-verse-top{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;}
        .be-ref{color:#fff;font-size:15px;font-weight:950;}
        .be-meta{margin-top:5px;color:#67e8f9;font-size:10px;font-weight:950;text-transform:uppercase;letter-spacing:.08em;}
        .be-text{margin:9px 0 0;color:#d7e2f0;font-size:14px;line-height:1.75;}
        .be-empty{border:1px dashed rgba(255,255,255,.16);background:rgba(255,255,255,.035);border-radius:22px;padding:28px;text-align:center;color:#94a3b8;font-size:14px;}
        .be-actions{display:flex;flex-wrap:wrap;gap:10px;margin-top:16px;}
        .be-muted-box{border:1px solid rgba(255,255,255,.1);background:rgba(0,0,0,.22);border-radius:18px;padding:13px;color:#94a3b8;font-size:12px;line-height:1.7;}
        .be-table{display:grid;gap:12px;}
        @media(max-width:1100px){.be-hero-inner{grid-template-columns:1fr}.be-stat-grid{max-width:620px}.be-main-grid{grid-template-columns:1fr}.be-tabs{grid-template-columns:repeat(2,minmax(0,1fr));}.be-form-row{grid-template-columns:1fr}.be-grid-3,.be-grid-2{grid-template-columns:1fr}}
        @media(max-width:640px){.be-hero-inner{padding:18px}.be-tabs{padding:0 18px 18px;grid-template-columns:1fr}.be-title{font-size:30px}.be-stat-grid{grid-template-columns:repeat(2,1fr)}}
    </style>

    <div class="be-shell be-stack">
        <section class="be-hero">
            <div class="be-hero-inner">
                <div>
                    <div class="be-pill"><span class="be-dot"></span> Shared AppsHub Engine</div>
                    <h1 class="be-title">Bible Engine</h1>
                    <p class="be-subtitle">Global Bible library, reusable scripture picker, topic packs, collections, and daily scripture batch preparation for every app on AppsHub.</p>
                    <div class="be-tags">
                        <span class="be-tag">Global Bible Content</span>
                        <span class="be-tag">App-Scoped Publishing</span>
                        <span class="be-tag">Quote Engine Bridge</span>
                        <span class="be-tag">Book Builder Ready</span>
                    </div>
                </div>

                <div class="be-stat-grid">
                    @foreach ([
                        ['label' => 'Translations', 'value' => number_format($stats['translations'] ?? 0)],
                        ['label' => 'Books', 'value' => number_format($stats['books'] ?? 0)],
                        ['label' => 'Verses', 'value' => number_format($stats['verses'] ?? 0)],
                        ['label' => 'Topics', 'value' => number_format($stats['topics'] ?? 0)],
                        ['label' => 'Collections', 'value' => number_format($stats['collections'] ?? 0)],
                        ['label' => 'Selected', 'value' => number_format(count($selectedVerses))],
                    ] as $stat)
                        <div class="be-stat">
                            <div class="be-stat-value">{{ $stat['value'] }}</div>
                            <div class="be-stat-label">{{ $stat['label'] }}</div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="be-tabs">
                @foreach ([
                    'dashboard' => ['Dashboard', 'Control room'],
                    'picker' => ['Scripture Picker', 'Single / multi select'],
                    'batch' => ['Batch / Collections', 'Prepare many cards'],
                    'collections' => ['Collections', 'Scripture packs'],
                    'library' => ['Library Status', 'Translations'],
                ] as $key => $tab)
                    <button type="button" wire:click="selectTab('{{ $key }}')" class="be-tab {{ $activeTab === $key ? 'is-active' : '' }}">
                        <span class="be-tab-title">{{ $tab[0] }}</span>
                        <span class="be-tab-sub">{{ $tab[1] }}</span>
                    </button>
                @endforeach
            </div>
        </section>

        @if ($activeTab === 'dashboard')
            <section class="be-grid be-grid-3">
                @foreach ([
                    ['title' => 'Bible Library', 'value' => number_format($stats['translations'] ?? 0) . ' translations', 'body' => number_format($stats['books'] ?? 0) . ' books • ' . number_format($stats['chapters'] ?? 0) . ' chapters • ' . number_format($stats['verses'] ?? 0) . ' verses', 'icon' => '📖'],
                    ['title' => 'Scripture Topics', 'value' => number_format($stats['topics'] ?? 0) . ' topics', 'body' => 'Healing, faith, wisdom, prayer, protection, victory, peace, and reusable topic packs.', 'icon' => '🏷️'],
                    ['title' => 'Collections', 'value' => number_format($stats['collections'] ?? 0) . ' collections', 'body' => 'Reusable scripture packs for books, quote cards, devotionals, Bible studies, and apps.', 'icon' => '🧩'],
                ] as $card)
                    <article class="be-card">
                        <div style="display:flex;align-items:flex-start;justify-content:space-between;gap:14px">
                            <div>
                                <div class="be-mini-title">{{ $card['title'] }}</div>
                                <h2 class="be-card-title" style="margin-top:8px">{{ $card['value'] }}</h2>
                            </div>
                            <div class="be-icon">{{ $card['icon'] }}</div>
                        </div>
                        <p class="be-card-sub">{{ $card['body'] }}</p>
                    </article>
                @endforeach
            </section>

            <section class="be-card be-card-cyan">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap">
                    <div>
                        <h2 class="be-card-title">Quick topic picker</h2>
                        <p class="be-card-sub">Click a topic to search and start selecting scriptures for cards, collections, batches, and future book-builder inserts.</p>
                    </div>
                    <button type="button" wire:click="selectTab('picker')" class="be-btn be-btn-primary">Open Scripture Picker</button>
                </div>
                <div class="be-topic-row">
                    @forelse (array_slice($topics, 0, 42) as $topic)
                        <button type="button" wire:click="searchTopic('{{ addslashes($topic['name'] ?? '') }}')" class="be-topic">{{ $topic['name'] ?? 'Topic' }}</button>
                    @empty
                        <span class="be-card-sub">No topics yet. Seed or import Bible Engine verses first.</span>
                    @endforelse
                </div>
            </section>
        @endif

        @if ($activeTab === 'picker')
            <section class="be-main-grid">
                <div class="be-card">
                    <div class="be-form-row">
                        <label class="be-label">
                            Search Bible Engine
                            <input type="text" wire:model.defer="searchQuery" wire:keydown.enter="searchBible" placeholder="Search topic or reference: Proverbs, healing, Romans, John 3:16" class="be-input" />
                        </label>
                        <label class="be-label">
                            Translation
                            <select wire:model.defer="selectedTranslation" class="be-select">
                                <option value="">All translations</option>
                                @foreach ($translations as $translation)
                                    <option value="{{ $translation['key'] ?? '' }}">{{ strtoupper($translation['key'] ?? '') }}</option>
                                @endforeach
                            </select>
                        </label>
                        <button type="button" wire:click="searchBible" class="be-btn be-btn-primary">Search</button>
                    </div>

                    <div class="be-topic-row">
                        @foreach (['faith','healing','prayer','wisdom','proverbs','ecclesiastes','john','romans','victory','peace','protection','guidance','love','hope','strength'] as $quick)
                            <button type="button" wire:click="searchTopic('{{ $quick }}')" class="be-topic">{{ ucwords($quick) }}</button>
                        @endforeach
                    </div>

                    <div class="be-result-head">
                        <div>
                            <h2 class="be-card-title">Search Results</h2>
                            <p class="be-card-sub">Select one scripture or add visible results for batch workflows.</p>
                        </div>
                        <button type="button" wire:click="addVisibleResultsToTray" class="be-btn">Add visible results</button>
                    </div>

                    <div class="be-result-list">
                        @forelse ($searchResults as $row)
                            <article class="be-verse">
                                <div class="be-verse-top">
                                    <div style="min-width:0">
                                        <div class="be-ref">{{ $row['reference'] ?? 'Reference' }}</div>
                                        <div class="be-meta">{{ strtoupper($row['translation_key'] ?? '') }} • {{ $row['topic'] ?? 'Bible Engine' }}</div>
                                        <p class="be-text">{{ $row['text'] ?? '' }}</p>
                                    </div>
                                    <button type="button" wire:click="addVerseToTray({{ (int) ($row['id'] ?? 0) }})" class="be-btn be-btn-primary" style="padding:9px 12px;font-size:12px">Select</button>
                                </div>
                            </article>
                        @empty
                            <div class="be-empty">No scriptures found yet. Try “faith”, “healing”, “proverbs”, or “romans”.</div>
                        @endforelse
                    </div>
                </div>

                @include('filament.pages.partials.bible-engine-selected-tray')
            </section>
        @endif

        @if ($activeTab === 'batch')
            <section class="be-main-grid">
                <div class="be-stack">
                    <div class="be-card">
                        <h2 class="be-card-title">Create Scripture Collection</h2>
                        <p class="be-card-sub">Group selected scriptures into reusable packs for books, devotionals, quote cards, Bible studies, and apps.</p>
                        <div class="be-grid be-grid-2" style="margin-top:15px">
                            <input type="text" wire:model.defer="collectionTitle" placeholder="Collection title e.g. 30 Healing Scriptures" class="be-input" />
                            <input type="text" wire:model.defer="collectionDescription" placeholder="Short description" class="be-input" />
                        </div>
                        <div class="be-actions"><button type="button" wire:click="createCollectionFromTray" class="be-btn be-btn-primary">Create Collection from Selected</button></div>
                    </div>

                    <div class="be-card be-card-cyan">
                        <h2 class="be-card-title">Create Daily Scripture Batch</h2>
                        <p class="be-card-sub">Turn selected scriptures into app-scoped Daily Scripture cards, one per day from the start date.</p>
                        <div class="be-grid" style="grid-template-columns:repeat(3,minmax(0,1fr));margin-top:15px">
                            <input type="text" wire:model.defer="dailyBatchName" placeholder="Batch name" class="be-input" />
                            <input type="date" wire:model.defer="dailyBatchStartDate" class="be-input" />
                            <select wire:model.defer="dailyBatchStatus" class="be-select">
                                <option value="published">Publish / schedule active</option>
                                <option value="draft">Save as draft</option>
                            </select>
                        </div>
                        <div class="be-actions"><button type="button" wire:click="createDailyScriptureBatchFromTray" class="be-btn be-btn-primary">Create Daily Scripture Batch</button></div>
                    </div>
                </div>

                @include('filament.pages.partials.bible-engine-selected-tray')
            </section>
        @endif

        @if ($activeTab === 'collections')
            <section class="be-card">
                <div style="display:flex;align-items:center;justify-content:space-between;gap:14px;flex-wrap:wrap">
                    <div>
                        <h2 class="be-card-title">Recent Scripture Collections</h2>
                        <p class="be-card-sub">Reusable packs created from selected Bible Engine scriptures.</p>
                    </div>
                    <button type="button" wire:click="selectTab('batch')" class="be-btn be-btn-primary">Create New</button>
                </div>
                <div class="be-grid be-grid-3" style="margin-top:15px">
                    @forelse ($collections as $collection)
                        <article class="be-verse">
                            <div class="be-ref">{{ $collection['title'] ?? 'Collection' }}</div>
                            <div class="be-meta">{{ $collection['visibility'] ?? 'app' }} • {{ ($collection['is_active'] ?? false) ? 'Active' : 'Inactive' }}</div>
                            <p class="be-text">{{ $collection['description'] ?? '' }}</p>
                        </article>
                    @empty
                        <div class="be-empty">No scripture collections yet.</div>
                    @endforelse
                </div>
            </section>
        @endif

        @if ($activeTab === 'library')
            <section class="be-grid be-grid-3">
                @forelse ($translations as $translation)
                    <article class="be-card">
                        <h2 class="be-card-title">{{ $translation['name'] ?? 'Translation' }}</h2>
                        <div class="be-meta" style="margin-top:8px">{{ strtoupper($translation['key'] ?? '') }} • {{ $translation['language'] ?? 'en' }}</div>
                        <p class="be-card-sub">{{ $translation['copyright_note'] ?? 'License information should be recorded before full import.' }}</p>
                    </article>
                @empty
                    <div class="be-empty">No Bible translations found yet.</div>
                @endforelse
            </section>
        @endif
    </div>
</x-filament-panels::page>
