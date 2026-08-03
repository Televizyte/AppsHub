<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppItem;
use App\Models\AppSection;
use App\Models\ContentPost;
use App\Support\ActiveApp;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Str;

class BeginnerContentChannelController extends Controller
{
    public function index(Request $request, string $bucket): View
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);
        abort_unless($activeAppId > 0, 404);

        $bucket = $this->normalizeBucket($bucket);
        $channels = $this->channels();
        abort_unless(array_key_exists($bucket, $channels) || $this->isQuoteBucket($bucket), 404);

        $itemId = (int) $request->query('item_id', 0);
        $item = null;

        if ($itemId > 0) {
            $item = AppItem::query()
                ->with('section')
                ->where('id', $itemId)
                ->whereHas('section', fn ($q) => $q->where('app_id', $activeAppId))
                ->first();
        }

        $returnTo = $this->sanitizeReturnTo($request->query('return'), $item, $bucket);

        $posts = ContentPost::query()
            ->where('app_id', $activeAppId)
            ->where('bucket', $bucket)
            ->orderBy('sort_order')
            ->orderByDesc('published_at')
            ->orderByDesc('publish_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->appends($request->query());

        return view('admin.beginner.content-posts.channel', [
            'bucket' => $bucket,
            'bucketLabel' => $channels[$bucket] ?? $this->labelForBucket($bucket),
            'channels' => $channels,
            'posts' => $posts,
            'item' => $item,
            'itemId' => $itemId,
            'returnTo' => $returnTo,
            'returnUrl' => $this->returnUrl($item, $bucket, $returnTo),
            'createUrl' => $this->createUrl($bucket, $itemId, $item, $returnTo),
            'isQuoteDesignerBucket' => $this->isQuoteBucket($bucket),
            'isShortVideoBucket' => $bucket === 'short_videos',
            'activeDailyCard' => $this->activeDailyCard($bucket),
        ]);
    }

    private function createUrl(string $bucket, int $itemId, ?AppItem $item, string $returnTo): string
    {
        if ($this->isQuoteBucket($bucket)) {
            return route('admin.beginner.quote-designer.create', [
                'bucket' => $bucket,
                'item_id' => $itemId ?: null,
                'return' => $returnTo,
            ]);
        }

        return route('admin.beginner.content-posts.create', [
            'bucket' => $bucket,
            'item_id' => $itemId ?: null,
            'return' => $returnTo,
        ]);
    }

    private function labelForBucket(string $bucket): string
{
    $bucket = $this->normalizeBucket($bucket);

    return match ($bucket) {
        'daily_quotes' => 'Daily Quote Manager',
        'daily_scriptures' => 'Daily Scripture Manager',
        'sod_quotes' => 'SOD Quote Manager',
        'motivational_quotes' => 'Motivational Quote Manager',
        'article_quotes' => 'Article Quote Manager',
        'custom_quotes' => 'Custom Quote Manager',
        default => Str::headline(preg_replace('/^quote_|_quotes$/', '', $bucket)) . ' Manager',
    };
}

