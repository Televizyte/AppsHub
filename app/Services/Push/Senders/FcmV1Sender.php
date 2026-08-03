<?php

namespace App\Services\Push\Senders;

use App\Models\PushNotification;
use App\Support\AppBranding;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class FcmV1Sender
{
    public function send(PushNotification $notification): array
    {
        $credentials = $this->loadCredentials();

        if (! $credentials['ok']) {
            return [
                'ok' => false,
                'provider' => 'fcm_v1',
                'message' => $credentials['message'],
            ];
        }

        $projectId = (string) (config('push.fcm_v1_project_id') ?: ($credentials['data']['project_id'] ?? ''));

        if ($projectId === '') {
            return [
                'ok' => false,
                'provider' => 'fcm_v1',
                'message' => 'Firebase project ID is missing. Set FIREBASE_PROJECT_ID or use a service account JSON with project_id.',
            ];
        }

        $token = $this->accessToken($credentials['data']);

        if (! $token['ok']) {
            return [
                'ok' => false,
                'provider' => 'fcm_v1',
                'message' => $token['message'],
                'response' => $token['response'] ?? null,
            ];
        }

        $message = $this->buildMessage($notification);
        $endpoint = 'https://fcm.googleapis.com/v1/projects/' . rawurlencode($projectId) . '/messages:send';

        $response = Http::withToken($token['access_token'])
            ->acceptJson()
            ->asJson()
            ->post($endpoint, [
                'message' => $message,
            ]);

        if (! $response->successful()) {
            return [
                'ok' => false,
                'provider' => 'fcm_v1',
                'status' => $response->status(),
                'body' => $response->body(),
            ];
        }

        return [
            'ok' => true,
            'provider' => 'fcm_v1',
            'status' => $response->status(),
            'response' => $response->json(),
        ];
    }

    protected function buildMessage(PushNotification $notification): array
    {
        $app = $notification->app;
        $appSlug = (string) ($app?->slug ?: 'unknown');
        $appLogoUrl = (string) (data_get($notification->meta_json ?? [], 'app_logo_url')
            ?: data_get($notification->meta_json ?? [], 'logo_url')
            ?: AppBranding::logoUrl($app));

        $targetType = (string) ($notification->target_type ?: 'topic');
        $targetValue = trim((string) ($notification->target_value ?: ''));

        $topicPrefix = (string) config('push.topic_prefix', 'app-');
        $defaultTopic = $topicPrefix . $appSlug;

        $message = [
            'notification' => [
                'title' => (string) $notification->title,
                'body' => (string) $notification->body,
            ],
            'data' => $this->stringData(array_merge([
                'push_id' => (string) $notification->id,
                'app_id' => (string) $notification->app_id,
                'app_slug' => $appSlug,
                'target_type' => $targetType,
                'target_value' => $targetValue,
                'deep_link_url' => (string) ($notification->deep_link_url ?: ''),
                'action_type' => (string) data_get($notification->meta_json ?? [], 'action_type', 'internal'),
                'external_url' => (string) data_get($notification->meta_json ?? [], 'external_url', ''),
                'action_url' => (string) data_get($notification->meta_json ?? [], 'action_url', ($notification->deep_link_url ?: '')),
                'click_action' => (string) ($notification->click_action ?: config('push.default_click_action', 'FLUTTER_NOTIFICATION_CLICK')),
                'title' => (string) $notification->title,
                'body' => (string) $notification->body,
                'image_url' => (string) ($notification->image_url ?: ''),
                'app_logo_url' => $appLogoUrl,
                'logo_url' => $appLogoUrl,
                'action_label' => (string) data_get($notification->meta_json ?? [], 'action_label', filled($notification->deep_link_url) ? 'Open' : 'View Message'),
                'card_type' => 'in_app_notification_card',
            ], (array) ($notification->meta_json ?? []))),
            'android' => [
                'priority' => 'HIGH',
                'notification' => [
                    'channel_id' => (string) config('push.android_channel_id', 'high_importance_channel'),
                    'click_action' => (string) ($notification->click_action ?: config('push.default_click_action', 'FLUTTER_NOTIFICATION_CLICK')),
                    'default_sound' => true,
                    'default_vibrate_timings' => true,
                ],
            ],
        ];

        if ((string) $notification->image_url !== '') {
            $message['notification']['image'] = (string) $notification->image_url;
            $message['android']['notification']['image'] = (string) $notification->image_url;
        }

        if ($targetType === 'token' && $targetValue !== '') {
            $message['token'] = $targetValue;
            return $message;
        }

        if ($targetType === 'topic' && $targetValue !== '') {
            $message['topic'] = $this->normalizeTopic($targetValue);
            return $message;
        }

        // "all" means all devices subscribed to the selected app topic.
        $message['topic'] = $this->normalizeTopic($defaultTopic);

        return $message;
    }

    protected function accessToken(array $credentials): array
    {
        $clientEmail = (string) ($credentials['client_email'] ?? '');
        $privateKey = (string) ($credentials['private_key'] ?? '');

        if ($clientEmail === '' || $privateKey === '') {
            return [
                'ok' => false,
                'message' => 'Firebase service account is missing client_email or private_key.',
            ];
        }

        $cacheKey = 'push:fcm_v1_access_token:' . sha1($clientEmail);

        return Cache::remember($cacheKey, now()->addMinutes(50), function () use ($clientEmail, $privateKey): array {
            $now = time();

            $header = [
                'alg' => 'RS256',
                'typ' => 'JWT',
            ];

            $claim = [
                'iss' => $clientEmail,
                'scope' => 'https://www.googleapis.com/auth/firebase.messaging',
                'aud' => 'https://oauth2.googleapis.com/token',
                'iat' => $now,
                'exp' => $now + 3600,
            ];

            $unsignedJwt = $this->base64Url(json_encode($header)) . '.' . $this->base64Url(json_encode($claim));
            $signature = '';

            $signed = openssl_sign($unsignedJwt, $signature, $privateKey, OPENSSL_ALGO_SHA256);

            if (! $signed) {
                return [
                    'ok' => false,
                    'message' => 'Unable to sign Firebase OAuth JWT. Confirm the service account private key is valid.',
                ];
            }

            $jwt = $unsignedJwt . '.' . $this->base64Url($signature);

            $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);

            if (! $response->successful()) {
                return [
                    'ok' => false,
                    'message' => 'Unable to get Firebase OAuth access token.',
                    'response' => $response->json() ?: $response->body(),
                ];
            }

            $json = $response->json();

            return [
                'ok' => true,
                'access_token' => (string) ($json['access_token'] ?? ''),
                'expires_in' => (int) ($json['expires_in'] ?? 3600),
            ];
        });
    }

    protected function loadCredentials(): array
    {
        $raw = trim((string) config('push.fcm_v1_service_account_json'));

        if ($raw !== '') {
            $decodedRaw = base64_decode($raw, true);
            $json = $decodedRaw !== false && Str::startsWith(trim($decodedRaw), '{') ? $decodedRaw : $raw;
            $data = json_decode($json, true);

            if (is_array($data)) {
                return ['ok' => true, 'data' => $data];
            }

            return [
                'ok' => false,
                'message' => 'FIREBASE_CREDENTIALS / FCM_V1_SERVICE_ACCOUNT_JSON is set but is not valid JSON or base64 JSON.',
            ];
        }

        $path = trim((string) config('push.fcm_v1_service_account_path'));

        if ($path === '') {
            return [
                'ok' => false,
                'message' => 'Firebase service account is missing. Set FIREBASE_CREDENTIALS to JSON/base64 JSON or FIREBASE_CREDENTIALS_FILE to a server file path.',
            ];
        }

        $resolvedPath = Str::startsWith($path, '/') ? $path : base_path($path);

        if (! is_file($resolvedPath)) {
            return [
                'ok' => false,
                'message' => 'Firebase service account file was not found at: ' . $resolvedPath,
            ];
        }

        $data = json_decode((string) file_get_contents($resolvedPath), true);

        if (! is_array($data)) {
            return [
                'ok' => false,
                'message' => 'Firebase service account file exists but does not contain valid JSON.',
            ];
        }

        return ['ok' => true, 'data' => $data];
    }

    protected function normalizeTopic(string $topic): string
    {
        $topic = trim($topic);
        $topic = preg_replace('#^/topics/#', '', $topic) ?: $topic;
        $topic = str_replace(' ', '-', $topic);

        return $topic;
    }

    protected function stringData(array $data): array
    {
        $clean = [];

        foreach ($data as $key => $value) {
            if (is_null($value)) {
                $clean[(string) $key] = '';
                continue;
            }

            if (is_bool($value)) {
                $clean[(string) $key] = $value ? '1' : '0';
                continue;
            }

            if (is_scalar($value)) {
                $clean[(string) $key] = (string) $value;
                continue;
            }

            $clean[(string) $key] = json_encode($value);
        }

        return $clean;
    }

    protected function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
