<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppItem;
use App\Models\AppSection;
use App\Models\MediaAsset;
use App\Support\ActiveApp;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BeginnerDailyEditorController extends Controller
{
    public function edit(Request $request, string $kind): View
    {
        $kind = $this->cleanKind($kind);
        $item = $this->dailyItem($kind);

        abort_unless($item, 404);

        $returnTo = $this->sanitizeReturnTo($request->query('return'));

        return view('admin.beginner.daily.edit', [
            'kind' => $kind,
            'item' => $item,
            'payload' => is_array($item->payload_json) ? $item->payload_json : [],
            'mediaAssets' => $this->mediaAssets(),
            'returnTo' => $returnTo,
            'returnUrl' => $this->returnUrl($kind, $returnTo),
            'formatOptions' => $this->formatOptions(),
            'fontOptions' => $this->fontOptions(),
        ]);
    }

    public function update(Request $request, string $kind): RedirectResponse
    {
        $kind = $this->cleanKind($kind);
        $item = $this->dailyItem($kind);

        abort_unless($item, 404);

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'image_url' => ['nullable', 'string', 'max:1000'],
            'media_asset_id' => ['nullable', 'integer'],
            'image_file' => ['nullable', 'image', 'max:8192'],

            'mode' => ['nullable', 'in:auto,manual'],
            'background_mode' => ['nullable', 'in:image,gradient,solid'],
            'card_format' => ['nullable', 'string', 'max:80'],
            'font_family' => ['nullable', 'string', 'max:80'],
            'text_scale_mode' => ['nullable', 'in:auto,manual'],
            'text_color' => ['nullable', 'string', 'max:20'],
            'source_color' => ['nullable', 'string', 'max:20'],
            'bg_color' => ['nullable', 'string', 'max:20'],
            'bg_color_2' => ['nullable', 'string', 'max:20'],
            'accent_color' => ['nullable', 'string', 'max:20'],
            'font_size' => ['nullable', 'numeric', 'min:8', 'max:40'],
            'title_size' => ['nullable', 'numeric', 'min:10', 'max:64'],
            'font_weight' => ['nullable', 'string', 'max:10'],
            'source_weight' => ['nullable', 'string', 'max:10'],
            'highlight_phrases' => ['nullable', 'string', 'max:1000'],
            'highlight_color' => ['nullable', 'string', 'max:20'],
            'highlight_scale' => ['nullable', 'numeric', 'min:0.8', 'max:1.8'],
            'highlight_weight' => ['nullable', 'string', 'max:10'],
            'text_align' => ['nullable', 'in:left,center,right'],
            'vertical_align' => ['nullable', 'in:start,center,end'],
            'overlay_strength' => ['nullable', 'integer', 'min:0', 'max:100'],
            'content_width' => ['nullable', 'integer', 'min:45', 'max:100'],
            'card_padding' => ['nullable', 'integer', 'min:12', 'max:80'],
            'card_padding_x' => ['nullable', 'integer', 'min:8', 'max:120'],
            'card_padding_y' => ['nullable', 'integer', 'min:8', 'max:120'],
            'line_height' => ['nullable', 'numeric', 'min:1', 'max:2'],
            'text_shadow' => ['nullable', 'in:on,off,soft,strong'],
            'show_quote_mark' => ['nullable'],

            'ref' => ['nullable', 'string', 'max:255'],
            'verse' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'quote' => ['nullable', 'string'],
            'source' => ['nullable', 'string', 'max:255'],
            'return' => ['nullable', 'string', 'max:80'],
        ]);

        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);
        abort_unless($activeAppId > 0, 404);

        $imageUrl = trim((string) ($validated['image_url'] ?? ''));

        if ($imageUrl === '' && ! empty($validated['media_asset_id'])) {
            $asset = MediaAsset::query()
                ->where('type', 'image')
                ->where('is_active', true)
                ->where(function ($query) use ($activeAppId) {
                    $query->whereNull('app_id')->orWhere('app_id', $activeAppId);
                })
                ->where('id', (int) $validated['media_asset_id'])
                ->first();

            if ($asset) {
                $imageUrl = (string) ($asset->url ?: Storage::disk($asset->disk ?: 'public')->url($asset->path));
            }
        }

        if ($request->hasFile('image_file')) {
            $imageUrl = $this->storeUploadedImage($request, $activeAppId, $validated['title'], $kind);
        }

        $payload = [
            'home_kind' => $kind === 'scripture' ? 'daily_scripture' : 'daily_quote',
            'mode' => $validated['mode'] ?? 'manual',
            'background_mode' => $validated['background_mode'] ?? 'gradient',
            'card_format' => $validated['card_format'] ?? 'portrait',
            'font_family' => $validated['font_family'] ?? 'system',
            'text_scale_mode' => $validated['text_scale_mode'] ?? 'auto',
            'text_color' => $validated['text_color'] ?? '#ffffff',
            'source_color' => $validated['source_color'] ?? ($validated['accent_color'] ?? '#38bdf8'),
            'bg_color' => $validated['bg_color'] ?? '#160042',
            'bg_color_2' => $validated['bg_color_2'] ?? '#e2388a',
            'accent_color' => $validated['accent_color'] ?? '#38bdf8',
            'font_size' => (string) ($validated['font_size'] ?? 14),
            'title_size' => (string) ($validated['title_size'] ?? 24),
            'font_weight' => (string) ($validated['font_weight'] ?? '700'),
            'source_weight' => (string) ($validated['source_weight'] ?? '700'),
            'highlight_phrases' => trim((string) ($validated['highlight_phrases'] ?? '')),
            'highlight_rules' => $this->highlightRulesFromRequest($validated),
            'highlight_color' => $validated['highlight_color'] ?? ($validated['accent_color'] ?? '#facc15'),
            'highlight_scale' => (string) ($validated['highlight_scale'] ?? '1.08'),
            'highlight_weight' => (string) ($validated['highlight_weight'] ?? '800'),
            'text_align' => $validated['text_align'] ?? 'center',
            'vertical_align' => $validated['vertical_align'] ?? 'center',
            'overlay_strength' => (string) ($validated['overlay_strength'] ?? 58),
            'content_width' => (string) ($validated['content_width'] ?? 86),
            'card_padding' => (string) ($validated['card_padding'] ?? 34),
            'card_padding_x' => (string) ($validated['card_padding_x'] ?? ($validated['card_padding'] ?? 34)),
            'card_padding_y' => (string) ($validated['card_padding_y'] ?? ($validated['card_padding'] ?? 34)),
            'line_height' => (string) ($validated['line_height'] ?? 1.35),
            'text_shadow' => $validated['text_shadow'] ?? 'soft',
            'show_quote_mark' => $request->boolean('show_quote_mark'),
            'quote_size' => (string) ($validated['title_size'] ?? 24),
            'source_size' => (string) ($validated['font_size'] ?? 14),
            'background_color' => $validated['bg_color'] ?? '#160042',
            'gradient_start' => $validated['bg_color'] ?? '#160042',
            'gradient_end' => $validated['bg_color_2'] ?? '#e2388a',
            'padding_x' => (string) ($validated['card_padding_x'] ?? ($validated['card_padding'] ?? 34)),
            'padding_y' => (string) ($validated['card_padding_y'] ?? ($validated['card_padding'] ?? 34)),
        ];

        if ($kind === 'scripture') {
            $payload['ref'] = $validated['ref'] ?? '';
            $payload['verse'] = $validated['verse'] ?? '';
            $payload['note'] = $validated['note'] ?? '';
            $payload['quote_text'] = $payload['verse'];
            $payload['quote_source'] = $payload['ref'];
            $payload['source'] = $payload['ref'];
        } else {
            $payload['quote'] = $validated['quote'] ?? '';
            $payload['source'] = $validated['source'] ?? '';
            $payload['quote_text'] = $payload['quote'];
            $payload['quote_source'] = $payload['source'];
        }

        $item->update([
            'title' => $validated['title'],
            'image_url' => $imageUrl,
            'payload_json' => $payload,
            'sort_order' => 10,
            'is_enabled' => true,
        ]);

        $this->removeDailyDuplicates($kind, $item->id);

        $returnTo = $this->sanitizeReturnTo($validated['return'] ?? null);

        $redirectUrl = $this->returnUrl($kind, $returnTo);

        if ($returnTo !== 'quote-engine') {
            return redirect()
                ->route('admin.beginner.daily.edit', ['kind' => $kind, 'return' => $returnTo === 'home' ? null : $returnTo])
                ->with('status', ucfirst($kind) . ' updated successfully.');
        }

        return redirect($redirectUrl)
            ->with('status', ucfirst($kind) . ' updated successfully.');
    }


    private function highlightRulesFromRequest(array $validated): array
    {
        $raw = trim((string) ($validated['highlight_phrases'] ?? ''));

        if ($raw === '') {
            return [];
        }

        $phrases = collect(preg_split('/[\r\n,]+/', $raw) ?: [])
            ->map(fn ($value) => trim((string) $value))
            ->filter()
            ->unique()
            ->values()
            ->take(12)
            ->all();

        if (empty($phrases)) {
            return [];
        }

        return [[
            'phrases' => $phrases,
            'color' => $validated['highlight_color'] ?? ($validated['accent_color'] ?? '#facc15'),
            'scale' => (string) ($validated['highlight_scale'] ?? '1.08'),
            'weight' => (string) ($validated['highlight_weight'] ?? '800'),
        ]];
    }

    private function dailyItem(string $kind): ?AppItem
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);
        abort_unless($activeAppId > 0, 404);

        $sectionKey = $kind === 'scripture' ? 'home_daily_scripture' : 'home_daily_quote';
        $homeKind = $kind === 'scripture' ? 'daily_scripture' : 'daily_quote';

        $section = AppSection::query()
            ->where('app_id', $activeAppId)
            ->where('key', $sectionKey)
            ->first();

        if (! $section) {
            return null;
        }

        $item = AppItem::query()
            ->where('section_id', $section->id)
            ->orderByDesc('id')
            ->get()
            ->first(function (AppItem $item) use ($homeKind) {
                $payload = is_array($item->payload_json) ? $item->payload_json : [];
                return ($payload['home_kind'] ?? null) === $homeKind;
            });

        if ($item) {
            $this->removeDailyDuplicates($kind, $item->id);
            return $item;
        }

        return AppItem::create([
            'section_id' => $section->id,
            'type' => 'link',
            'title' => $kind === 'scripture' ? 'Daily Scripture' : 'Daily Quote',
            'image_url' => null,
            'payload_json' => [
                'home_kind' => $homeKind,
                'mode' => 'manual',
                'background_mode' => 'gradient',
                'card_format' => 'portrait',
                'font_family' => 'system',
                'text_scale_mode' => 'auto',
                'text_color' => '#ffffff',
                'bg_color' => '#160042',
                'bg_color_2' => '#e2388a',
                'accent_color' => '#38bdf8',
                'font_size' => '14',
                'title_size' => '24',
                'font_weight' => '700',
                'text_align' => 'center',
                'vertical_align' => 'center',
                'overlay_strength' => '58',
                'content_width' => '86',
                'card_padding' => '34',
                'card_padding_x' => '34',
                'card_padding_y' => '34',
                'line_height' => '1.35',
                'text_shadow' => 'soft',
                'show_quote_mark' => true,
                'quote_size' => '24',
                'source_size' => '14',
                'source_color' => '#38bdf8',
                'source_weight' => '700',
                'highlight_phrases' => '',
                'highlight_rules' => [],
                'highlight_color' => '#facc15',
                'highlight_scale' => '1.08',
                'highlight_weight' => '800',
                'background_color' => '#160042',
                'gradient_start' => '#160042',
                'gradient_end' => '#e2388a',
                'padding_x' => '34',
                'padding_y' => '34',
            ],
            'sort_order' => 10,
            'is_enabled' => true,
        ]);
    }

    private function removeDailyDuplicates(string $kind, int $keepItemId): void
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);
        $sectionKey = $kind === 'scripture' ? 'home_daily_scripture' : 'home_daily_quote';

        $sectionIds = AppSection::query()
            ->where('app_id', $activeAppId)
            ->where('key', $sectionKey)
            ->pluck('id');

        if ($sectionIds->isEmpty()) {
            return;
        }

        AppItem::query()
            ->whereIn('section_id', $sectionIds)
            ->where('id', '!=', $keepItemId)
            ->delete();
    }

    private function mediaAssets()
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);

        return MediaAsset::query()
            ->where('type', 'image')
            ->where('is_active', true)
            ->where(function ($query) use ($activeAppId) {
                $query->whereNull('app_id')->orWhere('app_id', $activeAppId);
            })
            ->latest()
            ->get();
    }

    private function storeUploadedImage(Request $request, int $activeAppId, string $title, string $kind): string
    {
        $file = $request->file('image_file');

        if (! $file) {
            return '';
        }

        $safeTitle = Str::slug($title ?: ('daily-' . $kind)) ?: 'daily-image';
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $extension : 'jpg';
        $filename = $safeTitle . '-' . now()->format('YmdHis') . '-' . Str::lower(Str::random(8)) . '.' . $extension;

        $path = $file->storeAs('assets/app-' . $activeAppId . '/daily', $filename, 'public');
        $url = Storage::disk('public')->url($path);

        $width = null;
        $height = null;

        try {
            $size = getimagesize($file->getRealPath());
            if (is_array($size)) {
                $width = $size[0] ?? null;
                $height = $size[1] ?? null;
            }
        } catch (\Throwable $e) {
            $width = null;
            $height = null;
        }

        MediaAsset::create([
            'app_id' => $activeAppId,
            'type' => 'image',
            'label' => $title ?: ('Daily ' . ucfirst($kind) . ' Image'),
            'bucket' => 'daily',
            'disk' => 'public',
            'path' => $path,
            'url' => $url,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'tags_json' => [
                'source' => 'beginner_daily_editor',
                'usage' => 'daily_' . $kind,
            ],
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return $url;
    }

    private function formatOptions(): array
    {
        return [
            'portrait' => ['label' => 'Portrait 4:5', 'ratio' => '4 / 5'],
            'square' => ['label' => 'Square 1:1', 'ratio' => '1 / 1'],
            'story' => ['label' => 'Story 9:16', 'ratio' => '9 / 16'],
            'landscape' => ['label' => 'Landscape 16:9', 'ratio' => '16 / 9'],
            'wide' => ['label' => 'Wide Banner 21:9', 'ratio' => '21 / 9'],
            'classic' => ['label' => 'Classic 3:2', 'ratio' => '3 / 2'],
        ];
    }

    private function fontOptions(): array
    {
        return [
            'system' => 'System Sans',
            'arial' => 'Arial',
            'inter' => 'Inter / Modern Sans',
            'verdana' => 'Verdana',
            'tahoma' => 'Tahoma',
            'trebuchet' => 'Trebuchet MS',
            'serif' => 'Classic Serif',
            'georgia' => 'Georgia',
            'times' => 'Times New Roman',
            'garamond' => 'Garamond',
            'impact' => 'Impact / Bold Poster',
            'arial_black' => 'Arial Black',
            'mono' => 'Monospace',
            'courier' => 'Courier New',
        ];
    }

    private function sanitizeReturnTo(mixed $value): string
    {
        $value = str_replace('_', '-', strtolower(trim((string) $value)));

        return match ($value) {
            'quote-engine' => 'quote-engine',
            default => 'home',
        };
    }

    private function returnUrl(string $kind, string $returnTo): string
    {
        if ($returnTo === 'quote-engine') {
            $bucket = $kind === 'scripture' ? 'daily_scriptures' : 'daily_quotes';

            return route('admin.beginner.content-posts.channel', [
                'bucket' => $bucket,
                'return' => 'quote-engine',
            ]);
        }

        return '/admin/beginner-dashboard?tab=home';
    }

    private function cleanKind(string $kind): string
    {
        return $kind === 'quote' ? 'quote' : 'scripture';
    }
}

