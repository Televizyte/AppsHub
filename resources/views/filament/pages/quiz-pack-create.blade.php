<x-filament::page>
    @php
        $types = [
            'bible' => 'Bible Quiz',
            'article' => 'Article Quiz',
            'daily' => 'Daily / Content Quiz',
            'sod' => 'SOD / Devotional',
            'general' => 'General Quiz',
            'custom' => 'Custom Quiz',
        ];
        $difficulties = ['easy' => 'Easy', 'medium' => 'Medium', 'hard' => 'Hard', 'custom' => 'Custom'];
        $statuses = ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'];
        $selectedType = $selectedType ?? request('type', 'custom');
    @endphp

    <div class="dxm-create-page" x-data="{ createType: @js($selectedType) }">
        @if (session('status'))
            <div class="dxm-notice">{{ session('status') }}</div>
        @endif

        <section class="dxm-create-hero">
            <div>
                <a href="{{ url('/admin/quiz-center') }}" class="dxm-back">← Back to Quiz Center</a>
                <div class="dxm-kicker">Quiz Engine · Separate Workspace</div>
                <h1>Create New Quiz Set / Pack</h1>
                <p>Create a separate listing for Bible, article, devotional, daily, or custom quiz content. After saving, add levels and questions from the Quiz Center.</p>
                <div class="dxm-chips">
                    <span>{{ $currentApp?->name ?? 'Selected App' }}</span>
                    <span>App-specific</span>
                    <span>No sidebar workflow</span>
                </div>
            </div>
            <button type="submit" form="quiz-pack-create-form" class="dxm-btn primary">Save Quiz Set / Pack</button>
        </section>

        <form id="quiz-pack-create-form" method="POST" action="{{ route('admin.beginner.quizzes.sets.store') }}" class="dxm-form-shell">
            @csrf
            <input type="hidden" name="return_tab" x-bind:value="createType || 'all'">
            <input type="hidden" name="return_scroll" value="0">

            <section class="dxm-panel">
                <div class="dxm-panel-title">
                    <div>
                        <strong>Basic Information</strong>
                        <small>Name this quiz pack clearly. Example: SOD Quiz - June 21, Genesis Level Pack, Article Quiz - Faith.</small>
                    </div>
                </div>
                <div class="dxm-form">
                    <label>Quiz Title <input name="title" required autofocus placeholder="Example: SOD Quiz - June 21, Genesis Quiz, Article Quiz - Faith"></label>
                    <label>Key <input name="key" placeholder="optional_auto_generated_key"></label>
                    <label class="wide">Subtitle <input name="subtitle" placeholder="Test what you learned."></label>
                    <label>Quiz Type <select name="type" x-model="createType">@foreach ($types as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                    <label>Difficulty <select name="difficulty">@foreach ($difficulties as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                    <label>Status <select name="status">@foreach ($statuses as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></label>
                    <label>Sort Order <input name="sort_order" type="number" min="0" value="10"></label>
                </div>
            </section>

            <section class="dxm-panel">
                <div class="dxm-panel-title">
                    <div>
                        <strong>Source / Attachment</strong>
                        <small>Connect this pack to a content source when needed. Leave blank for manual/custom quizzes.</small>
                    </div>
                </div>
                <div class="dxm-form">
                    <label>Source Bucket <input name="source_bucket" x-bind:placeholder="createType === 'bible' ? 'bible' : (createType === 'article' ? 'articles' : ((createType === 'sod' || createType === 'daily') ? 'sod, devotionals, daily_content' : 'optional content bucket'))"></label>
                    <label>Source Key <input name="source_key" placeholder="optional related content key/date/slug"></label>
                    <div class="wide type-help" x-show="createType === 'bible'">Bible-specific books and study groups remain inside Bible Quiz. Use Bible Book/Testament only when creating Bible study groups.</div>
                    <div class="wide type-help" x-show="createType === 'sod'">SOD is app-specific for Dunamis TV. Use this only for daily devotional quiz listings when the selected app needs it.</div>
                    <div class="wide type-help" x-show="createType === 'article'">Use Article Quiz for article/tutorial/topic based quiz packs. No Bible Book field is required.</div>
                    <div class="wide type-help" x-show="createType === 'daily'">Use Daily / Content Quiz for any app-specific devotional, daily reading, or content-based quiz that is not necessarily SOD.</div>
                    <label class="wide">Image URL <input name="image_url" placeholder="Optional cover image URL"></label>
                </div>
            </section>

            <section class="dxm-panel">
                <div class="dxm-panel-title">
                    <div>
                        <strong>Publishing & Play Settings</strong>
                        <small>These defaults can be refined later at level and question level.</small>
                    </div>
                </div>
                <div class="dxm-form">
                    <label>Questions Per Session <input name="questions_per_session" type="number" min="1" max="500" value="20"></label>
                    <label>Session Mode <input name="session_mode" placeholder="custom_pool" value="custom_pool"></label>
                    <label class="toggle"><input name="is_enabled" type="checkbox" value="1" checked> Enabled</label>
                    <label class="toggle"><input name="allow_retake" type="checkbox" value="1" checked> Allow Retake</label>
                    <label class="toggle"><input name="show_answers_after_submit" type="checkbox" value="1"> Show Answers After Submit</label>
                    <label class="toggle"><input name="shuffle_questions" type="checkbox" value="1" checked> Shuffle Questions</label>
                    <label class="toggle"><input name="shuffle_options" type="checkbox" value="1"> Shuffle Options</label>
                </div>
            </section>

            <section class="dxm-actions-bar">
                <a href="{{ url('/admin/quiz-center') }}" class="dxm-btn">Cancel</a>
                <button type="submit" class="dxm-btn primary">Save Quiz Set / Pack</button>
            </section>
        </form>
    </div>

    <style>
        .fi-sidebar,.fi-sidebar-close-overlay{display:none!important}.fi-main{margin-inline-start:0!important;max-width:none!important}.fi-main-ctn{margin-inline-start:0!important}.fi-topbar{display:none!important}body{background:#030712!important}.dxm-create-page{max-width:1180px;margin:0 auto;padding:18px;display:grid;gap:18px}.dxm-notice{border:1px solid rgba(34,197,94,.28);background:rgba(34,197,94,.10);color:#dcfce7;padding:12px 14px;border-radius:16px;font-size:13px;font-weight:850}.dxm-create-hero{display:flex;justify-content:space-between;gap:18px;align-items:center;border:1px solid rgba(34,211,238,.20);border-radius:30px;padding:24px;background:radial-gradient(circle at 10% 0%,rgba(34,211,238,.18),transparent 34%),radial-gradient(circle at 100% 0%,rgba(168,85,247,.16),transparent 36%),linear-gradient(135deg,rgba(2,6,23,.98),rgba(15,23,42,.94))}.dxm-back{display:inline-flex;margin-bottom:14px;color:#cffafe;text-decoration:none;font-weight:950;font-size:13px}.dxm-kicker{display:inline-flex;padding:7px 11px;border-radius:999px;border:1px solid rgba(34,211,238,.35);background:rgba(34,211,238,.10);color:#cffafe;font-size:11px;font-weight:950;letter-spacing:.08em;text-transform:uppercase}.dxm-create-hero h1{margin:12px 0 0;color:#fff;font-size:clamp(30px,4vw,46px);line-height:1.05;font-weight:950;letter-spacing:-.05em}.dxm-create-hero p{max-width:780px;margin:10px 0 0;color:rgba(255,255,255,.68);font-size:14px;line-height:1.6}.dxm-chips{display:flex;gap:7px;flex-wrap:wrap;margin-top:13px}.dxm-chips span{display:inline-flex;min-height:25px;border-radius:999px;border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.06);padding:5px 8px;color:rgba(255,255,255,.72);font-size:10.5px;font-weight:900;text-transform:uppercase}.dxm-form-shell{display:grid;gap:16px}.dxm-panel{border:1px solid rgba(255,255,255,.09);border-radius:24px;background:rgba(2,6,23,.66);overflow:hidden}.dxm-panel-title{display:flex;justify-content:space-between;gap:12px;align-items:center;padding:15px 17px;border-bottom:1px solid rgba(255,255,255,.08);color:#fff}.dxm-panel-title strong{display:block;font-size:16px}.dxm-panel-title small{display:block;margin-top:3px;color:rgba(255,255,255,.58);font-size:12px}.dxm-form{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:13px;padding:16px}.dxm-form label{display:grid;gap:7px;color:#fff;font-weight:900;font-size:12px}.dxm-form input,.dxm-form select,.dxm-form textarea{width:100%;border:1px solid rgba(255,255,255,.10);border-radius:14px;background:rgba(15,23,42,.82);color:#fff;padding:12px 13px;outline:none}.wide{grid-column:1/-1}.toggle{display:flex!important;grid-template-columns:auto 1fr!important;align-items:center;gap:9px;border:1px solid rgba(255,255,255,.08);border-radius:14px;background:rgba(255,255,255,.035);padding:12px}.toggle input{width:auto}.type-help{border:1px solid rgba(34,211,238,.18);background:rgba(34,211,238,.07);border-radius:14px;padding:12px;color:#cffafe;font-size:12px;line-height:1.45}.dxm-btn{display:inline-flex;gap:7px;align-items:center;justify-content:center;min-height:39px;border-radius:14px;padding:9px 13px;border:1px solid rgba(255,255,255,.12);background:rgba(255,255,255,.055);color:#fff;text-decoration:none;font-size:12px;font-weight:950;cursor:pointer}.primary{background:linear-gradient(135deg,rgba(34,211,238,.34),rgba(59,130,246,.22))!important;border-color:rgba(34,211,238,.48)!important}.dxm-actions-bar{position:sticky;bottom:14px;display:flex;justify-content:flex-end;gap:10px;border:1px solid rgba(34,211,238,.16);border-radius:20px;background:rgba(2,6,23,.88);padding:12px;backdrop-filter:blur(14px)}@media(max-width:900px){.dxm-create-hero{display:grid}.dxm-create-hero .dxm-btn{width:100%}.dxm-form{grid-template-columns:1fr}.dxm-actions-bar{display:grid}.dxm-btn{width:100%}}
    </style>
</x-filament::page>
