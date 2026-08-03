<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeedPost;
use App\Support\ActiveApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BeginnerFeedPostController extends Controller
{
    public function create(Request $request): View
    {
        return view('admin.beginner.feed-posts.editor', [
            'mode' => 'create',
            'post' => null,
            'form' => $this->defaults($request),
            'saveUrl' => route('admin.beginner.feed-posts.store'),
            'backUrl' => url('/admin/feed-engine?tab=' . urlencode((string) $request->query('tab', 'overview'))),
            'currentAppName' => $this->activeAppName(),
            'currentAppSlug' => $this->activeAppSlug(),
            'mediaAssets' => $this->mediaAssets(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validated($request);
        $thumbnailUrl = $this->handleThumbnailUpload($request) ?: $this->blankToNull($data['thumbnail_url'] ?? null);
        $status = $request->input('publish_action') === 'publish' ? 'published' : ($data['status'] ?? 'draft');
        $appId = ActiveApp::ensureId();

        $post = FeedPost::query()->create([
            'app_id' => $appId,
            'bucket' => $data['bucket'],
            'post_type' => $data['post_type'],
            'source_engine' => $this->blankToNull($data['source_engine'] ?? null),
            'source_id' => $this->blankToNull($data['source_id'] ?? null),
            'title' => $data['title'],
            'body' => $this->blankToNull($data['body'] ?? null),
            'excerpt' => $this->blankToNull($data['excerpt'] ?? null),
            'thumbnail_url' => $thumbnailUrl,
            'media_url' => $this->blankToNull($data['media_url'] ?? null),
            'deep_link' => $this->blankToNull($data['deep_link'] ?? null),
            'cta_label' => $this->blankToNull($data['cta_label'] ?? 'Open'),
            'status' => $status,
            'approval_status' => 'approved',
            'visibility' => $data['visibility'] ?? 'public',
            'is_pinned' => $request->boolean('is_pinned'),
            'is_featured' => $request->boolean('is_featured'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'published_at' => $status === 'published' ? now() : null,
            'created_by_type' => 'admin',
            'created_by_id' => auth()->id(),
            'meta_json' => [],
        ]);

        return redirect(url('/admin/beginner/feed-posts/' . $post->id . '/edit?tab=content'))
            ->with('status', $status === 'published' ? 'Feed post published.' : 'Feed draft saved.');
    }

    public function edit(Request $request, FeedPost $feedPost): View
    {
        abort_unless((int) $feedPost->app_id === (int) ActiveApp::ensureId(), 404);

        return view('admin.beginner.feed-posts.editor', [
            'mode' => 'edit',
            'post' => $feedPost,
            'form' => $this->fromPost($feedPost),
            'saveUrl' => route('admin.beginner.feed-posts.update', ['feedPost' => $feedPost->id]),
            'backUrl' => url('/admin/feed-engine?tab=published'),
            'currentAppName' => $this->activeAppName(),
            'currentAppSlug' => $this->activeAppSlug(),
            'mediaAssets' => $this->mediaAssets(),
        ]);
    }

    public function update(Request $request, FeedPost $feedPost): RedirectResponse
    {
        abort_unless((int) $feedPost->app_id === (int) ActiveApp::ensureId(), 404);

        $data = $this->validated($request);
        $thumbnailUrl = $this->handleThumbnailUpload($request) ?: $this->blankToNull($data['thumbnail_url'] ?? null);
        $status = $request->input('publish_action') === 'publish' ? 'published' : ($data['status'] ?? $feedPost->status ?? 'draft');

        $feedPost->update([
            'bucket' => $data['bucket'],
            'post_type' => $data['post_type'],
            'source_engine' => $this->blankToNull($data['source_engine'] ?? null),
            'source_id' => $this->blankToNull($data['source_id'] ?? null),
            'title' => $data['title'],
            'body' => $this->blankToNull($data['body'] ?? null),
            'excerpt' => $this->blankToNull($data['excerpt'] ?? null),
            'thumbnail_url' => $thumbnailUrl,
            'media_url' => $this->blankToNull($data['media_url'] ?? null),
            'deep_link' => $this->blankToNull($data['deep_link'] ?? null),
            'cta_label' => $this->blankToNull($data['cta_label'] ?? 'Open'),
            'status' => $status,
            'approval_status' => $status === 'published' ? 'approved' : ($feedPost->approval_status ?: 'approved'),
            'visibility' => $data['visibility'] ?? 'public',
            'is_pinned' => $request->boolean('is_pinned'),
            'is_featured' => $request->boolean('is_featured'),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'published_at' => $status === 'published' ? ($feedPost->published_at ?: now()) : $feedPost->published_at,
        ]);

        return redirect(url('/admin/beginner/feed-posts/' . $feedPost->id . '/edit?tab=' . urlencode((string) $request->input('_active_tab', 'content'))))
            ->with('status', $status === 'published' ? 'Feed post published.' : 'Feed post saved.');
    }

    protected function validated(Request $request): array
    {
        return $request->validate([
            'post_type' => ['required', 'string', 'max:80'],
            'bucket' => ['required', 'string', 'max:80'],
            'status' => ['nullable', 'string', 'max:40'],
            'visibility' => ['nullable', 'string', 'max:40'],
            'title' => ['required', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string', 'max:2000'],
            'body' => ['nullable', 'string'],
            'cta_label' => ['nullable', 'string', 'max:80'],
            'thumbnail_url' => ['nullable', 'string', 'max:2048'],
            'thumbnail_file' => ['nullable', 'image', 'max:10240'],
            'media_url' => ['nullable', 'string', 'max:2048'],
            'source_engine' => ['nullable', 'string', 'max:80'],
            'source_id' => ['nullable', 'string', 'max:120'],
            'deep_link' => ['nullable', 'string', 'max:255'],
            'sort_order' => ['nullable', 'integer', 'min:-100000', 'max:100000'],
        ]);
    }

    protected function defaults(Request $request): array
    {
        return [
            'post_type' => (string) $request->query('type', 'announcement'),
            'bucket' => (string) $request->query('bucket', 'announcements'),
            'status' => 'draft',
            'visibility' => 'public',
            'title' => '',
            'excerpt' => '',
            'body' => '',
            'cta_label' => 'Open',
            'thumbnail_url' => '',
            'media_url' => '',
            'source_engine' => '',
            'source_id' => '',
            'deep_link' => '',
            'sort_order' => 0,
            'is_pinned' => false,
            'is_featured' => false,
        ];
    }

    protected function fromPost(FeedPost $post): array
    {
        return [
            'post_type' => $post->post_type ?: 'announcement',
            'bucket' => $post->bucket ?: 'announcements',
            'status' => $post->status ?: 'draft',
            'visibility' => $post->visibility ?: 'public',
            'title' => $post->title ?: '',
            'excerpt' => $post->excerpt ?: '',
            'body' => $post->body ?: '',
            'cta_label' => $post->cta_label ?: 'Open',
            'thumbnail_url' => $post->thumbnail_url ?: '',
            'media_url' => $post->media_url ?: '',
            'source_engine' => $post->source_engine ?: '',
            'source_id' => $post->source_id ?: '',
            'deep_link' => $post->deep_link ?: '',
            'sort_order' => (int) ($post->sort_order ?? 0),
            'is_pinned' => (bool) $post->is_pinned,
            'is_featured' => (bool) $post->is_featured,
        ];
    }

    protected function handleThumbnailUpload(Request $request): ?string
    {
        if (! $request->hasFile('thumbnail_file')) {
            return null;
        }

        $file = $request->file('thumbnail_file');
        if (! $file || ! $file->isValid()) {
            return null;
        }

        $slug = $this->activeAppSlug();
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $name = 'feed_thumb_' . now()->format('Ymd_His') . '_' . Str::lower(Str::random(8)) . '.' . $ext;
        $path = $file->storeAs("media/{$slug}/feed-thumbnails", $name, 'public');
        return $this->absoluteUrl(Storage::disk('public')->url($path));
    }

    protected function mediaAssets(): array
    {
        if (! class_exists(\App\Models\MediaAsset::class)) {
            return [];
        }

        $appId = (int) ActiveApp::ensureId();

        try {
            return \App\Models\MediaAsset::query()
                ->where(function ($query) use ($appId) {
                    $query->whereNull('app_id');
                    if ($appId > 0) {
                        $query->orWhere('app_id', $appId);
                    }
                })
                ->where(function ($query) {
                    $query->where('type', 'image')->orWhere('mime', 'like', 'image/%');
                })
                ->latest('id')
                ->limit(36)
                ->get(['id', 'label', 'url', 'path', 'bucket', 'type'])
                ->map(fn ($asset) => [
                    'id' => (int) $asset->id,
                    'label' => (string) ($asset->label ?: basename((string) ($asset->path ?: $asset->url))),
                    'url' => (string) $asset->url,
                    'bucket' => (string) ($asset->bucket ?: 'library'),
                ])
                ->filter(fn ($asset) => $asset['url'] !== '')
                ->values()
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function blankToNull($value): ?string
    {
        $value = trim((string) $value);
        return $value === '' ? null : $value;
    }

    protected function activeAppName(): string
    {
        $app = \App\Models\App::query()->find(ActiveApp::ensureId());
        return $app?->name ?: 'Active App';
    }

    protected function activeAppSlug(): string
    {
        $app = \App\Models\App::query()->find(ActiveApp::ensureId());
        return $app?->slug ?: 'active-app';
    }

    protected function absoluteUrl(string $path): string
    {
        if (Str::startsWith($path, ['http://', 'https://'])) {
            return $path;
        }

        return rtrim((string) config('app.url'), '/') . '/' . ltrim($path, '/');
    }
}