private function normalizeBucket(string $bucket): string
{
    $bucket = strtolower(trim($bucket));
    $bucket = str_replace(['-', ' '], '_', $bucket);
    $bucket = preg_replace('/[^a-z0-9_]/', '', $bucket) ?: '';

    return match ($bucket) {
        'daily_quote', 'daily_quotes', 'daily_quotes_quotes' => 'daily_quotes',
        'daily_scripture', 'daily_scriptures', 'scripture', 'scriptures' => 'daily_scriptures',
        'sod', 'sod_quote', 'sod_quotes', 'quote', 'quotes' => 'sod_quotes',
        'motivation', 'motivation_quote', 'motivation_quotes', 'motivational_quote', 'motivational_quotes' => 'motivational_quotes',
        'article_quote', 'article_quotes' => 'article_quotes',
        'custom_quote', 'custom_quotes' => 'custom_quotes',
        default => $bucket !== '' ? $bucket : 'sod_quotes',
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

            'daily_quotes' => 'Daily Quote Manager',
            'daily_scriptures' => 'Daily Scripture Manager',
            'sod_quotes' => 'SOD Quote Manager',
            'motivational_quotes' => 'Motivational Quote Manager',
            'article_quotes' => 'Article Quote Manager',
            'custom_quotes' => 'Custom Quote Manager',

            'short_videos' => 'Short Videos',
        ];
    }

    private function isQuoteBucket(string $bucket): bool
    {
        return str_ends_with($bucket, '_quotes')
            || $bucket === 'daily_scriptures'
            || str_starts_with($bucket, 'quote_');
    }


    private function activeDailyCard(string $bucket): ?array
    {
        if (! in_array($bucket, ['daily_quotes', 'daily_scriptures'], true)) {
            return null;
        }

        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);

        if ($activeAppId <= 0) {
            return null;
        }

        $kind = $bucket === 'daily_scriptures' ? 'scripture' : 'quote';
        $sectionKey = $kind === 'scripture' ? 'home_daily_scripture' : 'home_daily_quote';
        $homeKind = $kind === 'scripture' ? 'daily_scripture' : 'daily_quote';

        $section = AppSection::query()
            ->where('app_id', $activeAppId)
            ->where('key', $sectionKey)
            ->first();

        if (! $section) {
            return null;
        }

        $items = AppItem::query()
            ->where('section_id', $section->id)
            ->orderByDesc('id')
            ->get();

        $item = $items->first(function (AppItem $row) use ($homeKind) {
            $payload = is_array($row->payload_json) ? $row->payload_json : [];

            return ($payload['home_kind'] ?? null) === $homeKind
                || ($payload['kind'] ?? null) === $homeKind
                || ($payload['type'] ?? null) === $homeKind;
        });

        $item ??= $items->first();

        if (! $item) {
            return null;
        }

        $payload = is_array($item->payload_json) ? $item->payload_json : [];
        $meta = is_array($item->meta_json ?? null) ? $item->meta_json : [];
        $data = array_merge($meta, $payload);

        $quoteText = $kind === 'scripture'
            ? $this->firstText($data, ['verse', 'scripture', 'scripture_text', 'main_text', 'text', 'quote', 'quote_text'])
            : $this->firstText($data, ['quote', 'quote_text', 'main_text', 'text', 'verse']);

        if ($quoteText === '') {
            return null;
        }

        $source = $kind === 'scripture'
            ? $this->firstText($data, ['ref', 'reference', 'scripture_reference', 'source', 'quote_source'])
            : $this->firstText($data, ['source', 'quote_source', 'ref', 'reference']);

        return [
            'id' => $item->id,
            'kind' => $kind,
            'title' => $kind === 'scripture' ? 'Active Daily Scripture' : 'Active Daily Quote',
            'quote_text' => $quoteText,
            'quote_source' => $source,
            'payload' => $data,
            'edit_url' => route('admin.beginner.daily.edit', [
                'kind' => $kind,
                'return' => 'quote-engine',
            ]),
        ];
    }

    private function firstText(array $data, array $keys): string
    {
        foreach ($keys as $key) {
            $value = $data[$key] ?? null;

            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }

            if (is_numeric($value)) {
                return trim((string) $value);
            }
        }

        return '';
    }

    private function sanitizeReturnTo(mixed $value, ?AppItem $item, string $bucket): string
    {
        $value = str_replace('_', '-', strtolower(trim((string) $value)));

        if ($value === 'quote-engine' && $this->isQuoteBucket($bucket)) {
            return 'quote-engine';
        }

        if ($value === 'short-video-engine' && $bucket === 'short_videos') {
            return 'short-video-engine';
        }

        if ($item && $value === 'item') {
            return 'item';
        }

        return match ($value) {
            'channels' => 'channels',
            'destination' => 'destination',
            'short-video-engine' => $bucket === 'short_videos' ? 'short-video-engine' : 'channels',
            default => $item ? 'item' : ($this->isQuoteBucket($bucket) ? 'quote-engine' : ($bucket === 'short_videos' ? 'short-video-engine' : 'channels')),
        };
    }

    private function returnUrl(?AppItem $item, string $bucket, string $returnTo): string
    {
        if ($returnTo === 'quote-engine' && $this->isQuoteBucket($bucket)) {
            return '/admin/quote-engine?quote_tab=' . urlencode($this->tabForBucket($bucket));
        }

        if ($returnTo === 'short-video-engine' && $bucket === 'short_videos') {
            return '/admin/short-video-engine?tab=library';
        }

        if ($item && $item->section && $returnTo === 'item') {
            return route('admin.beginner.items.edit', [
                'appItem' => $item->id,
                'tab' => $item->section->tab_key,
                'return' => 'dashboard',
            ]);
        }

        if ($returnTo === 'destination') {
            return '/admin/destination-builder?tab=inspire';
        }

        return '/admin/content-channels';
    }

    private function tabForBucket(string $bucket): string
    {
        return match ($bucket) {
            'daily_quotes' => 'daily_quote',
            'daily_scriptures' => 'daily_scripture',
            'sod_quotes' => 'sod_quotes',
            'motivational_quotes' => 'motivational_quotes',
            'article_quotes' => 'article_quotes',
            'custom_quotes' => 'custom_quotes',
            default => str_starts_with($bucket, 'quote_') ? 'custom_' . substr($bucket, 6) : 'all',
        };
    }
}

