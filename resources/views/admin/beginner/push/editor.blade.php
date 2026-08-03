
<style id="push-logo-holder-clean-6j">
    [data-push-logo-wrap],
    .push-logo-holder,
    .push-preview-logo-holder,
    .push-card-logo-holder,
    .push-app-logo-holder {
        background: transparent !important;
        background-color: transparent !important;
        background-image: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
        padding: 0 !important;
        overflow: visible !important;
    }

    [data-push-logo-wrap] img,
    .push-logo-holder img,
    .push-preview-logo-holder img,
    .push-card-logo-holder img,
    .push-app-logo-holder img {
        background: transparent !important;
        background-color: transparent !important;
        background-image: none !important;
        object-fit: contain !important;
        border-radius: 0 !important;
        box-shadow: none !important;
    }

    .push-preview-logo-bg,
    .push-card-logo-bg,
    .push-message-logo-bg {
        background: transparent !important;
        background-color: transparent !important;
        background-image: none !important;
    }
</style>

@php
    $n = $notification;
    $isEdit = $mode === 'edit';
    $isView = $readOnly || $mode === 'view';
    $titleValue = old('title', $n->title ?: 'New Update from ' . $app->name);
    $bodyValue = old('body', $n->body ?: 'Open the app to see the latest update prepared for you.');
    $deepLinkValue = old('deep_link_url', $n->deep_link_url ?: '/');
    $externalUrlValue = old('external_url', data_get($meta, 'external_url', ''));
    $actionType = old('action_type', data_get($meta, 'action_type', filled($externalUrlValue) ? 'external' : 'internal'));
    $timezoneValue = old('timezone', $n->timezone ?: (config('push.default_timezone') ?: config('app.timezone') ?: 'Africa/Lagos'));
    $scheduledForValue = old('scheduled_for');
    if ($scheduledForValue === null) {
        $scheduledForValue = \App\Support\Scheduling\AdminScheduleTime::toLocalInput(
            $n->scheduled_for,
            $timezoneValue
        );
    }
    $imageValue = old('image_url', $n->image_url ?: '');
    $selectedMediaId = (int) old('media_asset_id', $n->media_asset_id ?: 0);
    $targetType = old('target_type', $n->target_type ?: 'all');
    $targetValue = old('target_value', $n->target_value ?: '');
    $defaultAppTopic = (string) config('push.topic_prefix', 'app-') . $app->slug;
    if (! old('target_type') && $targetType === 'topic' && $targetValue === $defaultAppTopic) { $targetType = 'all'; }
    $internalLinks = $internalLinks ?? [];
    $sendMode = old('send_mode', $isEdit ? (((string) ($n->recurrence_type ?: 'none')) !== 'none' ? 'repeat' : (($n->status === 'scheduled') ? 'schedule' : 'draft')) : 'draft');
    $recurrenceType = old('recurrence_type', $n->recurrence_type ?: 'none');
    $recurrenceHourValue = old('recurrence_hour', is_null($n->recurrence_hour) ? 0 : $n->recurrence_hour);
    $recurrenceMinuteValue = old('recurrence_minute', is_null($n->recurrence_minute) ? 0 : $n->recurrence_minute);
    $weekdays = old('recurrence_weekdays', is_array($n->recurrence_weekdays ?? null) ? $n->recurrence_weekdays : []);
    if ($recurrenceType === 'daily' && empty($weekdays)) { $weekdays = [1,2,3,4,5,6,7]; }
    $actionLabel = old('action_label', data_get($meta, 'action_label', filled($deepLinkValue) ? 'Open' : 'View Message'));
    $campaignType = old('campaign_type', data_get($meta, 'campaign_type', 'general'));
    $appInitial = strtoupper(mb_substr($app->name, 0, 1));
    $pageTitle = $isView ? 'View Notification' : ($isEdit ? 'Edit Notification' : 'Create Notification');
