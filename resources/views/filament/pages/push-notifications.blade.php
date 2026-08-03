<x-filament::page>
    @php
        $activeAppId = (int) (\App\Support\ActiveApp::ensureId() ?? 0);
        $activeApp = $activeAppId > 0 ? \App\Models\App::query()->find($activeAppId) : null;
        $branding = is_array($activeApp?->branding_json ?? null) ? $activeApp->branding_json : [];
        $contentNames = data_get($branding, 'content_names', []);
        $nameFor = function (string $key, string $fallback) use ($contentNames) {
            $value = data_get($contentNames, $key);
            return filled($value) ? (string) $value : $fallback;
        };

        $appName = $activeApp?->name ?? 'this app';
        $appSlug = $activeApp?->slug ?? 'app';
        $appLogo = (string) (data_get($branding, 'logo_url') ?: data_get($branding, 'app_logo') ?: data_get($branding, 'logo') ?: data_get($branding, 'assets.logo') ?: '');
        $appInitial = strtoupper(mb_substr($appName, 0, 1));
        $devotionalName = $nameFor('devotional', 'Devotional');
        $articleName = $nameFor('article', 'Article');
        $shortVideoName = $nameFor('short_video', 'Short Video');
        $quoteName = $nameFor('quote', 'Quote');
        $scriptureName = $nameFor('scripture', 'Scripture');
        $quizName = $nameFor('quiz', 'Quiz');
        $bookName = $nameFor('book', 'Book');
        $gameName = $nameFor('game', 'Game');
        $wordName = $nameFor('wordification', 'Word Teaching');
        $motivationName = $nameFor('motivation', 'Motivation');
        $highlightName = $nameFor('highlight', 'Highlight');

        $notifications = $activeAppId > 0
            ? \App\Models\PushNotification::query()->where('app_id', $activeAppId)->latest('updated_at')->limit(140)->get()
            : collect();

        $mediaAssets = $activeAppId > 0
            ? \App\Models\MediaAsset::query()
                ->where('is_active', true)
                ->where('type', 'image')
                ->where(function ($query) use ($activeAppId) {
                    $query->whereNull('app_id')->orWhere('app_id', $activeAppId);
                })
                ->orderByRaw("CASE WHEN bucket = 'push' THEN 0 WHEN bucket IN ('banners','thumbnails','covers') THEN 1 ELSE 2 END")
                ->latest('updated_at')
                ->limit(160)
                ->get()
            : collect();

        $editId = (int) request('id', 0);
        $editNotification = $editId > 0
            ? \App\Models\PushNotification::query()->where('app_id', $activeAppId)->find($editId)
            : null;
        if ($editNotification && ($editNotification->status ?? '') === 'sent') {
            $editNotification = null;
        }

        $viewId = (int) request('view', 0);
        $viewNotification = $viewId > 0
            ? \App\Models\PushNotification::query()->where('app_id', $activeAppId)->find($viewId)
            : null;

        $tabs = [
            'overview' => 'Overview',
            'drafts' => 'Drafts / Queue',
            'schedule' => 'Schedule',
            'sent' => 'Sent Archive',
            'campaigns' => 'Templates',
            'automation' => 'Auto Alerts',
            'safety' => 'Guide',
        ];

        $activeTab = strtolower((string) request('tab', 'overview'));
        if ($activeTab === 'create') {
            $activeTab = 'overview';
        }
        if (in_array($activeTab, ['queue', 'edit'], true)) {
            $activeTab = 'drafts';
        }
        if (! array_key_exists($activeTab, $tabs)) {
            $activeTab = 'overview';
        }

        $campaignGroups = [
            'Home' => [
                'home_general' => ['Home Update', 'Notify users about important home updates.', 'New Update from ' . $appName, 'Open the app to see the latest update prepared for you.', '/home', 'Home'],
                'daily_scripture' => ['Daily ' . $scriptureName, 'Send a scripture reminder.', 'Today’s ' . $scriptureName, 'Take a moment to meditate on today’s ' . strtolower($scriptureName) . '.', '/home', $scriptureName],
                'daily_quote' => ['Daily ' . $quoteName, 'Send a quote or inspiration reminder.', 'Today’s Inspiration', 'A fresh ' . strtolower($quoteName) . ' is waiting for you.', '/home', $quoteName],
                'home_short_video' => ['Home ' . $shortVideoName, 'Alert users about a new short video on Home.', 'New ' . $shortVideoName, 'A new inspiring short clip is available now.', '/home', 'Shorts'],
            ],
            'Watch' => [
                'live_service' => ['Live Broadcast Alert', 'Notify users when live service or live broadcast starts.', 'Live Broadcast is Starting', 'Join the live broadcast now and stay connected.', '/watch', 'Live'],
                'new_video' => ['New Video Message', 'Notify users about a new sermon, message, or video.', 'New Video Message Available', 'Watch the latest video message now.', '/watch', 'Video'],
                'watch_playlist' => ['Watch Playlist Update', 'Notify users about a playlist update.', 'New Playlist Update', 'A new playlist update is available.', '/watch', 'Playlist'],
            ],
            'Inspire' => [
                'new_article' => ['New ' . $articleName . ' / ' . $devotionalName, 'Notify users about new article, teaching, or devotional content.', 'New Inspirational ' . $articleName, 'A fresh teaching is now available. Tap to read.', '/inspire', $articleName],
                'devotional_quote' => [$devotionalName . ' ' . $quoteName, 'Notify users about a new devotional quote.', 'New ' . $devotionalName . ' ' . $quoteName, 'A new devotional quote is available.', '/inspire', $devotionalName],
                'inspire_short_video' => ['Inspire ' . $shortVideoName, 'Notify users about a short video under Inspire.', 'New Inspire Short', 'A new inspiring short video is available.', '/inspire', 'Shorts'],
                'motivation' => [$motivationName . ' Alert', 'Notify users about new motivational content.', 'New ' . $motivationName . ' Posted', 'A fresh motivational message is available.', '/inspire', $motivationName],
                'word_teaching' => [$wordName . ' Alert', 'Notify users about new word/teaching content.', 'New ' . $wordName . ' Posted', 'A fresh teaching post is available.', '/inspire', $wordName],
                'highlights' => [$highlightName . ' Alert', 'Notify users about new highlights.', 'New ' . $highlightName . ' Available', 'A new highlight is available now.', '/inspire', $highlightName],
            ],
            'Explore' => [
                'quote_creator' => ['Quote Creator Reminder', 'Invite users to create and share a quote.', 'Create a Quote Today', 'Open Quote Creator and design something inspiring.', '/explore', 'Tool'],
                'notes' => ['Notes Reminder', 'Encourage users to write or review notes.', 'Write Your Note', 'Capture your thoughts inside Notes today.', '/explore', 'Notes'],
                'bible' => ['Bible Reminder', 'Encourage Bible reading.', 'Read the Bible Today', 'Open the Bible and continue reading.', '/explore', 'Bible'],
                'books' => [$bookName . ' Library Alert', 'Notify users about books/library.', $bookName . ' Library Update', 'A library update is available.', '/explore', $bookName],
                'games' => [$gameName . ' Alert', 'Notify users about games.', 'New Game Challenge', 'Open the app and play the latest challenge.', '/explore', $gameName],
                'quiz' => [$quizName . ' Alert', 'Notify users about quizzes.', 'New ' . $quizName . ' Available', 'Test your understanding with the latest quiz.', '/explore', $quizName],
            ],
            'More' => [
                'event' => ['Event Reminder', 'Announce programmes, conferences, and meetings.', 'Upcoming Event Reminder', 'Do not miss this special programme. Open the app for details.', '/home', 'Event'],
                'general' => ['General Announcement', 'Normal app announcement.', 'New Update from ' . $appName, 'Open the app to see the latest update prepared for you.', '/', 'General'],
            ],
        ];

        $campaignType = strtolower((string) request('type', 'general'));
        $flatCampaigns = collect($campaignGroups)->flatMap(fn ($items) => $items)->toArray();
        if (! array_key_exists($campaignType, $flatCampaigns)) {
            $campaignType = 'general';
        }
        $campaign = $flatCampaigns[$campaignType];

        $formNotification = $activeTab === 'edit' ? $editNotification : null;
        $formAction = $formNotification ? route('admin.beginner.push.update', $formNotification) : route('admin.beginner.push.store');

        $statusClass = function (?string $status) {
            return match ($status) {
                'sent' => 'good',
                'queued', 'sending' => 'info',
                'scheduled' => 'warn',
                'failed' => 'danger',
                'cancelled' => 'muted',
                default => 'draft',
            };
        };
        $statusLabel = fn (?string $status) => strtoupper(str_replace('_', ' ', (string) ($status ?: 'draft')));
        $formatDate = function ($value) {
            if (! $value) return 'Not scheduled';
            try {
                return \Carbon\Carbon::parse($value)->timezone(config('app.timezone', 'Africa/Lagos'))->format('M j, Y · g:i A');
            } catch (\Throwable $e) {
                return (string) $value;
            }
        };

        $stats = [
            'total' => $notifications->count(),
            'draft' => $notifications->where('status', 'draft')->count(),
            'scheduled' => $notifications->where('status', 'scheduled')->count(),
            'queued' => $notifications->whereIn('status', ['queued', 'sending'])->count(),
            'sent' => $notifications->where('status', 'sent')->count(),
            'failed' => $notifications->where('status', 'failed')->count(),
            'with_image' => $notifications->filter(fn ($n) => filled($n->image_url))->count(),
            'recurring' => $notifications->filter(fn ($n) => ($n->recurrence_type ?? 'none') !== 'none')->count(),
        ];

        $workflowGroups = [
            'Drafts' => $notifications->where('status', 'draft'),
            'Queue / Sending' => $notifications->filter(fn ($n) => in_array($n->status, ['queued', 'sending'], true)),
            'Failed / Cancelled' => $notifications->filter(fn ($n) => in_array($n->status, ['failed', 'cancelled'], true)),
        ];

        $scheduleItems = $notifications
            ->filter(fn ($n) => $n->status === 'scheduled' || (((string) ($n->recurrence_type ?? 'none')) !== 'none' && ! in_array($n->status, ['sent', 'cancelled', 'failed'], true)))
            ->sortBy('scheduled_for')
            ->values();

        $fieldValue = function (string $field, $default = '') use ($formNotification, $campaign) {
            if ($formNotification) {
                return old($field, $formNotification->{$field} ?? $default);
            }
            return old($field, match ($field) {
                'title' => $campaign[2],
                'body' => $campaign[3],
                'deep_link_url' => $campaign[4],
                default => $default,
            });
        };
        $campaignKeyForForm = $formNotification ? data_get($formNotification->meta_json ?? [], 'campaign_type', 'general') : $campaignType;
        $currentMediaId = (int) old('media_asset_id', $formNotification?->media_asset_id ?: 0);
        $currentImageUrl = (string) old('image_url', $formNotification?->image_url ?: '');
        $sendModeDefault = old('send_mode', $formNotification ? (($formNotification->status === 'scheduled') ? (($formNotification->recurrence_type ?? 'none') !== 'none' ? 'repeat' : 'schedule') : 'draft') : 'draft');
        $recurrenceDefault = old('recurrence_type', $formNotification?->recurrence_type ?: 'none');
    @endphp

    <style>
        .dxm-push-shell{display:grid;gap:16px;padding-bottom:32px}.dxm-push-hero{border:1px solid rgba(34,211,238,.18);border-radius:26px;padding:20px;background:radial-gradient(circle at top left,rgba(34,211,238,.18),transparent 34%),radial-gradient(circle at top right,rgba(168,85,247,.17),transparent 34%),linear-gradient(135deg,rgba(2,6,23,.96),rgba(15,23,42,.9))}.dxm-push-hero-inner{display:grid;grid-template-columns:minmax(0,1fr) auto;gap:18px;align-items:center}.dxm-kicker{display:inline-flex;align-items:center;min-height:28px;padding:6px 11px;border-radius:999px;border:1px solid rgba(34,211,238,.34);background:rgba(34,211,238,.10);color:rgba(207,250,254,.96);font-size:11px;font-weight:950;letter-spacing:.08em;text-transform:uppercase}.dxm-title{margin:11px 0 0;color:#fff;font-size:clamp(28px,4vw,42px);line-height:1.03;font-weight:950;letter-spacing:-.055em}.dxm-sub{margin-top:8px;max-width:900px;color:rgba(255,255,255,.68);font-size:13px;line-height:1.58}.dxm-stat-grid{display:grid;grid-template-columns:repeat(2,minmax(120px,1fr));gap:10px;min-width:280px}.dxm-stat{padding:14px;border:1px solid rgba(255,255,255,.10);border-radius:18px;background:rgba(2,6,23,.48)}.dxm-stat strong{display:block;color:#fff;font-size:22px;line-height:1}.dxm-stat span{display:block;margin-top:6px;color:rgba(255,255,255,.58);font-size:11px;font-weight:850}.dxm-tabs{position:sticky;top:72px;z-index:20;display:flex;gap:8px;flex-wrap:wrap;border:1px solid rgba(255,255,255,.08);border-radius:20px;padding:9px;background:rgba(3,7,18,.88);backdrop-filter:blur(14px)}.dxm-tab{display:inline-flex;gap:7px;align-items:center;min-height:36px;border:1px solid rgba(255,255,255,.10);background:rgba(255,255,255,.045);color:rgba(255,255,255,.76);border-radius:999px;padding:8px 12px;font-size:12px;font-weight:900;text-decoration:none}.dxm-tab:hover,.dxm-tab.active{border-color:rgba(34,211,238,.45);background:rgba(34,211,238,.12);color:#fff}.dxm-panel{border:1px solid rgba(255,255,255,.09);border-radius:22px;padding:15px;background:rgba(255,255,255,.035)}.dxm-section-title{margin:0;color:#fff;font-size:20px;font-weight:950;letter-spacing:-.035em}.dxm-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(250px,1fr));gap:12px}.dxm-workspace{display:grid;grid-template-columns:minmax(0,1fr) minmax(320px,430px);gap:14px;align-items:start}.dxm-card{border:1px solid rgba(255,255,255,.09);border-radius:20px;background:rgba(2,6,23,.42);padding:15px;display:grid;gap:11px;min-height:120px}.dxm-card.good{border-color:rgba(34,197,94,.22);background:linear-gradient(135deg,rgba(34,197,94,.11),rgba(2,6,23,.42))}.dxm-card.warn{border-color:rgba(245,158,11,.23);background:linear-gradient(135deg,rgba(245,158,11,.11),rgba(2,6,23,.42))}.dxm-card.danger{border-color:rgba(239,68,68,.25);background:linear-gradient(135deg,rgba(239,68,68,.10),rgba(2,6,23,.42))}.dxm-card strong{color:#fff;font-size:15px;font-weight:950}.dxm-card small{color:rgba(255,255,255,.62);font-size:12px;line-height:1.5}.dxm-btn-row{display:flex;gap:8px;flex-wrap:wrap}.dxm-btn{display:inline-flex;align-items:center;justify-content:center;min-height:38px;padding:8px 12px;border-radius:12px;border:1px solid rgba(255,255,255,.11);background:rgba(255,255,255,.055);color:rgba(255,255,255,.84);font-size:12px;font-weight:900;text-decoration:none;cursor:pointer}.dxm-btn.primary{border-color:rgba(34,211,238,.35);background:linear-gradient(135deg,rgba(34,211,238,.18),rgba(124,58,237,.20));color:#fff}.dxm-btn.danger{border-color:rgba(239,68,68,.35);background:rgba(239,68,68,.10);color:#fecaca}.dxm-btn:disabled{opacity:.45;cursor:not-allowed}.dxm-pill-row{display:flex;gap:7px;flex-wrap:wrap}.dxm-pill{display:inline-flex;align-items:center;min-height:24px;padding:5px 8px;border-radius:999px;border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.055);color:rgba(255,255,255,.70);font-size:10px;font-weight:950;text-transform:uppercase;letter-spacing:.04em}.dxm-pill.good{border-color:rgba(34,197,94,.28);background:rgba(34,197,94,.12);color:#bbf7d0}.dxm-pill.warn{border-color:rgba(245,158,11,.28);background:rgba(245,158,11,.12);color:#fde68a}.dxm-pill.info{border-color:rgba(34,211,238,.28);background:rgba(34,211,238,.12);color:#cffafe}.dxm-pill.danger{border-color:rgba(239,68,68,.25);background:rgba(239,68,68,.10);color:#fecaca}.dxm-pill.muted{opacity:.7}.dxm-form{display:grid;gap:14px}.dxm-form-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.dxm-field{display:grid;gap:6px}.dxm-field label{color:#fff;font-size:12px;font-weight:900}.dxm-field input,.dxm-field textarea,.dxm-field select{width:100%;border:1px solid rgba(255,255,255,.12);border-radius:13px;background:rgba(15,23,42,.78);color:#fff;padding:10px 12px;outline:none}.dxm-field select option{background:#0f172a;color:#fff}.dxm-field textarea{min-height:106px;resize:vertical}.dxm-help{color:rgba(255,255,255,.55);font-size:11px;line-height:1.45}.dxm-subsection{border:1px solid rgba(255,255,255,.08);background:rgba(255,255,255,.025);border-radius:18px;padding:13px;display:grid;gap:12px}.dxm-subsection h3{margin:0;color:#fff;font-size:14px;font-weight:950}.dxm-preview-stack{position:sticky;top:138px;display:grid;gap:12px}.dxm-preview-phone{border:1px solid rgba(34,211,238,.18);border-radius:28px;background:linear-gradient(180deg,rgba(15,23,42,.92),rgba(2,6,23,.98));padding:13px}.dxm-preview-label{color:rgba(255,255,255,.55);font-size:11px;font-weight:950;text-transform:uppercase;letter-spacing:.08em}.dxm-notification-preview{border:1px solid rgba(255,255,255,.12);border-radius:20px;background:rgba(255,255,255,.08);padding:12px;display:grid;grid-template-columns:58px minmax(0,1fr);gap:12px}.dxm-preview-img{width:58px;height:58px;border-radius:16px;background:rgba(255,255,255,.09);display:grid;place-items:center;overflow:hidden;color:#fff;font-weight:950}.dxm-preview-img img{width:100%;height:100%;object-fit:cover}.dxm-notification-preview strong{display:block;color:#fff;font-size:13px}.dxm-notification-preview small{display:block;margin-top:4px;color:rgba(255,255,255,.68);font-size:11px;line-height:1.4}.dxm-inapp-card{border:1px solid rgba(255,255,255,.10);border-radius:24px;background:radial-gradient(circle at top left,rgba(34,211,238,.12),transparent 38%),rgba(15,23,42,.72);overflow:hidden}.dxm-inapp-image{height:142px;background:rgba(255,255,255,.06);display:grid;place-items:center;overflow:hidden}.dxm-inapp-image img{width:100%;height:100%;object-fit:cover}.dxm-inapp-body{padding:14px;display:grid;gap:10px}.dxm-logo-line{display:flex;align-items:center;gap:9px}.dxm-logo{width:38px;height:38px;border-radius:13px;background:linear-gradient(135deg,#22d3ee,#7c3aed);display:grid;place-items:center;color:#fff;font-weight:950;overflow:hidden}.dxm-logo img{width:100%;height:100%;object-fit:cover}.dxm-row{border:1px solid rgba(255,255,255,.09);border-radius:18px;background:rgba(2,6,23,.40);padding:13px;display:grid;grid-template-columns:minmax(0,1fr) auto;gap:12px;align-items:center;margin-bottom:10px}.dxm-row h3{margin:0;color:#fff;font-size:14px;font-weight:950}.dxm-row p{margin:5px 0 0;color:rgba(255,255,255,.58);font-size:12px;line-height:1.45}.dxm-alert{border:1px dashed rgba(34,211,238,.24);border-radius:18px;background:rgba(34,211,238,.07);padding:13px;color:rgba(224,250,255,.85);font-size:12px;line-height:1.55}.dxm-group-title{margin:12px 0 0;color:rgba(255,255,255,.88);font-size:13px;font-weight:950;text-transform:uppercase;letter-spacing:.06em}.dxm-modal-backdrop{position:fixed;inset:0;z-index:9998;background:rgba(0,0,0,.72);display:none;align-items:center;justify-content:center;padding:20px}.dxm-modal-backdrop.open{display:flex}.dxm-modal{width:min(1040px,96vw);max-height:88vh;overflow:hidden;border:1px solid rgba(34,211,238,.22);border-radius:24px;background:#06111f;box-shadow:0 24px 70px rgba(0,0,0,.5);display:grid;grid-template-rows:auto auto minmax(0,1fr)}.dxm-modal-head{display:flex;align-items:center;justify-content:space-between;gap:12px;padding:16px 18px;border-bottom:1px solid rgba(255,255,255,.08)}.dxm-modal-title{margin:0;color:#fff;font-size:17px;font-weight:950}.dxm-modal-search{padding:12px 18px;border-bottom:1px solid rgba(255,255,255,.08)}.dxm-modal-search input{width:100%;border:1px solid rgba(255,255,255,.12);border-radius:14px;background:rgba(15,23,42,.75);color:#fff;padding:11px 13px}.dxm-image-grid{overflow:auto;padding:16px 18px;display:grid;grid-template-columns:repeat(auto-fill,minmax(150px,1fr));gap:12px}.dxm-image-card{border:1px solid rgba(255,255,255,.09);border-radius:17px;background:rgba(255,255,255,.04);padding:9px;display:grid;gap:8px;color:#fff;cursor:pointer;text-align:left}.dxm-image-card:hover,.dxm-image-card.active{border-color:rgba(34,211,238,.45);background:rgba(34,211,238,.10)}.dxm-image-thumb{height:105px;border-radius:13px;background:rgba(255,255,255,.07);overflow:hidden;display:grid;place-items:center}.dxm-image-thumb img{width:100%;height:100%;object-fit:cover}.dxm-image-card strong{font-size:12px;line-height:1.2;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.dxm-image-card small{font-size:10px;color:rgba(255,255,255,.58)}.dxm-selected-image{display:grid;grid-template-columns:72px minmax(0,1fr);gap:10px;align-items:center;border:1px dashed rgba(34,211,238,.25);border-radius:16px;padding:10px;background:rgba(34,211,238,.06)}.dxm-selected-image img{width:72px;height:52px;border-radius:12px;object-fit:cover;background:rgba(255,255,255,.06)}.dxm-repeat-box[data-hidden="true"]{display:none}@media(max-width:1100px){.dxm-workspace{grid-template-columns:1fr}.dxm-preview-stack{position:relative;top:auto}.dxm-push-hero-inner{grid-template-columns:1fr}.dxm-stat-grid{min-width:0}}@media(max-width:760px){.dxm-tabs{top:56px;overflow-x:auto;flex-wrap:nowrap}.dxm-tab{flex:0 0 auto}.dxm-stat-grid,.dxm-form-grid{grid-template-columns:1fr}.dxm-row{grid-template-columns:1fr}.dxm-image-grid{grid-template-columns:repeat(auto-fill,minmax(128px,1fr))}}
    </style>

    <div class="dxm-push-shell" id="dxmPushCenter" data-app-name="{{ e($appName) }}" data-app-logo="{{ e($appLogo) }}">
        @if (session('status'))
            <div class="dxm-alert">{{ session('status') }}</div>
        @endif

        <section class="dxm-push-hero">
            <div class="dxm-push-hero-inner">
                <div>
                    <div class="dxm-kicker">Beginner Push Message Center</div>
                    <h1 class="dxm-title">{{ $appName }} Push Notifications</h1>
                    <div class="dxm-sub">Create clean push messages, preview the in-app card, schedule or repeat delivery, and keep sent notifications as read-only archive records.</div>
                    <div class="dxm-btn-row" style="margin-top:14px;">
                        <a href="{{ route('admin.beginner.push.create') }}" class="dxm-btn primary">+ Create Notification</a>
                        <a href="{{ url('/admin/push-center?tab=campaigns') }}" class="dxm-btn">Content Templates</a>
                        <a href="{{ url('/admin/push-center?tab=drafts') }}" class="dxm-btn">Drafts / Queue</a>
                        <a href="{{ url('/admin/push-notifications') }}" class="dxm-btn">Advanced Table</a>
                    </div>
                </div>
                <div class="dxm-stat-grid">
                    <div class="dxm-stat"><strong>{{ $stats['draft'] }}</strong><span>Drafts</span></div>
                    <div class="dxm-stat"><strong>{{ $stats['scheduled'] }}</strong><span>Scheduled</span></div>
                    <div class="dxm-stat"><strong>{{ $stats['sent'] }}</strong><span>Sent Archive</span></div>
                    <div class="dxm-stat"><strong>{{ $stats['failed'] }}</strong><span>Failed</span></div>
                </div>
            </div>
        </section>

        <nav class="dxm-tabs" aria-label="Push notification sections">
            @foreach ($tabs as $key => $label)
                <a class="dxm-tab {{ $activeTab === $key ? 'active' : '' }}" href="{{ url('/admin/push-center') }}?tab={{ $key }}{{ $key === 'edit' && $editNotification ? '&id=' . $editNotification->id : '' }}">{{ $label }}</a>
            @endforeach
        </nav>

        @if ($viewNotification)
            <section class="dxm-panel">
                <div class="dxm-workspace">
                    <div>
                        <div class="dxm-kicker">Read-only notification detail</div>
                        <h2 class="dxm-section-title" style="margin-top:10px;">{{ $viewNotification->title }}</h2>
                        <div class="dxm-sub">{{ $viewNotification->body }}</div>
                        <div class="dxm-pill-row" style="margin-top:12px;">
                            <span class="dxm-pill {{ $statusClass($viewNotification->status) }}">{{ $statusLabel($viewNotification->status) }}</span>
                            <span class="dxm-pill">{{ $viewNotification->target_type ?: 'topic' }}</span>
                            <span class="dxm-pill">{{ $viewNotification->recurrence_type ?: 'none' }}</span>
                            @if ($viewNotification->image_url)<span class="dxm-pill info">Image</span>@endif
                        </div>
                        <div class="dxm-btn-row" style="margin-top:14px;">
                            <a class="dxm-btn" href="{{ url('/admin/push-center?tab=drafts') }}">Close Detail</a>
                            <form method="POST" action="{{ route('admin.beginner.push.duplicate', $viewNotification) }}">@csrf <button class="dxm-btn primary" type="submit">Duplicate as Draft</button></form>
                            <form method="POST" action="{{ route('admin.beginner.push.send-now', $viewNotification) }}">@csrf @method('PATCH')<button class="dxm-btn" type="submit">Send Again Copy</button></form>
                        </div>
                    </div>
                    @include('filament.pages.partials.push-notification-preview-card', ['item' => $viewNotification, 'appName' => $appName, 'appLogo' => $appLogo, 'appInitial' => $appInitial])
                </div>
            </section>
        @endif

        @if ($activeTab === 'overview')
            <section class="dxm-panel"><h2 class="dxm-section-title">Notification Overview</h2><div class="dxm-sub">Clean status separation for the selected app only. Sent messages are archive records; drafts and scheduled messages are editable.</div></section>
            <section class="dxm-grid">
                <a class="dxm-card" href="{{ url('/admin/push-center?tab=drafts') }}"><strong>Drafts / Queue</strong><small>Prepared messages that can still be edited.</small><span class="dxm-pill">{{ $stats['draft'] }} drafts</span></a>
                <a class="dxm-card warn" href="{{ url('/admin/push-center?tab=schedule') }}"><strong>Scheduled / Repeating</strong><small>Upcoming and recurring messages.</small><span class="dxm-pill warn">{{ $stats['scheduled'] }} scheduled</span></a>
                <a class="dxm-card good" href="{{ url('/admin/push-center?tab=sent') }}"><strong>Sent Archive</strong><small>Read-only sent records with view, duplicate, send-again and delete options.</small><span class="dxm-pill good">{{ $stats['sent'] }} sent</span></a>
                <a class="dxm-card" href="{{ url('/admin/push-center?tab=campaigns') }}"><strong>Content Templates</strong><small>Article, video, daily quote, daily scripture, book, quiz and event alerts.</small><span class="dxm-pill info">Reusable</span></a>
                <div class="dxm-card"><strong>Image Cards</strong><small>Messages with image support and in-app card preview.</small><span class="dxm-pill info">{{ $stats['with_image'] }} with image</span></div>
                <div class="dxm-card danger"><strong>Failed</strong><small>Needs provider/key/queue check if real sending is enabled.</small><span class="dxm-pill danger">{{ $stats['failed'] }} failed</span></div>
            </section>
        @elseif ($activeTab === 'campaigns')
            <section class="dxm-panel"><h2 class="dxm-section-title">Content Notification Templates</h2><div class="dxm-sub">Start from the correct content type: article, short video, daily scripture, daily quote, book, quiz, live broadcast, or event.</div></section>
            @foreach ($campaignGroups as $group => $items)
                <h3 class="dxm-group-title">{{ $group }}</h3>
                <section class="dxm-grid">@foreach ($items as $key => $item)<a class="dxm-card" href="{{ route('admin.beginner.push.create') }}?type={{ $key }}"><strong>{{ $item[0] }}</strong><small>{{ $item[1] }}</small><div class="dxm-pill-row"><span class="dxm-pill info">{{ $item[5] }}</span><span class="dxm-pill">{{ $item[4] }}</span></div></a>@endforeach</section>
            @endforeach
        @elseif ($activeTab === 'drafts')
            <section class="dxm-panel"><h2 class="dxm-section-title">Drafts / Queue</h2><div class="dxm-sub">Only editable drafts, queued/sending messages, failed and cancelled notifications appear here. Sent messages are separated under Sent Archive.</div></section>
            @foreach ($workflowGroups as $group => $items)
                <h3 class="dxm-group-title">{{ $group }}</h3>
                @forelse ($items as $item)
                    <article class="dxm-row"><div><h3>{{ $item->title }}</h3><p>{{ \Illuminate\Support\Str::limit($item->body, 160) }}</p><div class="dxm-pill-row" style="margin-top:8px;"><span class="dxm-pill {{ $statusClass($item->status) }}">{{ $statusLabel($item->status) }}</span><span class="dxm-pill">{{ $item->target_type ?: 'topic' }}</span><span class="dxm-pill">{{ $item->recurrence_type ?: 'none' }}</span>@if ($item->image_url)<span class="dxm-pill info">Image</span>@endif @if ($item->deep_link_url)<span class="dxm-pill">{{ $item->deep_link_url }}</span>@endif</div><p>Schedule: {{ $formatDate($item->scheduled_for) }}</p></div><div class="dxm-btn-row">
                        <a class="dxm-btn primary" href="{{ route('admin.beginner.push.view', ['pushNotification' => $item->id]) }}">View</a>
                        @if (in_array($item->status, ['draft','scheduled','queued','sending','failed'], true))<a class="dxm-btn" href="{{ route('admin.beginner.push.edit', ['pushNotification' => $item->id]) }}">Edit</a>@endif
                        <form method="POST" action="{{ route('admin.beginner.push.send-now', $item) }}">@csrf @method('PATCH')<button class="dxm-btn" type="submit">{{ $item->status === 'sent' ? 'Send Again' : 'Send Now' }}</button></form>
                        <form method="POST" action="{{ route('admin.beginner.push.duplicate', $item) }}">@csrf <button class="dxm-btn" type="submit">Duplicate as Draft</button></form>
                        @if (in_array($item->status, ['draft','scheduled','queued','sending'], true))<form method="POST" action="{{ route('admin.beginner.push.cancel', $item) }}">@csrf @method('PATCH')<button class="dxm-btn danger" type="submit">Cancel</button></form>@endif
                        @if ($item->status !== 'sent')<form method="POST" action="{{ route('admin.beginner.push.delete', $item) }}" onsubmit="return confirm('Delete this notification?');">@csrf @method('DELETE')<button class="dxm-btn danger" type="submit">Delete</button></form>@endif
                    </div></article>
                @empty <div class="dxm-alert">No notification in this group yet.</div> @endforelse
            @endforeach

        @elseif ($activeTab === 'sent')
            <section class="dxm-panel"><h2 class="dxm-section-title">Sent Archive</h2><div class="dxm-sub">Read-only history of messages already sent out. Use View for details, Duplicate as Draft to reuse, Send Again to resend a clean copy, or Delete to remove an archive record.</div></section>
            @forelse ($notifications->where('status', 'sent') as $item)
                <article class="dxm-row"><div><h3>{{ $item->title }}</h3><p>{{ \Illuminate\Support\Str::limit($item->body, 170) }}</p><div class="dxm-pill-row" style="margin-top:8px;"><span class="dxm-pill good">SENT</span><span class="dxm-pill">{{ $item->target_type ?: 'topic' }}</span>@if ($item->image_url)<span class="dxm-pill info">Image</span>@endif @if ($item->deep_link_url)<span class="dxm-pill">{{ $item->deep_link_url }}</span>@endif</div><p>Sent record: {{ $formatDate($item->updated_at) }}</p></div><div class="dxm-btn-row"><a class="dxm-btn primary" href="{{ route('admin.beginner.push.view', ['pushNotification' => $item->id]) }}">View</a><form method="POST" action="{{ route('admin.beginner.push.duplicate', $item) }}">@csrf <button class="dxm-btn" type="submit">Duplicate as Draft</button></form><form method="POST" action="{{ route('admin.beginner.push.send-now', $item) }}">@csrf @method('PATCH')<button class="dxm-btn" type="submit">Send Again Copy</button></form><form method="POST" action="{{ route('admin.beginner.push.delete', $item) }}" onsubmit="return confirm('Delete this sent archive record?');">@csrf @method('DELETE')<button class="dxm-btn danger" type="submit">Delete</button></form></div></article>
            @empty <div class="dxm-alert">No sent notification yet.</div>@endforelse
        @elseif ($activeTab === 'schedule')
            <section class="dxm-panel"><h2 class="dxm-section-title">Repeat & Schedule</h2><div class="dxm-sub">A clean list of one-time scheduled messages and recurring reminders.</div></section>
            <section class="dxm-grid"><div class="dxm-card"><strong>Daily</strong><small>Sends every day at the selected hour/minute. Good for daily scripture or daily quote.</small></div><div class="dxm-card"><strong>Weekly</strong><small>Sends on selected weekdays. Good for service reminders.</small></div><div class="dxm-card"><strong>Monthly</strong><small>Sends on selected month day. Good for monthly programmes.</small></div></section>
            @forelse ($scheduleItems as $item)
                <article class="dxm-row">
                    <div>
                        <h3>{{ $item->title }}</h3>
                        <p>{{ \Illuminate\Support\Str::limit($item->body, 150) }}</p>
                        <div class="dxm-pill-row" style="margin-top:8px;">
                            <span class="dxm-pill {{ $statusClass($item->status) }}">{{ $statusLabel($item->status) }}</span>
                            <span class="dxm-pill">{{ $item->recurrence_type ?: 'none' }}</span>
                            @if ($item->timezone)<span class="dxm-pill info">{{ $item->timezone }}</span>@endif
                            @if ($item->max_runs)<span class="dxm-pill">Max {{ $item->max_runs }} runs</span>@endif
                            @if ($item->image_url)<span class="dxm-pill info">Image</span>@endif
                        </div>
                        <p>Next/current schedule: {{ $formatDate($item->scheduled_for) }}</p>
                    </div>
                    <div class="dxm-btn-row">
                        <a class="dxm-btn primary" href="{{ route('admin.beginner.push.edit', ['pushNotification' => $item->id]) }}">Edit Schedule</a>
                        <form method="POST" action="{{ route('admin.beginner.push.send-now', $item) }}">@csrf @method('PATCH')<button class="dxm-btn" type="submit">Send Now Copy</button></form>
                        <form method="POST" action="{{ route('admin.beginner.push.duplicate', $item) }}">@csrf <button class="dxm-btn" type="submit">Duplicate as Draft</button></form>
                        <form method="POST" action="{{ route('admin.beginner.push.delete', $item) }}" onsubmit="return confirm('Delete this scheduled/repeating notification?');">@csrf @method('DELETE')<button class="dxm-btn danger" type="submit">Delete</button></form>
                    </div>
                </article>
            @empty <div class="dxm-alert">No scheduled or recurring notification yet.</div>@endforelse
        @elseif ($activeTab === 'automation')
            <section class="dxm-panel"><h2 class="dxm-section-title">Auto Alerts Blueprint</h2><div class="dxm-sub">Content publishing can later create notification drafts automatically. This prepares the proper categories.</div></section>
            <section class="dxm-grid"><div class="dxm-card good"><strong>New {{ $articleName }} Published</strong><small>Create a notification when article/devotional/motivation content is published.</small><span class="dxm-pill info">Planned</span></div><div class="dxm-card good"><strong>New Video Published</strong><small>Notify users when a new watch/video item is published.</small><span class="dxm-pill info">Planned</span></div><div class="dxm-card good"><strong>New {{ $shortVideoName }}</strong><small>Notify users when a short video is posted.</small><span class="dxm-pill info">Planned</span></div><div class="dxm-card good"><strong>Daily {{ $scriptureName }} / {{ $quoteName }}</strong><small>Prepare reminders from the daily card engines.</small><span class="dxm-pill info">Planned</span></div><div class="dxm-card good"><strong>New {{ $bookName }} / {{ $quizName }}</strong><small>Notify users about new learning and library items.</small><span class="dxm-pill info">Planned</span></div></section>
        @else
            <section class="dxm-panel"><h2 class="dxm-section-title">Push Notification Guide</h2><div class="dxm-sub">Simple rules for safe delivery and a clean user experience.</div></section>
            <section class="dxm-grid"><div class="dxm-card good"><strong>Use clear messages</strong><small>Title should be short. Body should explain exactly what the user gets when they tap.</small></div><div class="dxm-card"><strong>Add image when useful</strong><small>Images work best for events, new videos, and major announcements.</small></div><div class="dxm-card warn"><strong>Schedule carefully</strong><small>Use Africa/Lagos timing. Avoid too many notifications in one day.</small></div><div class="dxm-card danger"><strong>Check provider mode</strong><small>If PUSH_DRIVER=log, sending is simulated. If FCM is active, credentials must be configured.</small></div></section>
        @endif
    </div>

    <script>
        (function () {
            const modal = document.getElementById('dxmImageLibraryModal');
            const openBtn = document.getElementById('dxmOpenImageLibrary');
            const closeBtn = document.getElementById('dxmCloseImageLibrary');
            const searchInput = document.getElementById('dxmImageLibrarySearch');
            const mediaInput = document.getElementById('dxmSelectedMediaAssetId');
            const selectedBox = document.getElementById('dxmSelectedImageBox');
            const selectedPreview = document.getElementById('dxmSelectedImagePreview');
            const selectedLabel = document.getElementById('dxmSelectedImageLabel');
            const selectedInfo = document.getElementById('dxmSelectedImageInfo');
            const phonePreviewImg = document.getElementById('dxmPhonePreviewImg');
            const phonePreviewIcon = document.getElementById('dxmPhonePreviewIcon');
            const inAppImage = document.getElementById('dxmInAppImage');
            const imageUrlInput = document.getElementById('dxmImageUrlInput');
            const uploadInput = document.getElementById('dxmUploadImageInput');
            const sendMode = document.getElementById('dxmSendMode');
            const recurrenceType = document.getElementById('dxmRecurrenceType');

            function text(id, value) { const node = document.getElementById(id); if (node) node.textContent = value || ''; }
            function bindPreview(inputId, targetIds, fallback) { const input = document.getElementById(inputId); if (!input) return; const update = () => targetIds.forEach(id => text(id, input.value.trim() || fallback)); input.addEventListener('input', update); update(); }
            bindPreview('dxmTitleInput', ['dxmPreviewTitle', 'dxmInAppTitle'], 'Notification title');
            bindPreview('dxmBodyInput', ['dxmPreviewBody', 'dxmInAppBody'], 'Message body');
            bindPreview('dxmDeepLinkInput', ['dxmPreviewDeepLink'], '/notifications');

            function showPreview(url, label, info) { if (!url) return; if (selectedBox) selectedBox.style.display = 'grid'; if (selectedPreview) selectedPreview.src = url; if (selectedLabel) selectedLabel.textContent = label || 'Selected image'; if (selectedInfo) selectedInfo.textContent = info || url; [phonePreviewImg, inAppImage].forEach(img => { if (img) { img.src = url; img.style.display = 'block'; } }); if (phonePreviewIcon) phonePreviewIcon.style.display = 'none'; }
            function syncRepeatFields() { const mode = sendMode ? sendMode.value : 'draft'; const rec = recurrenceType ? recurrenceType.value : 'none'; document.querySelectorAll('[data-schedule-field]').forEach(el => el.setAttribute('data-hidden', (mode === 'schedule' || mode === 'repeat') ? 'false' : 'true')); document.querySelectorAll('[data-repeat-field]').forEach(el => el.setAttribute('data-hidden', mode === 'repeat' ? 'false' : 'true')); document.querySelectorAll('[data-weekly-field]').forEach(el => el.setAttribute('data-hidden', (mode === 'repeat' && rec === 'weekly') ? 'false' : 'true')); document.querySelectorAll('[data-monthly-field]').forEach(el => el.setAttribute('data-hidden', (mode === 'repeat' && rec === 'monthly') ? 'false' : 'true')); }
            if (sendMode) sendMode.addEventListener('change', syncRepeatFields); if (recurrenceType) recurrenceType.addEventListener('change', syncRepeatFields); syncRepeatFields();

            if (openBtn && modal) openBtn.addEventListener('click', () => { modal.classList.add('open'); modal.setAttribute('aria-hidden', 'false'); });
            if (closeBtn && modal) closeBtn.addEventListener('click', () => { modal.classList.remove('open'); modal.setAttribute('aria-hidden', 'true'); });
            if (modal) modal.addEventListener('click', event => { if (event.target === modal) { modal.classList.remove('open'); modal.setAttribute('aria-hidden', 'true'); }});
            document.querySelectorAll('.dxm-image-card').forEach(card => { card.addEventListener('click', () => { const id = card.getAttribute('data-id'); const url = card.getAttribute('data-url'); const label = card.getAttribute('data-label'); document.querySelectorAll('.dxm-image-card').forEach(item => item.classList.remove('active')); card.classList.add('active'); if (mediaInput) mediaInput.value = id || ''; if (imageUrlInput) imageUrlInput.value = url || ''; showPreview(url, label, 'Media asset ID: ' + id); if (modal) { modal.classList.remove('open'); modal.setAttribute('aria-hidden', 'true'); }}); });
            if (searchInput) searchInput.addEventListener('input', () => { const term = searchInput.value.trim().toLowerCase(); document.querySelectorAll('.dxm-image-card').forEach(card => { const source = card.getAttribute('data-search') || ''; card.style.display = source.includes(term) ? 'grid' : 'none'; }); });
            if (uploadInput) uploadInput.addEventListener('change', () => { const file = uploadInput.files && uploadInput.files[0] ? uploadInput.files[0] : null; if (!file) return; const url = URL.createObjectURL(file); if (mediaInput) mediaInput.value = ''; showPreview(url, file.name, 'Upload preview. It will save when you submit.'); });
            if (imageUrlInput) imageUrlInput.addEventListener('input', () => { const url = imageUrlInput.value.trim(); if (!url) return; if (mediaInput) mediaInput.value = ''; showPreview(url, 'Image URL', url); });
        })();
    </script>
</x-filament::page>
