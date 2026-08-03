<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\SendPushNotificationJob;
use App\Models\App;
use App\Models\MediaAsset;
use App\Models\PushNotification;
use App\Support\ActiveApp;
use App\Support\Scheduling\AdminScheduleTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BeginnerPushNotificationController extends Controller
{

    private function pushCenterReturnUrl(?PushNotification $notification = null, ?string $fallback = null): string
    {
        if ($fallback) {
            return $fallback;
        }

        $status = (string) ($notification?->status ?? 'draft');
        $recurrence = (string) ($notification?->recurrence_type ?? 'none');

        if ($status === 'sent') {
            return '/admin/push-center?tab=sent';
        }

        if ($status === 'scheduled' || $recurrence !== 'none') {
            return '/admin/push-center?tab=schedule';
        }

        return '/admin/push-center?tab=drafts';
    }

    private function cleanDuplicateTitle(?string $title): string
    {
        $value = trim((string) $title);
        $value = preg_replace('/^(copy\s+of\s+)+/i', '', $value) ?: $value;
        return trim($value) !== '' ? trim($value) : 'New Push Notification';
    }

    private function makeSendCopy(PushNotification $source, string $reason = 'send_again'): PushNotification
    {
        $copy = $source->replicate();
        $copy->title = $this->cleanDuplicateTitle($source->title);
        $copy->status = 'queued';
        $copy->scheduled_for = now();
        $copy->recurrence_type = 'none';
        $copy->recurrence_weekdays = null;
        $copy->recurrence_month_day = null;
        $copy->recurrence_hour = null;
        $copy->recurrence_minute = null;
        $copy->ends_at = null;
        $copy->max_runs = null;
        $copy->runs_count = 0;
        $copy->meta_json = array_merge((array) ($source->meta_json ?? []), [
            'created_from' => 'beginner_push_center',
            'send_copy_reason' => $reason,
            'source_push_notification_id' => $source->id,
            'payload_version' => '6o',
        ]);
        $copy->save();

        return $copy;
    }


    public function create(Request $request): View
    {
        $appId = $this->activeAppId();
        $app = App::query()->findOrFail($appId);

        $notification = new PushNotification([
            'app_id' => $appId,
            'title' => 'New Update from ' . $app->name,
            'body' => 'Open the app to see the latest update prepared for you.',
            'deep_link_url' => '/',
            'target_type' => 'all',
            'target_value' => (string) config('push.topic_prefix', 'app-') . $app->slug,
            'status' => 'draft',
            'recurrence_type' => 'none',
            'recurrence_weekdays' => [],
            'timezone' => (string) (config('push.default_timezone') ?: config('app.timezone') ?: 'Africa/Lagos'),
            'meta_json' => ['campaign_type' => (string) $request->query('type', 'general'), 'action_label' => 'Open', 'action_type' => 'internal', 'external_url' => '', 'action_url' => '/'],
        ]);

        return $this->editorView($request, $app, $notification, 'create', false);
    }

    public function edit(Request $request, PushNotification $pushNotification)
    {
        $this->authorizeApp($pushNotification);

        if (($pushNotification->status ?? '') === 'sent') {
            return redirect()->route('admin.beginner.push.view', ['pushNotification' => $pushNotification->id])
                ->with('status', 'Sent notifications are archive records. Duplicate or send again instead of editing the original.');
        }

        $app = App::query()->findOrFail($this->activeAppId());

        return $this->editorView($request, $app, $pushNotification, 'edit', false);
    }

    public function show(Request $request, PushNotification $pushNotification): View
    {
        $this->authorizeApp($pushNotification);
        $app = App::query()->findOrFail($this->activeAppId());

        return $this->editorView($request, $app, $pushNotification, 'view', true);
    }

    private function resolveAppLogo(App $app): string
    {
        $branding = is_array($app->branding_json ?? null) ? $app->branding_json : [];
        $settings = is_array($app->settings_json ?? null) ? $app->settings_json : [];

        $logo = (string) (
            data_get($app, 'logo_url')
            ?: data_get($app, 'app_logo_url')
            ?: data_get($app, 'logo')
            ?: data_get($app, 'app_logo')
            ?: data_get($app, 'icon_url')
            ?: data_get($app, 'icon')
            ?: data_get($branding, 'logo_url')
            ?: data_get($branding, 'app_logo_url')
            ?: data_get($branding, 'app_logo')
            ?: data_get($branding, 'logo')
            ?: data_get($branding, 'icon_url')
            ?: data_get($branding, 'icon')
            ?: data_get($branding, 'assets.logo')
            ?: data_get($branding, 'assets.icon')
            ?: data_get($settings, 'logo_url')
            ?: data_get($settings, 'app_logo_url')
            ?: data_get($settings, 'app_logo')
            ?: data_get($settings, 'logo')
            ?: data_get($settings, 'icon_url')
            ?: data_get($settings, 'icon')
            ?: ''
        );

        if ($logo !== '' && ! (str_starts_with($logo, 'http://') || str_starts_with($logo, 'https://') || str_starts_with($logo, '/'))) {
            $logo = Storage::url($logo);
        }

        if ($logo !== '' && str_starts_with($logo, '/')) {
            $logo = url($logo);
        }

        return $logo;
    }

    private function editorView(Request $request, App $app, PushNotification $notification, string $mode, bool $readOnly): View
    {
        $appLogo = $this->resolveAppLogo($app);
        $meta = is_array($notification->meta_json ?? null) ? $notification->meta_json : [];

        return view('admin.beginner.push.editor', [
            'mode' => $mode,
            'readOnly' => $readOnly,
            'app' => $app,
            'appLogo' => $appLogo,
            'notification' => $notification,
            'meta' => $meta,
            'mediaAssets' => $this->pushMediaAssets((int) $app->id),
            'saveUrl' => $mode === 'create'
                ? route('admin.beginner.push.store')
                : route('admin.beginner.push.update', ['pushNotification' => $notification->id]),
            'methodField' => $mode === 'create' ? null : 'PATCH',
            'returnUrl' => '/admin/push-center?tab=drafts',
            'internalLinks' => $this->internalPushLinks(),
        ]);
    }

    private function internalPushLinks(): array
    {
        return [
            '/' => 'Home',
            '/watch' => 'Watch Tab',
            '/live' => 'Live TV / Live Options',
            '/watch/channels' => 'Other Channels',
            '/watch/videos' => 'Videos / Messages',
            '/short-videos' => 'Short Videos',
            '/inspire' => 'Inspire Tab',
            '/sod' => 'Seeds of Destiny Hub',
            '/sod/keypoints' => 'SOD Key Points',
            '/sod/quotes' => 'SOD Quotes',
            '/highlights' => 'Message Highlights',
            '/articles' => 'Inside Dunamis / Articles',
            '/wordification' => 'Wordification',
            '/motivation' => 'Motivation',
            '/daily-scripture' => 'Daily Scripture',
            '/explore' => 'Explore Tab',
            '/tools' => 'Quick Tools Hub',
            '/tools/quote' => 'Quote Creator',
            '/tools/quote/library' => 'Quote Library',
            '/tools/notes' => 'Notes',
            '/tools/notes/categories' => 'Note Categories',
            '/tools/notes/favorites' => 'Favorite Notes',
            '/tools/bible' => 'Bible',
            '/tools/bible/books' => 'Bible Books',
            '/tools/bible/search' => 'Bible Search',
            '/tools/bible/saved' => 'Saved Bible Verses',
            '/tools/books' => 'Book Reader / Library',
            '/games' => 'Games Hub',
            '/games/dominion-match' => 'Dominion Match Game',
            '/games/race-of-faith' => 'Race of Faith Game',
            '/games/dominion-growth' => 'Dominion Builder / Growth Game',
            '/quiz/bible_quiz' => 'Bible Quiz',
            '/more' => 'More Tab',
            '/notifications' => 'Notifications',
            '/account' => 'Account',
            '/saved' => 'Saved',
            '/downloads' => 'Downloads',
            '/settings' => 'Settings',
            '/rate-app' => 'Rate App',
        ];
    }

    private function pushMediaAssets(int $appId)
    {
        return MediaAsset::query()
            ->where('is_active', true)
            ->where('type', 'image')
            ->where(function ($query) use ($appId) {
                $query->whereNull('app_id')->orWhere('app_id', $appId);
            })
            ->orderByRaw("CASE WHEN bucket = 'push' THEN 0 WHEN bucket IN ('banners','thumbnails','covers') THEN 1 ELSE 2 END")
            ->latest('updated_at')
            ->limit(80)
            ->get();
    }
    public function store(Request $request): RedirectResponse
    {
        $appId = $this->activeAppId();
        $app = App::query()->findOrFail($appId);

        $payload = $this->validatedPayload($request);
        $image = $this->resolveImage($request, $app, null);
        $notificationData = $this->buildNotificationData($payload, $appId, $app, $image);

        $notification = PushNotification::query()->create($notificationData);

        if (($payload['send_mode'] ?? 'draft') === 'now') {
            SendPushNotificationJob::dispatch((int) $notification->id);
        }

        return redirect()->route('admin.beginner.push.edit', ['pushNotification' => $notification->id])
            ->with('status', ($payload['send_mode'] ?? 'draft') === 'now'
                ? 'Notification created and queued for sending.'
                : 'Notification saved successfully.');
    }

    public function update(Request $request, PushNotification $pushNotification): RedirectResponse
    {
        $this->authorizeApp($pushNotification);

        if (($pushNotification->status ?? '') === 'sent') {
            return redirect('/admin/push-center?tab=queue&view=' . $pushNotification->id)
                ->with('status', 'Sent notifications are read-only. Duplicate it or send it again instead of editing the original archive record.');
        }

        $appId = $this->activeAppId();
        $app = App::query()->findOrFail($appId);

        $payload = $this->validatedPayload($request);
        $image = $this->resolveImage($request, $app, $pushNotification);
        $notificationData = $this->buildNotificationData($payload, $appId, $app, $image, $pushNotification);

        $pushNotification->fill($notificationData);
        $pushNotification->save();

        if (($payload['send_mode'] ?? 'draft') === 'now') {
            SendPushNotificationJob::dispatch((int) $pushNotification->id);
        }

        return redirect()->route('admin.beginner.push.edit', ['pushNotification' => $pushNotification->id])
            ->with('status', 'Notification updated successfully.');
    }

    public function sendNow(PushNotification $pushNotification): RedirectResponse
    {
        $this->authorizeApp($pushNotification);

        $status = (string) ($pushNotification->status ?? 'draft');
        $recurrence = (string) ($pushNotification->recurrence_type ?? 'none');

        if ($status === 'cancelled') {
            return redirect($this->pushCenterReturnUrl($pushNotification))
                ->with('status', 'Cancelled notifications cannot be sent. Duplicate it as a draft first.');
        }

        if ($status === 'sent') {
            $copy = $this->makeSendCopy($pushNotification, 'send_again_from_sent_archive');
            SendPushNotificationJob::dispatch((int) $copy->id);

            return redirect('/admin/push-center?tab=sent')
                ->with('status', 'A clean send-again copy was queued. The original sent archive record was not changed.');
        }

        if ($status === 'scheduled' || $recurrence !== 'none') {
            $copy = $this->makeSendCopy($pushNotification, 'send_now_from_schedule');
            SendPushNotificationJob::dispatch((int) $copy->id);

            return redirect('/admin/push-center?tab=schedule')
                ->with('status', 'A one-time copy was queued now. The original schedule remains active.');
        }

        $pushNotification->status = 'queued';
        $pushNotification->scheduled_for = now();
        $pushNotification->save();

        SendPushNotificationJob::dispatch((int) $pushNotification->id);

        return redirect('/admin/push-center?tab=drafts')
            ->with('status', 'Notification queued for sending.');
    }

    public function cancel(PushNotification $pushNotification): RedirectResponse
    {
        $this->authorizeApp($pushNotification);

        $pushNotification->status = 'cancelled';
        $pushNotification->save();

        return redirect('/admin/push-center?tab=schedule')
            ->with('status', 'Notification cancelled.');
    }

    public function duplicate(PushNotification $pushNotification): RedirectResponse
    {
        $this->authorizeApp($pushNotification);

        $copy = $pushNotification->replicate();
        $copy->title = $this->cleanDuplicateTitle($pushNotification->title);
        $copy->status = 'draft';
        $copy->scheduled_for = null;
        $copy->recurrence_type = 'none';
        $copy->recurrence_weekdays = null;
        $copy->recurrence_month_day = null;
        $copy->recurrence_hour = null;
        $copy->recurrence_minute = null;
        $copy->ends_at = null;
        $copy->max_runs = null;
        $copy->runs_count = 0;
        $copy->meta_json = array_merge((array) ($pushNotification->meta_json ?? []), [
            'duplicated_from' => $pushNotification->id,
            'created_from' => 'beginner_push_center_duplicate',
            'payload_version' => '6o',
        ]);
        $copy->save();

        return redirect()->route('admin.beginner.push.edit', ['pushNotification' => $copy->id])
            ->with('status', 'Notification duplicated as a clean draft. Adjust the title, schedule, image, or message before sending.');
    }

    public function delete(PushNotification $pushNotification): RedirectResponse
    {
        $this->authorizeApp($pushNotification);

        $returnUrl = $this->pushCenterReturnUrl($pushNotification);
        $pushNotification->delete();

        return redirect($returnUrl)
            ->with('status', 'Notification deleted.');
    }

    private function validatedPayload(Request $request): array
    {
        return $request->validate([
            'campaign_type' => ['nullable', 'string', 'max:80'],
            'title' => ['required', 'string', 'max:120'],
            'body' => ['required', 'string', 'max:600'],
            'image_url' => ['nullable', 'string', 'max:600'],
            'image_upload' => ['nullable', 'image', 'max:5120'],
            'media_asset_id' => ['nullable', 'integer', 'min:1'],
            'deep_link_url' => ['nullable', 'string', 'max:600'],
            'custom_deep_link_url' => ['nullable', 'string', 'max:600'],
            'action_type' => ['nullable', 'string', 'in:internal,external'],
            'external_url' => ['nullable', 'string', 'max:900'],
            'app_logo_url' => ['nullable', 'string', 'max:900'],
            'action_label' => ['nullable', 'string', 'max:60'],
            'target_type' => ['required', 'string', 'in:topic,all,token'],
            'target_value' => ['nullable', 'string', 'max:255'],
            'send_mode' => ['required', 'string', 'in:draft,now,schedule,repeat'],
            'timezone' => ['nullable', 'string', 'max:80'],
            'scheduled_for' => ['nullable', 'date'],
            'recurrence_type' => ['nullable', 'string', 'in:none,daily,weekly,monthly,yearly'],
            'recurrence_weekdays' => ['nullable', 'array'],
            'recurrence_weekdays.*' => ['integer', 'min:1', 'max:7'],
            'recurrence_month_day' => ['nullable', 'integer', 'min:1', 'max:31'],
            'recurrence_hour' => ['nullable', 'integer', 'min:0', 'max:23'],
            'recurrence_minute' => ['nullable', 'integer', 'min:0', 'max:59'],
            'max_runs' => ['nullable', 'integer', 'min:1', 'max:366'],
        ]);
    }

    private function buildNotificationData(array $data, int $appId, App $app, array $image, ?PushNotification $existing = null): array
    {
        $actionType = (string) ($data['action_type'] ?? data_get($existing?->meta_json ?? [], 'action_type', 'internal'));
        $customDeepLink = trim((string) ($data['custom_deep_link_url'] ?? ''));
        $rawDeepLink = (string) ($data['deep_link_url'] ?? '');
        if ($rawDeepLink === '__custom' && $customDeepLink !== '') {
            $rawDeepLink = $customDeepLink;
        }
        $externalUrl = $this->normalizeExternalUrl($data['external_url'] ?? data_get($existing?->meta_json ?? [], 'external_url', null));
        $deepLink = $this->normalizeDeepLink($rawDeepLink !== '' ? $rawDeepLink : ($existing?->deep_link_url ?? null));

        if ($actionType === 'external' && filled($externalUrl)) {
            $deepLink = null;
        } else {
            $actionType = 'internal';
            $externalUrl = null;
        }

        $actionUrl = $actionType === 'external' ? (string) $externalUrl : (string) ($deepLink ?: '/');
        $appLogoUrl = $this->resolveAppLogo($app);
        $sendMode = (string) ($data['send_mode'] ?? 'draft');
        $timezone = trim((string) ($data['timezone'] ?? ($existing?->timezone ?: (config('push.default_timezone') ?: config('app.timezone') ?: 'Africa/Lagos'))));
        if ($timezone === '') {
            $timezone = 'Africa/Lagos';
        }

        $recurrenceType = $sendMode === 'repeat'
            ? (string) ($data['recurrence_type'] ?? 'daily')
            : 'none';

        if ($recurrenceType === 'yearly') {
            $recurrenceType = 'monthly';
            $data['max_runs'] = $data['max_runs'] ?? 12;
        }

        if (! in_array($recurrenceType, ['daily', 'weekly', 'monthly'], true)) {
            $recurrenceType = $sendMode === 'repeat' ? 'daily' : 'none';
        }

        $status = match ($sendMode) {
            'now' => 'queued',
            'schedule', 'repeat' => 'scheduled',
            default => 'draft',
        };

        $scheduledFor = null;
        if ($sendMode === 'now') {
            $scheduledFor = now();
        } elseif (in_array($sendMode, ['schedule', 'repeat'], true)) {
            // datetime-local contains a wall-clock value with no timezone offset.
            // Interpret it in the selected admin timezone, then normalize to UTC
            // before Eloquent writes the timezone-naive database DATETIME value.
            $scheduledFor = ! empty($data['scheduled_for'])
                ? AdminScheduleTime::toUtc($data['scheduled_for'], $timezone)
                : \Carbon\Carbon::now($timezone)->addMinute()->utc();
        } elseif ($existing && in_array($existing->status, ['scheduled', 'queued', 'sending'], true)) {
            $scheduledFor = $existing->scheduled_for;
        }

        $targetType = (string) ($data['target_type'] ?? 'topic');
        $targetValue = trim((string) ($data['target_value'] ?? ''));

        if ($targetType === 'all') {
            $targetValue = (string) config('push.topic_prefix', 'app-') . $app->slug;
        } elseif ($targetType === 'topic' && $targetValue === '') {
            $targetValue = (string) config('push.topic_prefix', 'app-') . $app->slug;
        }

        $weekdayValues = array_values(array_map('intval', (array) ($data['recurrence_weekdays'] ?? [])));
        $weekdayValues = array_values(array_filter($weekdayValues, fn ($day) => $day >= 1 && $day <= 7));
        if ($recurrenceType === 'daily') {
            $weekdayValues = [1, 2, 3, 4, 5, 6, 7];
        } elseif ($recurrenceType === 'weekly' && empty($weekdayValues)) {
            $weekdayValues = [1];
        } elseif ($recurrenceType !== 'weekly') {
            $weekdayValues = null;
        }

        $imageUrl = $image['url'] ?: ($data['image_url'] ?? ($existing?->image_url));
        $actionLabel = (string) ($data['action_label'] ?? data_get($existing?->meta_json ?? [], 'action_label', (filled($actionUrl) ? 'Open' : 'View Message')));

        // Preserve explicit zero values. When a repeat time is genuinely omitted,
        // derive it from the selected start time rather than the time the form was saved.
        $repeatBase = $scheduledFor
            ? $scheduledFor->copy()->setTimezone($timezone)
            : \Carbon\Carbon::now($timezone);

        $recurrenceHour = null;
        $recurrenceMinute = null;
        if ($recurrenceType !== 'none') {
            $recurrenceHour = array_key_exists('recurrence_hour', $data)
                && $data['recurrence_hour'] !== null
                && $data['recurrence_hour'] !== ''
                    ? (int) $data['recurrence_hour']
                    : (int) $repeatBase->hour;

            $recurrenceMinute = array_key_exists('recurrence_minute', $data)
                && $data['recurrence_minute'] !== null
                && $data['recurrence_minute'] !== ''
                    ? (int) $data['recurrence_minute']
                    : (int) $repeatBase->minute;
        }

        return [
            'app_id' => $appId,
            'title' => trim((string) $data['title']),
            'body' => trim((string) $data['body']),
            'image_url' => $imageUrl,
            'media_asset_id' => $image['media_asset_id'] ?: ($existing?->media_asset_id),
            'deep_link_url' => $deepLink,
            'click_action' => (string) config('push.default_click_action', 'FLUTTER_NOTIFICATION_CLICK'),
            'target_type' => $targetType,
            'target_value' => $targetValue,
            'timezone' => $timezone,
            'scheduled_for' => $scheduledFor,
            'recurrence_type' => $recurrenceType,
            'recurrence_weekdays' => $weekdayValues,
            'recurrence_month_day' => $recurrenceType === 'monthly' ? (int) ($data['recurrence_month_day'] ?? \Carbon\Carbon::now($timezone)->day) : null,
            'recurrence_hour' => $recurrenceHour,
            'recurrence_minute' => $recurrenceMinute,
            'ends_at' => null,
            'max_runs' => $recurrenceType !== 'none' ? ($data['max_runs'] ?? null) : null,
            'runs_count' => $existing?->runs_count ?? 0,
            'status' => $status,
            'meta_json' => array_merge((array) ($existing?->meta_json ?? []), [
                'campaign_type' => (string) ($data['campaign_type'] ?? 'general'),
                'created_from' => 'beginner_push_center',
                'updated_from' => 'beginner_push_center',
                'app_name' => (string) ($app->name ?? ''),
                'app_slug' => (string) ($app->slug ?? ''),
                'image_url' => (string) ($imageUrl ?? ''),
                'app_logo_url' => (string) ($appLogoUrl ?? ''),
                'logo_url' => (string) ($appLogoUrl ?? ''),
                'deep_link_url' => (string) ($deepLink ?? ''),
                'action_type' => $actionType,
                'external_url' => (string) ($externalUrl ?? ''),
                'action_url' => (string) $actionUrl,
                'action_target' => $actionType,
                'action_label' => $actionLabel,
                'card_type' => 'in_app_notification_card',
                'notification_kind' => (string) ($data['campaign_type'] ?? 'general'),
                'payload_version' => '6o',
            ]),
        ];
    }

    private function activeAppId(): int
    {
        $appId = (int) (ActiveApp::ensureId() ?? 0);

        if ($appId <= 0) {
            abort(422, 'No active app selected.');
        }

        return $appId;
    }

    private function authorizeApp(PushNotification $pushNotification): void
    {
        $appId = $this->activeAppId();

        if ((int) $pushNotification->app_id !== $appId) {
            abort(403, 'This notification does not belong to the selected app.');
        }
    }

    private function normalizeDeepLink(?string $value): ?string
    {
        $deepLink = trim((string) $value);

        if ($deepLink === '') {
            return null;
        }

        if (! Str::startsWith($deepLink, ['/', 'http://', 'https://'])) {
            $deepLink = '/' . $deepLink;
        }

        return $deepLink;
    }


    private function normalizeExternalUrl(?string $value): ?string
    {
        $url = trim((string) $value);

        if ($url === '') {
            return null;
        }

        if (! Str::startsWith($url, ['http://', 'https://', 'market://', 'intent://'])) {
            $url = 'https://' . ltrim($url, '/');
        }

        return $url;
    }

    private function resolveImage(Request $request, App $app, ?PushNotification $existing = null): array
    {
        $assetId = (int) ($request->input('media_asset_id') ?: 0);
        if ($assetId > 0) {
            $asset = MediaAsset::query()
                ->where('id', $assetId)
                ->where(function ($query) use ($app) {
                    $query->whereNull('app_id')->orWhere('app_id', (int) $app->id);
                })
                ->first();

            if ($asset) {
                return [
                    'url' => (string) ($asset->url ?: (filled($asset->path) ? Storage::disk($asset->disk ?: 'public')->url($asset->path) : '')),
                    'media_asset_id' => (int) $asset->id,
                ];
            }
        }

        if (! $request->hasFile('image_upload')) {
            return ['url' => null, 'media_asset_id' => null];
        }

        $file = $request->file('image_upload');
        if (! $file || ! $file->isValid()) {
            return ['url' => null, 'media_asset_id' => null];
        }

        $directory = 'media/' . $app->slug . '/push';
        $path = $file->storePublicly($directory, 'public');
        $disk = Storage::disk('public');
        $url = $disk->url($path);
        $absolute = $disk->path($path);

        $width = null;
        $height = null;
        $imageInfo = @getimagesize($absolute);
        if (is_array($imageInfo) && isset($imageInfo[0], $imageInfo[1])) {
            $width = (int) $imageInfo[0];
            $height = (int) $imageInfo[1];
        }

        $media = MediaAsset::query()->create([
            'app_id' => (int) $app->id,
            'type' => 'image',
            'label' => basename($path),
            'bucket' => 'push',
            'disk' => 'public',
            'path' => $path,
            'url' => $url,
            'mime' => @mime_content_type($absolute) ?: $file->getMimeType(),
            'size' => @filesize($absolute) ?: $file->getSize(),
            'width' => $width,
            'height' => $height,
            'tags_json' => null,
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return ['url' => $url, 'media_asset_id' => (int) $media->id];
    }
}
