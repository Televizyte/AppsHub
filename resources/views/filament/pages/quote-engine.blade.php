<x-filament-panels::page>
    <style>
        .qe-wrap{max-width:1120px;margin:0 auto 48px;color:#fff}
        .qe-hero{border:1px solid rgba(34,211,238,.24);background:radial-gradient(circle at top left,rgba(6,182,212,.22),transparent 34%),linear-gradient(135deg,rgba(2,6,23,.96),rgba(30,27,75,.88));border-radius:28px;padding:26px;margin-bottom:18px;overflow:hidden}
        .qe-topline{display:flex;align-items:flex-start;justify-content:space-between;gap:14px;flex-wrap:wrap}
        .qe-eyebrow{display:inline-flex;align-items:center;gap:8px;border:1px solid rgba(34,211,238,.40);background:rgba(8,145,178,.14);color:#a5f3fc;border-radius:999px;padding:8px 13px;font-size:11px;font-weight:950;letter-spacing:.05em;text-transform:uppercase}
        .qe-hero h1{font-size:42px;line-height:1.05;margin:16px 0 10px;font-weight:950;letter-spacing:-.045em}
        .qe-hero p{max-width:790px;color:rgba(255,255,255,.78);font-size:15px;line-height:1.55;margin:0}
        .qe-stats{display:flex;gap:8px;flex-wrap:wrap;margin-top:18px}
        .qe-chip{border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.06);border-radius:999px;padding:8px 11px;font-size:11px;font-weight:900;color:#e5e7eb;text-transform:uppercase}
        .qe-btn,.qe-tab,.qe-mini-btn{display:inline-flex;align-items:center;justify-content:center;gap:8px;border:1px solid rgba(255,255,255,.14);background:rgba(15,23,42,.82);color:#fff;border-radius:14px;padding:11px 14px;font-size:13px;font-weight:900;text-decoration:none;cursor:pointer;white-space:nowrap}
        .qe-btn.primary,.qe-mini-btn.primary{background:linear-gradient(135deg,#0891b2,#7c3aed);border-color:rgba(34,211,238,.35)}
        .qe-btn:hover,.qe-mini-btn:hover,.qe-tab:hover{border-color:rgba(34,211,238,.55);color:#fff}
        .qe-mini-btn{padding:8px 11px;border-radius:12px;font-size:12px}
        .qe-mini-btn.danger{border-color:rgba(248,113,113,.28);background:rgba(127,29,29,.22)}
        .qe-tabs{position:sticky;top:0;z-index:3;border:1px solid rgba(255,255,255,.10);background:rgba(3,7,18,.92);backdrop-filter:blur(16px);border-radius:22px;padding:10px;margin-bottom:14px;display:flex;gap:8px;overflow-x:auto}
        .qe-tab{min-width:96px;min-height:54px}
        .qe-tab.active{background:rgba(8,145,178,.23);border-color:#22d3ee;box-shadow:inset 4px 0 0 #22d3ee}
        .qe-section-title{font-size:25px;font-weight:950;margin:24px 0 12px;letter-spacing:-.03em}
        .qe-set{border:1px solid rgba(34,211,238,.20);background:linear-gradient(180deg,rgba(15,23,42,.86),rgba(2,6,23,.96));border-radius:26px;margin-bottom:18px;overflow:hidden}
        .qe-set-head{display:grid;grid-template-columns:240px 1fr;min-height:160px;border-bottom:1px solid rgba(255,255,255,.08)}
        .qe-set-art{background:linear-gradient(135deg,#2563eb,#d946ef,#e4007c);display:flex;align-items:center;justify-content:center;font-size:48px;font-weight:950}
        .qe-set-info{padding:24px}
        .qe-set-info h2{font-size:28px;font-weight:950;margin:0 0 8px;letter-spacing:-.035em}
        .qe-set-info p{color:rgba(255,255,255,.75);margin:0 0 14px;font-size:14px}
        .qe-set-actions{display:flex;gap:10px;flex-wrap:wrap;margin-top:16px}
        .qe-note{border:1px solid rgba(34,211,238,.18);background:rgba(8,145,178,.08);border-radius:14px;padding:12px 14px;color:#dbeafe;font-size:13px;line-height:1.45;margin:14px}
        .qe-batch-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px;padding:14px}
        .qe-batch{border:1px solid rgba(255,255,255,.10);background:rgba(15,23,42,.78);border-radius:20px;overflow:hidden}
        .qe-batch-head{padding:15px 16px}
        .qe-batch-title{display:flex;align-items:center;justify-content:space-between;gap:10px}
        .qe-batch h3{font-size:16px;font-weight:950;margin:0}
        .qe-batch-count{min-width:32px;height:32px;border-radius:999px;border:1px solid rgba(255,255,255,.15);display:inline-flex;align-items:center;justify-content:center;font-weight:950;background:rgba(255,255,255,.06)}
        .qe-batch-meta{color:#bfdbfe;font-size:13px;font-weight:800;margin-top:5px}
        .qe-batch-actions{display:flex;gap:8px;flex-wrap:wrap;margin-top:13px}
        .qe-batch-line{height:6px;background:linear-gradient(90deg,#22d3ee,#a855f7);margin-top:3px}
        .qe-items{max-height:380px;overflow:auto;padding:12px;background:rgba(2,6,23,.35)}
        .qe-item{border:1px solid rgba(255,255,255,.09);background:rgba(255,255,255,.045);border-radius:15px;padding:12px;margin-bottom:10px}
        .qe-item-title{font-size:13.5px;font-weight:950;line-height:1.35;margin-bottom:4px}
        .qe-item-source{color:#bfdbfe;font-size:12px;font-weight:800;margin-bottom:10px}
        .qe-empty{border:1px dashed rgba(255,255,255,.14);border-radius:16px;padding:16px;color:#94a3b8;font-weight:800}
        .qe-home-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}
        .qe-home-card{border:1px solid rgba(34,211,238,.22);background:rgba(8,47,73,.50);border-radius:22px;padding:18px}
        .qe-home-row{display:flex;gap:14px;align-items:flex-start}
        .qe-icon{width:54px;height:54px;border-radius:16px;background:linear-gradient(135deg,#2563eb,#d946ef);display:flex;align-items:center;justify-content:center;font-weight:950;font-size:22px}
        .qe-preview{border:1px dashed rgba(250,204,21,.28);background:rgba(255,255,255,.04);border-radius:14px;padding:12px;margin-top:14px;color:#f8fafc;font-size:13px;line-height:1.45}
        .qe-modal-backdrop{position:fixed;inset:0;z-index:60;background:rgba(2,6,23,.76);backdrop-filter:blur(10px);display:flex;align-items:center;justify-content:center;padding:20px}
        .qe-modal{width:min(760px,96vw);max-height:88vh;overflow:auto;border:1px solid rgba(34,211,238,.26);background:linear-gradient(180deg,#0f172a,#020617);border-radius:24px;box-shadow:0 24px 80px rgba(0,0,0,.55)}
        .qe-modal-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:18px 20px;border-bottom:1px solid rgba(255,255,255,.10)}
        .qe-modal-title{font-size:20px;font-weight:950}
        .qe-modal-body{padding:20px}
        .qe-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}
        .qe-field{margin-bottom:12px}
        .qe-field label{display:block;color:#a5f3fc;font-size:11px;text-transform:uppercase;font-weight:950;margin-bottom:7px}
        .qe-input,.qe-textarea,.qe-select{width:100%;border:1px solid rgba(255,255,255,.12);background:rgba(2,6,23,.82);color:#fff;border-radius:14px;padding:12px 13px;font-weight:800}
        .qe-textarea{min-height:150px;resize:vertical}
        .qe-help{font-size:12px;color:#94a3b8;line-height:1.45;margin-top:6px}
        .qe-modal-actions{display:flex;justify-content:flex-end;gap:10px;flex-wrap:wrap;margin-top:18px}
        @media(max-width:900px){
            .qe-set-head{grid-template-columns:1fr}
            .qe-set-art{min-height:150px}
            .qe-batch-grid,.qe-home-grid,.qe-form-grid{grid-template-columns:1fr}
            .qe-hero h1{font-size:32px}
        }
    </style>

    <div class="qe-wrap" x-data x-init="
        window.addEventListener('quote-engine-keep-position', () => {
            const y = sessionStorage.getItem('quote_engine_scroll_y');
            if (y) setTimeout(() => window.scrollTo(0, Number(y)), 80);
        });
        window.addEventListener('beforeunload', () => sessionStorage.setItem('quote_engine_scroll_y', String(window.scrollY)));
    ">
        <section class="qe-hero">
            <div class="qe-topline">
                <div>
                    <span class="qe-eyebrow">Reusable Shared Engine • Quote Manager</span>
                    <h1>Quote Engine</h1>
                    <p>
                        Manage quote categories, batches, schedules, and designer-ready quote cards from one reusable engine.
                        Current app: <strong>{{ $currentApp?->name ?? 'No app selected' }}</strong>.
                    </p>
                </div>

                <div class="qe-set-actions" style="margin-top:0">
                    <button type="button" class="qe-btn primary" wire:click="openCreateCategory">+ Create Quote Category</button>
                    <button type="button" class="qe-btn primary" wire:click="openScripturePicker">+ Scripture Picker</button>
                    <button type="button" class="qe-btn {{ $activeQuoteTab === 'frontend_quotes' ? 'primary' : '' }}" wire:click="selectQuoteTab('frontend_quotes')">Frontend Featured Quotes</button>
                    <button type="button" class="qe-btn {{ $activeQuoteTab === 'frontend_scriptures' ? 'primary' : '' }}" wire:click="selectQuoteTab('frontend_scriptures')">Frontend Scriptures</button>
                </div>
            </div>

            <div class="qe-stats">
                <span class="qe-chip">{{ $stats['daily_ready'] ?? 0 }}/2 daily cards ready</span>
                <span class="qe-chip">{{ $stats['frontend_quotes'] ?? 0 }} frontend quotes</span>
                <span class="qe-chip">{{ $stats['frontend_scriptures'] ?? 0 }} frontend scriptures</span>
                <span class="qe-chip">{{ $stats['categories'] ?? 0 }} categories</span>
                <span class="qe-chip">{{ $stats['total_quotes'] ?? 0 }} quotes</span>
                <span class="qe-chip">{{ $stats['published'] ?? 0 }} published</span>
                <span class="qe-chip">{{ $stats['drafts'] ?? 0 }} drafts</span>
            </div>
        </section>

        <nav class="qe-tabs">
            @foreach($tabs as $tab)
                <button
                    type="button"
                    class="qe-tab {{ $activeQuoteTab === $tab['key'] ? 'active' : '' }}"
                    wire:click="selectQuoteTab('{{ $tab['key'] }}')"
                    onclick="sessionStorage.setItem('quote_engine_scroll_y', String(window.scrollY))"
                >
                    {{ $tab['short_label'] ?? $tab['label'] }}
                </button>
            @endforeach
        </nav>

        @if($activeQuoteTab === 'all')
            <h2 class="qe-section-title">Daily Home Cards</h2>
            <div class="qe-home-grid">
                @foreach($dailyCards as $card)
                    <article class="qe-home-card">
                        <div class="qe-home-row">
                            <div class="qe-icon">{{ $card['icon'] }}</div>
                            <div>
                                <h3 style="font-size:22px;font-weight:950;margin:0 0 5px">{{ $card['label'] }}</h3>
                                <p style="color:rgba(255,255,255,.75);margin:0;font-size:14px">{{ $card['description'] }}</p>
                                <div class="qe-stats" style="margin-top:12px">
                                    <span class="qe-chip">{{ $card['status'] }}</span>
                                    <span class="qe-chip">{{ $card['background_mode'] }}</span>
                                    <span class="qe-chip">{{ $card['style_preset'] }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="qe-preview">
                            <strong>{{ $card['main_text'] ?: 'No content yet' }}</strong>
                            <br>
                            {{ $card['support_text'] ?: 'No source/reference yet' }}
                        </div>

                        <div class="qe-set-actions">
                            <a class="qe-mini-btn primary" href="{{ $card['edit_url'] }}">Edit Designer</a>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif

        @if($activeQuoteTab === 'frontend_quotes')
            <section class="qe-set" id="frontend-featured-quotes">
                <div class="qe-set-head">
                    <div class="qe-set-art">★</div>
                    <div class="qe-set-info">
                        <h2>Frontend Featured Quotes</h2>
                        <p>All published quote cards currently allowed to enter the Home Daily Quote carousel API. This is the backend tracking room before troubleshooting the mobile frontend.</p>
                        <div class="qe-stats">
                            <span class="qe-chip">{{ count($frontendQuoteCards) }} items</span>
                            <span class="qe-chip">Daily Quote API Feed</span>
                            <span class="qe-chip">SOD • Motivation • Article • Custom • Dynamic</span>
                        </div>
                    </div>
                </div>

                <div class="qe-note">
                    If an item appears here, the backend has marked it for frontend Daily Quote use. If it still does not show in Flutter, the next fix is frontend payload mapping/cache refresh.
                </div>

                <div class="qe-batch-grid">
                    @forelse($frontendQuoteCards as $item)
                        <article class="qe-batch" wire:key="frontend-quote-{{ $item['id'] }}">
                            <div class="qe-batch-head">
                                <div class="qe-batch-title">
                                    <h3>{{ $item['bucket_label'] }}</h3>
                                    <span class="qe-batch-count">★</span>
                                </div>
                                <div class="qe-batch-meta">{{ $item['status'] }}{{ ! empty($item['publish_at']) ? ' • '.$item['publish_at'] : '' }}</div>
                            </div>
                            <div class="qe-batch-line"></div>
                            <div class="qe-items">
                                <div class="qe-item">
                                    <div class="qe-item-title">{{ $item['title'] }}</div>
                                    <div class="qe-item-source">{{ $item['source'] ?: 'No source yet' }}</div>
                                    <div class="qe-batch-actions">
                                        <a href="{{ $item['edit_url'] }}" class="qe-mini-btn">{{ ! empty($item['is_virtual']) ? 'Edit Daily Card' : 'Edit' }}</a>
                                        @if(! empty($item['is_virtual']))
                                            <span class="qe-mini-btn" style="cursor:default">Builder Card</span>
                                        @else
                                            <button type="button" class="qe-mini-btn danger" wire:click="toggleDailyFeature({{ $item['id'] }})" wire:confirm="Remove this item from the frontend Daily Quote carousel?">Remove Daily</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="qe-empty">No featured frontend quotes yet. Open any quote category and use Feature Daily on the quote you want to send to the Home carousel.</div>
                    @endforelse
                </div>
            </section>
        @endif

        @if($activeQuoteTab === 'frontend_scriptures')
            <section class="qe-set" id="frontend-featured-scriptures">
                <div class="qe-set-head">
                    <div class="qe-set-art">B</div>
                    <div class="qe-set-info">
                        <h2>Frontend Daily Scriptures</h2>
                        <p>All published scripture cards plus the active Home Daily Scripture builder card currently visible to the backend/frontend tracking area. This helps confirm what should be available before adjusting Flutter.</p>
                        <div class="qe-stats">
                            <span class="qe-chip">{{ count($frontendScriptureCards) }} items</span>
                            <span class="qe-chip">Daily Scripture API Feed</span>
                        </div>
                    </div>
                </div>

                <div class="qe-note">
                    Use this as the backend verification room for scripture cards. Builder cards come from the Home Daily Scripture builder; normal cards come from published Daily Scripture rows.
                </div>

                <div class="qe-batch-grid">
                    @forelse($frontendScriptureCards as $item)
                        <article class="qe-batch" wire:key="frontend-scripture-{{ $item['id'] }}">
                            <div class="qe-batch-head">
                                <div class="qe-batch-title">
                                    <h3>{{ $item['bucket_label'] }}</h3>
                                    <span class="qe-batch-count">B</span>
                                </div>
                                <div class="qe-batch-meta">{{ $item['status'] }}{{ ! empty($item['publish_at']) ? ' • '.$item['publish_at'] : '' }}</div>
                            </div>
                            <div class="qe-batch-line"></div>
                            <div class="qe-items">
                                <div class="qe-item">
                                    <div class="qe-item-title">{{ $item['title'] }}</div>
                                    <div class="qe-item-source">{{ $item['source'] ?: 'No reference yet' }}</div>
                                    <div class="qe-batch-actions">
                                        <a href="{{ $item['edit_url'] }}" class="qe-mini-btn">{{ ! empty($item['is_virtual']) ? 'Edit Daily Card' : 'Edit' }}</a>
                                        @if(! empty($item['is_virtual']))
                                            <span class="qe-mini-btn" style="cursor:default">Builder Card</span>
                                        @else
                                            <button type="button" class="qe-mini-btn danger" wire:click="toggleDailyFeature({{ $item['id'] }})" wire:confirm="Remove this item from the frontend Daily Scripture carousel?">Remove Daily</button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </article>
                    @empty
                        <div class="qe-empty">No frontend scripture cards found yet. Add or publish Daily Scripture cards first.</div>
                    @endforelse
                </div>
            </section>
        @endif

        @foreach($quoteSets as $set)
            @if($activeQuoteTab === 'all' || $activeQuoteTab === $set['key'])
                <section class="qe-set" id="quote-set-{{ $set['key'] }}">
                    <div class="qe-set-head">
                        <div class="qe-set-art">{{ $set['icon'] }}</div>
                        <div class="qe-set-info">
                            <h2>{{ $set['label'] }}</h2>
                            <p>{{ $set['description'] }}</p>

                            <div class="qe-stats">
                                <span class="qe-chip">Key: {{ strtoupper($set['bucket']) }}</span>
                                <span class="qe-chip">{{ $set['count'] }} total</span>
                                <span class="qe-chip">{{ $set['published'] }} published</span>
                                <span class="qe-chip">{{ $set['drafts'] }} drafts</span>
                                <span class="qe-chip">{{ $set['status'] }}</span>
                            </div>

                            <div class="qe-set-actions">
                                <button type="button" class="qe-mini-btn primary" wire:click="openAddQuote('{{ $set['bucket'] }}')">{{ ($set['bucket'] ?? '') === 'daily_scriptures' ? '+ Add Scripture' : '+ Add Quote' }}</button>
                                @if(($set['bucket'] ?? '') === 'daily_scriptures')
                                    <button type="button" class="qe-mini-btn primary" wire:click="openScripturePicker">+ Multi Pick Scripture</button>
                                @endif
                                <button type="button" class="qe-mini-btn" wire:click="openBulkImport('{{ $set['bucket'] }}')">Bulk Import</button>
                                <button type="button" class="qe-mini-btn" wire:click="openAiDraft('{{ $set['bucket'] }}')">AI Draft</button>
                                <button type="button" class="qe-mini-btn" wire:click="openSchedule('{{ $set['bucket'] }}')">Schedule</button>
                            </div>
                        </div>
                    </div>

                    <div class="qe-note">
                        Use batches like weekly, monthly, SOD Batch 1, June 2026, or custom campaign names.
                        {{ ($set['bucket'] ?? '') === 'daily_scriptures' ? 'Each scripture can be edited, published/unpublished, copied, or deleted.' : 'Each quote can be edited, published/unpublished, copied, or deleted.' }}
                    </div>

                    <div class="qe-batch-grid">
                        @foreach($set['groups'] as $group)
                            <article class="qe-batch">
                                <div class="qe-batch-head">
                                    <div class="qe-batch-title">
                                        <h3>{{ $group['name'] }}</h3>
                                        <span class="qe-batch-count">{{ $group['count'] }}</span>
                                    </div>
                                    <div class="qe-batch-meta">{{ $group['published'] }}/{{ $group['count'] }} published • {{ $group['drafts'] }} drafts</div>

                                    <div class="qe-batch-actions">
                                        <button type="button" class="qe-mini-btn primary" wire:click="openAddQuote('{{ $set['bucket'] }}', '{{ addslashes($group['name']) }}')">{{ ($set['bucket'] ?? '') === 'daily_scriptures' ? '+ Scripture' : '+ Quote' }}</button>
                                        <button type="button" class="qe-mini-btn" wire:click="openBulkImport('{{ $set['bucket'] }}', '{{ addslashes($group['name']) }}')">Bulk Import</button>
                                        @if($group['count'] > 0 && empty($group['is_virtual']))
                                            <button type="button" class="qe-mini-btn" wire:click="openSchedule('{{ $set['bucket'] }}', '{{ addslashes($group['name']) }}')">Schedule</button>
                                        @endif
                                        @if($group['count'] > 0 && empty($group['is_virtual']))
                                            <button type="button" class="qe-mini-btn danger" wire:click="clearBatch('{{ $set['bucket'] }}', '{{ addslashes($group['name']) }}')" wire:confirm="Clear this batch?">Clear Batch</button>
                                        @endif
                                    </div>
                                </div>

                                <div class="qe-batch-line"></div>

                                <div class="qe-items">
                                    @forelse($group['items'] as $item)
                                        <div class="qe-item" wire:key="quote-item-{{ $item['id'] }}">
                                            <div class="qe-item-title">{{ $item['title'] }}</div>
                                            <div class="qe-item-source">{{ $item['source'] ?: 'No source yet' }}</div>
                                            @if(empty($item['is_virtual']) && ! empty($item['scheduled_for']))
                                                <div class="qe-item-source" style="color:#a5f3fc">Scheduled: {{ $item['scheduled_for'] }}{{ ! empty($item['schedule_frequency']) ? ' • '.$item['schedule_frequency'] : '' }}</div>
                                            @endif
                                            <div class="qe-batch-actions">
                                                <a href="{{ $item['edit_url'] }}" class="qe-mini-btn">Edit</a>
                                                @if(! empty($item['is_virtual']))
                                                    <span class="qe-mini-btn {{ $item['enabled'] ? 'primary' : '' }}" style="cursor:default">{{ $item['enabled'] ? 'ACTIVE' : 'DRAFT' }}</span>
                                                    <span class="qe-mini-btn" style="cursor:default">Daily Card</span>
                                                @else
                                                    <button type="button" class="qe-mini-btn {{ $item['enabled'] ? 'primary' : '' }}" wire:click="toggleQuote({{ $item['id'] }})">{{ $item['enabled'] ? 'ON' : 'OFF' }}</button>
                                                    <button type="button" class="qe-mini-btn {{ ! empty($item['daily_featured']) ? 'primary' : '' }}" wire:click="toggleDailyFeature({{ $item['id'] }})">{{ ! empty($item['daily_featured']) ? 'Featured Daily' : 'Feature Daily' }}</button>
                                                    <button type="button" class="qe-mini-btn" wire:click="copyQuote({{ $item['id'] }})">Copy</button>
                                                    <button type="button" class="qe-mini-btn danger" wire:click="deleteQuote({{ $item['id'] }})" wire:confirm="Delete this quote?">Del</button>
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <div class="qe-empty">{{ ($set['bucket'] ?? '') === 'daily_scriptures' ? 'No scriptures in this batch yet. Use + Scripture, Multi Pick Scripture, or Bulk Import.' : 'No quotes in this batch yet. Use + Quote or Bulk Import.' }}</div>
                                    @endforelse
                                </div>
                            </article>
                        @endforeach
                    </div>
                </section>
            @endif
        @endforeach

        <section class="qe-note" style="border-color:rgba(34,211,238,.28);background:rgba(8,145,178,.12);margin-top:22px">
            <strong>Schedule / Rotation</strong><br>
            Use the Schedule button on any category or batch to assign daily, weekly, or monthly publish dates. The next frontend/API step will make the home card auto-pick today’s scheduled item.
        </section>
    </div>

    @if($modal !== '')
        <div class="qe-modal-backdrop" wire:key="quote-engine-modal">
            <div class="qe-modal">
                <div class="qe-modal-head">
                    <div class="qe-modal-title">
                        @if($modal === 'category') Create Quote Category @endif
                        @if($modal === 'add') {{ $modalBucket === 'daily_scriptures' ? 'Add Scripture to Daily Scripture' : 'Add Quote to '.$this->labelForBucket($modalBucket) }} @endif
                        @if($modal === 'bulk') Bulk Import into {{ $this->labelForBucket($modalBucket) }} @endif
                        @if($modal === 'ai') AI Draft for {{ $this->labelForBucket($modalBucket) }} @endif
                        @if($modal === 'schedule') Schedule / Rotation for {{ $this->labelForBucket($modalBucket) }} @endif
                        @if($modal === 'scripture_picker') Scripture Picker @endif
                    </div>
                    <button type="button" class="qe-mini-btn" wire:click="closeModal">Close</button>
                </div>

                <div class="qe-modal-body">
                    @if($modal === 'category')
                        <form wire:submit.prevent="createCategory">
                            <div class="qe-form-grid">
                                <div class="qe-field">
                                    <label>Category Name</label>
                                    <input class="qe-input" type="text" wire:model.defer="categoryLabel" placeholder="Love Quotes, Faith Quotes, Relationship Quotes...">
                                    @error('categoryLabel') <div class="qe-help" style="color:#fecaca">{{ $message }}</div> @enderror
                                </div>
                                <div class="qe-field">
                                    <label>Icon Text</label>
                                    <input class="qe-input" type="text" wire:model.defer="categoryIcon" maxlength="4" placeholder="L, F, LOVE">
                                    <div class="qe-help">Short text shown on the category card.</div>
                                </div>
                            </div>
                            <div class="qe-field">
                                <label>Description</label>
                                <textarea class="qe-textarea" style="min-height:90px" wire:model.defer="categoryDescription" placeholder="Short description for this quote category."></textarea>
                            </div>
                            <div class="qe-modal-actions">
                                <button type="button" class="qe-btn" wire:click="closeModal">Cancel</button>
                                <button type="submit" class="qe-btn primary">Create Category</button>
                            </div>
                        </form>
                    @endif

                    @if($modal === 'add')
                        <form wire:submit.prevent="addSingleQuote">
                            <div class="qe-field">
                                <label>{{ $modalBucket === 'daily_scriptures' ? 'Scripture Text' : 'Quote Text' }}</label>
                                <textarea class="qe-textarea" wire:model.defer="singleQuoteText" placeholder="{{ $modalBucket === 'daily_scriptures' ? 'Enter scripture text...' : 'Enter quote text...' }}"></textarea>
                                @error('singleQuoteText') <div class="qe-help" style="color:#fecaca">{{ $message }}</div> @enderror
                            </div>
                            <div class="qe-form-grid">
                                <div class="qe-field">
                                    <label>{{ $modalBucket === 'daily_scriptures' ? 'Bible Reference' : 'Source / Author' }}</label>
                                    <input class="qe-input" type="text" wire:model.defer="singleQuoteSource" placeholder="{{ $modalBucket === 'daily_scriptures' ? 'John 3:16' : 'Seeds of Destiny, Pastor, Article...' }}">
                                </div>
                                <div class="qe-field">
                                    <label>Batch / Schedule Group</label>
                                    <input class="qe-input" type="text" wire:model.defer="modalBatchName" placeholder="Default Batch, June Week 1...">
                                </div>
                                <div class="qe-field">
                                    <label>Status</label>
                                    <select class="qe-select" wire:model.defer="singleQuoteStatus">
                                        <option value="draft">Draft first</option>
                                        <option value="published">Publish now</option>
                                    </select>
                                </div>
                            </div>
                            @if($modalBucket === 'daily_scriptures')
                                <div class="qe-note" style="margin:0 0 14px">
                                    You can type manually here, or use Scripture Picker to search the Bible Engine and insert exact scriptures.
                                </div>
                                <button type="button" class="qe-mini-btn" wire:click="openScripturePickerForSingle">Open Scripture Picker</button>
                            @endif
                            <div class="qe-modal-actions">
                                <button type="button" class="qe-btn" wire:click="closeModal">Cancel</button>
                                <button type="submit" class="qe-btn primary">{{ $modalBucket === 'daily_scriptures' ? 'Save Scripture' : 'Save Quote' }}</button>
                            </div>
                        </form>
                    @endif

                    @if($modal === 'bulk')
                        <form wire:submit.prevent="bulkImport">
                            <div class="qe-note" style="margin:0 0 14px">
                                TXT format: <strong>{{ $modalBucket === 'daily_scriptures' ? 'Scripture text | Reference' : 'Quote text | Source' }}</strong>. One item per line or separate longer items with blank lines.
                            </div>

                            @if($modalBucket === 'daily_scriptures')
                                <div class="qe-note" style="margin:0 0 14px">
                                    You can also open Scripture Picker, select multiple scriptures, and insert them here before importing.
                                </div>
                                <button type="button" class="qe-mini-btn" wire:click="openScripturePickerForBulk">Open Scripture Picker</button>
                            @endif

                            <div class="qe-field">
                                <label>Paste {{ $modalBucket === 'daily_scriptures' ? 'Scriptures' : 'Quotes' }}</label>
                                <textarea class="qe-textarea" wire:model.defer="bulkText" placeholder="{{ $modalBucket === 'daily_scriptures' ? 'For God so loved the world... | John 3:16' : 'Quote one | Source' }}&#10;{{ $modalBucket === 'daily_scriptures' ? 'The Lord is my shepherd... | Psalm 23:1' : 'Quote two | Source' }}"></textarea>
                            </div>

                            <div class="qe-form-grid">
                                <div class="qe-field">
                                    <label>Upload TXT File</label>
                                    <input class="qe-input" type="file" wire:model="bulkFile" accept=".txt,text/plain">
                                    <div class="qe-help">Optional. You can paste text, upload a .txt file, or use both.</div>
                                    @error('bulkFile') <div class="qe-help" style="color:#fecaca">{{ $message }}</div> @enderror
                                </div>
                                <div class="qe-field">
                                    <label>Batch / Schedule Group</label>
                                    <input class="qe-input" type="text" wire:model.defer="modalBatchName" placeholder="June Week 1, SOD Batch 1...">
                                </div>
                                <div class="qe-field">
                                    <label>Status</label>
                                    <select class="qe-select" wire:model.defer="bulkStatus">
                                        <option value="draft">Draft first</option>
                                        <option value="published">Publish now</option>
                                    </select>
                                </div>
                            </div>

                            <div class="qe-modal-actions">
                                <button type="button" class="qe-btn" wire:click="closeModal">Cancel</button>
                                <button type="submit" class="qe-btn primary">Import {{ $modalBucket === 'daily_scriptures' ? 'Scriptures' : 'Quotes' }}</button>
                            </div>
                        </form>
                    @endif

                    @if($modal === 'scripture_picker')
                        <form wire:submit.prevent="createSelectedScripturesFromPicker">
                            <div class="qe-note" style="margin:0 0 14px">
                                @if($scripturePickerMode === 'single')
                                    Pick one scripture from the Bible Engine and insert it into the Daily Scripture form.
                                @elseif($scripturePickerMode === 'bulk')
                                    Pick multiple scriptures from the Bible Engine and insert them into Bulk Import for review.
                                @else
                                    Select one or many scriptures from the Bible Engine and create Daily Scripture cards.
                                @endif
                            </div>
                            <div class="qe-form-grid">
                                <div class="qe-field">
                                    <label>Search Topic / Reference</label>
                                    <input class="qe-input" type="text" wire:model.defer="scriptureSearch" placeholder="faith, healing, prayer, Proverbs, John, Romans...">
                                </div>
                                <div class="qe-field" style="align-self:end;display:flex;gap:10px;flex-wrap:wrap">
                                    <button type="button" class="qe-btn primary" wire:click="searchScriptureLibrary">Search Scripture</button>
                                    <button type="button" class="qe-btn" wire:click="addVisibleScripturesToSelection">Add Visible Results</button>
                                </div>
                            </div>
                            <div class="qe-note" style="margin:14px 0;display:flex;justify-content:space-between;align-items:center;gap:12px;flex-wrap:wrap">
                                <span><strong>{{ count($selectedScriptureReferences) }}</strong> scripture(s) selected.</span>
                                <span style="display:flex;gap:10px;flex-wrap:wrap">
                                    @if($scripturePickerMode === 'single')
                                        <button type="button" class="qe-mini-btn" wire:click="backToDailyScriptureAdd">Back to Form</button>
                                    @elseif($scripturePickerMode === 'bulk')
                                        <button type="button" class="qe-mini-btn" wire:click="backToDailyScriptureBulk">Back to Bulk Import</button>
                                        <button type="button" class="qe-mini-btn primary" wire:click="insertSelectedScripturesIntoBulkImport">Insert Selected into Bulk Import</button>
                                    @else
                                        <button type="button" class="qe-mini-btn primary" wire:click="createSelectedScripturesFromPicker">Create Selected Cards</button>
                                    @endif
                                    <button type="button" class="qe-mini-btn" wire:click="clearSelectedScriptures">Clear Selected</button>
                                </span>
                            </div>
                            <div class="qe-batch-grid" style="margin:14px 0">
                                @forelse($scriptureSearchResults as $verse)
                                    @php($ref = (string) ($verse['reference'] ?? ''))
                                    @php($isPicked = in_array($ref, $selectedScriptureReferences ?? [], true))
                                    <article class="qe-item" style="margin:0;border-color:{{ $isPicked ? 'rgba(34,211,238,.65)' : 'rgba(148,163,184,.18)' }}">
                                        <div class="qe-item-source" style="margin-bottom:6px">{{ $verse['reference'] ?? '' }}{{ ! empty($verse['topic']) ? ' • '.$verse['topic'] : '' }}</div>
                                        <div class="qe-item-title">{{ $verse['text'] ?? '' }}</div>
                                        <div class="qe-batch-actions" style="margin-top:12px">
                                            <button type="button" class="qe-mini-btn {{ $isPicked ? 'primary' : '' }}" wire:click="toggleScriptureSelection('{{ addslashes($ref) }}')">{{ $isPicked ? 'Selected' : 'Select' }}</button>
                                            @if($scripturePickerMode === 'single')
                                                <button type="button" class="qe-mini-btn primary" wire:click="useScriptureForSingle('{{ addslashes($ref) }}')">Use in Form</button>
                                            @endif
                                        </div>
                                    </article>
                                @empty
                                    <div class="qe-empty">No scripture found yet. Try faith, healing, prayer, wisdom, Proverbs, Romans, John, or Psalm.</div>
                                @endforelse
                            </div>
                            @if($scripturePickerMode === 'create')
                                <div class="qe-form-grid">
                                    <div class="qe-field"><label>Batch / Schedule Group</label><input class="qe-input" type="text" wire:model.defer="scriptureCardBatchName" placeholder="Default Batch, Healing Scriptures..."></div>
                                    <div class="qe-field"><label>Status</label><select class="qe-select" wire:model.defer="scriptureCardStatus"><option value="published">Publish now</option><option value="draft">Draft first</option></select></div>
                                </div>
                                <div class="qe-modal-actions"><button type="button" class="qe-btn" wire:click="closeModal">Cancel</button><button type="button" class="qe-btn primary" wire:click="createSelectedScripturesFromPicker">Create Selected Cards</button></div>
                            @else
                                <div class="qe-modal-actions">
                                    <button type="button" class="qe-btn" wire:click="{{ $scripturePickerMode === 'bulk' ? 'backToDailyScriptureBulk' : 'backToDailyScriptureAdd' }}">Back</button>
                                    @if($scripturePickerMode === 'bulk')<button type="button" class="qe-btn primary" wire:click="insertSelectedScripturesIntoBulkImport">Insert Selected into Bulk Import</button>@endif
                                </div>
                            @endif
                        </form>
                    @endif

                    @if($modal === 'ai')
                        <div class="qe-note" style="margin:0">
                            AI Draft is queued for a later hotfix. For launch, use Bulk Import to paste or upload prepared quote files.
                        </div>
                    @endif

                    @if($modal === 'schedule')
                        <form wire:submit.prevent="scheduleBatch">
                            <div class="qe-note" style="margin:0 0 14px">
                                Schedule a batch so each item receives a publish date. Use daily for month-long scripture/quote rotations, weekly for weekly campaigns, or monthly for long-term recurring content.
                            </div>

                            <div class="qe-form-grid">
                                <div class="qe-field">
                                    <label>Batch / Schedule Group</label>
                                    <input class="qe-input" type="text" wire:model.defer="scheduleBatchName" placeholder="Default Batch, June Week 1, SOD Batch 1...">
                                    <div class="qe-help">This must match the batch name you want to schedule.</div>
                                </div>
                                <div class="qe-field">
                                    <label>Start Date</label>
                                    <input class="qe-input" type="date" wire:model.defer="scheduleStartDate">
                                    @error('scheduleStartDate') <div class="qe-help" style="color:#fecaca">{{ $message }}</div> @enderror
                                </div>
                                <div class="qe-field">
                                    <label>End Date (optional)</label>
                                    <input class="qe-input" type="date" wire:model.defer="scheduleEndDate">
                                    @error('scheduleEndDate') <div class="qe-help" style="color:#fecaca">{{ $message }}</div> @enderror
                                </div>
                                <div class="qe-field">
                                    <label>Frequency</label>
                                    <select class="qe-select" wire:model.defer="scheduleFrequency">
                                        <option value="daily">Daily rotation</option>
                                        <option value="weekly">Weekly rotation</option>
                                        <option value="monthly">Monthly rotation</option>
                                    </select>
                                </div>
                                <div class="qe-field">
                                    <label>Status</label>
                                    <select class="qe-select" wire:model.defer="scheduleStatus">
                                        <option value="published">Publish scheduled items</option>
                                        <option value="draft">Keep as drafts</option>
                                    </select>
                                </div>
                                <div class="qe-field">
                                    <label>Fallback Behavior</label>
                                    <select class="qe-select" wire:model.defer="scheduleFallback">
                                        <option value="keep_current">If no item is due, keep current active card</option>
                                        <option value="repeat_batch">If no item is due, repeat from this batch later</option>
                                    </select>
                                </div>
                            </div>

                            <div class="qe-modal-actions">
                                <button type="button" class="qe-btn" wire:click="closeModal">Cancel</button>
                                <button type="submit" class="qe-btn primary">Schedule Batch</button>
                            </div>
                        </form>
                    @endif
                </div>
            </div>
        </div>
    @endif
</x-filament-panels::page>
