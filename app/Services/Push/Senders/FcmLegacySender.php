<?php

namespace App\Services\Push\Senders;

use App\Models\PushNotification;
use App\Support\AppBranding;
use Illuminate\Support\Facades\Http;

class FcmLegacySender
{
    public function send(PushNotification $notification): array
    {
        $key = (string) config('push.fcm_legacy_server_key');

        if ($key === '') {
            return [
                'ok' => false,
                'provider' => 'fcm_legacy',
                'message' => 'FCM legacy server key is missing. Set FCM_LEGACY_SERVER_KEY in .env or switch PUSH_DRIVER=log.',
            ];
        }

        $app = $notification->app;
        $appSlug = $app?->slug ?: 'unknown';
        $appLogoUrl = (string) (data_get($notification->meta_json ?? [], 'app_logo_url')
            ?: data_get($notification->meta_json ?? [], 'logo_url')
            ?: AppBranding::logoUrl($app));

        $targetType  = (string) ($notification->target_type ?: 'topic');
        $targetValue = (string) ($notification->target_value ?: '');

        if ($targetType === 'topic' && trim($targetValue) === '') {
            $prefix = (string) config('push.topic_prefix', 'app-');
            $targetValue = $prefix . $appSlug;
        }

        $clickAction = (string) ($notification->click_action ?: config('push.default_click_action', 'FLUTTER_NOTIFICATION_CLICK'));
        $deepLink = (string) ($notification->deep_link_url ?: '');

        $payload = [
            'priority' => 'high',
            'notification' => [
                'title' => (string) $notification->title,
                'body'  => (string) $notification->body,
                'click_action' => $clickAction,
            ],
            'data' => array_merge([
                'push_id' => (string) $notification->id,
                'app_id' => (string) $notification->app_id,
                'app_slug' => (string) $appSlug,
                'target_type' => (string) $targetType,
                'target_value' => (string) $targetValue,
                'deep_link_url' => $deepLink,
                'action_type' => (string) data_get($notification->meta_json ?? [], 'action_type', 'internal'),
                'external_url' => (string) data_get($notification->meta_json ?? [], 'external_url', ''),
                'action_url' => (string) data_get($notification->meta_json ?? [], 'action_url', $deepLink),
                'click_action' => $clickAction,
                'title' => (string) $notification->title,
                'body' => (string) $notification->body,
                'image_url' => (string) ($notification->image_url ?: ''),
                'app_logo_url' => $appLogoUrl,
                'logo_url' => $appLogoUrl,
                'action_label' => (string) data_get($notification->meta_json ?? [], 'action_label', filled($deepLink) ? 'Open' : 'View Message'),
                'card_type' => 'in_app_notification_card',
            ], (array) ($notification->meta_json ?? [])),
        ];

        if ((string) $notification->image_url !== '') {
            $payload['notification']['image'] = (string) $notification->image_url;
        }

        if ($targetType === 'token') {
            $payload['to'] = $targetValue;
        } elseif ($targetType === 'topic') {
            $payload['to'] = '/topics/' . $targetValue;
        } else {
            $prefix = (string) config('push.topic_prefix', 'app-');
            $payload['to'] = '/topics/' . ($prefix . $appSlug);
        }

        $res = Http::withHeaders([
            'Authorization' => 'key=' . $key,
            'Content-Type'  => 'application/json',
        ])->post('https://fcm.googleapis.com/fcm/send', $payload);

        if (!$res->successful()) {
            return [
                'ok' => false,
                'provider' => 'fcm_legacy',
                'status' => $res->status(),
                'body' => $res->body(),
            ];
        }

        return [
            'ok' => true,
            'provider' => 'fcm_legacy',
            'status' => $res->status(),
            'response' => $res->json(),
        ];
    }
}
