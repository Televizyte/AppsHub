<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AppItem;
use App\Models\ContentPost;
use App\Models\MediaAsset;
use App\Support\ActiveApp;
use App\Support\Scheduling\AdminScheduleTime;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BeginnerQuoteDesignerController extends Controller
{
    public function create(Request $request): View
    {
        $activeAppId = $this->activeAppId();
        $bucket = $this->normalizeBucket((string) $request->query('bucket', 'sod_quotes'));
        abort_unless($this->isQuoteBucket($bucket), 404);

        $item = $this->resolveItem($activeAppId, (int) $request->query('item_id', 0));
        $returnTo = $this->sanitizeReturnTo($request->query('return'), $item);

        $post = new ContentPost([
            'app_id' => $activeAppId,
            'bucket' => $bucket,
            'status' => 'draft',
            'title' => '',
            'subtitle' => '',
            'cover_image_url' => null,
            'body_html' => null,
            'is_featured' => false,
            'sort_order' => 0,
            'meta_json' => $this->defaultDesignerMeta($bucket),
        ]);

        return view('admin.beginner.content-posts.quote-designer', [
            'mode' => 'create',
            'post' => $post,
            'bucket' => $bucket,
            'bucketLabel' => $this->labelForBucket($bucket),
            'mediaAssets' => $this->mediaAssets($activeAppId),
            'item' => $item,
            'itemId' => $item?->id,
            'returnTo' => $returnTo,
            'returnUrl' => $this->returnUrl($returnTo, $bucket, $item),
            'saveUrl' => route('admin.beginner.quote-designer.store'),
            'methodField' => null,
            'statusOptions' => $this->statusOptions(),
            'formatOptions' => $this->formatOptions(),
            'fontOptions' => $this->fontOptions(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $activeAppId = $this->activeAppId();
        $validated = $this->validateDesigner($request);
        $bucket = $this->normalizeBucket((string) ($validated['bucket'] ?? 'sod_quotes'));
        abort_unless($this->isQuoteBucket($bucket), 422);

        $quoteText = trim((string) ($validated['quote_text'] ?? ''));
        $source = trim((string) ($validated['quote_source'] ?? ''));
        $title = $this->resolveTitle((string) ($validated['title'] ?? ''), $quoteText, $source);

        $item = $this->resolveItem($activeAppId, (int) ($validated['item_id'] ?? 0));
        $returnTo = $this->sanitizeReturnTo($validated['return'] ?? null, $item);
        $meta = $this->designerMetaFromRequest($request, $validated, $bucket, $item, $title);
        $imageUrl = $this->resolveImageUrl($request, $activeAppId, $bucket, $title, null);
        $status = (string) ($validated['status'] ?? 'draft');
        $publishAt = AdminScheduleTime::toUtc($validated['publish_at'] ?? null);
        $publishedAt = AdminScheduleTime::toUtc($validated['published_at'] ?? null);

        $post = ContentPost::create([
            'app_id' => $activeAppId,
            'bucket' => $bucket,
            'status' => $status,
            'title' => $title,
            'subtitle' => $this->designerSubtitleValue($bucket, $validated['subtitle'] ?? null, $source),
            'slug' => $this->uniqueSlug($activeAppId, $title),
            'cover_image_url' => $imageUrl,
            'body_html' => $this->quoteBodyHtml($quoteText, $source),
            'blocks_json' => [['type' => 'quote_card', 'quote' => $quoteText, 'source' => $source, 'meta' => $meta]],
            'tags_json' => $this->tagsFromInput((string) ($validated['tags'] ?? '')),
            'meta_json' => $meta,
            'publish_at' => $publishAt,
            'published_at' => $status === 'published' ? $publishedAt : null,
            'author_name' => $source !== '' ? $source : null,
            'author_user_id' => null,
            'is_featured' => (bool) ($validated['is_featured'] ?? false),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        return redirect($this->returnUrl($returnTo, $bucket, $item))
            ->with('status', 'Quote design created successfully: ' . $post->title);
    }

    public function edit(Request $request, ContentPost $contentPost): View
    {
        $activeAppId = $this->activeAppId();
        abort_unless((int) $contentPost->app_id === $activeAppId, 404);

        $bucket = $this->normalizeBucket((string) $contentPost->bucket);
        abort_unless($this->isQuoteBucket($bucket), 404);

        $item = $this->resolveItem($activeAppId, (int) $request->query('item_id', 0));
        $returnTo = $this->sanitizeReturnTo($request->query('return'), $item);

        return view('admin.beginner.content-posts.quote-designer', [
            'mode' => 'edit',
            'post' => $contentPost,
            'bucket' => $bucket,
            'bucketLabel' => $this->labelForBucket($bucket),
            'mediaAssets' => $this->mediaAssets($activeAppId),
            'item' => $item,
            'itemId' => $item?->id,
            'returnTo' => $returnTo,
            'returnUrl' => $this->returnUrl($returnTo, $bucket, $item),
            'saveUrl' => route('admin.beginner.quote-designer.update', ['contentPost' => $contentPost->id]),
            'methodField' => 'PUT',
            'statusOptions' => $this->statusOptions(),
            'formatOptions' => $this->formatOptions(),
            'fontOptions' => $this->fontOptions(),
        ]);
    }

    public function update(Request $request, ContentPost $contentPost): RedirectResponse
    {
        $activeAppId = $this->activeAppId();
        abort_unless((int) $contentPost->app_id === $activeAppId, 404);

        if ($request->input('_designer_action') === 'delete') {
            return $this->deleteDesignerPost($request, $contentPost, $activeAppId);
        }

        if ($request->input('_designer_action') === 'duplicate') {
            return $this->duplicateDesignerPost($request, $contentPost, $activeAppId);
        }

        $validated = $this->validateDesigner($request);
        $bucket = $this->normalizeBucket((string) ($validated['bucket'] ?? $contentPost->bucket));
        abort_unless($this->isQuoteBucket($bucket), 422);

        $quoteText = trim((string) ($validated['quote_text'] ?? ''));
        $source = trim((string) ($validated['quote_source'] ?? ''));
        $title = $this->resolveTitle((string) ($validated['title'] ?? ''), $quoteText, $source);

        $item = $this->resolveItem($activeAppId, (int) ($validated['item_id'] ?? 0));
        $returnTo = $this->sanitizeReturnTo($validated['return'] ?? null, $item);
        $meta = $this->designerMetaFromRequest($request, $validated, $bucket, $item, $title);
        $imageUrl = $this->resolveImageUrl($request, $activeAppId, $bucket, $title, $contentPost->cover_image_url);
        $status = (string) ($validated['status'] ?? 'draft');
        $publishAt = AdminScheduleTime::toUtc($validated['publish_at'] ?? null);
        $publishedAt = AdminScheduleTime::toUtc($validated['published_at'] ?? null) ?? $contentPost->published_at;

        if ($status === 'published' && empty($publishedAt)) {
            $publishedAt = now();
        }

        if ($status !== 'published' && $request->boolean('clear_published_at')) {
            $publishedAt = null;
        }

        $contentPost->fill([
            'bucket' => $bucket,
            'status' => $status,
            'title' => $title,
            'subtitle' => $this->designerSubtitleValue($bucket, $validated['subtitle'] ?? null, $source),
            'cover_image_url' => $imageUrl,
            'body_html' => $this->quoteBodyHtml($quoteText, $source),
            'blocks_json' => [['type' => 'quote_card', 'quote' => $quoteText, 'source' => $source, 'meta' => $meta]],
            'tags_json' => $this->tagsFromInput((string) ($validated['tags'] ?? '')),
            'meta_json' => $meta,
            'publish_at' => $publishAt,
            'published_at' => $publishedAt,
            'author_name' => $source !== '' ? $source : null,
            'is_featured' => (bool) ($validated['is_featured'] ?? false),
            'sort_order' => (int) ($validated['sort_order'] ?? 0),
        ]);

        $contentPost->save();

        return redirect($this->returnUrl($returnTo, $bucket, $item))
            ->with('status', 'Quote design updated successfully.');
    }

    private function designerSubtitleValue(string $bucket, ?string $subtitle, ?string $source): ?string
    {
        $subtitle = trim((string) $subtitle);
        $source = trim((string) $source);

        if ($subtitle !== '') {
            return $subtitle;
        }

        if ($source !== '') {
            return $source;
        }

        return null;
    }

    private function validateDesigner(Request $request): array
    {
        return $request->validate([
            'bucket' => ['required', 'string', 'max:80'],
            'title' => ['nullable', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'quote_text' => ['required', 'string'],
            'quote_source' => ['nullable', 'string', 'max:255'],
            'status' => ['required', 'in:draft,published,archived'],
            'sort_order' => ['nullable', 'integer'],
            'is_featured' => ['nullable', 'boolean'],
            'publish_at' => ['nullable', 'date'],
            'published_at' => ['nullable', 'date'],
            'clear_published_at' => ['nullable', 'boolean'],
            'background_mode' => ['nullable', 'in:image,solid,gradient'],
            'cover_image_url' => ['nullable', 'string', 'max:1000'],
            'cover_image_file' => ['nullable', 'image', 'max:8192'],
            'media_asset_id' => ['nullable', 'integer', 'exists:media_assets,id'],
            'bg_color' => ['nullable', 'string', 'max:20'],
            'bg_color_2' => ['nullable', 'string', 'max:20'],
            'text_color' => ['nullable', 'string', 'max:20'],
            'source_color' => ['nullable', 'string', 'max:20'],
            'accent_color' => ['nullable', 'string', 'max:20'],
            'overlay_strength' => ['nullable', 'integer', 'min:0', 'max:95'],
            'text_align' => ['nullable', 'in:left,center,right'],
            'vertical_align' => ['nullable', 'in:start,center,end'],
            'font_weight' => ['nullable', 'string', 'max:10'],
            'quote_size' => ['nullable', 'integer', 'min:10', 'max:96'],
            'source_size' => ['nullable', 'integer', 'min:8', 'max:48'],
            'source_weight' => ['nullable', 'string', 'max:10'],
            'highlight_phrases' => ['nullable', 'string', 'max:1000'],
            'highlight_color' => ['nullable', 'string', 'max:20'],
            'highlight_scale' => ['nullable', 'numeric', 'min:0.8', 'max:2.4'],
            'highlight_weight' => ['nullable', 'string', 'max:10'],
            'style_preset' => ['nullable', 'string', 'max:80'],
            'show_quote_mark' => ['nullable', 'boolean'],
            'card_format' => ['nullable', 'string', 'max:40'],
            'font_family' => ['nullable', 'string', 'max:80'],
            'text_scale_mode' => ['nullable', 'in:auto,manual'],
            'content_width' => ['nullable', 'integer', 'min:30', 'max:100'],
            'card_padding' => ['nullable', 'integer', 'min:0', 'max:220'],
            'card_padding_x' => ['nullable', 'integer', 'min:0', 'max:220'],
            'card_padding_y' => ['nullable', 'integer', 'min:0', 'max:220'],
            'line_height' => ['nullable', 'numeric', 'min:0.8', 'max:2.4'],
            'text_shadow' => ['nullable', 'in:on,off,soft,strong'],
            'offset_x' => ['nullable', 'integer', 'min:-240', 'max:240'],
            'offset_y' => ['nullable', 'integer', 'min:-240', 'max:240'],
            'letter_spacing' => ['nullable', 'numeric', 'min:-1', 'max:8'],
            'source_spacing' => ['nullable', 'integer', 'min:0', 'max:100'],
            'quote_mark_size' => ['nullable', 'integer', 'min:24', 'max:220'],
            'quote_mark_opacity' => ['nullable', 'integer', 'min:0', 'max:80'],
            'quote_mark_position' => ['nullable', 'in:top_left,top_right,center_left,center_right,bottom_left,bottom_right'],
            'background_fit' => ['nullable', 'in:cover,contain,fill'],
            'background_position' => ['nullable', 'string', 'max:40'],
            'overlay_style' => ['nullable', 'in:bottom,full,none,top_bottom'],
            'border_radius' => ['nullable', 'integer', 'min:0', 'max:80'],
            'tags' => ['nullable', 'string', 'max:500'],
            'item_id' => ['nullable', 'integer'],
            'return' => ['nullable', 'string'],
        ]);
    }

    private function designerMetaFromRequest(Request $request, array $validated, string $bucket, ?AppItem $item, string $title): array
    {
        $format = $validated['card_format'] ?? 'portrait';
        $formatData = $this->formatOptions()[$format] ?? $this->formatOptions()['portrait'];

        $meta = [
            'designer_type' => 'quote_card',
            'designer_version' => '2.0',
            'beginner_bucket' => $bucket,
            'resolved_title' => $title,
            'quote_text' => trim((string) ($validated['quote_text'] ?? '')),
            'quote_source' => trim((string) ($validated['quote_source'] ?? '')),
            'background_mode' => $validated['background_mode'] ?? 'image',
            'bg_color' => $validated['bg_color'] ?? '#160042',
            'bg_color_2' => $validated['bg_color_2'] ?? '#e2388a',
            'text_color' => $validated['text_color'] ?? '#ffffff',
            'source_color' => $validated['source_color'] ?? ($validated['accent_color'] ?? '#38bdf8'),
            'accent_color' => $validated['accent_color'] ?? '#38bdf8',
            'overlay_strength' => (string) ($validated['overlay_strength'] ?? 58),
            'text_align' => $validated['text_align'] ?? 'center',
            'vertical_align' => $validated['vertical_align'] ?? 'center',
            'font_weight' => (string) ($validated['font_weight'] ?? '700'),
            'quote_size' => (string) ($validated['quote_size'] ?? 24),
            'source_size' => (string) ($validated['source_size'] ?? 14),
            'source_weight' => (string) ($validated['source_weight'] ?? '700'),
            'highlight_phrases' => trim((string) ($validated['highlight_phrases'] ?? '')),
            'highlight_rules' => $this->highlightRulesFromRequest($validated),
            'highlight_color' => $validated['highlight_color'] ?? ($validated['accent_color'] ?? '#38bdf8'),
            'highlight_scale' => (string) ($validated['highlight_scale'] ?? '1.08'),
            'highlight_weight' => (string) ($validated['highlight_weight'] ?? '800'),
            'style_preset' => $validated['style_preset'] ?? 'royal',
            'show_quote_mark' => $request->boolean('show_quote_mark'),
            'card_format' => $format,
            'format_ratio' => $formatData['ratio'],
            'format_label' => $formatData['label'],
            'font_family' => $validated['font_family'] ?? 'system',
            'font_key' => $validated['font_family'] ?? 'system',
            'text_scale_mode' => $validated['text_scale_mode'] ?? 'auto',
            'content_width' => (string) ($validated['content_width'] ?? 86),
            'card_padding' => (string) ($validated['card_padding'] ?? 34),
            'card_padding_x' => (string) ($validated['card_padding_x'] ?? ($validated['card_padding'] ?? 34)),
            'card_padding_y' => (string) ($validated['card_padding_y'] ?? ($validated['card_padding'] ?? 34)),
            'line_height' => (string) ($validated['line_height'] ?? 1.35),
            'text_shadow' => $validated['text_shadow'] ?? 'soft',
            'offset_x' => (string) ($validated['offset_x'] ?? 0),
            'offset_y' => (string) ($validated['offset_y'] ?? 0),
            'letter_spacing' => (string) ($validated['letter_spacing'] ?? 0),
            'source_spacing' => (string) ($validated['source_spacing'] ?? 18),
            'quote_mark_size' => (string) ($validated['quote_mark_size'] ?? 96),
            'quote_mark_opacity' => (string) ($validated['quote_mark_opacity'] ?? 16),
            'quote_mark_position' => $validated['quote_mark_position'] ?? 'top_left',
            'background_fit' => $validated['background_fit'] ?? 'cover',
            'background_position' => $validated['background_position'] ?? 'center',
            'overlay_style' => $validated['overlay_style'] ?? 'bottom',
            'border_radius' => (string) ($validated['border_radius'] ?? 26),
            'source' => trim((string) ($validated['quote_source'] ?? '')),
            'quote' => trim((string) ($validated['quote_text'] ?? '')),
            'source_size' => (string) ($validated['source_size'] ?? 14),
            'background_color' => $validated['bg_color'] ?? '#160042',
            'gradient_start' => $validated['bg_color'] ?? '#160042',
            'gradient_end' => $validated['bg_color_2'] ?? '#e2388a',
            'padding_x' => (string) ($validated['card_padding_x'] ?? ($validated['card_padding'] ?? 34)),
            'padding_y' => (string) ($validated['card_padding_y'] ?? ($validated['card_padding'] ?? 34)),
            'dxm_design_schema' => 'quote_card_v2_1',
            'design' => [
                'version' => 'quote_card_v2_1',
                'format' => $format,
                'background' => [
                    'mode' => $validated['background_mode'] ?? 'image',
                    'color' => $validated['bg_color'] ?? '#160042',
                    'color2' => $validated['bg_color_2'] ?? '#e2388a',
                    'fit' => $validated['background_fit'] ?? 'cover',
                    'position' => $validated['background_position'] ?? 'center',
                    'overlay_strength' => (int) ($validated['overlay_strength'] ?? 58),
                    'overlay_style' => $validated['overlay_style'] ?? 'bottom',
                ],
                'text' => [
                    'font_key' => $validated['font_family'] ?? 'system',
                    'font_family' => $validated['font_family'] ?? 'system',
                    'font_size' => (int) ($validated['quote_size'] ?? 24),
                    'font_weight' => (string) ($validated['font_weight'] ?? '700'),
                    'color' => $validated['text_color'] ?? '#ffffff',
                    'align' => $validated['text_align'] ?? 'center',
                    'vertical_align' => $validated['vertical_align'] ?? 'center',
                    'content_width' => (int) ($validated['content_width'] ?? 86),
                    'padding_x' => (int) ($validated['card_padding_x'] ?? ($validated['card_padding'] ?? 34)),
                    'padding_y' => (int) ($validated['card_padding_y'] ?? ($validated['card_padding'] ?? 34)),
                    'offset_x' => (int) ($validated['offset_x'] ?? 0),
                    'offset_y' => (int) ($validated['offset_y'] ?? 0),
                    'line_height' => (float) ($validated['line_height'] ?? 1.35),
                    'letter_spacing' => (float) ($validated['letter_spacing'] ?? 0),
                ],
                'source' => [
                    'color' => $validated['source_color'] ?? ($validated['accent_color'] ?? '#38bdf8'),
                    'font_size' => (int) ($validated['source_size'] ?? 14),
                    'font_weight' => (string) ($validated['source_weight'] ?? '700'),
                    'spacing' => (int) ($validated['source_spacing'] ?? 18),
                ],
                'highlight' => [
                    'color' => $validated['highlight_color'] ?? ($validated['accent_color'] ?? '#38bdf8'),
                    'scale' => (float) ($validated['highlight_scale'] ?? 1.08),
                    'weight' => (string) ($validated['highlight_weight'] ?? '800'),
                ],
                'decorations' => [
                    'quote_mark_enabled' => $request->boolean('show_quote_mark'),
                    'quote_mark_size' => (int) ($validated['quote_mark_size'] ?? 96),
                    'quote_mark_opacity' => (int) ($validated['quote_mark_opacity'] ?? 16),
                    'quote_mark_position' => $validated['quote_mark_position'] ?? 'top_left',
                    'border_radius' => (int) ($validated['border_radius'] ?? 26),
                ],
            ],
        ];

        if ($item) {
            $meta['source_item_id'] = $item->id;
            $meta['source_item_title'] = $item->title;
            $meta['source_section_id'] = $item->section_id;
        }

        return $meta;
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

    private function defaultDesignerMeta(string $bucket): array
    {
        return [
            'designer_type' => 'quote_card',
            'designer_version' => '2.0',
            'beginner_bucket' => $bucket,
            'quote_text' => '',
            'quote_source' => '',
            'background_mode' => 'gradient',
            'bg_color' => '#160042',
            'bg_color_2' => '#e2388a',
            'text_color' => '#ffffff',
            'source_color' => '#38bdf8',
            'accent_color' => '#38bdf8',
            'overlay_strength' => '58',
            'text_align' => 'center',
            'vertical_align' => 'center',
            'font_weight' => '700',
            'quote_size' => '24',
            'source_size' => '14',
            'source_weight' => '700',
            'highlight_phrases' => '',
            'highlight_rules' => [],
            'highlight_color' => '#facc15',
            'highlight_scale' => '1.08',
            'highlight_weight' => '800',
            'style_preset' => 'royal',
            'show_quote_mark' => true,
            'card_format' => 'portrait',
            'format_ratio' => '4 / 5',
            'format_label' => 'Portrait 4:5',
            'font_family' => 'system',
            'font_key' => 'system',
            'text_scale_mode' => 'auto',
            'content_width' => '86',
            'card_padding' => '34',
            'card_padding_x' => '34',
            'card_padding_y' => '34',
            'line_height' => '1.35',
            'text_shadow' => 'soft',
            'offset_x' => '0',
            'offset_y' => '0',
            'letter_spacing' => '0',
            'source_spacing' => '18',
            'quote_mark_size' => '96',
            'quote_mark_opacity' => '16',
            'quote_mark_position' => 'top_left',
            'background_fit' => 'cover',
            'background_position' => 'center',
            'overlay_style' => 'bottom',
            'border_radius' => '26',
            'source' => '',
            'quote' => '',
            'background_color' => '#160042',
            'gradient_start' => '#160042',
            'gradient_end' => '#e2388a',
            'padding_x' => '34',
            'padding_y' => '34',
            'dxm_design_schema' => 'quote_card_v2_1',
            'design' => [
                'version' => 'quote_card_v2_1',
                'text' => [
                    'font_key' => 'system',
                    'font_family' => 'system',
                ],
            ],
        ];
    }

    private function resolveTitle(string $title, string $quoteText, string $source): string
    {
        $title = trim($title);
        if ($title !== '') {
            return Str::limit($title, 245, '');
        }

        $firstLine = collect(preg_split('/\R+/', $quoteText))
            ->map(fn ($line) => trim($line))
            ->first(fn ($line) => $line !== '');

        if ($firstLine) {
            return Str::limit($firstLine, 70, '');
        }

        if ($source !== '') {
            return 'Quote by ' . Str::limit($source, 60, '');
        }

        return 'Quote Design ' . now()->format('Ymd His');
    }

    private function resolveImageUrl(Request $request, int $activeAppId, string $bucket, string $title, ?string $currentCover): ?string
    {
        if ($request->hasFile('cover_image_file')) {
            return $this->storeUploadedImage($request, $activeAppId, $bucket, $title);
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

        $manual = trim((string) $request->input('cover_image_url', ''));
        return $manual !== '' ? $manual : $currentCover;
    }

    private function storeUploadedImage(Request $request, int $activeAppId, string $bucket, string $title): string
    {
        $file = $request->file('cover_image_file');
        if (! $file) {
            return '';
        }

        $safeTitle = Str::slug($title ?: 'quote-design');
        $extension = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        $extension = in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) ? $extension : 'jpg';

        $filename = $safeTitle . '-' . now()->format('YmdHis') . '-' . Str::lower(Str::random(8)) . '.' . $extension;
        $path = $file->storeAs('assets/app-' . $activeAppId . '/quote-designs', $filename, 'public');
        $url = Storage::disk('public')->url($path);

        $width = null;
        $height = null;
        try {
            $size = getimagesize($file->getRealPath());
            $width = $size[0] ?? null;
            $height = $size[1] ?? null;
        } catch (\Throwable $e) {
            //
        }

        MediaAsset::create([
            'app_id' => $activeAppId,
            'type' => 'image',
            'label' => $title ?: 'Quote Design Background',
            'bucket' => 'quote_designs',
            'disk' => 'public',
            'path' => $path,
            'url' => $url,
            'mime' => $file->getMimeType(),
            'size' => $file->getSize(),
            'width' => $width,
            'height' => $height,
            'tags_json' => [
                'source' => 'beginner_quote_designer',
                'usage' => 'quote_background',
                'bucket' => $bucket,
            ],
            'is_active' => true,
            'sort_order' => 0,
        ]);

        return $url;
    }

    private function duplicateDesignerPost(Request $request, ContentPost $contentPost, int $activeAppId): RedirectResponse
    {
        abort_unless((int) $contentPost->app_id === $activeAppId, 404);

        $item = $this->resolveItem($activeAppId, (int) $request->input('item_id', 0));
        $returnTo = $this->sanitizeReturnTo($request->input('return'), $item);
        $bucket = $this->normalizeBucket((string) $contentPost->bucket);

        $copy = $contentPost->replicate();
        $copy->title = $contentPost->title . ' Copy';
        $copy->slug = $this->uniqueSlug($activeAppId, $copy->title);
        $copy->status = 'draft';
        $copy->published_at = null;
        $copy->publish_at = null;
        $copy->sort_order = ((int) $contentPost->sort_order) + 1;
        $copy->created_at = now();
        $copy->updated_at = now();
        $copy->save();

        return redirect()
            ->route('admin.beginner.quote-designer.edit', [
                'contentPost' => $copy->id,
                'bucket' => $bucket,
                'item_id' => $item?->id,
                'return' => $returnTo,
            ])
            ->with('status', 'Quote design duplicated. You are editing the draft copy now.');
    }

    private function deleteDesignerPost(Request $request, ContentPost $contentPost, int $activeAppId): RedirectResponse
    {
        abort_unless((int) $contentPost->app_id === $activeAppId, 404);

        $validated = $request->validate([
            'delete_confirmation' => ['required', 'string'],
            'bucket' => ['nullable', 'string'],
            'item_id' => ['nullable', 'integer'],
            'return' => ['nullable', 'string'],
        ]);

        abort_unless(trim((string) $validated['delete_confirmation']) === 'DELETE', 422);

        $bucket = $this->normalizeBucket((string) ($validated['bucket'] ?? $contentPost->bucket));
        $item = $this->resolveItem($activeAppId, (int) ($validated['item_id'] ?? 0));
        $returnTo = $this->sanitizeReturnTo($validated['return'] ?? null, $item);
        $title = $contentPost->title;

        try {
            $contentPost->likes()->delete();
            $contentPost->comments()->delete();
        } catch (\Throwable $e) {
            //
        }

        $contentPost->delete();

        return redirect($this->returnUrl($returnTo, $bucket, $item))
            ->with('status', 'Deleted quote design: ' . $title);
    }

    private function quoteBodyHtml(string $quote, string $source): string
    {
        $quote = e($quote);
        $source = e($source);
        return '<blockquote><p>' . nl2br($quote) . '</p>' . ($source !== '' ? '<footer>' . $source . '</footer>' : '') . '</blockquote>';
    }

    private function tagsFromInput(string $tags): array
    {
        return collect(explode(',', $tags))->map(fn ($tag) => trim($tag))->filter()->values()->all();
    }

    private function mediaAssets(int $activeAppId)
    {
        return MediaAsset::query()
            ->where('type', 'image')
            ->where('is_active', true)
            ->where(function ($query) use ($activeAppId) {
                $query->whereNull('app_id')->orWhere('app_id', $activeAppId);
            })
            ->orderByDesc('id')
            ->limit(300)
            ->get();
    }

    private function uniqueSlug(int $appId, string $title): string
    {
        $base = Str::slug($title) ?: 'quote-design';
        $slug = $base;
        $count = 2;

        while (ContentPost::query()->where('app_id', $appId)->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $count;
            $count++;
        }

        return $slug;
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


    private function isQuoteBucket(string $bucket): bool
    {
        return str_ends_with($bucket, '_quotes')
            || $bucket === 'daily_scriptures'
            || str_starts_with($bucket, 'quote_');
    }

    private function labelForBucket(string $bucket): string
{
    $bucket = $this->normalizeBucket($bucket);

    return match ($bucket) {
        'daily_quotes' => 'Daily Quote Designer',
        'daily_scriptures' => 'Daily Scripture Designer',
        'sod_quotes' => 'SOD Quote Designer',
        'motivational_quotes' => 'Motivational Quote Designer',
        'article_quotes' => 'Article Quote Designer',
        'custom_quotes' => 'Custom Quote Designer',
        default => Str::headline(preg_replace('/^quote_|_quotes$/', '', $bucket)) . ' Designer',
    };
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

    private function statusOptions(): array
    {
        return ['draft' => 'Draft', 'published' => 'Published', 'archived' => 'Archived'];
    }

    private function formatOptions(): array
    {
        return [
            'portrait' => ['label' => 'Portrait 4:5', 'ratio' => '4 / 5'],
            'square' => ['label' => 'Square 1:1', 'ratio' => '1 / 1'],
            'story' => ['label' => 'Story 9:16', 'ratio' => '9 / 16'],
            'wide' => ['label' => 'Wide 16:9', 'ratio' => '16 / 9'],
            'cinematic' => ['label' => 'Cinematic 21:9', 'ratio' => '21 / 9'],
            'classic' => ['label' => 'Classic 3:4', 'ratio' => '3 / 4'],
        ];
    }

    private function fontOptions(): array
    {
        return [
            'system' => 'System Sans',
            'poppins' => 'Poppins',
            'montserrat' => 'Montserrat',
            'impact' => 'Impact Style / Anton',
            'anton' => 'Anton',
            'archivo_black' => 'Archivo Black',
            'bebas_neue' => 'Bebas Neue',
            'oswald' => 'Oswald',
            'playfair_display' => 'Playfair Display',
            'merriweather' => 'Merriweather',
            'lora' => 'Lora',
            'roboto_slab' => 'Roboto Slab',
            'arial' => 'Arial / System',
            'inter' => 'Inter / Poppins',
            'verdana' => 'Verdana / System',
            'tahoma' => 'Tahoma / System',
            'trebuchet' => 'Trebuchet / System',
            'serif' => 'Classic Serif',
            'georgia' => 'Georgia / Merriweather',
            'times' => 'Times / Merriweather',
            'garamond' => 'Garamond / Lora',
            'arial_black' => 'Arial Black / Archivo Black',
            'mono' => 'Mono Clean',
            'courier' => 'Courier New / Roboto Mono',
        ];
    }

    private function sanitizeReturnTo(?string $value, ?AppItem $item): string
    {
        $value = str_replace('_', '-', strtolower(trim((string) $value)));

        if ($value === 'quote-engine') {
            return 'quote-engine';
        }

        if ($item && $value === 'item') {
            return 'item';
        }

        return match ($value) {
            'channels' => 'channels',
            'destination' => 'destination',
            default => $item ? 'item' : 'quote-engine',
        };
    }

    private function returnUrl(string $returnTo, string $bucket, ?AppItem $item): string
    {
        if ($returnTo === 'quote-engine') {
            return route('admin.beginner.content-posts.channel', [
                'bucket' => $bucket,
                'item_id' => $item?->id,
                'return' => 'quote-engine',
            ]);
        }

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

        return route('admin.beginner.content-posts.channel', [
            'bucket' => $bucket,
            'item_id' => $item?->id,
            'return' => $returnTo === 'channels' ? null : $returnTo,
        ]);
    }

    private function activeAppId(): int
    {
        $activeAppId = (int) (ActiveApp::ensureId() ?? 0);
        abort_unless($activeAppId > 0, 404);
        return $activeAppId;
    }
}