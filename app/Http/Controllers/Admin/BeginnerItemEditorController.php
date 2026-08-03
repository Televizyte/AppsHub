<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppItem;
use App\Models\AppSection;
use App\Models\IconPreset;
use App\Models\MediaAsset;
use App\Models\WatchLink;
use App\Support\ActiveApp;
use App\Support\MediaAssetSync;
use App\Support\AppItemImagePayload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BeginnerItemEditorController extends Controller
{
    private array $tabs = [
        'home' => 'Home',
        'watch' => 'Watch',
        'inspire' => 'Inspire',
        'explore' => 'Explore',
        'more' => 'More',
    ];

    public function edit(Request $request, AppItem $appItem): View
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);
        abort_unless($activeAppId > 0, 404);

        $appItem->load('section');
        abort_unless($appItem->section && (int) $appItem->section->app_id === $activeAppId, 404);

        MediaAssetSync::syncAppItemImages($activeAppId);

        $sections = AppSection::query()
            ->where('app_id', $activeAppId)
            ->orderBy('tab_key')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['id', 'title', 'tab_key', 'key']);

        $payload = is_array($appItem->payload_json) ? $appItem->payload_json : [];
        $action = is_array($payload['action'] ?? null) ? $payload['action'] : [];
        $selectedWatchLinkId = (int) ($payload['watch_link_id'] ?? $action['watch_link_id'] ?? 0);

        $currentTab = $this->cleanTab($request->query('tab', $appItem->section->tab_key));
        $returnTo = $this->sanitizeReturnTo($request->query('return'));
        $returnUrl = $this->returnUrl($returnTo, $currentTab);

        return view('admin.beginner.items.edit', [
            'item' => $appItem,
            'sections' => $sections,
            'icons' => $this->icons(),
            'mediaAssets' => $this->mediaAssets($activeAppId),
            'watchLinks' => $this->watchLinks($activeAppId),
            'selectedWatchLinkId' => $selectedWatchLinkId,
            'actionType' => (string) ($action['type'] ?? 'route'),
            'routeKey' => (string) ($action['route_key'] ?? ''),
            'url' => (string) ($action['url'] ?? $appItem->url ?? ''),
            'youtube' => (string) ($action['youtube'] ?? ''),
            'imagePreview' => $this->resolveImagePreview($appItem->image_url),
            'currentTab' => $currentTab,
            'returnTo' => $returnTo,
            'returnUrl' => $returnUrl,
        ]);
    }

    public function update(Request $request, AppItem $appItem): RedirectResponse
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);
        abort_unless($activeAppId > 0, 404);

        $appItem->load('section');
        abort_unless($appItem->section && (int) $appItem->section->app_id === $activeAppId, 404);

        $validated = $request->validate([
            'section_id' => ['required', 'integer', 'exists:app_sections,id'],
            'current_tab' => ['nullable', 'in:home,watch,inspire,explore,more'],
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'icon' => ['nullable', 'string', 'max:120'],
            'image_url' => ['nullable', 'string', 'max:1000'],
            'media_asset_id' => ['nullable', 'integer'],
            'image_file' => ['nullable', 'image', 'max:8192'],
            'clear_image' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer'],
            'is_enabled' => ['nullable', 'boolean'],
            'action_type' => ['required', 'in:route,webview,external_url,youtube_video,youtube_playlist,live_stream'],
            'route_key' => ['nullable', 'string', 'max:255'],
            'url' => ['nullable', 'string', 'max:1000'],
            'youtube' => ['nullable', 'string', 'max:1000'],
            'watch_link_id' => ['nullable', 'integer'],
            'item_kind' => ['nullable', 'string', 'max:80'],
            'source_type' => ['nullable', 'string', 'max:120'],
            'source_bucket' => ['nullable', 'string', 'max:255'],
            'source_channel' => ['nullable', 'string', 'max:255'],
            'content_id' => ['nullable', 'string', 'max:120'],
            'quote_id' => ['nullable', 'string', 'max:120'],
            'short_video_id' => ['nullable', 'string', 'max:120'],
            'book_id' => ['nullable', 'string', 'max:120'],
            'quiz_set_id' => ['nullable', 'string', 'max:120'],
            'layout_template' => ['nullable', 'string', 'max:120'],
            'card_style' => ['nullable', 'string', 'max:120'],
            'size_preset' => ['nullable', 'string', 'max:120'],
            'animation_style' => ['nullable', 'string', 'max:120'],
            'open_mode' => ['nullable', 'string', 'max:120'],
            'badge_text' => ['nullable', 'string', 'max:120'],
            'return' => ['nullable', 'string'],
        ]);

        $section = AppSection::query()->find((int) $validated['section_id']);
        abort_unless($section && (int) $section->app_id === $activeAppId, 404);

        $selectedWatchLink = $this->selectedWatchLinkForRequest((int) ($validated['watch_link_id'] ?? 0), $activeAppId);

        $imageUrl = trim((string) ($validated['image_url'] ?? ''));

        $selectedMediaUrl = $this->urlFromSelectedMediaAsset((int) ($validated['media_asset_id'] ?? 0), $activeAppId);
        if ($selectedMediaUrl !== '') {
            $imageUrl = $selectedMediaUrl;
        }

        if ($request->hasFile('image_file')) {
            $imageUrl = $this->storeUploadedImage($request, $activeAppId, $validated['title']);
        }

        $url = trim((string) ($validated['url'] ?? ''));
        $youtube = trim((string) ($validated['youtube'] ?? ''));
        $actionType = trim((string) $validated['action_type']);

        if ($selectedWatchLink) {
            $actionType = $this->actionTypeForSelectedWatchLink($selectedWatchLink, $actionType);
            $watchUrl = trim((string) $selectedWatchLink->url);

            if ($watchUrl !== '') {
                if (in_array($actionType, ['youtube_video', 'youtube_playlist'], true)) {
                    $youtube = $watchUrl;
                } else {
                    $url = $watchUrl;
                }
            }
        }

        $persistedUrl = $this->persistedUrlForAction($actionType, $url, $youtube);

        $finalImageUrl = $request->boolean('clear_image')
            ? null
            : ($imageUrl !== '' ? $imageUrl : $appItem->image_url);

        $selectedMediaAssetId = (int) ($validated['media_asset_id'] ?? 0);
        if ($selectedMediaAssetId <= 0 && $finalImageUrl) {
            $selectedMediaAssetId = AppItemImagePayload::mediaAssetIdFromUrl($activeAppId, $finalImageUrl);
        }

        $appItem->fill([
            'section_id' => (int) $validated['section_id'],
            'title' => $validated['title'],
            'subtitle' => $validated['subtitle'] ?? null,
            'icon' => $validated['icon'] ?? null,
            'image_url' => $finalImageUrl,
            'url' => $persistedUrl !== '' ? $persistedUrl : null,
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
            'is_enabled' => (bool) ($validated['is_enabled'] ?? false),
            'payload_json' => AppItemImagePayload::mergeIntoPayload(
                $this->buildPayload(
                    $actionType,
                    (string) ($validated['route_key'] ?? ''),
                    $url,
                    $youtube,
                    (int) ($validated['watch_link_id'] ?? 0),
                    $validated
                ),
                $finalImageUrl,
                $selectedMediaAssetId
            ),
        ]);

        $appItem->save();
        MediaAssetSync::syncAppItemImages($activeAppId);

        return redirect()
            ->route('admin.beginner.items.edit', [
                'appItem' => $appItem->id,
                'tab' => $this->cleanTab($validated['current_tab'] ?? $section->tab_key),
                'return' => $this->sanitizeReturnTo($validated['return'] ?? null),
            ])
            ->with('status', 'Item saved successfully. Image library is now synced.');
    }

    public function move(Request $request, AppItem $appItem, string $direction): RedirectResponse
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);
        abort_unless($activeAppId > 0, 404);

        $appItem->load('section');
        abort_unless($appItem->section && (int) $appItem->section->app_id === $activeAppId, 404);

        $direction = strtolower(trim($direction));
        abort_unless(in_array($direction, ['up', 'down'], true), 404);

        $currentOrder = (int) $appItem->sort_order;

        $swapQuery = AppItem::query()
            ->where('section_id', $appItem->section_id)
            ->where('id', '!=', $appItem->id);

        if ($direction === 'up') {
            $swapItem = $swapQuery
                ->where('sort_order', '<=', $currentOrder)
                ->orderByDesc('sort_order')
                ->orderByDesc('id')
                ->first();
        } else {
            $swapItem = $swapQuery
                ->where('sort_order', '>=', $currentOrder)
                ->orderBy('sort_order')
                ->orderBy('id')
                ->first();
        }

        if (!$swapItem) {
            return back()->with('status', 'Item is already in that position.');
        }

        DB::transaction(function () use ($appItem, $swapItem, $currentOrder) {
            $appItem->sort_order = (int) $swapItem->sort_order;
            $appItem->save();

            $swapItem->sort_order = $currentOrder;
            $swapItem->save();
        });

        return back()->with('status', 'Item order updated.');
    }

    public function destroy(Request $request, AppItem $appItem): RedirectResponse
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);
        abort_unless($activeAppId > 0, 404);

        $appItem->load('section');
        abort_unless($appItem->section && (int) $appItem->section->app_id === $activeAppId, 404);

        $validated = $request->validate([
            'current_tab' => ['nullable', 'in:home,watch,inspire,explore,more'],
            'return' => ['nullable', 'string'],
        ]);

        $tab = $this->cleanTab($validated['current_tab'] ?? $appItem->section->tab_key);
        $returnTo = $this->sanitizeReturnTo($validated['return'] ?? null);

        $appItem->delete();

        return redirect($this->returnUrl($returnTo, $tab))
            ->with('status', 'Item deleted successfully.');
    }

    private function icons()
    {
        return IconPreset::query()
            ->where('is_active', true)
            ->orderBy('label')
            ->get(['key', 'label', 'svg']);
    }

    private function mediaAssets(int $activeAppId)
    {
        MediaAssetSync::syncAppItemImages($activeAppId);

        return MediaAsset::query()
            ->where('type', 'image')
            ->where('is_active', true)
            ->where(function ($query) use ($activeAppId) {
                $query->whereNull('app_id')->orWhere('app_id', $activeAppId);
            })
            ->orderByRaw("CASE WHEN bucket = 'item-images' THEN 0 ELSE 1 END")
            ->orderBy('bucket')
            ->orderBy('label')
            ->orderByDesc('updated_at')
            ->get(['id', 'label', 'url', 'path', 'disk', 'bucket']);
    }

    private function buildPayload(string $type, string $routeKey, string $url, string $youtube, int $watchLinkId = 0, array $builder = []): array
    {
        $payload = ['action' => ['type' => $type]];

        if (trim($routeKey) !== '') {
            $payload['action']['route_key'] = trim($routeKey);
        }

        if (trim($url) !== '') {
            $payload['action']['url'] = trim($url);
        }

        if (trim($youtube) !== '') {
            $payload['action']['youtube'] = trim($youtube);
            $payload['action']['url'] = trim($youtube);
        }

        if ($watchLinkId > 0) {
            $payload['action']['watch_link_id'] = $watchLinkId;
            $payload['watch_link_id'] = $watchLinkId;
        }

        $extra = $this->builderPayloadFromValidated($builder);
        if ($extra !== []) {
            $payload = array_replace_recursive($payload, $extra);
        }

        return $payload;
    }

    private function builderPayloadFromValidated(array $validated): array
    {
        $clean = function (string $key) use ($validated): string {
            return trim((string) ($validated[$key] ?? ''));
        };

        $source = array_filter([
            'type' => $clean('source_type'),
            'bucket' => $clean('source_bucket'),
            'channel' => $clean('source_channel'),
            'content_id' => $clean('content_id'),
            'quote_id' => $clean('quote_id'),
            'short_video_id' => $clean('short_video_id'),
            'book_id' => $clean('book_id'),
            'quiz_set_id' => $clean('quiz_set_id'),
        ], fn ($value) => $value !== '');

        $display = array_filter([
            'layout_template' => $clean('layout_template'),
            'card_style' => $clean('card_style'),
            'size_preset' => $clean('size_preset'),
            'animation_style' => $clean('animation_style'),
            'open_mode' => $clean('open_mode'),
            'badge_text' => $clean('badge_text'),
        ], fn ($value) => $value !== '');

        $itemBuilder = array_filter([
            'kind' => $clean('item_kind'),
            'source_type' => $clean('source_type'),
            'source_bucket' => $clean('source_bucket'),
            'source_channel' => $clean('source_channel'),
            'layout_template' => $clean('layout_template'),
            'card_style' => $clean('card_style'),
            'size_preset' => $clean('size_preset'),
            'animation_style' => $clean('animation_style'),
            'open_mode' => $clean('open_mode'),
            'badge_text' => $clean('badge_text'),
        ], fn ($value) => $value !== '');

        return array_filter([
            'item_builder' => $itemBuilder,
            'source' => $source,
            'display' => $display,
        ], fn ($value) => is_array($value) ? $value !== [] : $value !== null);
    }

    private function persistedUrlForAction(string $type, string $url, string $youtube): string
    {
        $type = trim($type);
        $url = trim($url);
        $youtube = trim($youtube);

        if (in_array($type, ['youtube_video', 'youtube_playlist'], true)) {
            return $youtube !== '' ? $youtube : $url;
        }

        if (in_array($type, ['webview', 'external_url', 'live_stream'], true)) {
            return $url !== '' ? $url : $youtube;
        }

        return $url !== '' ? $url : $youtube;
    }

    private function storeUploadedImage(Request $request, int $activeAppId, string $title): string
    {
        $file = $request->file('image_file');

        if (!$file) {
            return '';
        }

        $safeTitle = Str::slug($title ?: 'item-image');
        $extension = $file->getClientOriginalExtension() ?: 'jpg';
        $filename = $safeTitle . '-' . now()->format('YmdHis') . '-' . Str::random(6) . '.' . $extension;

        $path = $file->storeAs(
            'assets/app-' . $activeAppId . '/items',
            $filename,
            'public'
        );

        $url = Storage::disk('public')->url($path);

        $width = null;
        $height = null;

        try {
            $absolutePath = storage_path('app/public/' . $path);
            if (is_file($absolutePath)) {
                $size = getimagesize($absolutePath);
                if (is_array($size)) {
                    $width = $size[0] ?? null;
                    $height = $size[1] ?? null;
                }
            }
        } catch (\Throwable $e) {
            $width = null;
            $height = null;
        }

        MediaAsset::create([
            'app_id' => $activeAppId,
            'type' => 'image',
            'label' => $title ?: 'Uploaded Item Image',
            'bucket' => 'item-images',
            'disk' => 'public',
            'path' => $path,
            'url' => $url,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'tags_json' => [
                'source' => 'beginner_item_editor',
                'usage' => 'app_item',
            ],
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return $url;
    }

    private function urlFromSelectedMediaAsset(int $assetId, int $activeAppId): string
    {
        if ($assetId <= 0) {
            return '';
        }

        $asset = MediaAsset::query()
            ->where('id', $assetId)
            ->where('type', 'image')
            ->where('is_active', true)
            ->where(function ($query) use ($activeAppId) {
                $query->whereNull('app_id')->orWhere('app_id', $activeAppId);
            })
            ->first();

        if (!$asset) {
            return '';
        }

        return MediaAssetSync::publicUrlForAsset($asset);
    }

    private function resolveImagePreview(?string $value): ?string
    {
        $value = trim((string) $value);

        if ($value === '') {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        try {
            return Storage::disk('public')->url($value);
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function cleanTab($value): string
    {
        $value = strtolower(trim((string) $value));

        return array_key_exists($value, $this->tabs) ? $value : 'home';
    }

    private function sanitizeReturnTo(?string $value): string
    {
        return match (trim((string) $value)) {
            'destination' => 'destination',
            default => 'dashboard',
        };
    }

    private function returnUrl(string $returnTo, string $tab): string
    {
        return match ($returnTo) {
            'destination' => '/admin/destination-builder?tab=' . urlencode($tab),
            default => '/admin/beginner-dashboard?tab=' . urlencode($tab),
        };
    }


    private function watchLinks(int $activeAppId)
    {
        return WatchLink::query()
            ->where('app_id', $activeAppId)
            ->orderBy('sort_order')
            ->orderBy('title')
            ->get()
            ->map(function (WatchLink $link) {
                $meta = is_array($link->meta_json ?? null) ? $link->meta_json : [];

                return [
                    'id' => (int) $link->id,
                    'title' => (string) $link->title,
                    'type' => (string) $link->type,
                    'url' => (string) $link->url,
                    'label' => (string) ($meta['label'] ?? ''),
                    'player' => (string) ($meta['player'] ?? ''),
                    'subtitle' => (string) ($meta['subtitle'] ?? ''),
                    'image_url' => (string) ($meta['image_url'] ?? ''),
                ];
            });
    }

    private function selectedWatchLinkForRequest(int $watchLinkId, int $activeAppId): ?WatchLink
    {
        if ($watchLinkId <= 0 || $activeAppId <= 0) {
            return null;
        }

        return WatchLink::query()
            ->where('app_id', $activeAppId)
            ->where('id', $watchLinkId)
            ->first();
    }

    private function actionTypeForSelectedWatchLink(WatchLink $watchLink, string $fallback): string
    {
        $fallback = trim($fallback);
        $type = strtolower(trim((string) $watchLink->type));
        $meta = is_array($watchLink->meta_json ?? null) ? $watchLink->meta_json : [];
        $player = strtolower(trim((string) ($meta['player'] ?? '')));

        if (in_array($type, ['live_hls', 'live_youtube', 'commanding_day'], true)) {
            return 'live_stream';
        }

        if ($player === 'youtube' || str_contains(strtolower((string) $watchLink->url), 'youtube.com') || str_contains(strtolower((string) $watchLink->url), 'youtu.be')) {
            return 'youtube_playlist';
        }

        if (in_array($type, ['channel', 'web'], true)) {
            return 'webview';
        }

        return in_array($fallback, ['route', 'webview', 'external_url', 'youtube_video', 'youtube_playlist', 'live_stream'], true)
            ? $fallback
            : 'webview';
    }
}
