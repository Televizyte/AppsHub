<?php

namespace App\Filament\Pages;

use App\Support\AdminAccess;

use App\Models\App;
use App\Models\AppItem;
use App\Models\AppSection;
use App\Models\MediaAsset;
use App\Models\WatchLink;
use App\Support\ActiveApp;
use App\Support\AppCapabilities;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class WatchBuilder extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-tv';
    protected static ?string $navigationLabel = 'Watch Builder';
    protected static ?string $navigationGroup = 'Watch Builder';
    protected static ?int $navigationSort = 10;

    protected static ?string $slug = 'watch-builder';

    protected static string $view = 'filament.pages.watch-builder';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::page('watch_builder');
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('watch_builder');
    }

    public ?App $currentApp = null;

    public array $links = [];
    public array $groups = [];
    public array $stats = [];
    public array $imageAssets = [];


    public function mount(): void
    {
        $appId = (int) (ActiveApp::ensureId() ?? 0);

        $this->currentApp = $appId > 0 ? App::query()->find($appId) : null;

        $usageMap = $this->buildUsageMap($appId);

        $records = WatchLink::query()
            ->where('app_id', $appId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        $this->links = $records
            ->map(fn (WatchLink $link): array => $this->watchPayload($link, $usageMap))
            ->values()
            ->all();

        $collection = collect($this->links);

        $this->groups = $this->buildGroups($collection);
        $this->stats = $this->buildStats($collection);
        $this->imageAssets = $this->loadImageAssets($appId);
    }

    protected function watchPayload(WatchLink $link, array $usageMap = []): array
    {
        $meta = is_array($link->meta_json ?? null) ? $link->meta_json : [];

        $type = strtolower((string) ($link->type ?? 'video'));
        $player = strtolower((string) data_get($meta, 'player', ''));
        $group = strtolower((string) data_get($meta, 'group', ''));
        $url = (string) ($link->url ?? '');
        $title = (string) ($link->title ?? 'Untitled Watch Link');
        $imageUrl = $this->publicUrl((string) data_get($meta, 'image_url', ''));

        if ($player === '') {
            $player = $this->inferPlayer($type, $url);
        }

        if ($group === '') {
            $group = $this->inferGroup($type, $title, $url);
        }

        $usage = $usageMap[(int) $link->id] ?? [];

        return [
            'id' => (int) $link->id,
            'title' => $title,
            'type' => $type,
            'url' => $url,
            'is_enabled' => (bool) $link->is_enabled,
            'sort_order' => (int) ($link->sort_order ?? 0),
            'subtitle' => (string) data_get($meta, 'subtitle', ''),
            'label' => (string) data_get($meta, 'label', $this->defaultLabel($type, $group)),
            'player' => $player,
            'group' => $group,
            'image_url' => $imageUrl,
            'edit_url' => url('/admin/watch-links/' . $link->id . '/edit'),
            'kind_label' => $this->kindLabel($type, $player, $group),
            'health' => $this->healthLabel($url, (bool) $link->is_enabled),
            'usage' => $usage,
            'usage_count' => count($usage),
            'usage_label' => count($usage) > 0 ? count($usage) . ' placement(s)' : 'Not placed yet',
        ];
    }

    protected function buildUsageMap(int $appId): array
    {
        if ($appId <= 0 || ! class_exists(AppSection::class) || ! class_exists(AppItem::class)) {
            return [];
        }

        if (! Schema::hasTable('app_sections') || ! Schema::hasTable('app_items')) {
            return [];
        }

        try {
            $links = WatchLink::query()
                ->where('app_id', $appId)
                ->get(['id', 'title', 'url']);

            if ($links->isEmpty()) {
                return [];
            }

            $sections = AppSection::query()
                ->where('app_id', $appId)
                ->get();

            if ($sections->isEmpty()) {
                return [];
            }

            $sectionsById = $sections->keyBy('id');

            $items = AppItem::query()
                ->whereIn('section_id', $sections->pluck('id')->filter()->values())
                ->get();

            if ($items->isEmpty()) {
                return [];
            }

            $map = [];

            foreach ($links as $link) {
                $linkId = (int) $link->id;
                $linkUrl = trim((string) $link->url);

                foreach ($items as $item) {
                    $section = $sectionsById->get($item->section_id);

                    if (! $section) {
                        continue;
                    }

                    if (! $this->itemMatchesWatchLink($item, $linkId, $linkUrl)) {
                        continue;
                    }

                    $map[$linkId][] = [
                        'tab' => (string) ($section->tab_key ?? 'unknown'),
                        'section' => (string) ($section->title ?? $section->key ?? 'Section'),
                        'section_key' => (string) ($section->key ?? ''),
                        'item' => (string) ($item->title ?? 'Item'),
                        'edit_url' => url('/admin/destination-builder?tab=' . urlencode((string) ($section->tab_key ?? 'home'))),
                    ];
                }

                if (! empty($map[$linkId])) {
                    $map[$linkId] = collect($map[$linkId])
                        ->unique(fn (array $place) => $place['tab'] . '|' . $place['section_key'] . '|' . $place['item'])
                        ->values()
                        ->all();
                }
            }

            return $map;
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function itemMatchesWatchLink(AppItem $item, int $watchLinkId, string $watchUrl): bool
    {
        $raw = $item->toArray();

        $payload = $this->decodeArray($raw['payload_json'] ?? null);
        $meta = $this->decodeArray($raw['meta_json'] ?? null);

        $directIds = [
            $raw['watch_link_id'] ?? null,
            $payload['watch_link_id'] ?? null,
            $payload['watchLinkId'] ?? null,
            $payload['watch_link'] ?? null,
            $meta['watch_link_id'] ?? null,
            $meta['watchLinkId'] ?? null,
            data_get($payload, 'action.watch_link_id'),
            data_get($payload, 'action.watchLinkId'),
            data_get($meta, 'action.watch_link_id'),
            data_get($meta, 'action.watchLinkId'),
        ];

        foreach ($directIds as $id) {
            if ((string) $id !== '' && (int) $id === $watchLinkId) {
                return true;
            }
        }

        if ($watchUrl !== '') {
            $urls = [
                $raw['url'] ?? null,
                $raw['youtube_url'] ?? null,
                $payload['url'] ?? null,
                $payload['youtube_url'] ?? null,
                $payload['youtube'] ?? null,
                $payload['link'] ?? null,
                data_get($payload, 'action.url'),
                data_get($payload, 'action.youtube'),
                data_get($payload, 'action.youtube_url'),
                $meta['url'] ?? null,
                $meta['youtube_url'] ?? null,
                data_get($meta, 'action.url'),
            ];

            foreach ($urls as $url) {
                if ($this->sameUrl((string) $url, $watchUrl)) {
                    return true;
                }
            }
        }

        $haystack = strtolower(json_encode($raw) ?: '');

        if (str_contains($haystack, '"watch_link_id":' . $watchLinkId)
            || str_contains($haystack, '"watch_link_id":"' . $watchLinkId . '"')
            || str_contains($haystack, '"watchlinkid":' . $watchLinkId)
            || str_contains($haystack, '"watchlinkid":"' . $watchLinkId . '"')) {
            return true;
        }

        if ($watchUrl !== '' && str_contains($haystack, strtolower($watchUrl))) {
            return true;
        }

        return false;
    }

    protected function decodeArray(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        if (is_string($value) && trim($value) !== '') {
            $decoded = json_decode($value, true);

            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    protected function sameUrl(string $a, string $b): bool
    {
        $a = trim($a);
        $b = trim($b);

        if ($a === '' || $b === '') {
            return false;
        }

        if ($a === $b) {
            return true;
        }

        $normalize = function (string $url): string {
            $url = html_entity_decode($url);
            $url = preg_replace('/\s+/', '', $url) ?? $url;
            $url = rtrim($url, '/');

            return strtolower($url);
        };

        return $normalize($a) === $normalize($b);
    }

    protected function buildGroups($links): array
    {
        $primary = $links->filter(fn (array $link) => in_array($link['type'], ['live_hls', 'live_youtube', 'commanding_day'], true))->values();

        $videos = $links->filter(fn (array $link) => $link['group'] === 'video'
            || in_array($link['type'], ['video', 'youtube', 'vod', 'playlist', 'youtube_video'], true)
        )->values();

        $channels = $links->filter(fn (array $link) => $link['group'] === 'other_channels'
            || ($link['type'] === 'channel')
            || ($link['type'] === 'web' && str_contains(strtolower($link['url']), 'iframe'))
        )->values();

        $fallback = $links->filter(function (array $link) {
            $text = strtolower($link['title'] . ' ' . $link['type'] . ' ' . $link['group']);

            return str_contains($text, 'fallback')
                || str_contains($text, 'backup')
                || str_contains($text, 'alternative');
        })->values();

        $knownIds = collect()
            ->merge($primary->pluck('id'))
            ->merge($videos->pluck('id'))
            ->merge($channels->pluck('id'))
            ->merge($fallback->pluck('id'))
            ->unique();

        $other = $links->reject(fn (array $link) => $knownIds->contains($link['id']))->values();

        return [
            'primary' => [
                'label' => 'Main Live & Priority Links',
                'description' => 'Main Watch tab links: live stream, live service, prayer broadcast, and priority player links.',
                'items' => $primary->all(),
            ],
            'videos' => [
                'label' => 'Video Library / Playlists',
                'description' => 'YouTube videos, message playlists, services, events, and video-backed cards.',
                'items' => $videos->all(),
            ],
            'channels' => [
                'label' => 'Other TV Channels',
                'description' => 'Embedded channels, iframe TV channels, and external Christian channel links.',
                'items' => $channels->all(),
            ],
            'fallback' => [
                'label' => 'Fallback / Backup Links',
                'description' => 'Backup streams or alternative watch options for downtime or special events.',
                'items' => $fallback->all(),
            ],
            'other' => [
                'label' => 'Other Watch Items',
                'description' => 'Reusable watch links that do not yet belong to a fixed group.',
                'items' => $other->all(),
            ],
        ];
    }

    protected function buildStats($links): array
    {
        return [
            'total' => $links->count(),
            'enabled' => $links->where('is_enabled', true)->count(),
            'disabled' => $links->where('is_enabled', false)->count(),
            'live' => $links->filter(fn (array $link) => in_array($link['type'], ['live_hls', 'live_youtube', 'commanding_day'], true))->count(),
            'videos' => $links->filter(fn (array $link) => $link['group'] === 'video' || in_array($link['type'], ['video', 'youtube', 'vod', 'playlist', 'youtube_video'], true))->count(),
            'channels' => $links->filter(fn (array $link) => $link['group'] === 'other_channels')->count(),
            'placed' => $links->filter(fn (array $link) => (int) ($link['usage_count'] ?? 0) > 0)->count(),
        ];
    }

    protected function loadImageAssets(int $appId): array
    {
        try {
            return MediaAsset::query()
                ->where('type', 'image')
                ->where('is_active', true)
                ->where(function ($query) use ($appId) {
                    $query->whereNull('app_id');

                    if ($appId > 0) {
                        $query->orWhere('app_id', $appId);
                    }
                })
                ->orderByDesc('updated_at')
                ->limit(160)
                ->get()
                ->map(fn (MediaAsset $asset): array => [
                    'label' => (string) ($asset->label ?? basename((string) $asset->url)),
                    'url' => $this->publicUrl((string) $asset->url),
                    'type' => (string) $asset->type,
                    'bucket' => (string) ($asset->bucket ?? ''),
                ])
                ->filter(fn (array $asset) => trim($asset['url']) !== '')
                ->values()
                ->all();
        } catch (\Throwable $e) {
            return [];
        }
    }

    protected function inferPlayer(string $type, string $url): string
    {
        $u = strtolower($url);

        if ($type === 'live_hls' || str_contains($u, '.m3u8')) {
            return 'hls';
        }

        if (str_contains($u, 'youtube.com') || str_contains($u, 'youtu.be')) {
            return 'youtube';
        }

        return 'web';
    }

    protected function inferGroup(string $type, string $title, string $url): string
    {
        $text = strtolower($type . ' ' . $title . ' ' . $url);

        if ($type === 'channel' || str_contains($text, 'other channel') || str_contains($text, 'iframe.viewmedia')) {
            return 'other_channels';
        }

        if (in_array($type, ['video', 'youtube', 'vod', 'playlist', 'youtube_video'], true)) {
            return 'video';
        }

        if (str_contains($text, 'fallback') || str_contains($text, 'backup')) {
            return 'fallback';
        }

        return '';
    }

    protected function defaultLabel(string $type, string $group): string
    {
        return match (true) {
            $type === 'live_hls' => 'LIVE',
            $type === 'live_youtube' => 'SERVICE',
            $type === 'commanding_day' => 'PRAYER',
            $group === 'other_channels' => 'CHANNEL',
            in_array($type, ['video', 'youtube', 'playlist', 'vod'], true) => 'VIDEO',
            default => 'WATCH',
        };
    }

    protected function kindLabel(string $type, string $player, string $group): string
    {
        if ($type === 'live_hls') {
            return 'Live / HLS';
        }

        if ($type === 'live_youtube') {
            return 'YouTube Live';
        }

        if ($type === 'commanding_day') {
            return 'Prayer Broadcast';
        }

        if ($group === 'other_channels') {
            return 'TV Channel';
        }

        if ($player === 'youtube') {
            return 'YouTube / Playlist';
        }

        if ($player === 'hls') {
            return 'HLS Stream';
        }

        if ($player === 'web') {
            return 'Web / Embed';
        }

        return 'Watch Link';
    }

    protected function healthLabel(string $url, bool $enabled): string
    {
        if (! $enabled) {
            return 'Disabled';
        }

        if (trim($url) === '') {
            return 'No URL';
        }

        return 'Ready';
    }

    protected function publicUrl(string $value): string
    {
        $value = trim($value);

        if ($value === '') {
            return '';
        }

        if (Str::startsWith($value, ['http://', 'https://', '/'])) {
            return $value;
        }

        try {
            return Storage::disk('public')->exists($value)
                ? Storage::disk('public')->url($value)
                : $value;
        } catch (\Throwable $e) {
            return $value;
        }
    }
}
