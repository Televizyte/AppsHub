<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\DeviceToken;
use Illuminate\Http\Request;

class DeviceTokenController extends Controller
{
    public function upsert(Request $request, string $appSlug)
    {
        $currentApp = $request->attributes->get('current_app');

        if ($currentApp instanceof App) {
            $app = $currentApp;
        } else {
            $app = App::query()
                ->where('slug', $appSlug)
                ->where('is_active', true)
                ->first();
        }

        if (! $app) {
            return response()->json([
                'ok' => false,
                'error' => 'APP_NOT_FOUND',
                'message' => 'App not found or inactive.',
            ], 404);
        }

        $data = $request->validate([
            'token' => ['required', 'string', 'min:10', 'max:500'],
            'platform' => ['nullable', 'string', 'max:30'],
            'is_active' => ['nullable', 'boolean'],
            'meta' => ['nullable', 'array'],
        ]);

        // This route intentionally supports guests. When a valid Sanctum
        // bearer token is present, bind the device to that authenticated user.
        // Without a bearer token, keep the device active as an app guest.
        // Never accept a public user_id supplied by the client.
        $authenticatedUser = auth('sanctum')->user();

        $row = DeviceToken::query()->updateOrCreate(
            [
                'app_id' => (int) $app->id,
                'token' => $data['token'],
            ],
            [
                'platform' => $data['platform'] ?? null,
                'user_id' => $authenticatedUser?->id,
                'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : true,
                'last_seen_at' => now(),
                'meta_json' => $data['meta'] ?? null,
            ]
        );

        return response()->json([
            'ok' => true,
            'app' => [
                'id' => (int) $app->id,
                'name' => (string) $app->name,
                'slug' => (string) $app->slug,
            ],
            'item' => [
                'id' => (int) $row->id,
                'platform' => $row->platform,
                'user_id' => $row->user_id ? (int) $row->user_id : null,
                'is_active' => (bool) $row->is_active,
                'last_seen_at' => optional($row->last_seen_at)->toISOString(),
            ],
        ]);
    }

    public function deactivate(Request $request, string $appSlug)
    {
        $currentApp = $request->attributes->get('current_app');

        if ($currentApp instanceof App) {
            $app = $currentApp;
        } else {
            $app = App::query()
                ->where('slug', $appSlug)
                ->where('is_active', true)
                ->first();
        }

        if (! $app) {
            return response()->json([
                'ok' => false,
                'error' => 'APP_NOT_FOUND',
                'message' => 'App not found or inactive.',
            ], 404);
        }

        $data = $request->validate([
            'token' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        DeviceToken::query()
            ->where('app_id', (int) $app->id)
            ->where('token', $data['token'])
            ->update([
                'is_active' => false,
                'last_seen_at' => now(),
            ]);

        return response()->json([
            'ok' => true,
            'app' => [
                'id' => (int) $app->id,
                'name' => (string) $app->name,
                'slug' => (string) $app->slug,
            ],
            'message' => 'Token deactivated.',
        ]);
    }
}
