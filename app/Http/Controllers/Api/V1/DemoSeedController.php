<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Support\DemoSeed\DunamisTvDemoSeeder;
use Illuminate\Http\Request;

class DemoSeedController extends Controller
{
    public function seed(Request $request, string $appSlug)
    {
        // Basic protection: require token
        $token = (string) ($request->header('X-Seed-Token') ?? '');
        $envToken = (string) (env('APP_SEED_TOKEN') ?? '');

        if ($envToken === '' || !hash_equals($envToken, $token)) {
            return response()->json([
                'ok' => false,
                'error' => 'UNAUTHORIZED',
                'message' => 'Missing or invalid X-Seed-Token.',
            ], 401);
        }

        $app = App::query()
            ->where('slug', $appSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $res = DunamisTvDemoSeeder::run((int) $app->id);

        return response()->json($res);
    }
}
