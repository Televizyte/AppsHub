<?php

namespace App\Http\Middleware;

use App\Models\App;
use Closure;
use Illuminate\Http\Request;

class AppSanctumScope
{
    /**
     * Ensures the authenticated Sanctum token is scoped to this app.
     *
     * Accepts either:
     * - ability "app:{appId}"
     * - ability "app_slug:{slug}"
     *
     * Requires auth:sanctum to run before this middleware.
     */
    public function handle(Request $request, Closure $next)
    {
        $appSlug = (string) $request->route('appSlug');

        if ($appSlug === '') {
            return response()->json([
                'ok' => false,
                'error' => 'APP_SLUG_MISSING',
                'message' => 'Missing appSlug in route.',
            ], 400);
        }

        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'ok' => false,
                'error' => 'UNAUTHENTICATED',
                'message' => 'Missing or invalid bearer token.',
            ], 401);
        }

        $token = $user->currentAccessToken();
        if (! $token) {
            return response()->json([
                'ok' => false,
                'error' => 'TOKEN_MISSING',
                'message' => 'No active access token found.',
            ], 401);
        }

        // Resolve app (reuse if already set by another middleware)
        $app = $request->attributes->get('current_app');
        if (! $app instanceof App) {
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

        $request->attributes->set('current_app', $app);

        $needA = 'app:' . (int) $app->id;
        $needB = 'app_slug:' . (string) $app->slug;

        $abilities = is_array($token->abilities) ? $token->abilities : [];

        $ok = in_array($needA, $abilities, true) || in_array($needB, $abilities, true);

        if (! $ok) {
            return response()->json([
                'ok' => false,
                'error' => 'TOKEN_SCOPE_INVALID',
                'message' => 'This token is not allowed for the requested app.',
                'required_any_of' => [$needA, $needB],
            ], 403);
        }

        return $next($request);
    }
}
