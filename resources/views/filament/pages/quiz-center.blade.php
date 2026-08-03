
<x-filament::page>
    @php
        $types = ['bible'=>'Bible Quiz','article'=>'Article Quiz','daily'=>'Daily / Content Quiz','sod'=>'SOD / Devotional','general'=>'General Quiz','custom'=>'Custom Quiz'];
        $difficulties = ['easy'=>'Easy','medium'=>'Medium','hard'=>'Hard','custom'=>'Custom'];
        $statuses = ['draft'=>'Draft','published'=>'Published','archived'=>'Archived'];
        $visibleTypes = collect($quizSets ?? [])->pluck('type')->filter()->unique()->values()->all();
        $dialogId = fn ($id) => 'quiz-edit-' . $id;
        $levelDialogId = fn ($id) => 'quiz-level-' . $id;
        $groupDialogId = fn ($id) => 'quiz-group-' . $id;
        $editLevelDialogId = fn ($id) => 'quiz-level-edit-' . $id;
        $questionDialogId = fn ($id, $level = null) => 'quiz-question-' . $id . ($level ? '-l' . $level : '');
        $aiDraftDialogId = fn ($id) => 'quiz-ai-draft-' . $id;
        $bulkImportDialogId = fn ($id) => 'quiz-bulk-import-' . $id;
        $editQuestionDialogId = fn ($id) => 'quiz-question-edit-' . $id;
    @endphp

    <div class="dxm-quiz" x-data="{ activeType: @js(request('quiz_tab', 'all')), openCreate(type = 'custom') { const url = new URL('/admin/quiz-center/packs/create', window.location.origin); url.searchParams.set('type', type || 'custom'); window.location.href = url.toString(); } }" x-init="$nextTick(() => { const y = Number(@js(request('quiz_scroll', 0))) || 0; if (y > 0) window.scrollTo({ top: y, behavior: 'instant' }); })">
        @if (session('status')) <div class="dxm-notice">{{ session('status') }}</div> @endif

        <section class="dxm-hero">
            <div>
                <div class="dxm-kicker">Content Intelligent AI · Quiz Drafts</div>
                <h1>{{ $currentApp?->name ?? 'Selected App' }} Quiz Center</h1>
                <p>Create unlimited levels, set targets, add manual questions, or paste article/devotional text to generate draft questions for review before publishing.</p>
                <div class="dxm-chips">
                    <span>{{ $stats['enabled'] ?? 0 }}/{{ $stats['total'] ?? 0 }} enabled</span>
                    <span>{{ $stats['published'] ?? 0 }} published</span>
                    <span>{{ $stats['draft'] ?? 0 }} drafts</span>
                    <span>{{ $stats['questions'] ?? 0 }} questions</span>
                    <span>{{ $stats['collections'] ?? 0 }} collections</span>
                    <span>{{ $stats['packs'] ?? 0 }} packs</span>
                </div>
            </div>
            <button type="button" class="dxm-btn primary" x-on:click="openCreate('custom')">+ New Quiz Collection / Set</button>
        </section>

        @if (!empty($quizCollections))
            <section class="dxm-collections">
                <div class="dxm-section-head">
                    <div>
                        <strong>App Quiz Collections</strong>
                        <small>Collections are app-specific. SOD/Devotional remains a Dunamis TV collection, not a global engine default.</small>
                    </div>
                </div>
                <div class="collection-grid">
                    @foreach ($quizCollections as $collection)
                        <div class="collection-card {{ $collection['is_enabled'] && $collection['status'] === 'published' ? '' : 'muted' }}">
                            <strong>{{ $collection['title'] }}</strong>
                            <small>{{ strtoupper($collection['type']) }} · {{ $collection['status'] }} · {{ $collection['pack_count'] }} pack(s)</small>
                        </div>
                    @endforeach
                </div>
            </section>
        @endif

        <section class="dxm-create-redirect">
            <div>
                <strong>Create quiz sets in a focused workspace</strong>
                <small>The long form has been moved to a separate full page so this Quiz Center stays compact.</small>
            </div>
            <button type="button" class="dxm-btn primary" x-on:click="openCreate(activeType === 'all' ? 'custom' : activeType)">+ New Quiz Set / Pack</button>
        </section>

        <div class="dxm-tabs">
            <button type="button" :class="{active: activeType === 'all'}" x-on:click="activeType = 'all'">All</button>
            @foreach ($types as $value => $label)@if (in_array($value, $visibleTypes, true))<button type="button" :class="{active: activeType === '{{ $value }}'}" x-on:click="activeType = '{{ $value }}'">{{ $label }}</button>@endif @endforeach
        </div>

        <section class="dxm-list">
            @forelse ($quizSets as $quiz)
                <article class="dxm-card" x-show="activeType === 'all' || activeType === '{{ $quiz['type'] }}'" x-cloak>
                    <div class="head">
                        <div class="cover">@if ($quiz['image_url'])<img src="{{ $quiz['image_url'] }}" alt="">@else<span>?</span>@endif<em>{{ strtoupper($quiz['type']) }}</em></div>
                        <div class="head-main">
                            <div class="top"><div><h2>{{ $quiz['title'] }}</h2><p>{{ $quiz['subtitle'] ?: 'No subtitle yet.' }}</p></div><b class="{{ $quiz['is_enabled'] ? 'on' : 'off' }}">{{ $quiz['is_enabled'] ? 'ON' : 'OFF' }}</b></div>
                            <div class="meta"><span>Key: {{ $quiz['key'] }}</span><span>{{ $quiz['status'] }}</span><span>{{ $quiz['difficulty'] }}</span><span>{{ $quiz['enabled_questions_count'] }}/{{ $quiz['questions_count'] }} active</span><span>{{ $quiz['admin_labels']['context'] ?? 'Quiz' }}</span></div>
                            <div class="actions">
                                <button type="button" x-on:click="document.getElementById('{{ $dialogId($quiz['id']) }}').showModal()">Edit Quiz</button>
                                @if ($quiz['admin_labels']['show_group_button'] ?? false)
                                    <button type="button" x-on:click="document.getElementById('{{ $groupDialogId($quiz['id']) }}').showModal()">{{ $quiz['admin_labels']['group'] }}</button>
                                @else
                                    <button type="button" x-on:click="openCreate('{{ $quiz['type'] }}')">{{ $quiz['admin_labels']['create_pack'] }}</button>
                                @endif
                                <button type="button" x-on:click="document.getElementById('{{ $levelDialogId($quiz['id']) }}').showModal()">+ Add Level</button>
                                <form method="POST" action="{{ route('admin.beginner.quizzes.sets.toggle-status', $quiz['id']) }}">@csrf @method('PATCH')<button type="submit">{{ $quiz['status'] === 'published' ? 'Draft' : 'Publish' }}</button></form>
                                <form method="POST" action="{{ route('admin.beginner.quizzes.sets.delete', $quiz['id']) }}" onsubmit="return confirm('Delete this quiz set and its questions?');">@csrf @method('DELETE')<button type="submit">Delete</button></form>
                            </div>
                        </div>
                    </div>

                    @if (!empty($quiz['study_groups']))
                        <div class="study-groups-grid">
                            @foreach ($quiz['study_groups'] as $group)
                                <div class="study-group-card" x-data="{open:false}">
                                    <div class="study-group-head" x-on:click="open = !open" style="cursor:pointer">
                                        <div class="study-group-cover">
                                            @if (!empty($group['image_url']))
                                                <img src="{{ $group['image_url'] }}" alt="">
                                            @else
                                                <span>{{ strtoupper(substr($group['title'], 0, 1)) }}</span>
                                            @endif
                                        </div>

                                        <div class="study-group-main">
                                            <strong>{{ $group['title'] }}</strong>
                                            <small>{{ $group['subtitle'] ?: ($group['description'] ?: 'Book / study group') }}</small>
                                            <div class="study-group-meta">
                                                <span>{{ $group['levels_count'] ?? 0 }} levels</span>
                                                <span>{{ $group['enabled_questions_count'] ?? 0 }}/{{ $group['questions_count'] ?? 0 }} active questions</span>
                                                <span>{{ $group['is_enabled'] ? 'ON' : 'OFF' }}</span>
                                            </div>
                                        </div>

                                        <div class="study-group-actions" x-on:click.stop>
                                            <button type="button" x-on:click="open = !open">View Levels</button>
                                            <button type="button" x-on:click="document.getElementById('quiz-group-edit-{{ $group['id'] }}').showModal()">Edit Group</button>
                                        </div>
                                    </div>

                                    <div class="study-group-levels" x-show="open" x-cloak>
                                        <div class="level-grid">
                                            @forelse ($group['levels'] ?? [] as $level)
                                                @include('admin.beginner.shared.quiz-level-card', [
                                                    'quiz' => $quiz,
                                                    'level' => $level,
                                                    'questionDialogId' => $questionDialogId,
                                                    'aiDraftDialogId' => $aiDraftDialogId,
                                                    'bulkImportDialogId' => $bulkImportDialogId,
                                                    'editLevelDialogId' => $editLevelDialogId,
                                                    'editQuestionDialogId' => $editQuestionDialogId,
                                                ])
                                            @empty
                                                <div class="empty-small">No levels inside {{ $group['title'] }} yet. Add a level and select this group.</div>
                                            @endforelse
                                        </div>
                                    </div>
                                </div>

                                @include('admin.beginner.shared.quiz-study-group-dialog', [
                                    'quiz' => $quiz,
                                    'group' => $group,
                                    'dialogId' => 'quiz-group-edit-' . $group['id'],
                                ])
                            @endforeach
                        </div>
                    @endif

                    <div class="empty-small" style="margin:0 14px 12px">{{ $quiz['admin_labels']['empty_hint'] ?? 'Create levels and questions inside this quiz set.' }}</div>

                    @if (!empty($quiz['levels']))
                        <div class="level-grid">
                            @foreach ($quiz['levels'] as $level)
                                @include('admin.beginner.shared.quiz-level-card', [
                                    'quiz' => $quiz,
                                    'level' => $level,
                                    'questionDialogId' => $questionDialogId,
                                    'aiDraftDialogId' => $aiDraftDialogId,
                                    'bulkImportDialogId' => $bulkImportDialogId,
                                    'editLevelDialogId' => $editLevelDialogId,
                                    'editQuestionDialogId' => $editQuestionDialogId,
                                ])
                            @endforeach
                        </div>
                    @elseif (empty($quiz['study_groups']))
                        <div class="level-grid">
                            <div class="empty-small">No levels yet. Add your first level.</div>
                        </div>
                    @endif
                </article>

                @include('admin.beginner.shared.quiz-set-dialog', ['quiz' => $quiz, 'dialogId' => $dialogId($quiz['id']), 'types' => $types, 'difficulties' => $difficulties, 'statuses' => $statuses])
                @include('admin.beginner.shared.quiz-study-group-dialog', ['quiz' => $quiz, 'dialogId' => $groupDialogId($quiz['id'])])
                @include('admin.beginner.shared.quiz-level-create-dialog', ['quiz' => $quiz, 'dialogId' => $levelDialogId($quiz['id'])])
            @empty
                <div class="empty"><strong>No quiz set yet.</strong><p>Create app-specific quiz collections and quiz sets. Bible, Article, Daily/Content, and Custom quizzes are supported without hardcoding SOD globally.</p><button type="button" class="dxm-btn primary" x-on:click="openCreate('custom')">Create First Quiz</button></div>
            @endforelse
        </section>
    </div>

    <script>
        document.addEventListener('submit', function (event) {
            const form = event.target;
            if (!form || !form.matches('.dxm-quiz form')) {
                return;
            }

            const root = form.closest('.dxm-quiz');
            let activeTab = 'all';

            try {
                if (root && root.__x && root.__x.$data && root.__x.$data.activeType) {
                    activeTab = root.__x.$data.activeType;
                } else if (window.Alpine && root) {
                    activeTab = Alpine.$data(root).activeType || 'all';
                }
            } catch (error) {
                activeTab = 'all';
            }

            let tabInput = form.querySelector('input[name="return_tab"]');
            if (!tabInput) {
                tabInput = document.createElement('input');
                tabInput.type = 'hidden';
                tabInput.name = 'return_tab';
                form.appendChild(tabInput);
            }
            tabInput.value = activeTab || 'all';

            let scrollInput = form.querySelector('input[name="return_scroll"]');
            if (!scrollInput) {
                scrollInput = document.createElement('input');
                scrollInput.type = 'hidden';
                scrollInput.name = 'return_scroll';
                form.appendChild(scrollInput);
            }
            scrollInput.value = String(window.scrollY || 0);
        }, true);
    </script>

    <style>
        [x-cloak]{display:none!important}.dxm-quiz{display:grid;gap:18px;max-width:1400px}.dxm-notice{border:1px solid rgba(34,197,94,.28);background:rgba(34,197,94,.10);color:#dcfce7;padding:12px 14px;border-radius:16px;font-size:13px;font-weight:850}.dxm-hero{display:flex;justify-content:space-between;gap:18px;align-items:center;border:1px solid rgba(34,211,238,.20);border-radius:28px;padding:22px;background:radial-gradient(circle at 10% 0%,rgba(34,211,238,.18),transparent 34%),radial-gradient(circle at 100% 0%,rgba(168,85,247,.16),transparent 36%),linear-gradient(135deg,rgba(2,6,23,.94),rgba(15,23,42,.90))}.dxm-kicker{display:inline-flex;padding:7px 11px;border-radius:999px;border:1px solid rgba(34,211,238,.35);background:rgba(34,211,238,.10);color:#cffafe;font-size:11px;font-weight:950;letter-spacing:.08em;text-transform:uppercase}.dxm-hero h1{margin:11px 0 0;color:#fff;font-size:clamp(29px,4vw,43px);line-height:1.05;font-weight:950;letter-spacing:-.05em}.dxm-hero p{max-width:900px;margin:10px 0 0;color:rgba(255,255,255,.68);font-size:14px;line-height:1.6}.dxm-chips,.meta{display:flex;gap:7px;flex-wrap:wrap;margin-top:13px}.dxm-chips span,.meta span{display:inline-flex;min-height:25px;border-radius:999px;border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.06);padding:5px 8px;color:rgba(255,255,255,.72);font-size:10.5px;font-weight:900;text-transform:uppercase}.dxm-btn,.actions button,.q-actions button,.level-actions button{display:inline-flex;gap:7px;align-items:center;justify-content:center;min-height:36px;border-radius:13px;padding:8px 11px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.055);color:#fff;text-decoration:none;font-size:12px;font-weight:950;cursor:pointer}.level-actions .ai{border-color:rgba(168,85,247,.45);background:rgba(168,85,247,.14)}.primary{background:linear-gradient(135deg,rgba(34,211,238,.34),rgba(59,130,246,.22))!important;border-color:rgba(34,211,238,.48)!important}.dxm-panel,.dxm-card{border:1px solid rgba(255,255,255,.09);border-radius:24px;background:rgba(2,6,23,.42);overflow:hidden}.dxm-panel-title{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:14px 16px;border-bottom:1px solid rgba(255,255,255,.08);color:#fff}.dxm-panel-title small{display:block;margin-top:3px;color:rgba(255,255,255,.58);font-size:12px}.dxm-panel-title button{width:34px;height:34px;border-radius:999px;border:1px solid rgba(255,255,255,.14);background:rgba(255,255,255,.08);color:#fff;font-size:19px}.dxm-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px;padding:15px}.dxm-form label{display:grid;gap:7px;color:#fff;font-weight:900;font-size:12px}.dxm-form input,.dxm-form select,.dxm-form textarea{width:100%;border:1px solid rgba(255,255,255,.10);border-radius:13px;background:rgba(15,23,42,.80);color:#fff;padding:11px 12px;outline:none}.wide{grid-column:1/-1}.toggle{display:flex!important;grid-template-columns:auto 1fr!important;align-items:center;gap:9px}.toggle input{width:auto}.actions,.q-actions,.level-actions{display:flex;gap:8px;flex-wrap:wrap}.level-body{display:block}.level-title:after{content:"▾";color:#67e8f9;font-weight:950}.dxm-tabs{display:flex;gap:8px;overflow-x:auto;border:1px solid rgba(255,255,255,.08);border-radius:18px;padding:9px;background:rgba(255,255,255,.035)}.dxm-tabs button{border:1px solid rgba(255,255,255,.10);background:rgba(15,23,42,.75);color:rgba(255,255,255,.75);border-radius:14px;padding:10px 12px;font-weight:950}.dxm-tabs button.active{border-color:rgba(34,211,238,.55);background:rgba(34,211,238,.12);color:#fff}.dxm-list{display:grid;gap:16px}.head{display:grid;grid-template-columns:240px 1fr}.cover{position:relative;min-height:190px;background:linear-gradient(135deg,#0B1F4D,#1D5CFF,#E2388A);display:grid;place-items:center;overflow:hidden}.cover img{width:100%;height:100%;object-fit:cover}.cover span{font-size:64px;color:#fff;font-weight:950}.cover em{position:absolute;top:12px;left:12px;border-radius:999px;background:rgba(0,0,0,.55);color:#fff;padding:6px 9px;font-size:10px;font-style:normal;font-weight:950}.head-main{padding:16px;display:grid;gap:12px}.top{display:flex;justify-content:space-between;gap:10px}.top h2{margin:0;color:#fff;font-size:23px;font-weight:950}.top p{margin:6px 0 0;color:rgba(255,255,255,.58);font-size:13px;line-height:1.45}.top b{display:inline-flex;height:27px;align-items:center;border-radius:999px;padding:5px 8px;font-size:10px}.on{background:rgba(34,197,94,.13);color:#bbf7d0;border:1px solid rgba(34,197,94,.28)}.off{background:rgba(239,68,68,.12);color:#fecaca;border:1px solid rgba(239,68,68,.24)}.study-groups-strip{display:flex;gap:10px;overflow-x:auto;padding:12px 14px;border-top:1px solid rgba(255,255,255,.08)}.study-groups-strip button{min-width:190px;text-align:left;border:1px solid rgba(34,211,238,.18);background:rgba(34,211,238,.07);border-radius:16px;padding:11px;color:#fff;cursor:pointer}.study-groups-strip strong{display:block;font-size:13px}.study-groups-strip small{display:block;margin-top:4px;color:rgba(255,255,255,.55);font-size:10px}.level-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(310px,1fr));gap:12px;padding:0 14px 14px;max-height:980px;overflow-y:auto;overscroll-behavior:contain;scrollbar-width:thin}.level-grid::-webkit-scrollbar{width:8px}.level-grid::-webkit-scrollbar-thumb{background:rgba(168,85,247,.28);border-radius:999px}.level-grid::-webkit-scrollbar-track{background:rgba(255,255,255,.04);border-radius:999px}.level-card{border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.035);border-radius:20px;overflow:hidden;display:flex;flex-direction:column}.level-title{display:flex;justify-content:space-between;gap:10px;padding:13px;border-bottom:1px solid rgba(255,255,255,.08)}.level-title strong{display:block;color:#fff;font-size:14px}.level-title small{display:block;margin-top:4px;color:rgba(255,255,255,.52);font-size:11px;line-height:1.35}.level-title span{height:27px;border-radius:999px;padding:6px 8px;background:rgba(34,211,238,.10);border:1px solid rgba(34,211,238,.20);color:#cffafe;font-size:10px;font-weight:950}.bar{height:6px;background:rgba(255,255,255,.08)}.bar i{display:block;height:100%;background:linear-gradient(90deg,#22d3ee,#a855f7)}.level-actions{padding:10px;border-bottom:1px solid rgba(255,255,255,.08)}.questions{display:grid;gap:8px;padding:10px;max-height:520px;overflow-y:auto;overscroll-behavior:contain;scrollbar-width:thin}.questions::-webkit-scrollbar{width:8px}.questions::-webkit-scrollbar-thumb{background:rgba(34,211,238,.28);border-radius:999px}.questions::-webkit-scrollbar-track{background:rgba(255,255,255,.04);border-radius:999px}.q,.empty-small{border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.04);border-radius:14px;padding:10px}.q{display:grid;gap:10px}.q strong{display:block;color:#fff;font-size:12px;line-height:1.35}.q small,.empty-small{display:block;color:rgba(255,255,255,.55);font-size:11px;line-height:1.4}.q-actions button{min-height:31px;padding:6px 8px;font-size:10px}.empty{border:1px dashed rgba(255,255,255,.18);border-radius:22px;background:rgba(2,6,23,.24);padding:28px;text-align:center;color:#fff}.empty p{color:rgba(255,255,255,.60)}.dxm-dialog{width:min(900px,96vw);border:1px solid rgba(34,211,238,.22);border-radius:24px;background:#070b18;color:#fff;padding:0;box-shadow:0 30px 90px rgba(0,0,0,.55)}.dxm-dialog::backdrop{background:rgba(0,0,0,.72);backdrop-filter:blur(8px)}@media(max-width:900px){.head{grid-template-columns:1fr}.cover{min-height:160px}.dxm-hero,.top{display:block}.dxm-form{grid-template-columns:1fr}.dxm-btn{width:100%}}
    
.study-groups-grid{display:grid;gap:12px;padding:12px 14px;border-top:1px solid rgba(255,255,255,.08)}
.study-group-card{border:1px solid rgba(34,211,238,.16);background:linear-gradient(180deg,rgba(34,211,238,.06),rgba(168,85,247,.035));border-radius:20px;overflow:hidden}
.study-group-head{display:grid;grid-template-columns:70px 1fr auto;gap:12px;align-items:center;padding:12px}
.study-group-cover{width:58px;height:58px;border-radius:16px;overflow:hidden;background:linear-gradient(135deg,#0f2a5f,#7c2d92);display:grid;place-items:center;color:#fff;font-size:22px;font-weight:950;border:1px solid rgba(255,255,255,.12)}
.study-group-cover img{width:100%;height:100%;object-fit:cover}
.study-group-main strong{display:block;color:#fff;font-size:15px}
.study-group-main small{display:block;margin-top:4px;color:rgba(255,255,255,.58);font-size:11px;line-height:1.35}
.study-group-meta{display:flex;flex-wrap:wrap;gap:6px;margin-top:9px}
.study-group-meta span{font-size:10px;font-weight:900;text-transform:uppercase;border:1px solid rgba(34,211,238,.16);background:rgba(34,211,238,.07);border-radius:999px;padding:5px 8px;color:#cffafe}
.study-group-actions{display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end}
.study-group-levels{border-top:1px solid rgba(255,255,255,.08);padding-top:12px}
.study-group-levels .level-grid{padding-top:0}
@media(max-width:900px){.study-group-head{grid-template-columns:54px 1fr}.study-group-actions{grid-column:1/-1;justify-content:flex-start}.study-group-cover{width:48px;height:48px;border-radius:14px}}


.dxm-create-redirect{display:flex;justify-content:space-between;gap:12px;align-items:center;border:1px solid rgba(34,211,238,.16);border-radius:20px;background:linear-gradient(135deg,rgba(34,211,238,.07),rgba(168,85,247,.045));padding:13px 15px}.dxm-create-redirect strong{display:block;color:#fff;font-size:14px;font-weight:950}.dxm-create-redirect small{display:block;margin-top:3px;color:rgba(255,255,255,.58);font-size:12px}@media(max-width:900px){.dxm-create-redirect{display:grid}.dxm-create-redirect .dxm-btn{width:100%}}

.dxm-collections{border:1px solid rgba(255,255,255,.08);border-radius:24px;background:rgba(255,255,255,.035);padding:14px}.dxm-section-head{display:flex;justify-content:space-between;gap:12px;align-items:center;margin-bottom:12px}.dxm-section-head strong{display:block;color:#fff;font-size:15px;font-weight:950}.dxm-section-head small{display:block;color:rgba(255,255,255,.58);font-size:12px;margin-top:3px}.collection-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(210px,1fr));gap:10px}.collection-card{border:1px solid rgba(34,211,238,.18);background:rgba(34,211,238,.07);border-radius:16px;padding:12px}.collection-card strong{display:block;color:#fff;font-size:13px}.collection-card small{display:block;margin-top:5px;color:rgba(255,255,255,.62);font-size:10.5px;font-weight:850}.collection-card.muted{opacity:.55;border-color:rgba(255,255,255,.10);background:rgba(255,255,255,.04)}.type-help{border:1px solid rgba(34,211,238,.18);background:rgba(34,211,238,.07);border-radius:14px;padding:11px 12px;color:#cffafe;font-size:12px;line-height:1.45}
    </style>
</x-filament::page>

