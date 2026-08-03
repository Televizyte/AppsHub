<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppItem;
use App\Models\ContentPost;
use App\Models\MediaAsset;
use App\Models\User;
use App\Support\ActiveApp;
use App\Support\Scheduling\AdminScheduleTime;
use App\Support\ShortVideos\ShortVideoPayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BeginnerContentEditorController extends Controller
{
    public function edit(Request $request, ContentPost $contentPost): View
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);

        abort_unless($activeAppId > 0, 404);
        abort_unless((int) $contentPost->app_id === $activeAppId, 404);

        $users = User::query()
            ->orderBy('name')
            ->orderBy('email')
            ->get(['id', 'name', 'email']);

        $item = $this->resolveItem($activeAppId, (int) $request->query('item_id', 0));
        $returnTo = $this->sanitizeReturnTo($request->query('return'), $item);

        return view('admin.beginner.content-posts.edit', [
            'post' => $contentPost,
            'channels' => $this->channels(),
            'statusOptions' => $this->statusOptions(),
            'users' => $users,
            'mediaAssets' => $this->mediaAssets($activeAppId),
            'shortVideoCategories' => $this->shortVideoCategories($activeAppId),
            'item' => $item,
            'itemId' => $item?->id,
            'returnTo' => $returnTo,
            'returnUrl' => $this->returnUrl($returnTo, $contentPost->bucket, $item),
        ]);
    }

    public function update(Request $request, ContentPost $contentPost): RedirectResponse
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);

        abort_unless($activeAppId > 0, 404);
        abort_unless((int) $contentPost->app_id === $activeAppId, 404);

        if ($request->input('_beginner_action') === 'delete') {
            return $this->deleteFromBeginner($request, $contentPost, $activeAppId);
        }

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'bucket' => ['required', 'string'],
            'status' => ['required', 'in:draft,published'],
            'author_user_id' => ['nullable', 'integer', 'exists:users,id'],
            'author_name' => ['nullable', 'string', 'max:255'],
            'is_featured' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
            'cover_image_url' => ['nullable', 'string', 'max:1000'],
            'cover_image_file' => ['nullable', 'image', 'max:8192'],
            'media_asset_id' => ['nullable', 'integer', 'exists:media_assets,id'],
            'body_html' => ['nullable', 'string'],
            'publish_at' => ['nullable', 'date'],
            'published_at' => ['nullable', 'date'],
            'icon_key' => ['nullable', 'string', 'max:120'],
            'item_id' => ['nullable', 'integer'],
            'return' => ['nullable', 'string'],

            'video_url' => ['nullable', 'string', 'max:2000'],
            'video_file' => ['nullable', 'file', 'mimetypes:video/mp4,video/webm,video/quicktime', 'max:204800'],
            'video_engine' => ['nullable', 'string', 'max:80'],
            'video_duration' => ['nullable', 'string', 'max:80'],
            'video_aspect' => ['nullable', 'string', 'max:80'],
            'feed_label' => ['nullable', 'string', 'max:120'],
            'short_video_category' => ['nullable', 'string', 'max:120'],
            'short_video_category_label' => ['nullable', 'string', 'max:120'],
            'short_video_tags' => ['nullable', 'string', 'max:500'],
        ]);

        $bucket = $this->normalizeBucket($validated['bucket']);
        abort_unless(array_key_exists($bucket, $this->channels()), 422);

        $item = $this->resolveItem($activeAppId, (int) ($validated['item_id'] ?? 0));

        $authorUserId = $validated['author_user_id'] ?? null;
        $authorName = trim((string) ($validated['author_name'] ?? ''));

        if ($authorUserId) {
            $user = User::query()->find((int) $authorUserId);
            if ($user) {
                $authorName = (string) $user->name;
            }
        }

        $coverImageUrl = $this->resolveCoverImageUrl($request, $contentPost, $activeAppId, $bucket, $validated['title']);
        $bodyHtml = trim((string) ($validated['body_html'] ?? ''));

        $meta = is_array($contentPost->meta_json) ? $contentPost->meta_json : [];
        $meta['beginner_bucket'] = $bucket;

        $iconKey = trim((string) ($validated['icon_key'] ?? ''));
        if ($iconKey !== '') {
            $meta['icon_key'] = $iconKey;
        } else {
            unset($meta['icon_key']);
        }

        if ($bucket === ShortVideoPayload::DEFAULT_BUCKET) {
            $uploadedVideoAsset = null;
            $videoUrl = trim((string) ($validated['video_url'] ?? ($meta['video_url'] ?? '')));
            $videoEngine = trim((string) ($validated['video_engine'] ?? ($meta['video_engine'] ?? 'auto'))) ?: 'auto';

            if ($request->hasFile('video_file')) {
                $uploadedVideoAsset = $this->storeUploadedVideo($request, $activeAppId, $bucket, $validated['title']);

                if ($uploadedVideoAsset) {
                    $videoUrl = (string) $uploadedVideoAsset->url;
                    $videoEngine = $this->videoEngineFromMime((string) $uploadedVideoAsset->mime);
                }
            }

            $meta = ShortVideoPayload::metaFromRequest($validated, $meta);
            $meta['video_url'] = $videoUrl;
            $meta['video_engine'] = $videoEngine;
            $meta['video_duration'] = trim((string) ($validated['video_duration'] ?? ($meta['video_duration'] ?? '')));
            $meta['video_aspect'] = trim((string) ($validated['video_aspect'] ?? ($meta['video_aspect'] ?? 'portrait'))) ?: 'portrait';
            $meta['feed_label'] = trim((string) ($validated['feed_label'] ?? ($meta['feed_label'] ?? ($meta['category_label'] ?? 'Short Feed')))) ?: 'Short Feed';
            $meta['video_source'] = $uploadedVideoAsset ? 'uploaded' : ($videoUrl !== '' ? ($meta['video_source'] ?? 'external') : 'none');

            if ($uploadedVideoAsset) {
                $meta['video_media_asset_id'] = (int) $uploadedVideoAsset->id;
                $meta['video_mime'] = (string) $uploadedVideoAsset->mime;
                $meta['video_size'] = (int) $uploadedVideoAsset->size;
                $meta['video_path'] = (string) $uploadedVideoAsset->path;
            }
        }

        $status = (string) $validated['status'];
        $validated['publish_at'] = AdminScheduleTime::toUtc($validated['publish_at'] ?? null);
        $validated['published_at'] = AdminScheduleTime::toUtc($validated['published_at'] ?? null);

        $contentPost->update([
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'bucket' => $bucket,
            'status' => $status,
            'cover_image_url' => $coverImageUrl,
            'body_html' => $bodyHtml !== '' ? $bodyHtml : null,
            'blocks_json' => $this->buildBlocksFromHtml($bodyHtml),
            'tags_json' => $bucket === ShortVideoPayload::DEFAULT_BUCKET ? ($meta['tags'] ?? []) : $contentPost->tags_json,
            'author_user_id' => $authorUserId ?: null,
            'author_name' => $authorName !== '' ? $authorName : null,
            'is_featured' => (bool) ($validated['is_featured'] ?? false),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'publish_at' => $validated['publish_at'] ?? null,
            'published_at' => $this->resolvePublishedAt($validated, $contentPost, $status),
            'meta_json' => $meta,
        ]);

        $returnTo = $this->sanitizeReturnTo($validated['return'] ?? null, $item);

        return redirect($this->returnUrl($returnTo, $bucket, $item))
            ->with('status', 'Content updated successfully.');
    }

    private function resolveCoverImageUrl(Request $request, ContentPost $contentPost, int $activeAppId, string $bucket, string $title): ?string
    {
        if ($request->hasFile('cover_image_file')) {
            return $this->storeUploadedCover($request, $activeAppId, $bucket, $title);
        }

        $mediaAssetId = (int) $request->input('media_asset_id', 0);

        if ($mediaAssetId > 0) {
            $asset = MediaAsset::query()
                ->where('id', $mediaAssetId)
                ->where('type', 'image')
                ->where('is_active', true)
                ->where(function ($query) use ($activeAppId) {
                    $query->whereNull('app_id')->orWhere('app_id', $activeAppId);
                })
                ->first();

            if ($asset) {
                if (is_string($asset->url) && trim($asset->url) !== '') {
                    return trim($asset->url);
                }

                if (is_string($asset->path) && trim($asset->path) !== '') {
                    return Storage::disk($asset->disk ?: 'public')->url($asset->path);
                }
            }
        }

        $manualUrl = trim((string) $request->input('cover_image_url', ''));

        if ($manualUrl !== '') {
            return $manualUrl;
        }

        return $contentPost->cover_image_url ?: null;
    }

    private function storeUploadedCover(Request $request, int $activeAppId, string $bucket, string $title): string
    {
        $file = $request->file('cover_image_file');

        if (! $file) {
            return '';
        }

        $safeTitle = Str::slug($title ?: 'content-cover');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $extension : 'jpg';

        $filename = $safeTitle . '-' . now()->format('YmdHis') . '-' . Str::lower(Str::random(8)) . '.' . $extension;
        $path = $file->storeAs('assets/app-' . $activeAppId . '/content-covers', $filename, 'public');
        $url = Storage::disk('public')->url($path);

        MediaAsset::create([
            'app_id' => $activeAppId,
            'type' => 'image',
            'label' => $title ?: 'Content Cover',
            'bucket' => 'content_' . $bucket,
            'disk' => 'public',
            'path' => $path,
            'url' => $url,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => null,
            'height' => null,
            'tags_json' => [
                'source' => 'beginner_content_edit',
                'usage' => $bucket === ShortVideoPayload::DEFAULT_BUCKET ? 'short_video_thumbnail' : 'content_cover',
                'bucket' => $bucket,
            ],
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return $url;
    }

    private function storeUploadedVideo(Request $request, int $activeAppId, string $bucket, string $title): ?MediaAsset
    {
        $file = $request->file('video_file');

        if (! $file) {
            return null;
        }

        $safeTitle = Str::slug($title ?: 'short-video');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'mp4');
        $extension = in_array($extension, ['mp4', 'webm', 'mov', 'm4v'], true) ? $extension : 'mp4';

        $filename = $safeTitle . '-' . now()->format('YmdHis') . '-' . Str::lower(Str::random(8)) . '.' . $extension;
        $path = $file->storeAs('assets/app-' . $activeAppId . '/short-videos', $filename, 'public');
        $url = Storage::disk('public')->url($path);

        return MediaAsset::create([
            'app_id' => $activeAppId,
            'type' => 'video',
            'label' => $title ?: 'Short Video',
            'bucket' => 'content_' . $bucket,
            'disk' => 'public',
            'path' => $path,
            'url' => $url,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => null,
            'height' => null,
            'tags_json' => [
                'source' => 'beginner_content_edit',
                'usage' => 'short_video',
                'bucket' => $bucket,
            ],
            'is_active' => true,
            'sort_order' => 0,
        ]);
    }

    private function videoEngineFromMime(string $mime): string
    {
        return match (strtolower(trim($mime))) {
            'video/mp4' => 'mp4',
            'video/webm' => 'webm',
            'video/quicktime' => 'mov',
            default => 'uploaded',
        };
    }

    private function resolvePublishedAt(array $validated, ContentPost $contentPost, string $status): mixed
    {
        if (! empty($validated['published_at'])) {
            return $validated['published_at'];
        }

        if ($status === 'published') {
            return $contentPost->published_at ?: now();
        }

        return null;
    }

    private function buildBlocksFromHtml(?string $html): array
    {
        $html = trim((string) $html);

        return $html === '' ? [] : [['type' => 'html', 'html' => $html]];
    }

    private function deleteFromBeginner(Request $request, ContentPost $contentPost, int $activeAppId): RedirectResponse
    {
        $request->validate([
            'delete_confirmation' => ['required', 'in:DELETE'],
            'bucket' => ['nullable', 'string'],
            'return' => ['nullable', 'string'],
            'item_id' => ['nullable', 'integer'],
        ]);

        $bucket = $this->normalizeBucket((string) ($request->input('bucket') ?: $contentPost->bucket));
        $item = $this->resolveItem($activeAppId, (int) $request->input('item_id', 0));
        $returnTo = $this->sanitizeReturnTo($request->input('return'), $item);

        $contentPost->delete();

        return redirect($this->returnUrl($returnTo, $bucket, $item))
            ->with('status', 'Content deleted successfully.');
    }

    private function resolveItem(int $activeAppId, int $itemId): ?AppItem
    {
        if ($itemId < 1) {
            return null;
        }

        return AppItem::query()
            ->with('section')
            ->where('id', $itemId)
            ->whereHas('section', fn ($q) => $q->where('app_id', $activeAppId))
            ->first();
    }

    private function mediaAssets(int $activeAppId)
    {
        return MediaAsset::query()
            ->whereIn('type', ['image', 'video'])
            ->where('is_active', true)
            ->where(function ($q) use ($activeAppId) {
                $q->whereNull('app_id')->orWhere('app_id', $activeAppId);
            })
            ->orderByRaw("CASE WHEN bucket LIKE 'content_short_videos%' THEN 0 WHEN bucket LIKE 'content_%' THEN 1 ELSE 2 END ASC")
            ->orderByDesc('id')
            ->limit(300)
            ->get();
    }

    private function shortVideoCategories(int $activeAppId): array
    {
        return ContentPost::query()
            ->where('app_id', $activeAppId)
            ->where('bucket', ShortVideoPayload::DEFAULT_BUCKET)
            ->get(['meta_json'])
            ->map(function (ContentPost $post) {
                $meta = is_array($post->meta_json) ? $post->meta_json : [];
                $key = ShortVideoPayload::normalizeCategoryKey($meta['category_key'] ?? $meta['category'] ?? null);
                $label = ShortVideoPayload::normalizeCategoryLabel($meta['category_label'] ?? null, $key);

                return [
                    'key' => $key,
                    'label' => $label,
                ];
            })
            ->unique('key')
            ->values()
            ->all();
    }

    private function normalizeBucket(string $bucket): string
    {
        return match (strtolower(trim($bucket))) {
            'highlight', 'message_highlights', 'message-highlight', 'message-highlights' => 'highlights',
            'inside', 'inside-dunamis', 'inside_dunamis_articles', 'articles', 'article' => 'inside_dunamis',
            'sod-quote', 'sod-quotes', 'quotes' => 'sod_quotes',
            'short', 'shorts', 'short-video', 'short-videos', 'short_video', 'short_videos', 'reel', 'reels' => ShortVideoPayload::DEFAULT_BUCKET,
            default => strtolower(trim($bucket)),
        };
    }

    private function channels(): array
    {
        return [
            'motivation' => 'Motivation',
            'wordification' => 'Wordification',
            'highlights' => 'Message Highlights',
            'inside_dunamis' => 'Inside Dunamis / Articles',
            'sod' => 'Seed of Destiny',
            'sod_quotes' => 'SOD Quotes',
            ShortVideoPayload::DEFAULT_BUCKET => 'Short Videos',
        ];
    }

    private function statusOptions(): array
    {
        return [
            'draft' => 'Draft',
            'published' => 'Published',
        ];
    }

    private function sanitizeReturnTo(?string $value, ?AppItem $item): string
    {
        $value = trim((string) $value);

        if ($item && $value === 'item') {
            return 'item';
        }

        return match ($value) {
            'channels' => 'channels',
            'destination' => 'destination',
            'short-video-engine' => 'short-video-engine',
            default => $item ? 'item' : 'channels',
        };
    }

    private function returnUrl(string $returnTo, string $bucket, ?AppItem $item): string
    {
        if ($returnTo === 'item' && $item && $item->section) {
            return route('admin.beginner.items.edit', [
                'appItem' => $item->id,
                'tab' => $item->section->tab_key,
                'return' => 'dashboard',
            ]);
        }

        if ($returnTo === 'destination') {
            return '/admin/destination-builder?tab=inspire';
        }

        if ($returnTo === 'short-video-engine' && $bucket === \App\Support\ShortVideos\ShortVideoPayload::DEFAULT_BUCKET) {
            return '/admin/short-video-engine?tab=library';
        }

        return route('admin.beginner.content-posts.channel', [
            'bucket' => $bucket,
            'item_id' => $item?->id,
        ]);
    }
}