@endphp
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle }} • {{ $app->name }}</title>
    <style>
        *{box-sizing:border-box}body{margin:0;background:#030712;color:#fff;font-family:Inter,ui-sans-serif,system-ui,-apple-system,BlinkMacSystemFont,"Segoe UI",sans-serif}.pe-shell{min-height:100vh;padding:18px;background:radial-gradient(circle at top left,rgba(34,211,238,.12),transparent 34%),radial-gradient(circle at top right,rgba(124,58,237,.14),transparent 36%),#030712}.pe-top{display:flex;align-items:center;justify-content:space-between;gap:12px;border:1px solid rgba(255,255,255,.09);border-radius:23px;padding:16px 18px;background:linear-gradient(135deg,rgba(8,47,73,.70),rgba(17,24,39,.76),rgba(49,46,129,.35));box-shadow:0 18px 46px rgba(0,0,0,.22)}.pe-kicker{display:inline-flex;min-height:29px;align-items:center;border:1px solid rgba(34,211,238,.38);border-radius:999px;background:rgba(34,211,238,.10);color:#a5f3fc;padding:5px 10px;font-size:11px;font-weight:950;letter-spacing:.08em;text-transform:uppercase}.pe-title{margin:8px 0 0;font-size:clamp(21px,3vw,28px);line-height:1.1;font-weight:950;letter-spacing:-.04em}.pe-sub{margin-top:5px;color:rgba(255,255,255,.68);font-size:13px}.pe-back{border:1px solid rgba(255,255,255,.12);border-radius:14px;background:rgba(2,6,23,.72);color:#fff;text-decoration:none;padding:11px 15px;font-weight:950}.pe-tabs{margin-top:12px;display:flex;gap:8px;overflow:auto;border:1px solid rgba(255,255,255,.08);border-radius:20px;padding:8px;background:rgba(2,6,23,.68);position:sticky;top:8px;z-index:20}.pe-tab{flex:0 0 auto;border:1px solid rgba(255,255,255,.10);border-radius:999px;background:rgba(255,255,255,.055);color:rgba(255,255,255,.78);padding:9px 14px;font-size:12px;font-weight:950;cursor:pointer}.pe-tab.active{border-color:rgba(34,211,238,.50);background:rgba(34,211,238,.14);color:#fff}.pe-grid{margin-top:12px;display:grid;grid-template-columns:minmax(0,1.04fr) minmax(360px,.96fr);gap:14px;align-items:start}.pe-panel{border:1px solid rgba(255,255,255,.09);border-radius:22px;background:rgba(15,23,42,.56);overflow:hidden}.pe-panel-head{padding:15px 16px;border-bottom:1px solid rgba(255,255,255,.08)}.pe-panel-title{margin:0;font-size:16px;font-weight:950}.pe-panel-body{padding:16px;display:grid;gap:13px;max-height:calc(100vh - 190px);overflow:auto}.pe-tab-panel{display:none}.pe-tab-panel.active{display:grid;gap:13px}.pe-row{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:12px}.pe-field{display:grid;gap:6px}.pe-field label{font-size:12px;font-weight:950;color:#dff7ff}.pe-field input,.pe-field textarea,.pe-field select{width:100%;border:1px solid rgba(255,255,255,.12);border-radius:14px;background:rgba(2,6,23,.70);color:#fff;padding:11px 13px;font-size:14px;outline:none}.pe-field textarea{min-height:170px;resize:vertical;line-height:1.5}.pe-help{color:rgba(255,255,255,.54);font-size:11px;line-height:1.45}.pe-actions{position:sticky;bottom:0;display:flex;gap:10px;justify-content:flex-end;padding:13px 16px;border-top:1px solid rgba(255,255,255,.08);background:rgba(3,7,18,.92);backdrop-filter:blur(14px)}.pe-btn{display:inline-flex;align-items:center;justify-content:center;min-height:39px;padding:9px 14px;border:1px solid rgba(255,255,255,.12);border-radius:14px;background:rgba(255,255,255,.055);color:#fff;font-weight:950;text-decoration:none;cursor:pointer}.pe-btn.primary{border-color:rgba(34,211,238,.45);background:linear-gradient(135deg,#0891b2,#6d5dfc)}.pe-preview-wrap{position:sticky;top:86px;display:grid;gap:12px}.pe-phone-preview{border:1px solid rgba(34,211,238,.20);border-radius:28px;background:linear-gradient(180deg,rgba(15,23,42,.92),rgba(2,6,23,.98));padding:14px}.pe-push-tiny{display:grid;grid-template-columns:54px minmax(0,1fr);gap:11px;border:1px solid rgba(255,255,255,.13);border-radius:20px;background:rgba(255,255,255,.08);padding:10px}.pe-logo{width:54px;height:54px;border-radius:16px;background:linear-gradient(135deg,#06b6d4,#7c3aed);display:grid;place-items:center;overflow:hidden;font-weight:950}.pe-logo img{width:100%;height:100%;object-fit:cover}.pe-push-tiny strong{display:block;font-size:13px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}.pe-push-tiny span{display:block;margin-top:4px;color:rgba(255,255,255,.70);font-size:11px;line-height:1.35}.pe-card{overflow:hidden;border:1px solid rgba(255,255,255,.12);border-radius:28px;background:var(--card-bg,#f8fafc);color:var(--card-fg,#111827);box-shadow:0 20px 55px rgba(0,0,0,.25)}.pe-banner{height:210px;background:linear-gradient(135deg,rgba(14,165,233,.22),rgba(124,58,237,.20));display:grid;place-items:center;color:rgba(255,255,255,.75);font-size:12px;font-weight:950;letter-spacing:.09em;text-transform:uppercase;overflow:hidden}.pe-banner img{width:100%;height:100%;object-fit:cover}.pe-card-body{padding:18px;display:grid;gap:10px}.pe-card-brand{display:flex;align-items:center;gap:9px}.pe-card-brand .pe-logo{width:38px;height:38px;border-radius:12px;color:#fff}.pe-card-brand strong{font-size:14px}.pe-card-brand span{display:block;color:rgba(15,23,42,.58);font-size:11px}.pe-card-title{font-size:21px;font-weight:950;line-height:1.18;letter-spacing:-.03em}.pe-card-message{font-size:14px;line-height:1.55;color:rgba(15,23,42,.78);white-space:pre-wrap}.pe-card-actions{display:flex;gap:9px;flex-wrap:wrap;margin-top:5px}.pe-card-actions .pe-btn{color:#fff;background:#0f172a}.pe-asset-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(120px,1fr));gap:10px;max-height:320px;overflow:auto;padding:2px}.pe-asset{border:1px solid rgba(255,255,255,.10);border-radius:16px;background:rgba(255,255,255,.045);padding:8px;display:grid;gap:6px;color:#fff;cursor:pointer;text-align:left}.pe-asset.active{border-color:rgba(34,211,238,.55);background:rgba(34,211,238,.10)}.pe-asset img{width:100%;height:78px;border-radius:12px;object-fit:cover;background:rgba(255,255,255,.08)}.pe-asset small{white-space:nowrap;overflow:hidden;text-overflow:ellipsis;color:rgba(255,255,255,.66)}.pe-checks{display:flex;gap:10px;flex-wrap:wrap}.pe-checks label{display:inline-flex;align-items:center;gap:6px;border:1px solid rgba(255,255,255,.10);border-radius:999px;padding:8px 10px;background:rgba(255,255,255,.045);font-size:12px}.pe-note{border:1px dashed rgba(34,211,238,.24);border-radius:16px;background:rgba(34,211,238,.07);padding:12px;color:#cffafe;font-size:12px;line-height:1.55}.pe-card.dark{--card-bg:#0f172a;--card-fg:#fff}.pe-card.dark .pe-card-message{color:rgba(255,255,255,.76)}.pe-card.dark .pe-card-brand span{color:rgba(255,255,255,.58)}@media(max-width:980px){.pe-grid{grid-template-columns:1fr}.pe-preview-wrap{position:relative;top:auto}.pe-row{grid-template-columns:1fr}.pe-panel-body{max-height:none}}@media(max-width:620px){.pe-shell{padding:10px}.pe-banner{height:160px}.pe-tabs{top:4px}.pe-top{align-items:flex-start;flex-direction:column}}
    </style>

<style id="push-logo-transparent-6j">
    .push-preview-logo-wrap,
    .push-message-logo-wrap,
    .push-card-logo-wrap,
    .push-app-logo-wrap,
    .push-notification-logo-wrap {
        background: transparent !important;
        background-image: none !important;
        box-shadow: none !important;
    }

    .push-preview-logo-wrap img,
    .push-message-logo-wrap img,
    .push-card-logo-wrap img,
    .push-app-logo-wrap img,
    .push-notification-logo-wrap img {
        background: transparent !important;
        object-fit: contain !important;
    }

    .push-preview-card [data-push-logo-wrap],
    .push-phone-preview [data-push-logo-wrap],
    .push-inapp-preview [data-push-logo-wrap] {
        background: transparent !important;
        background-image: none !important;
        box-shadow: none !important;
    }

    .push-preview-card [data-push-logo-wrap] img,
    .push-phone-preview [data-push-logo-wrap] img,
    .push-inapp-preview [data-push-logo-wrap] img {
        background: transparent !important;
        object-fit: contain !important;
    }
</style>

</head>
<body>
<div class="pe-shell" id="pushEditor">
    <div class="pe-top"><div><div class="pe-kicker">{{ $isView ? 'Notification Archive' : 'Beginner Push Designer' }}</div><div class="pe-title">{{ $pageTitle }}</div><div class="pe-sub">{{ $isView ? 'Sent notifications are read-only archive records.' : 'Build a focused app message with image, text, action button, audience and delivery rules.' }}</div></div><a class="pe-back" href="{{ $returnUrl }}">← Back</a></div>
    @if (session('status'))<div class="pe-note" style="margin-top:12px;">{{ session('status') }}</div>@endif
    <form method="POST" action="{{ $saveUrl }}" enctype="multipart/form-data">@csrf
        <input type="hidden" id="appLogoUrl" name="app_logo_url" value="{{ $appLogo }}"> @if ($methodField) @method($methodField) @endif
        <input type="hidden" name="campaign_type" value="{{ $campaignType }}"><input type="hidden" name="media_asset_id" id="mediaAssetId" value="{{ $selectedMediaId }}">
        <div class="pe-tabs" role="tablist"><button type="button" class="pe-tab active" data-tab="message">Message</button><button type="button" class="pe-tab" data-tab="visual">Image / Visual</button><button type="button" class="pe-tab" data-tab="audience">Audience</button><button type="button" class="pe-tab" data-tab="delivery">Delivery</button><button type="button" class="pe-tab" data-tab="schedule">Schedule / Repeat</button></div>
        <div class="pe-grid"><section class="pe-panel"><div class="pe-panel-head"><h2 class="pe-panel-title">Notification Controls</h2></div><div class="pe-panel-body">
            <div class="pe-tab-panel active" data-panel="message"><div class="pe-row"><div class="pe-field"><label>Notification Title</label><input {{ $isView ? 'readonly' : '' }} id="titleInput" name="title" value="{{ $titleValue }}"></div><div class="pe-field"><label>Action Type</label><select {{ $isView ? 'disabled' : '' }} id="actionTypeInput" name="action_type"><option value="internal" @selected($actionType !== 'external')>Open inside app</option><option value="external" @selected($actionType === 'external')>Open external link</option></select><div class="pe-help">Use external for Play Store, website, YouTube, donation or registration links.</div></div></div><div class="pe-row"><div class="pe-field" id="internalLinkField"><label>Internal App Link</label><select {{ $isView ? 'disabled' : '' }} id="internalQuickLink" name="deep_link_url"><option value="">Select destination...</option>@foreach($internalLinks as $route => $label)<option value="{{ $route }}" @selected($deepLinkValue === $route)>{{ $label }} — {{ $route }}</option>@endforeach<option value="__custom" @selected(! array_key_exists($deepLinkValue, $internalLinks) && filled($deepLinkValue))>Custom / manual link</option></select><input {{ $isView ? 'readonly' : '' }} id="linkInput" name="custom_deep_link_url" value="{{ array_key_exists($deepLinkValue, $internalLinks) ? '' : $deepLinkValue }}" placeholder="Custom link only, e.g. /tab/my_section"><div class="pe-help">Select a ready app destination. Use the custom field only for advanced/dynamic links.</div></div><div class="pe-field" id="externalLinkField"><label>External Link</label><input {{ $isView ? 'readonly' : '' }} id="externalLinkInput" name="external_url" value="{{ $externalUrlValue }}" placeholder="https://play.google.com/store/apps/details?id=..."></div></div><div class="pe-field"><label>Message Body</label><textarea {{ $isView ? 'readonly' : '' }} id="bodyInput" name="body" maxlength="600">{{ $bodyValue }}</textarea><div class="pe-help">Keep it short like a message card. It can take a few lines, but should not become a full article.</div></div><div class="pe-row"><div class="pe-field"><label>Action Button Label</label><input {{ $isView ? 'readonly' : '' }} id="actionInput" name="action_label" value="{{ $actionLabel ?: 'Open' }}" placeholder="Open, Read More, Watch Now, Update App"></div><div class="pe-field"><label>Card Style</label><select id="cardTheme"><option value="light">Light message card</option><option value="dark">Dark message card</option></select></div></div></div>
            <div class="pe-tab-panel" data-panel="visual"><div class="pe-row"><div class="pe-field"><label>Upload Image</label><input {{ $isView ? 'disabled' : '' }} type="file" name="image_upload" accept="image/*"><div class="pe-help">Use this for a fresh banner image. It will override URL/library image after saving.</div></div><div class="pe-field"><label>Paste Image URL</label><input {{ $isView ? 'readonly' : '' }} id="imageInput" name="image_url" value="{{ $imageValue }}" placeholder="https://..."></div></div><div class="pe-field"><label>Visual Media Library</label><div class="pe-asset-grid">@forelse ($mediaAssets as $asset)@php $assetUrl = (string) ($asset->url ?: ($asset->path ? Storage::disk($asset->disk ?: 'public')->url($asset->path) : '')); @endphp<button type="button" class="pe-asset {{ $selectedMediaId === (int) $asset->id ? 'active' : '' }}" data-id="{{ $asset->id }}" data-url="{{ $assetUrl }}" {{ $isView ? 'disabled' : '' }}>@if($assetUrl)<img src="{{ $assetUrl }}" alt="">@else<div style="height:78px;border-radius:12px;background:rgba(255,255,255,.08);"></div>@endif<small>{{ $asset->label ?: basename((string) $asset->path) }}</small></button>@empty<div class="pe-note">No image assets found yet. Upload an image or paste a URL.</div>@endforelse</div></div></div>
            <div class="pe-tab-panel" data-panel="audience"><div class="pe-row"><div class="pe-field"><label>Target Audience</label><select {{ $isView ? 'disabled' : '' }} id="targetTypeInput" name="target_type"><option value="all" @selected($targetType==='all')>All users of this app</option><option value="topic" @selected($targetType==='topic')>Topic / Segment</option><option value="token" @selected($targetType==='token')>Single device token</option></select></div><div class="pe-field" id="targetValueField"><label>Topic / Token Value</label><input {{ $isView ? 'readonly' : '' }} id="targetValueInput" name="target_value" value="{{ $targetValue ?: $defaultAppTopic }}" placeholder="Leave empty for app topic"></div></div><div class="pe-note">For normal use, select “All users of this app”. AppsHub saves that choice and maps it safely to <strong>{{ $defaultAppTopic }}</strong>.</div></div>
            <div class="pe-tab-panel" data-panel="delivery"><div class="pe-row"><div class="pe-field"><label>Delivery Mode</label><select {{ $isView ? 'disabled' : '' }} id="sendMode" name="send_mode"><option value="draft" @selected($sendMode==='draft')>Save as Draft</option><option value="now" @selected($sendMode==='now')>Send Now</option><option value="schedule" @selected($sendMode==='schedule')>Schedule Once</option><option value="repeat" @selected($sendMode==='repeat')>Repeat Automatically</option></select></div><div class="pe-field"><label>Start / Scheduled Time</label><input {{ $isView ? 'readonly' : '' }} type="datetime-local" name="scheduled_for" value="{{ $scheduledForValue }}"></div></div><div class="pe-row"><div class="pe-field"><label>Timezone</label><select {{ $isView ? 'disabled' : '' }} name="timezone"><option value="Africa/Lagos" @selected($timezoneValue==='Africa/Lagos')>Africa/Lagos - Nigeria / Abuja / Lagos time</option><option value="UTC" @selected($timezoneValue==='UTC')>UTC - Coordinated Universal Time</option></select></div><div class="pe-field"><label>Time Guide</label><input readonly value="Nigeria time is controlled by Africa/Lagos"></div></div><div class="pe-note">Drafts stay editable. Scheduled/repeating notifications remain editable until sent. Sent records become read-only archive messages.</div></div>
            <div class="pe-tab-panel" data-panel="schedule"><div class="pe-row"><div class="pe-field"><label>Repeat Pattern</label><select {{ $isView ? 'disabled' : '' }} id="recurrenceType" name="recurrence_type"><option value="none" @selected($recurrenceType==='none')>No Repeat</option><option value="daily" @selected($recurrenceType==='daily')>Daily</option><option value="weekly" @selected($recurrenceType==='weekly')>Weekly</option><option value="monthly" @selected($recurrenceType==='monthly')>Monthly</option></select></div><div class="pe-field"><label>Stop After How Many Sends?</label><input {{ $isView ? 'readonly' : '' }} type="number" name="max_runs" min="1" max="366" value="{{ old('max_runs', $n->max_runs) }}" placeholder="Optional"></div></div><div class="pe-note">Selected timezone: <strong>{{ $timezoneValue }}</strong>. Daily repeat saves Monday–Sunday automatically. Weekly repeat saves only the selected weekdays.</div><div class="pe-row"><div class="pe-field"><label>Repeat Hour</label><input {{ $isView ? 'readonly' : '' }} type="number" name="recurrence_hour" min="0" max="23" value="{{ $recurrenceHourValue }}" placeholder="0-23; use 0 for midnight"></div><div class="pe-field"><label>Repeat Minute</label><input {{ $isView ? 'readonly' : '' }} type="number" name="recurrence_minute" min="0" max="59" value="{{ $recurrenceMinuteValue }}" placeholder="0-59; use 0 for exact hour"></div></div><div class="pe-field"><label>Weekly Days</label><div class="pe-checks">@foreach([1=>'MON',2=>'TUE',3=>'WED',4=>'THU',5=>'FRI',6=>'SAT',7=>'SUN'] as $day=>$label)<label><input {{ $isView ? 'disabled' : '' }} type="checkbox" name="recurrence_weekdays[]" value="{{ $day }}" @checked(in_array($day, array_map('intval', (array) $weekdays), true))> {{ $label }}</label>@endforeach</div></div><div class="pe-field"><label>Monthly Day</label><input {{ $isView ? 'readonly' : '' }} type="number" min="1" max="31" name="recurrence_month_day" value="{{ old('recurrence_month_day', $n->recurrence_month_day) }}" placeholder="1-31"></div></div>
        </div>@unless($isView)<div class="pe-actions"><a class="pe-btn" href="{{ $returnUrl }}">Cancel</a><button class="pe-btn primary" type="submit">{{ $isEdit ? 'Save Notification' : 'Create Notification' }}</button></div>@else<div class="pe-actions"><a class="pe-btn" href="{{ $returnUrl }}">Close</a><a class="pe-btn primary" href="{{ route('admin.beginner.push.create') }}">Create New</a></div>@endunless</section>
        <aside class="pe-preview-wrap"><section class="pe-phone-preview"><div class="pe-help" style="font-weight:950;text-transform:uppercase;letter-spacing:.08em;margin-bottom:9px;">Android / iOS Push Banner Preview</div><div class="pe-push-tiny"><div data-push-logo-wrap class="pe-logo app-logo-slot">@if($appLogo)<img src="{{ $appLogo }}" alt="{{ $app->name }} logo">@else{{ $appInitial }}@endif</div><div><strong id="tinyTitle"></strong><span id="tinyBody"></span><span>Tap action: <b id="tinyLink"></b></span></div></div></section><section class="pe-card" id="messageCard"><div class="pe-banner" id="bannerBox"><span>Image Preview</span></div><div class="pe-card-body"><div class="pe-card-brand"><div data-push-logo-wrap class="pe-logo app-logo-slot">@if($appLogo)<img src="{{ $appLogo }}" alt="{{ $app->name }} logo">@else{{ $appInitial }}@endif</div><div><strong>{{ $app->name }}</strong><span>In-app notification card</span></div></div><div class="pe-card-title" id="cardTitle"></div><div class="pe-card-message" id="cardBody"></div><div class="pe-card-actions"><span class="pe-btn primary" id="cardAction"></span><span class="pe-btn">Close</span></div></div></section></aside></div>
    </form>
</div>
<script>(function(){const q=s=>document.querySelector(s);const tabs=[...document.querySelectorAll('.pe-tab')];const panels=[...document.querySelectorAll('.pe-tab-panel')];function setTab(k){tabs.forEach(t=>t.classList.toggle('active',t.dataset.tab===k));panels.forEach(p=>p.classList.toggle('active',p.dataset.panel===k));}tabs.forEach(t=>t.addEventListener('click',()=>setTab(t.dataset.tab)));const title=q('#titleInput'),body=q('#bodyInput'),link=q('#linkInput'),internalQuick=q('#internalQuickLink'),externalLink=q('#externalLinkInput'),actionType=q('#actionTypeInput'),internalField=q('#internalLinkField'),externalField=q('#externalLinkField'),targetType=q('#targetTypeInput'),targetValue=q('#targetValueInput'),targetValueField=q('#targetValueField'),image=q('#imageInput'),action=q('#actionInput'),theme=q('#cardTheme'),sendMode=q('#sendMode'),recurrence=q('#recurrenceType');const tinyTitle=q('#tinyTitle'),tinyBody=q('#tinyBody'),tinyLink=q('#tinyLink'),cardTitle=q('#cardTitle'),cardBody=q('#cardBody'),cardAction=q('#cardAction'),banner=q('#bannerBox'),card=q('#messageCard');function safe(v,f){v=(v||'').trim();return v||f;}function render(){const t=safe(title?.value,'Notification'),b=safe(body?.value,'Message preview will appear here.'),isExternal=(actionType?.value==='external'),quickVal=safe(internalQuick?.value,''),manualVal=safe(link?.value,''),internalUrl=(quickVal&&quickVal!=='__custom')?quickVal:safe(manualVal,'/'),externalUrl=safe(externalLink?.value,''),l=isExternal?safe(externalUrl,'External link not set'):internalUrl,a=safe(action?.value,(l&&l!=='/'?'Open':'View Message')),img=safe(image?.value,'');if(internalField)internalField.style.display=isExternal?'none':'grid';if(externalField)externalField.style.display=isExternal?'grid':'none';if(link&&internalQuick){link.style.display=(internalQuick.value==='__custom')?'block':'none';}if(targetValueField&&targetType){targetValueField.style.display=(targetType.value==='all')?'none':'grid';}tinyTitle.textContent=t;tinyBody.textContent=b;tinyLink.textContent=l;cardTitle.textContent=t;cardBody.textContent=b;cardAction.textContent=a;card.classList.toggle('dark',theme?.value==='dark');banner.innerHTML=img?'<img src="'+img.replace(/"/g,'&quot;')+'" alt="">':'<span>Image Preview</span>';}[title,body,link,externalLink,actionType,image,action,theme,targetType,targetValue,internalQuick].forEach(el=>el&&el.addEventListener('input',render));if(theme)theme.addEventListener('change',render);if(actionType)actionType.addEventListener('change',render);if(internalQuick)internalQuick.addEventListener('change',function(){if(link){link.style.display=(this.value==='__custom')?'block':'none';if(this.value!=='__custom'){link.value='';}}render();});if(targetType)targetType.addEventListener('change',function(){if(targetValueField)targetValueField.style.display=(this.value==='all')?'none':'grid';render();});document.querySelectorAll('.pe-asset').forEach(btn=>btn.addEventListener('click',function(){document.querySelectorAll('.pe-asset').forEach(b=>b.classList.remove('active'));this.classList.add('active');q('#mediaAssetId').value=this.dataset.id||'';if(image)image.value=this.dataset.url||'';render();}));if(recurrence){recurrence.addEventListener('change',function(){if(sendMode && this.value && this.value!=='none'){sendMode.value='repeat';}});}
function normalizeDeliveryBeforeSubmit(){
  if(!sendMode) return true;
  const scheduled = document.querySelector('input[name="scheduled_for"]');
  if(recurrence && recurrence.value && recurrence.value !== 'none'){
    sendMode.value = 'repeat';
  } else if(sendMode.value === 'draft' && scheduled && scheduled.value){
    sendMode.value = 'schedule';
  }
  return true;
}
const pushForm = document.querySelector('form.pe-editor');
if(pushForm){ pushForm.addEventListener('submit', normalizeDeliveryBeforeSubmit); }
render();})();</script>
</body>
</html>
