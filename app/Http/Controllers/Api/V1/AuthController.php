<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\User;
use App\Services\Accounts\DeleteAppUserAccount;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * POST /api/v1/apps/{appSlug}/auth/register
     * Headers: X-APP-TOKEN
     */
    public function register(Request $request, string $appSlug)
    {
        $app = App::query()
            ->where('slug', $appSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $data = $request->validate([
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string', 'min:8', 'max:190'],
        ]);

        $existing = User::query()->where('email', $data['email'])->first();
        if ($existing) {
            throw ValidationException::withMessages([
                'email' => ['This email is already registered. Please login instead.'],
            ]);
        }

        $user = new User();
        $user->name = $data['name'];
        $user->email = $data['email'];
        $user->password = $data['password']; // cast "hashed" handles hashing
        $user->active_app_id = (int) $app->id;
        $user->save();

        $tokenName = 'app:' . $app->slug;
        $abilities = ['app:' . (int) $app->id, 'app_slug:' . $app->slug];
        $token = $user->createToken($tokenName, $abilities)->plainTextToken;

        return response()->json([
            'ok' => true,
            'app' => [
                'id' => (int) $app->id,
                'slug' => (string) $app->slug,
                'name' => (string) $app->name,
            ],
            'user' => $this->userPayload($user),
            'token' => $token,
        ]);
    }

    /**
     * POST /api/v1/apps/{appSlug}/auth/login
     * Headers: X-APP-TOKEN
     */
    public function login(Request $request, string $appSlug)
    {
        $app = App::query()
            ->where('slug', $appSlug)
            ->where('is_active', true)
            ->firstOrFail();

        $data = $request->validate([
            'email' => ['required', 'email', 'max:190'],
            'password' => ['required', 'string', 'max:190'],
        ]);

        $user = User::query()->where('email', $data['email'])->first();
        if (! $user || ! Hash::check($data['password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Invalid credentials.'],
            ]);
        }

        $user->active_app_id = (int) $app->id;
        $user->save();

        $tokenName = 'app:' . $app->slug;
        $abilities = ['app:' . (int) $app->id, 'app_slug:' . $app->slug];
        $token = $user->createToken($tokenName, $abilities)->plainTextToken;

        return response()->json([
            'ok' => true,
            'app' => [
                'id' => (int) $app->id,
                'slug' => (string) $app->slug,
                'name' => (string) $app->name,
            ],
            'user' => $this->userPayload($user),
            'token' => $token,
        ]);
    }

    /**
     * GET /api/v1/apps/{appSlug}/auth/me
     * Headers: X-APP-TOKEN, Authorization: Bearer <token>
     */
    public function me(Request $request, string $appSlug)
    {
        $app = App::query()
            ->where('slug', $appSlug)
            ->where('is_active', true)
            ->firstOrFail();

        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'ok' => false,
                'error' => 'UNAUTHENTICATED',
                'message' => 'Missing or invalid bearer token.',
            ], 401);
        }

        if ((int) ($user->active_app_id ?? 0) !== (int) $app->id) {
            $user->active_app_id = (int) $app->id;
            $user->save();
        }

        return response()->json([
            'ok' => true,
            'app' => [
                'id' => (int) $app->id,
                'slug' => (string) $app->slug,
                'name' => (string) $app->name,
            ],
            'user' => $this->userPayload($user),
        ]);
    }

    /**
     * POST /api/v1/apps/{appSlug}/auth/logout
     * Headers: X-APP-TOKEN, Authorization: Bearer <token>
     */
    public function logout(Request $request, string $appSlug)
    {
        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'ok' => false,
                'error' => 'UNAUTHENTICATED',
                'message' => 'Missing or invalid bearer token.',
            ], 401);
        }

        try {
            $token = $user->currentAccessToken();
            if ($token) {
                $token->delete();
            }
        } catch (\Throwable $e) {
            // Keep response stable even if token deletion fails for any reason.
        }

        return response()->json([
            'ok' => true,
        ]);
    }


    /**
     * DELETE /api/v1/apps/{appSlug}/auth/account
     * Headers: X-APP-TOKEN, Authorization: Bearer <token>
     */
    public function destroyAccount(Request $request, string $appSlug, DeleteAppUserAccount $deletion)
    {
        $app = App::query()
            ->where('slug', $appSlug)
            ->where('is_active', true)
            ->firstOrFail();

        /** @var \App\Models\User|null $user */
        $user = $request->user();

        if (! $user) {
            return response()->json([
                'ok' => false,
                'error' => 'UNAUTHENTICATED',
                'message' => 'Missing or invalid bearer token.',
            ], 401);
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'max:190'],
            'confirmation' => ['required', 'accepted'],
        ]);

        if (! Hash::check($data['password'], (string) $user->password)) {
            throw ValidationException::withMessages([
                'password' => ['The password is incorrect.'],
            ]);
        }

        $result = $deletion->handle($user, $app);

        return response()->json([
            'ok' => true,
            'message' => 'Your account data for this app has been permanently deleted.',
            'result' => $result,
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'active_app_id' => $user->active_app_id ? (int) $user->active_app_id : null,
            'email_verified_at' => $user->email_verified_at ? $user->email_verified_at->toISOString() : null,
            'created_at' => $user->created_at ? $user->created_at->toISOString() : null,
        ];
    }
}
