<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Services\Video\VideoQueryService;
use App\Support\Video\VideoPayloadSerializer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    public function __construct(private readonly VideoQueryService $videos) {}

    public function channels(Request $request, string $appSlug): JsonResponse
    {
        $app = $this->resolveActiveApp($request, $appSlug);
        if (! $app) return $this->notFound('APP_NOT_FOUND');

        $paginator = $this->videos->paginateChannels($app, $this->filters($request));

        return $this->collection($app, $paginator, fn ($channel) => VideoPayloadSerializer::channel($channel));
    }

    public function channel(Request $request, string $appSlug, string $channelSlug): JsonResponse
    {
        $app = $this->resolveActiveApp($request, $appSlug);
        if (! $app) return $this->notFound('APP_NOT_FOUND');

        $channel = $this->videos->findChannel($app, $channelSlug);
        if (! $channel) return $this->notFound('VIDEO_CHANNEL_NOT_FOUND');

        $payload = VideoPayloadSerializer::channel($channel);
        $payload['playlists'] = $channel->playlists
            ->map(fn ($playlist) => VideoPayloadSerializer::playlist($playlist))
            ->values()->all();

        return response()->json(['ok' => true, 'app' => $this->appPayload($app), 'data' => $payload]);
    }

    public function playlists(Request $request, string $appSlug): JsonResponse
    {
        $app = $this->resolveActiveApp($request, $appSlug);
        if (! $app) return $this->notFound('APP_NOT_FOUND');

        $paginator = $this->videos->paginatePlaylists($app, $this->filters($request));

        return $this->collection($app, $paginator, fn ($playlist) => VideoPayloadSerializer::playlist($playlist));
    }

    public function playlist(Request $request, string $appSlug, string $playlistSlug): JsonResponse
    {
        $app = $this->resolveActiveApp($request, $appSlug);
        if (! $app) return $this->notFound('APP_NOT_FOUND');

        $playlist = $this->videos->findPlaylist($app, $playlistSlug);
        if (! $playlist) return $this->notFound('VIDEO_PLAYLIST_NOT_FOUND');

        return response()->json([
            'ok' => true,
            'app' => $this->appPayload($app),
            'data' => VideoPayloadSerializer::playlist($playlist, true),
        ]);
    }

    public function videos(Request $request, string $appSlug): JsonResponse
    {
        $app = $this->resolveActiveApp($request, $appSlug);
        if (! $app) return $this->notFound('APP_NOT_FOUND');

        $paginator = $this->videos->paginateVideos($app, $this->filters($request));

        return $this->collection($app, $paginator, fn ($video) => VideoPayloadSerializer::video($video), true);
    }

    public function video(Request $request, string $appSlug, string $videoSlug): JsonResponse
    {
        $app = $this->resolveActiveApp($request, $appSlug);
        if (! $app) return $this->notFound('APP_NOT_FOUND');

        $video = $this->videos->findVideo($app, $videoSlug);
        $payload = $video ? VideoPayloadSerializer::video($video) : null;
        if (! $payload) return $this->notFound('VIDEO_NOT_FOUND');

        return response()->json(['ok' => true, 'app' => $this->appPayload($app), 'data' => $payload]);
    }

    private function resolveActiveApp(Request $request, string $appSlug): ?App
    {
        $current = $request->attributes->get('current_app');
        if ($current instanceof App && (string) $current->slug === $appSlug && (bool) $current->is_active) {
            return $current;
        }

        return App::query()->where('slug', $appSlug)->where('is_active', true)->first();
    }

    private function filters(Request $request): array
    {
        return [
            'channel' => $request->query('channel'),
            'playlist' => $request->query('playlist'),
            'featured' => $this->nullableBoolean($request->query('featured')),
            'live' => $this->nullableBoolean($request->query('live')),
            'page' => $request->query('page'),
            'per_page' => $request->query('per_page'),
        ];
    }

    private function nullableBoolean(mixed $value): ?bool
    {
        if ($value === null || $value === '') return null;

        $parsed = filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE);

        return is_bool($parsed) ? $parsed : null;
    }

    private function collection(App $app, LengthAwarePaginator $paginator, callable $serializer, bool $filterNull = false): JsonResponse
    {
        $items = collect($paginator->items())->map($serializer);
        if ($filterNull) $items = $items->filter();

        return response()->json([
            'ok' => true,
            'app' => $this->appPayload($app),
            'data' => $items->values()->all(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    private function appPayload(App $app): array
    {
        return ['id' => (int) $app->id, 'slug' => (string) $app->slug, 'name' => (string) $app->name];
    }

    private function notFound(string $error): JsonResponse
    {
        return response()->json(['ok' => false, 'error' => $error], 404);
    }
}
