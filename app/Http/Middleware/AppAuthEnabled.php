<?php

namespace App\Http\Middleware;

use App\Models\App;
use Closure;
use Illuminate\Http\Request;

class AppAuthEnabled
{
    /**
     * Blocks auth endpoints (or any route you apply this middleware to)
     * if the app has enable_auth=false in branding_json flags/features.
     *
     * Looks at:
     * - branding_json.flags.enable_auth
     * - branding_json.features.enable_auth
     *
     * Default: true (enabled).
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

        // Reuse any already-resolved app (if another middleware sets it).
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

        // Cache for downstream middleware/controllers (safe, request-only)
        $request->attributes->set('current_app', $app);

        $branding = is_array($app->branding_json) ? $app->branding_json : [];

        $flags = data_get($branding, 'flags', []);
        $features = data_get($branding, 'features', []);

        $enableAuth =
            (is_array($flags) && array_key_exists('enable_auth', $flags)) ? (bool) data_get($flags, 'enable_auth') :
            ((is_array($features) && array_key_exists('enable_auth', $features)) ? (bool) data_get($features, 'enable_auth') : true);

        if (! $enableAuth) {
            return response()->json([
                'ok' => false,
                'error' => 'AUTH_DISABLED',
                'message' => 'Authentication is disabled for this app.',
                'app' => [
                    'id' => (int) $app->id,
                    'slug' => (string) $app->slug,
                    'name' => (string) $app->name,
                ],
            ], 403);
        }

        return $next($request);
    }
}
