<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\ContentPost;
use App\Models\UserFollow;
use App\Support\Ads\AdResolver;
use App\Support\Icons\SvgIconRegistry;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use App\Support\Publishing\PublicationVisibility;

class PostController extends Controller
{
    public function show(Request $request, string $appSlug, string $postSlug)
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

        $viewer = $request->user();
        $includeDrafts = $this->truthy($request->query('include_drafts'));

        $qb = ContentPost::query()
            ->where('app_id', (int) $app->id)
            ->where('slug', $postSlug);

        if (! $includeDrafts) {
            PublicationVisibility::apply($qb);
        }

        $post = $qb->first();

        if (! $post) {
            return response()->json([
                'ok' => false,
                'error' => 'CONTENT_NOT_FOUND',
                'message' => 'Content not found.',
            ], 404);
        }

        $item = $this->shapePost($post, (string) $app->slug, (int) $app->id, $viewer);

        $tab = $this->cleanTab($request->query('tab', 'inspire'));
        $bucket = $item['payload']['bucket'] ?? null;
        $routeKey = $bucket ? $bucket . '_read' : 'content_read';

        $ads = AdResolver::screenAdsForApp((int) $app->id, $tab, $routeKey);

        return response()->json([
            'ok' => true,
            'app' => [
                'id' => (int) $app->id,
                'name' => (string) $app->name,
                'slug' => (string) $app->slug,
            ],
            'scope' => [
                'tab' => $tab,
                'route_key' => $routeKey,
            ],
            'ads' => [
                'screen' => $ads,
                'native_in_list' => AdResolver::defaultNativeInList(),
            ],
            'meta' => [
                'api_version' => 'v1.1',
                'screen' => 'content_read',
                'detail_source' => 'posts_slug',
            ],
            'item' => $item,
            'post' => $item,
        ]);
    }

    private function shapePost(ContentPost $post, string $appSlug, int $appId, $viewer): array
    {
        $id = (int) $post->id;
        $slug = is_string($post->slug) && trim($post->slug) !== '' ? (string) $post->slug : null;
        $bucket = is_string($post->bucket) && trim($post->bucket) !== '' ? (string) $post->bucket : null;

        $pub = $post->published_at ?? $post->publish_at ?? $post->created_at;
        $idOrSlug = $slug ?: (string) $id;

        $tags = $this->asArray($post->tags_json);
        $meta = $this->asArray($post->meta_json, true);
        $blocks = $this->asArray($post->blocks_json);
        $featured = (bool) ($post->is_featured ?? false);

        $imageUrl = $post->cover_image_src;
        $bodyHtml = is_string($post->body_html) && trim($post->body_html) !== ''
            ? (string) $post->body_html
            : null;

        $coverAssetId = $this->metaInt($meta ?? [], 'cover_asset_id');
        $coverAssetUrl = $this->metaStr($meta ?? [], 'cover_asset_url');
        $coverAssetPath = $this->metaStr($meta ?? [], 'cover_asset_path');
        $coverAssetBucket = $this->metaStr($meta ?? [], 'cover_asset_bucket');
        $coverUpdatedAt = $this->metaStr($meta ?? [], 'cover_updated_at');

        $authorUserId = $post->author_user_id ? (int) $post->author_user_id : null;
        $authorName = is_string($post->author_name) && trim($post->author_name) !== '' ? trim((string) $post->author_name) : null;
        $publisher = $this->resolvePublisher($appId, $authorUserId, $authorName, $viewer);

        $likesCount = $this->likesCount($appId, $id);
        $commentsCount = $this->commentsCount($appId, $id);
        $likedByMe = $viewer ? $this->likedByViewer($appId, $id, (int) $viewer->id) : false;

        return [
            'id' => $id,
            'type' => 'content_post',
            'title' => $post->title ?? null,
            'subtitle' => $post->subtitle ?? null,
            'icon' => SvgIconRegistry::icon($bucket),
            'image_url' => $imageUrl,

            'author_name' => $authorName,
            'author_user_id' => $authorUserId,
            'publisher' => $publisher,

            'cover_asset_id' => $coverAssetId,
            'cover_asset_url' => $coverAssetUrl,
            'cover_asset_path' => $coverAssetPath,
            'cover_asset_bucket' => $coverAssetBucket,
            'cover_updated_at' => $coverUpdatedAt,

            'likes_count' => $likesCount,
            'comments_count' => $commentsCount,
            'liked_by_me' => $likedByMe,

            'route' => $slug ? ('/content/' . $slug) : ('/content/' . $id),
            'url' => null,

            'payload' => [
                'screen' => 'content_read',
                'content_type' => $bucket,
                'bucket' => $bucket,
                'badge' => $this->contentBadge($bucket),

                'slug' => $slug,
                'featured' => $featured,
                'published_at' => $pub,
                'author_name' => $authorName,

                'tags' => $tags,
                'meta' => $meta,
                'blocks' => $blocks,
                'body_html' => $bodyHtml,

                'api_url' => "/api/v1/apps/{$appSlug}/content/{$idOrSlug}",

                'cover' => [
                    'image_url' => $imageUrl,
                    'asset_id' => $coverAssetId,
                    'asset_url' => $coverAssetUrl,
                    'asset_path' => $coverAssetPath,
                    'asset_bucket' => $coverAssetBucket,
                    'updated_at' => $coverUpdatedAt,
                ],

                'publisher' => $publisher,

                'engagement' => [
                    'likes_count' => $likesCount,
                    'comments_count' => $commentsCount,
                    'liked_by_me' => $likedByMe,
                ],
            ],
        ];
    }

    private function resolvePublisher(int $appId, ?int $authorUserId, ?string $authorName, $viewer): ?array
    {
        if (! $authorUserId) {
            return $authorName ? [
                'id' => null,
                'name' => $authorName,
                'email' => null,
                'follow_route' => null,
                'followers_count' => 0,
                'following_by_me' => false,
            ] : null;
        }

        $user = DB::table('users')
            ->select(['id', 'name', 'email'])
            ->where('id', $authorUserId)
            ->first();

        if (! $user) {
            return $authorName ? [
                'id' => $authorUserId,
                'name' => $authorName,
                'email' => null,
                'follow_route' => '/users/' . $authorUserId . '/follow',
                'followers_count' => $this->followersCount($appId, $authorUserId),
                'following_by_me' => $viewer ? $this->viewerFollows($appId, (int) $viewer->id, $authorUserId) : false,
            ] : null;
        }

        return [
            'id' => (int) $user->id,
            'name' => (string) $user->name,
            'email' => (string) $user->email,
            'follow_route' => '/users/' . (int) $user->id . '/follow',
            'followers_count' => $this->followersCount($appId, (int) $user->id),
            'following_by_me' => $viewer ? $this->viewerFollows($appId, (int) $viewer->id, (int) $user->id) : false,
        ];
    }

    private function likesCount(int $appId, int $postId): int
    {
        if (! Schema::hasTable('content_post_likes')) {
            return 0;
        }

        return (int) DB::table('content_post_likes')
            ->where('app_id', $appId)
            ->where('content_post_id', $postId)
            ->count();
    }

    private function commentsCount(int $appId, int $postId): int
    {
        if (! Schema::hasTable('content_post_comments')) {
            return 0;
        }

        return (int) DB::table('content_post_comments')
            ->where('app_id', $appId)
            ->where('content_post_id', $postId)
            ->where('status', 'published')
            ->count();
    }

    private function likedByViewer(int $appId, int $postId, int $userId): bool
    {
        if (! Schema::hasTable('content_post_likes')) {
            return false;
        }

        return DB::table('content_post_likes')
            ->where('app_id', $appId)
            ->where('content_post_id', $postId)
            ->where('user_id', $userId)
            ->exists();
    }

    private function followersCount(int $appId, int $userId): int
    {
        if (! Schema::hasTable('user_follows')) {
            return 0;
        }

        return UserFollow::query()
            ->where('app_id', $appId)
            ->where('following_user_id', $userId)
            ->count();
    }

    private function viewerFollows(int $appId, int $viewerUserId, int $publisherUserId): bool
    {
        if (! Schema::hasTable('user_follows')) {
            return false;
        }

        return UserFollow::query()
            ->where('app_id', $appId)
            ->where('follower_user_id', $viewerUserId)
            ->where('following_user_id', $publisherUserId)
            ->exists();
    }

    private function asArray($value, bool $assoc = false)
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);
            if (is_array($decoded)) {
                return $decoded;
            }
        }

        return $assoc ? null : [];
    }

    private function metaStr(array $meta, string $key): ?string
    {
        if (! array_key_exists($key, $meta)) {
            return null;
        }

        $v = $meta[$key];

        if (is_string($v)) {
            $v = trim($v);
            return $v === '' ? null : $v;
        }

        if (is_numeric($v)) {
            return (string) $v;
        }

        return null;
    }

    private function metaInt(array $meta, string $key): ?int
    {
        if (! array_key_exists($key, $meta)) {
            return null;
        }

        $v = $meta[$key];

        if (is_int($v)) {
            return $v;
        }

        if (is_string($v) && ctype_digit($v)) {
            return (int) $v;
        }

        if (is_numeric($v)) {
            return (int) $v;
        }

        return null;
    }

    private function contentBadge(?string $bucket): ?array
    {
        $bucket = is_string($bucket) ? trim($bucket) : null;
        if (! $bucket) {
            return null;
        }

        $map = [
            'wordification' => 'Wordification',
            'motivation' => 'Motivation',
            'sod' => 'SOD',
            'highlight' => 'Highlights',
            'highlights' => 'Highlights',
            'inside_dunamis' => 'Inside Dunamis',
            'articles' => 'Articles',
            'devotional' => 'Devotional',
            'blog' => 'Blog',
        ];

        $text = $map[$bucket] ?? $this->titleCaseFromKey($bucket);

        return [
            'key' => $bucket,
            'text' => $text,
        ];
    }

    private function titleCaseFromKey(string $key): string
    {
        $key = str_replace(['-', '_'], ' ', strtolower(trim($key)));
        $key = preg_replace('/\s+/', ' ', $key) ?: $key;
        return ucwords($key);
    }

    private function truthy($v): bool
    {
        if (is_bool($v)) {
            return $v;
        }

        if (is_numeric($v)) {
            return (int) $v === 1;
        }

        if (! is_string($v)) {
            return false;
        }

        $v = strtolower(trim($v));
        return in_array($v, ['1', 'true', 'yes', 'on'], true);
    }

    private function cleanTab($v): string
    {
        $v = strtolower(trim((string) $v));
        return in_array($v, ['home', 'watch', 'inspire', 'explore', 'more'], true) ? $v : 'inspire';
    }
}
