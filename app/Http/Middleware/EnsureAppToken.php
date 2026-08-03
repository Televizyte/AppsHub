<?php

namespace App\Http\Middleware;

use App\Models\App;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAppToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $appSlug = (string) $request->route('appSlug');

        if ($appSlug === '') {
            return response()->json([
                'ok' => false,
                'error' => 'APP_SLUG_MISSING',
                'message' => 'Missing app slug.',
            ], 400);
        }

        /** @var \App\Models\App|null $app */
        $app = App::query()
            ->where('slug', $appSlug)
            ->first();

        if (! $app) {
            return response()->json([
                'ok' => false,
                'error' => 'APP_NOT_FOUND',
                'message' => 'App not found.',
            ], 404);
        }

        if (! $app->is_active) {
            return response()->json([
                'ok' => false,
                'error' => 'APP_INACTIVE',
                'message' => 'App is inactive.',
            ], 403);
        }

        $provided = (string) $request->header('X-APP-TOKEN', '');

        if ($provided === '') {
            return response()->json([
                'ok' => false,
                'error' => 'APP_TOKEN_MISSING',
                'message' => 'Missing X-APP-TOKEN header.',
            ], 401);
        }

        if (! hash_equals((string) $app->api_token, $provided)) {
            return response()->json([
                'ok' => false,
                'error' => 'APP_TOKEN_INVALID',
                'message' => 'Invalid app token.',
            ], 401);
        }

        // Make app available everywhere downstream
        $request->attributes->set('app', $app);
        $request->attributes->set('app_id', (int) $app->id);
        $request->attributes->set('current_app', $app);

        return $next($request);
    }
}
