@extends('layouts.beginner')

@php
    $meta = is_array($post->meta_json ?? null) ? $post->meta_json : [];

    $resolvedQuote = old('quote_text', $meta['quote_text'] ?? strip_tags((string) ($post->body_html ?? '')));
    $resolvedSource = old('quote_source', $meta['quote_source'] ?? ($post->subtitle ?? $post->author_name ?? ''));
    $resolvedTitle = old('title', $post->title ?? '');
    $resolvedSubtitle = old('subtitle', $post->subtitle ?? '');
    $resolvedBucket = old('bucket', $bucket ?? $post->bucket ?? 'sod_quotes');
    $resolvedStatus = old('status', $post->status ?? 'draft');
    $resolvedCover = old('cover_image_url', $post->cover_image_url ?? '');
    $resolvedBackgroundMode = old('background_mode', $meta['background_mode'] ?? 'gradient');
    $resolvedBgColor = old('bg_color', $meta['bg_color'] ?? '#160042');
    $resolvedBgColor2 = old('bg_color_2', $meta['bg_color_2'] ?? '#e2388a');
    $resolvedTextColor = old('text_color', $meta['text_color'] ?? '#ffffff');
    $resolvedSourceColor = old('source_color', $meta['source_color'] ?? ($meta['accent_color'] ?? '#38bdf8'));
    $resolvedAccentColor = old('accent_color', $meta['accent_color'] ?? '#38bdf8');
    $resolvedOverlayStrength = old('overlay_strength', $meta['overlay_strength'] ?? '58');
    $resolvedTextAlign = old('text_align', $meta['text_align'] ?? 'center');
    $resolvedVerticalAlign = old('vertical_align', $meta['vertical_align'] ?? 'center');
    $resolvedFontWeight = old('font_weight', $meta['font_weight'] ?? '700');
    $resolvedQuoteSize = old('quote_size', $meta['quote_size'] ?? '24');
    $resolvedSourceSize = old('source_size', $meta['source_size'] ?? '14');
    $resolvedSourceWeight = old('source_weight', $meta['source_weight'] ?? '700');
    $resolvedHighlightPhrases = old('highlight_phrases', $meta['highlight_phrases'] ?? '');
    $resolvedHighlightColor = old('highlight_color', $meta['highlight_color'] ?? '#facc15');
    $resolvedHighlightScale = old('highlight_scale', $meta['highlight_scale'] ?? '1.08');
    $resolvedHighlightWeight = old('highlight_weight', $meta['highlight_weight'] ?? '800');
    $resolvedStylePreset = old('style_preset', $meta['style_preset'] ?? 'royal');
    $resolvedShowQuoteMark = (bool) old('show_quote_mark', $meta['show_quote_mark'] ?? true);
    $resolvedTags = old('tags', implode(', ', is_array($post->tags_json ?? null) ? $post->tags_json : []));
    $resolvedCardFormat = old('card_format', $meta['card_format'] ?? 'portrait');
    $resolvedFontFamily = old('font_family', $meta['font_family'] ?? 'system');
    $resolvedTextScaleMode = old('text_scale_mode', $meta['text_scale_mode'] ?? 'auto');
    $resolvedContentWidth = old('content_width', $meta['content_width'] ?? '86');
    $resolvedCardPadding = old('card_padding', $meta['card_padding'] ?? '34');
    $resolvedCardPaddingX = old('card_padding_x', $meta['card_padding_x'] ?? ($meta['card_padding'] ?? '34'));
    $resolvedCardPaddingY = old('card_padding_y', $meta['card_padding_y'] ?? ($meta['card_padding'] ?? '34'));
    $resolvedLineHeight = old('line_height', $meta['line_height'] ?? '1.35');
    $resolvedTextShadow = old('text_shadow', $meta['text_shadow'] ?? 'soft');
    $resolvedOffsetX = old('offset_x', $meta['offset_x'] ?? '0');
    $resolvedOffsetY = old('offset_y', $meta['offset_y'] ?? '0');
    $resolvedLetterSpacing = old('letter_spacing', $meta['letter_spacing'] ?? '0');
    $resolvedSourceSpacing = old('source_spacing', $meta['source_spacing'] ?? '18');
    $resolvedQuoteMarkSize = old('quote_mark_size', $meta['quote_mark_size'] ?? '96');
    $resolvedQuoteMarkOpacity = old('quote_mark_opacity', $meta['quote_mark_opacity'] ?? '16');
    $resolvedQuoteMarkPosition = old('quote_mark_position', $meta['quote_mark_position'] ?? 'top_left');
    $resolvedBackgroundFit = old('background_fit', $meta['background_fit'] ?? 'cover');
    $resolvedBackgroundPosition = old('background_position', $meta['background_position'] ?? 'center');
    $resolvedOverlayStyle = old('overlay_style', $meta['overlay_style'] ?? 'bottom');
    $resolvedBorderRadius = old('border_radius', $meta['border_radius'] ?? '26');
@endphp

@section('title', $mode === 'create' ? 'Create Quote Design' : 'Edit Quote Design')
@section('eyebrow', 'Beginner Quote Designer')
@section('page_title', $mode === 'create' ? 'Create Quote Design' : 'Edit Quote Design')
@section('page_description', 'Design reusable quote cards for the active app without leaving beginner mode.')
@section('back_url', $returnUrl)
@section('form_title', $bucketLabel ?? 'Quote Designer')
@section('form_description', 'Create a visual quote card with format, background, typography, publishing, and media controls.')
@section('preview_description', 'Live adaptive quote preview.')

@section('studio_tabs')
    <button type="button" class="dxm-dock-tab is-active" data-dxm-tab="quote">Quote</button>
    <button type="button" class="dxm-dock-tab" data-dxm-tab="format">Format</button>
    <button type="button" class="dxm-dock-tab" data-dxm-tab="design">Design</button>
    <button type="button" class="dxm-dock-tab" data-dxm-tab="media">Media</button>
    <button type="button" class="dxm-dock-tab" data-dxm-tab="publishing">Publishing</button>
@endsection

@section('form')
    @if ($errors->any())
        <div class="dxm-alert dxm-alert--danger">
            <strong>Please fix these issues:</strong>
            <ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if (session('status'))
        <div class="dxm-alert">{{ session('status') }}</div>
    @endif

    @if (!empty($item))
        <div class="context-box">
            <div>
                <span class="context-eyebrow">Connected Card</span>
                <strong>{{ $item->title }}</strong>
                <small>{{ $item->section?->title }} • {{ $bucketLabel ?? 'Quote Designer' }}</small>
            </div>
            <a href="{{ $returnUrl }}" class="dxm-btn">← Back</a>
        </div>
    @endif

    <form method="POST" action="{{ $saveUrl }}" enctype="multipart/form-data" id="quoteDesignerForm">
        @csrf
        @if ($methodField)
            @method($methodField)
        @endif

        <input type="hidden" name="bucket" value="{{ $resolvedBucket }}">
        <input type="hidden" name="return" value="{{ $returnTo ?? (!empty($item) ? 'item' : 'channels') }}">
        <input type="hidden" name="item_id" value="{{ $itemId ?? optional($item ?? null)->id }}">
        <input type="hidden" name="_designer_action" id="designerAction" value="">
        <input type="hidden" name="delete_confirmation" id="deleteConfirmation" value="">

        <div class="dxm-tab-panel is-active" data-dxm-tab-panel="quote">
            <div class="quote-panel">
                <h3>Quote Content</h3>
                <p>The backend title is optional. If left empty, it will be generated from the first line of the quote.</p>

                <div class="quote-grid">
                    <div class="quote-field">
                        <label for="title">Backend Title (Optional)</label>
                        <input id="title" name="title" type="text" value="{{ $resolvedTitle }}" placeholder="Auto-generated if empty">
                    </div>

                    <div class="quote-field">
                        <label for="quote_source">Source / Speaker</label>
                        <input id="quote_source" name="quote_source" type="text" value="{{ $resolvedSource }}" placeholder="Example: Dr. Paul Enenche">
                    </div>

                    <div class="quote-field quote-full">
                        <label for="quote_text">Quote Text</label>
                        <textarea id="quote_text" name="quote_text" rows="10" placeholder="Enter the quote text here">{{ $resolvedQuote }}</textarea>
                    </div>

                    <div class="quote-field quote-full">
                        <label for="highlight_phrases">Highlight Words / Phrases</label>
                        <textarea id="highlight_phrases" name="highlight_phrases" rows="3" placeholder="Type words or phrases to highlight. Separate with commas or new lines.">{{ $resolvedHighlightPhrases }}</textarea>
                        <small>Use this for mixed color/size emphasis without needing the advanced designer.</small>
                    </div>

                    <div class="quote-field quote-full">
                        <label for="subtitle">Optional Short Subtitle</label>
                        <input id="subtitle" name="subtitle" type="text" value="{{ $resolvedSubtitle }}" placeholder="Optional helper text">
                    </div>

                    <div class="quote-field quote-full">
                        <label for="tags">Tags</label>
                        <input id="tags" name="tags" type="text" value="{{ $resolvedTags }}" placeholder="faith, prayer, wisdom">
                    </div>
                </div>
            </div>
        </div>

        <div class="dxm-tab-panel" data-dxm-tab-panel="format">
            <div class="quote-panel">
                <h3>Format & Size</h3>
                <p>Choose the canvas size and how text should fit inside the card.</p>

                <div class="quote-format-list">
                    @foreach(($formatOptions ?? []) as $value => $option)
                        <label class="quote-format-option">
                            <input type="radio" name="card_format" value="{{ $value }}" data-format-ratio="{{ $option['ratio'] }}" @checked($resolvedCardFormat === $value)>
                            <span>
                                <strong>{{ $option['label'] }}</strong>
                                <small>{{ $option['ratio'] }}</small>
                            </span>
                        </label>
                    @endforeach
                </div>

                <div class="quote-grid" style="margin-top:14px;">
                    <div class="quote-field">
                        <label for="text_scale_mode">Text Scale Mode</label>
                        <select id="text_scale_mode" name="text_scale_mode">
                            <option value="auto" @selected($resolvedTextScaleMode === 'auto')>Auto-fit text</option>
                            <option value="manual" @selected($resolvedTextScaleMode === 'manual')>Manual size</option>
                        </select>
                    </div>

                    <div class="quote-field">
                        <label for="content_width">Content Width</label>
                        <input id="content_width" name="content_width" type="range" min="30" max="100" value="{{ $resolvedContentWidth }}">
                        <small><span id="contentWidthValue">{{ $resolvedContentWidth }}</span>%</small>
                    </div>

                    <div class="quote-field">
                        <label for="card_padding_x">Horizontal Padding</label>
                        <input id="card_padding_x" name="card_padding_x" type="range" min="0" max="220" value="{{ $resolvedCardPaddingX }}">
                        <small><span id="cardPaddingXValue">{{ $resolvedCardPaddingX }}</span>px left/right</small>
                        <input type="hidden" id="card_padding" name="card_padding" value="{{ $resolvedCardPadding }}">
                    </div>

                    <div class="quote-field">
                        <label for="card_padding_y">Vertical Padding</label>
                        <input id="card_padding_y" name="card_padding_y" type="range" min="0" max="220" value="{{ $resolvedCardPaddingY }}">
                        <small><span id="cardPaddingYValue">{{ $resolvedCardPaddingY }}</span>px top/bottom</small>
                    </div>

                    <div class="quote-field">
                        <label for="line_height">Line Height</label>
                        <input id="line_height" name="line_height" type="range" min="0.8" max="2.4" step="0.05" value="{{ $resolvedLineHeight }}">
                        <small><span id="lineHeightValue">{{ $resolvedLineHeight }}</span></small>
                    </div>

                    <div class="quote-field">
                        <label for="offset_x">Text Offset X</label>
                        <input id="offset_x" name="offset_x" type="range" min="-240" max="240" value="{{ $resolvedOffsetX }}">
                        <small><span id="offsetXValue">{{ $resolvedOffsetX }}</span>px left/right</small>
                    </div>

                    <div class="quote-field">
                        <label for="offset_y">Text Offset Y</label>
                        <input id="offset_y" name="offset_y" type="range" min="-240" max="240" value="{{ $resolvedOffsetY }}">
                        <small><span id="offsetYValue">{{ $resolvedOffsetY }}</span>px up/down. Works even when vertical position is Center.</small>
                    </div>

                    <div class="quote-field">
                        <label for="letter_spacing">Letter Spacing</label>
                        <input id="letter_spacing" name="letter_spacing" type="range" min="-1" max="8" step="0.1" value="{{ $resolvedLetterSpacing }}">
                        <small><span id="letterSpacingValue">{{ $resolvedLetterSpacing }}</span>px</small>
                    </div>

                    <div class="quote-field">
                        <label for="source_spacing">Source Spacing</label>
                        <input id="source_spacing" name="source_spacing" type="range" min="0" max="100" value="{{ $resolvedSourceSpacing }}">
                        <small><span id="sourceSpacingValue">{{ $resolvedSourceSpacing }}</span>px between quote and source</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="dxm-tab-panel" data-dxm-tab-panel="design">
            <div class="quote-panel">
                <h3>Design Controls</h3>
                <p>Choose typography, alignment, colors, overlay, and preset style.</p>

                <div class="quote-grid">
                    <div class="quote-field"><label for="style_preset">Style Preset</label><select id="style_preset" name="style_preset"><option value="royal" @selected($resolvedStylePreset === 'royal')>Royal Deep</option><option value="ocean" @selected($resolvedStylePreset === 'ocean')>Ocean Blue</option><option value="fire" @selected($resolvedStylePreset === 'fire')>Fire Glow</option><option value="soft" @selected($resolvedStylePreset === 'soft')>Soft Light</option><option value="gold" @selected($resolvedStylePreset === 'gold')>Gold Classic</option><option value="midnight" @selected($resolvedStylePreset === 'midnight')>Midnight Calm</option></select></div>
                    <div class="quote-field"><label for="font_family">Font Family</label><select id="font_family" name="font_family">@foreach(($fontOptions ?? []) as $value => $label)<option value="{{ $value }}" @selected($resolvedFontFamily === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="quote-field"><label for="background_mode">Background Mode</label><select id="background_mode" name="background_mode"><option value="image" @selected($resolvedBackgroundMode === 'image')>Image</option><option value="gradient" @selected($resolvedBackgroundMode === 'gradient')>Gradient</option><option value="solid" @selected($resolvedBackgroundMode === 'solid')>Solid Color</option></select></div>
                    <div class="quote-field"><label for="text_align">Text Alignment</label><select id="text_align" name="text_align"><option value="left" @selected($resolvedTextAlign === 'left')>Left</option><option value="center" @selected($resolvedTextAlign === 'center')>Center</option><option value="right" @selected($resolvedTextAlign === 'right')>Right</option></select></div>
                    <div class="quote-field"><label for="vertical_align">Vertical Position</label><select id="vertical_align" name="vertical_align"><option value="start" @selected($resolvedVerticalAlign === 'start')>Top</option><option value="center" @selected($resolvedVerticalAlign === 'center')>Center</option><option value="end" @selected($resolvedVerticalAlign === 'end')>Bottom</option></select></div>
                    <div class="quote-field"><label for="font_weight">Font Weight</label><select id="font_weight" name="font_weight"><option value="400" @selected($resolvedFontWeight === '400')>Normal</option><option value="500" @selected($resolvedFontWeight === '500')>Medium Soft</option><option value="600" @selected($resolvedFontWeight === '600')>Medium</option><option value="700" @selected($resolvedFontWeight === '700')>Bold</option><option value="800" @selected($resolvedFontWeight === '800')>Extra Bold</option></select></div>
                    <div class="quote-field"><label for="text_shadow">Text Shadow</label><select id="text_shadow" name="text_shadow"><option value="off" @selected($resolvedTextShadow === 'off')>Off / Clean Text</option><option value="soft" @selected($resolvedTextShadow === 'soft')>Soft Shadow</option><option value="strong" @selected($resolvedTextShadow === 'strong')>Strong Shadow</option></select><small>Turn off when using light backgrounds and dark text.</small></div>

                    <div class="quote-field"><label for="quote_size">Quote Size</label><input id="quote_size" name="quote_size" type="range" min="10" max="96" value="{{ $resolvedQuoteSize }}"><small><span id="quoteSizeValue">{{ $resolvedQuoteSize }}</span>px</small></div>
                    <div class="quote-field"><label for="source_size">Source Size</label><input id="source_size" name="source_size" type="range" min="8" max="48" value="{{ $resolvedSourceSize }}"><small><span id="sourceSizeValue">{{ $resolvedSourceSize }}</span>px</small></div>
                    <div class="quote-field"><label for="source_weight">Source Weight</label><select id="source_weight" name="source_weight"><option value="400" @selected($resolvedSourceWeight === '400')>Normal</option><option value="600" @selected($resolvedSourceWeight === '600')>Medium</option><option value="700" @selected($resolvedSourceWeight === '700')>Bold</option><option value="800" @selected($resolvedSourceWeight === '800')>Extra Bold</option></select></div>
                    <div class="quote-field"><label for="highlight_scale">Highlight Size Boost</label><input id="highlight_scale" name="highlight_scale" type="range" min="0.80" max="2.40" step="0.01" value="{{ $resolvedHighlightScale }}"><small><span id="highlightScaleValue">{{ $resolvedHighlightScale }}</span>x</small></div>
                    <div class="quote-field"><label for="highlight_weight">Highlight Weight</label><select id="highlight_weight" name="highlight_weight"><option value="600" @selected($resolvedHighlightWeight === '600')>Medium</option><option value="700" @selected($resolvedHighlightWeight === '700')>Bold</option><option value="800" @selected($resolvedHighlightWeight === '800')>Extra Bold</option><option value="900" @selected($resolvedHighlightWeight === '900')>Black</option></select></div>
                    <div class="quote-field"><label for="overlay_strength">Overlay Strength</label><input id="overlay_strength" name="overlay_strength" type="range" min="0" max="95" value="{{ $resolvedOverlayStrength }}"><small><span id="overlayValue">{{ $resolvedOverlayStrength }}</span>%</small></div>

                    <div class="quote-field"><label for="bg_color">Background Color 1</label><div class="quote-color-row"><input id="bg_color" name="bg_color" type="color" value="{{ $resolvedBgColor }}"><input type="text" value="{{ $resolvedBgColor }}" data-color-copy="bg_color"></div></div>
                    <div class="quote-field"><label for="bg_color_2">Background Color 2</label><div class="quote-color-row"><input id="bg_color_2" name="bg_color_2" type="color" value="{{ $resolvedBgColor2 }}"><input type="text" value="{{ $resolvedBgColor2 }}" data-color-copy="bg_color_2"></div></div>
                    <div class="quote-field"><label for="text_color">Text Color</label><div class="quote-color-row"><input id="text_color" name="text_color" type="color" value="{{ $resolvedTextColor }}"><input type="text" value="{{ $resolvedTextColor }}" data-color-copy="text_color"></div></div>
                    <div class="quote-field"><label for="source_color">Source / Reference Color</label><div class="quote-color-row"><input id="source_color" name="source_color" type="color" value="{{ $resolvedSourceColor }}"><input type="text" value="{{ $resolvedSourceColor }}" data-color-copy="source_color"></div></div>
                    <div class="quote-field"><label for="highlight_color">Highlight Color</label><div class="quote-color-row"><input id="highlight_color" name="highlight_color" type="color" value="{{ $resolvedHighlightColor }}"><input type="text" value="{{ $resolvedHighlightColor }}" data-color-copy="highlight_color"></div></div>
                    <div class="quote-field"><label for="accent_color">Accent Color</label><div class="quote-color-row"><input id="accent_color" name="accent_color" type="color" value="{{ $resolvedAccentColor }}"><input type="text" value="{{ $resolvedAccentColor }}" data-color-copy="accent_color"></div></div>

                    <input type="hidden" name="show_quote_mark" value="0">
                    <label class="quote-check quote-full"><input type="checkbox" id="show_quote_mark" name="show_quote_mark" value="1" @checked($resolvedShowQuoteMark)><span>Show large decorative quote mark</span></label>

                    <div class="quote-field">
                        <label for="quote_mark_size">Quote Mark Size</label>
                        <input id="quote_mark_size" name="quote_mark_size" type="range" min="24" max="220" value="{{ $resolvedQuoteMarkSize }}">
                        <small><span id="quoteMarkSizeValue">{{ $resolvedQuoteMarkSize }}</span>px</small>
                    </div>

                    <div class="quote-field">
                        <label for="quote_mark_opacity">Quote Mark Opacity</label>
                        <input id="quote_mark_opacity" name="quote_mark_opacity" type="range" min="0" max="80" value="{{ $resolvedQuoteMarkOpacity }}">
                        <small><span id="quoteMarkOpacityValue">{{ $resolvedQuoteMarkOpacity }}</span>%</small>
                    </div>

                    <div class="quote-field">
                        <label for="quote_mark_position">Quote Mark Position</label>
                        <select id="quote_mark_position" name="quote_mark_position">
                            <option value="top_left" @selected($resolvedQuoteMarkPosition === 'top_left')>Top Left</option>
                            <option value="top_right" @selected($resolvedQuoteMarkPosition === 'top_right')>Top Right</option>
                            <option value="center_left" @selected($resolvedQuoteMarkPosition === 'center_left')>Center Left</option>
                            <option value="center_right" @selected($resolvedQuoteMarkPosition === 'center_right')>Center Right</option>
                            <option value="bottom_left" @selected($resolvedQuoteMarkPosition === 'bottom_left')>Bottom Left</option>
                            <option value="bottom_right" @selected($resolvedQuoteMarkPosition === 'bottom_right')>Bottom Right</option>
                        </select>
                    </div>

                    <div class="quote-field">
                        <label for="border_radius">Card Corner Radius</label>
                        <input id="border_radius" name="border_radius" type="range" min="0" max="80" value="{{ $resolvedBorderRadius }}">
                        <small><span id="borderRadiusValue">{{ $resolvedBorderRadius }}</span>px</small>
                    </div>

                    <div class="quote-field quote-full">
                        <label>Quick Palettes</label>
                        <div class="quote-palette-row">
                            @foreach([
                                ['preset'=>'royal','bg'=>'#160042','bg2'=>'#e2388a','text'=>'#ffffff','source'=>'#d8b4fe','highlight'=>'#facc15','accent'=>'#38bdf8'],
                                ['preset'=>'ocean','bg'=>'#061b3a','bg2'=>'#0ea5e9','text'=>'#ffffff','source'=>'#bae6fd','highlight'=>'#67e8f9','accent'=>'#67e8f9'],
                                ['preset'=>'fire','bg'=>'#450a0a','bg2'=>'#f97316','text'=>'#fff7ed','source'=>'#fed7aa','highlight'=>'#fde047','accent'=>'#facc15'],
                                ['preset'=>'soft','bg'=>'#fdf2f8','bg2'=>'#dbeafe','text'=>'#111827','source'=>'#334155','highlight'=>'#facc15','accent'=>'#2563eb'],
                                ['preset'=>'gold','bg'=>'#171717','bg2'=>'#a16207','text'=>'#fef3c7','source'=>'#fde68a','highlight'=>'#facc15','accent'=>'#facc15'],
                                ['preset'=>'midnight','bg'=>'#020617','bg2'=>'#312e81','text'=>'#e0e7ff','source'=>'#c4b5fd','highlight'=>'#38bdf8','accent'=>'#a78bfa'],
                                ['preset'=>'white-gold','bg'=>'#fff7ed','bg2'=>'#fef3c7','text'=>'#171717','source'=>'#854d0e','highlight'=>'#ca8a04','accent'=>'#ca8a04'],
                                ['preset'=>'purple-lilac','bg'=>'#2e1065','bg2'=>'#c084fc','text'=>'#ffffff','source'=>'#f5d0fe','highlight'=>'#f0abfc','accent'=>'#f0abfc'],
                                ['preset'=>'green-life','bg'=>'#052e16','bg2'=>'#22c55e','text'=>'#f0fdf4','source'=>'#bbf7d0','highlight'=>'#fde047','accent'=>'#86efac'],
                                ['preset'=>'blue-light','bg'=>'#dbeafe','bg2'=>'#eff6ff','text'=>'#0f172a','source'=>'#1d4ed8','highlight'=>'#2563eb','accent'=>'#2563eb'],
                                ['preset'=>'rose-black','bg'=>'#09090b','bg2'=>'#be185d','text'=>'#fff1f2','source'=>'#fbcfe8','highlight'=>'#f9a8d4','accent'=>'#ec4899'],
                                ['preset'=>'charcoal-cyan','bg'=>'#111827','bg2'=>'#0e7490','text'=>'#f8fafc','source'=>'#a5f3fc','highlight'=>'#22d3ee','accent'=>'#22d3ee'],
                            ] as $palette)
                                <button type="button" class="quote-palette" data-preset="{{ $palette['preset'] }}" data-bg="{{ $palette['bg'] }}" data-bg2="{{ $palette['bg2'] }}" data-text="{{ $palette['text'] }}" data-source="{{ $palette['source'] }}" data-highlight="{{ $palette['highlight'] }}" data-accent="{{ $palette['accent'] }}" style="background:linear-gradient(135deg,{{ $palette['bg'] }},{{ $palette['bg2'] }})"></button>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="dxm-tab-panel" data-dxm-tab-panel="media">
            <div class="quote-panel">
                <h3>Background Media</h3>
                <p>Upload a new background, select from the active app media library, or paste an image URL.</p>

                <div class="quote-grid">
                    <div class="quote-field quote-full"><label for="cover_image_file">Upload New Background</label><input type="file" id="cover_image_file" name="cover_image_file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"></div>
                    <div class="quote-field quote-full"><label for="cover_image_url">Image URL / Storage Path</label><input id="cover_image_url" name="cover_image_url" type="text" value="{{ $resolvedCover }}"></div>
                    <div class="quote-field"><label for="background_fit">Image Fit</label><select id="background_fit" name="background_fit"><option value="cover" @selected($resolvedBackgroundFit === 'cover')>Cover</option><option value="contain" @selected($resolvedBackgroundFit === 'contain')>Contain</option><option value="fill" @selected($resolvedBackgroundFit === 'fill')>Fill / Stretch</option></select></div>
                    <div class="quote-field"><label for="background_position">Image Position</label><select id="background_position" name="background_position"><option value="center" @selected($resolvedBackgroundPosition === 'center')>Center</option><option value="top" @selected($resolvedBackgroundPosition === 'top')>Top</option><option value="bottom" @selected($resolvedBackgroundPosition === 'bottom')>Bottom</option><option value="left" @selected($resolvedBackgroundPosition === 'left')>Left</option><option value="right" @selected($resolvedBackgroundPosition === 'right')>Right</option><option value="top left" @selected($resolvedBackgroundPosition === 'top left')>Top Left</option><option value="top right" @selected($resolvedBackgroundPosition === 'top right')>Top Right</option><option value="bottom left" @selected($resolvedBackgroundPosition === 'bottom left')>Bottom Left</option><option value="bottom right" @selected($resolvedBackgroundPosition === 'bottom right')>Bottom Right</option></select></div>
                    <div class="quote-field"><label for="overlay_style">Overlay Style</label><select id="overlay_style" name="overlay_style"><option value="bottom" @selected($resolvedOverlayStyle === 'bottom')>Bottom Fade</option><option value="full" @selected($resolvedOverlayStyle === 'full')>Full Overlay</option><option value="top_bottom" @selected($resolvedOverlayStyle === 'top_bottom')>Top + Bottom</option><option value="none" @selected($resolvedOverlayStyle === 'none')>No Overlay</option></select></div>
                    <div class="quote-field quote-full">@include('admin.beginner.partials.media-picker', ['pickerId' => 'quoteBackgroundPicker', 'inputId' => 'cover_image_url', 'selectName' => 'media_asset_id', 'assets' => $mediaAssets, 'title' => 'Background Library', 'emptyText' => 'No media assets found for this app yet. Upload a background image above.'])</div>
                </div>
            </div>
        </div>

        <div class="dxm-tab-panel" data-dxm-tab-panel="publishing">
            <div class="quote-panel">
                <h3>Publishing</h3>
                <p>Control visibility, ordering, scheduling, and workflow status.</p>

                <div class="quote-grid">
                    <div class="quote-field"><label for="status">Status</label><select id="status" name="status">@foreach($statusOptions as $value => $label)<option value="{{ $value }}" @selected($resolvedStatus === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div class="quote-field"><label for="sort_order">Display Order</label><input id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', $post->sort_order ?? 0) }}"></div>
                    <div class="quote-field"><label for="publish_at">Publish At (Lagos time)</label><input id="publish_at" name="publish_at" type="datetime-local" value="{{ old('publish_at', \App\Support\Scheduling\AdminScheduleTime::toLocalInput($post->publish_at ?? null)) }}"></div>
                    <div class="quote-field"><label for="published_at">Published At (Lagos time)</label><input id="published_at" name="published_at" type="datetime-local" value="{{ old('published_at', \App\Support\Scheduling\AdminScheduleTime::toLocalInput($post->published_at ?? null)) }}"></div>
                    <label class="quote-check"><input type="checkbox" id="is_featured" name="is_featured" value="1" @checked(old('is_featured', $post->is_featured ?? false))><span>Feature this quote</span></label>
                    <label class="quote-check"><input type="checkbox" id="clear_published_at" name="clear_published_at" value="1"><span>Clear published date if moved back to draft</span></label>
                </div>
            </div>

            @if ($mode === 'edit')
                <div class="quote-panel quote-danger">
                    <h3>Quote Actions</h3>
                    <p>Duplicate creates a draft copy. Delete permanently removes this quote design.</p>
                    <div class="quote-action-row"><button type="button" class="dxm-btn" id="duplicateQuoteButton">Duplicate Quote</button><button type="button" class="dxm-btn" id="deleteQuoteButton" style="border-color:rgba(248,113,113,.55);background:rgba(127,29,29,.35);">Delete Quote</button></div>
                </div>
            @endif
        </div>

        <div class="dxm-submit"><a href="{{ $returnUrl }}" class="dxm-btn">Cancel</a><button type="submit" class="dxm-btn dxm-btn--primary">{{ $mode === 'create' ? 'Create Quote Design' : 'Save Quote Design' }}</button></div>
    </form>
@endsection

@section('preview')
    <div class="quote-preview-shell">
        <div class="quote-preview-toolbar"><span id="quoteFormatLabel">Format Preview</span><span id="quoteCharacterCount">0 characters</span></div>
        <div class="quote-card-stage">
            <div class="quote-card-preview" id="quoteCardPreview">
                <div class="quote-card-bg" id="quotePreviewBg"></div>
                <div class="quote-card-overlay" id="quotePreviewOverlay"></div>
                <div class="quote-mark" id="quotePreviewMark">“</div>
                <div class="quote-card-content" id="quotePreviewContent"><div class="quote-card-inner" id="quotePreviewInner"><div class="quote-card-text" id="quotePreviewText"></div><div class="quote-card-source" id="quotePreviewSource"></div></div></div>
            </div>
        </div>
        <div class="quote-preview-note"><strong>Frontend-ready payload</strong><span>This saves a reusable quote-card payload in <code>content_posts.meta_json</code>.</span></div>
    </div>
@endsection

@push('styles')
<style>
.quote-panel{border:1px solid rgba(255,255,255,.10);background:rgba(2,6,23,.32);border-radius:20px;padding:16px;margin-bottom:14px}.quote-panel h3{margin:0 0 8px;color:#fff;font-size:15px}.quote-panel p{margin:0 0 14px;color:rgba(255,255,255,.62);font-size:12.5px;line-height:1.45}.quote-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:14px}.quote-full{grid-column:1/-1}.quote-field label{display:block;color:rgba(255,255,255,.82);font-size:12px;font-weight:800;margin-bottom:7px}.quote-field input,.quote-field select,.quote-field textarea{width:100%;border-radius:14px;border:1px solid rgba(255,255,255,.10);background:rgba(2,6,23,.78);color:#fff;padding:12px 13px;outline:none;font-size:13px}.quote-field textarea{resize:vertical;min-height:180px;line-height:1.6}.quote-field input[type=file]{padding:10px}.quote-field input[type=range]{padding:0;accent-color:#38bdf8}.quote-field input[type=color]{width:54px;min-width:54px;height:44px;padding:4px}.quote-field small{display:block;margin-top:7px;color:rgba(255,255,255,.50);font-size:11.5px;line-height:1.35}.quote-color-row{display:grid;grid-template-columns:58px minmax(0,1fr);gap:10px}.quote-check{display:flex;align-items:center;gap:10px;color:rgba(255,255,255,.78);font-size:12.5px;font-weight:700;min-height:44px}.quote-check input{width:18px;height:18px}.quote-palette-row{display:flex;gap:10px;flex-wrap:wrap}.quote-palette{width:40px;height:40px;border-radius:999px;border:2px solid rgba(255,255,255,.25);cursor:pointer}.quote-format-list{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px}.quote-format-option{display:flex;gap:10px;align-items:center;border:1px solid rgba(255,255,255,.10);background:rgba(2,6,23,.35);border-radius:16px;padding:12px;cursor:pointer}.quote-format-option input{width:16px;height:16px}.quote-format-option strong{display:block;font-size:13px;color:#fff}.quote-format-option small{display:block;margin-top:2px;color:rgba(255,255,255,.52)}.quote-preview-toolbar{display:flex;justify-content:space-between;gap:10px;margin-bottom:10px;color:rgba(255,255,255,.60);font-size:11px;font-weight:800;text-transform:uppercase;letter-spacing:.05em}.quote-card-stage{display:flex;justify-content:center;align-items:flex-start;overflow:auto;padding:10px}.quote-card-preview{position:relative;width:min(100%,390px);aspect-ratio:4/5;border-radius:26px;overflow:hidden;background:#160042;border:1px solid rgba(255,255,255,.10);box-shadow:0 24px 70px rgba(0,0,0,.35)}.quote-card-bg{position:absolute;inset:0;background:linear-gradient(135deg,#160042,#e2388a)}.quote-card-bg img{width:100%;height:100%;object-fit:cover;display:block}.quote-card-overlay{position:absolute;inset:0;background:linear-gradient(to bottom,rgba(0,0,0,.08),rgba(0,0,0,.35),rgba(0,0,0,.72))}.quote-mark{position:absolute;top:28px;left:28px;font-size:96px;line-height:.8;font-weight:900;color:rgba(255,255,255,.16);z-index:3}.quote-card-content{position:absolute;inset:34px;z-index:4;display:flex;flex-direction:column;justify-content:center;align-items:center;color:#fff;text-align:center}.quote-card-inner{width:86%;max-width:100%}.quote-card-text{font-size:24px;line-height:1.35;font-weight:700;white-space:pre-wrap;text-shadow:none;overflow-wrap:anywhere}.quote-card-source{font-size:14px;line-height:1.45;font-weight:700;margin-top:18px;opacity:.9}.quote-preview-note{margin-top:14px;border:1px solid rgba(255,255,255,.08);background:rgba(2,6,23,.30);border-radius:16px;padding:14px;color:rgba(255,255,255,.65);font-size:12.5px;line-height:1.5}.quote-preview-note strong{display:block;color:#fff;margin-bottom:4px}.quote-preview-note code{color:#67e8f9}.quote-danger{border-color:rgba(248,113,113,.34);background:rgba(127,29,29,.12)}.quote-action-row{display:flex;gap:10px;flex-wrap:wrap}@media(max-width:760px){.quote-grid,.quote-format-list{grid-template-columns:1fr}.quote-action-row .dxm-btn{width:100%}}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('quoteDesignerForm');
    const actionInput = document.getElementById('designerAction');
    const deleteInput = document.getElementById('deleteConfirmation');
    const fields = {
        quote: document.getElementById('quote_text'), source: document.getElementById('quote_source'),
        bgMode: document.getElementById('background_mode'), cover: document.getElementById('cover_image_url'),
        bg: document.getElementById('bg_color'), bg2: document.getElementById('bg_color_2'),
        text: document.getElementById('text_color'), sourceColor: document.getElementById('source_color'), highlightColor: document.getElementById('highlight_color'), overlay: document.getElementById('overlay_strength'),
        align: document.getElementById('text_align'), vertical: document.getElementById('vertical_align'),
        weight: document.getElementById('font_weight'), quoteSize: document.getElementById('quote_size'),
        sourceSize: document.getElementById('source_size'), sourceWeight: document.getElementById('source_weight'), highlightPhrases: document.getElementById('highlight_phrases'), highlightScale: document.getElementById('highlight_scale'), highlightWeight: document.getElementById('highlight_weight'), preset: document.getElementById('style_preset'), accent: document.getElementById('accent_color'),
        mark: document.getElementById('show_quote_mark'), font: document.getElementById('font_family'),
        scaleMode: document.getElementById('text_scale_mode'), contentWidth: document.getElementById('content_width'),
        cardPadding: document.getElementById('card_padding'), cardPaddingX: document.getElementById('card_padding_x'), cardPaddingY: document.getElementById('card_padding_y'), lineHeight: document.getElementById('line_height'), textShadow: document.getElementById('text_shadow'), offsetX: document.getElementById('offset_x'), offsetY: document.getElementById('offset_y'), letterSpacing: document.getElementById('letter_spacing'), sourceSpacing: document.getElementById('source_spacing'), quoteMarkSize: document.getElementById('quote_mark_size'), quoteMarkOpacity: document.getElementById('quote_mark_opacity'), quoteMarkPosition: document.getElementById('quote_mark_position'), bgFit: document.getElementById('background_fit'), bgPosition: document.getElementById('background_position'), overlayStyle: document.getElementById('overlay_style'), borderRadius: document.getElementById('border_radius')
    };
    const card = document.getElementById('quoteCardPreview'), previewBg = document.getElementById('quotePreviewBg'), previewContent = document.getElementById('quotePreviewContent'), previewInner = document.getElementById('quotePreviewInner'), previewText = document.getElementById('quotePreviewText'), previewSource = document.getElementById('quotePreviewSource'), previewMark = document.getElementById('quotePreviewMark');

    function esc(value){return String(value||'').replace(/[&<>"']/g,m=>({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[m]));}
    function fontStack(value){if(value==='arial')return 'Arial, Helvetica, sans-serif'; if(value==='inter')return 'Inter, Arial, Helvetica, sans-serif'; if(value==='verdana')return 'Verdana, Geneva, sans-serif'; if(value==='tahoma')return 'Tahoma, Geneva, sans-serif'; if(value==='trebuchet')return 'Trebuchet MS, Arial, sans-serif'; if(value==='serif')return 'Georgia, Times New Roman, serif'; if(value==='georgia')return 'Georgia, serif'; if(value==='times')return 'Times New Roman, Times, serif'; if(value==='garamond')return 'Garamond, Georgia, serif'; if(value==='impact')return 'Impact, Arial Black, sans-serif'; if(value==='arial_black')return 'Arial Black, Impact, sans-serif'; if(value==='courier')return 'Courier New, Courier, monospace'; if(value==='mono')return 'Consolas, Monaco, monospace'; return 'Arial, Helvetica, sans-serif';}
    function currentFormatRadio(){return document.querySelector('input[name="card_format"]:checked');}
    function autoSize(baseSize,text){if(!fields.scaleMode||fields.scaleMode.value!=='auto')return baseSize; const l=(text||'').length; if(l>420)return Math.max(10,baseSize-12); if(l>300)return Math.max(11,baseSize-9); if(l>220)return Math.max(12,baseSize-6); if(l>150)return Math.max(13,baseSize-3); return baseSize;}
    function escapeRegExp(value){return String(value).replace(/[.*+?^${}()|[\]\\]/g,'\\$&');}
    function highlightedHtml(text){
        let html=esc(text).replace(/\n/g,'<br>');
        const raw=(fields.highlightPhrases?fields.highlightPhrases.value:'').trim();
        if(!raw)return html;
        const phrases=raw.split(/[\r\n,]+/).map(v=>v.trim()).filter(Boolean).sort((a,b)=>b.length-a.length).slice(0,12);
        const color=(fields.highlightColor&&fields.highlightColor.value)||'#facc15';
        const scale=(fields.highlightScale&&fields.highlightScale.value)||'1.08';
        const weight=(fields.highlightWeight&&fields.highlightWeight.value)||'800';
        phrases.forEach(function(phrase){
            const safe=esc(phrase);
            if(!safe)return;
            const re=new RegExp(escapeRegExp(safe),'gi');
            html=html.replace(re,function(match){return '<span class="dxm-highlight-text" style="color:'+color+';font-size:'+scale+'em;font-weight:'+weight+';">'+match+'</span>';});
        });
        return html;
    }

    function updatePreview(){
        const mode=fields.bgMode.value||'gradient', cover=(fields.cover.value||'').trim(), bg=fields.bg.value||'#160042', bg2=fields.bg2.value||'#e2388a', overlayValue=parseInt(fields.overlay.value||'58',10)/100, quoteText=fields.quote.value||'Quote text will appear here.', base=parseInt(fields.quoteSize.value||'24',10), fmt=currentFormatRadio(), ratio=fmt?fmt.getAttribute('data-format-ratio'):'4 / 5';
        card.style.aspectRatio=ratio;
        card.style.borderRadius=(fields.borderRadius?fields.borderRadius.value:26)+'px';
        previewBg.innerHTML='';
        if(mode==='image'&&cover!==''){
            previewBg.style.background='linear-gradient(135deg,'+bg+','+bg2+')';
            previewBg.innerHTML='<img src="'+esc(cover)+'" alt="">';
            const img=previewBg.querySelector('img');
            if(img){img.style.objectFit=(fields.bgFit&&fields.bgFit.value==='fill')?'fill':((fields.bgFit&&fields.bgFit.value)||'cover'); img.style.objectPosition=(fields.bgPosition&&fields.bgPosition.value)||'center';}
        } else if(mode==='solid'){
            previewBg.style.background=bg;
        } else {
            previewBg.style.background='linear-gradient(135deg,'+bg+','+bg2+')';
        }
        const overlay=document.getElementById('quotePreviewOverlay');
        const overlayStyle=(fields.overlayStyle&&fields.overlayStyle.value)||'bottom';
        if(overlayStyle==='none') overlay.style.background='transparent';
        else if(overlayStyle==='full') overlay.style.background='rgba(0,0,0,'+overlayValue+')';
        else if(overlayStyle==='top_bottom') overlay.style.background='linear-gradient(to bottom, rgba(0,0,0,'+(overlayValue*.75)+'), rgba(0,0,0,'+(overlayValue*.15)+'), rgba(0,0,0,'+overlayValue+'))';
        else overlay.style.background='linear-gradient(to bottom, rgba(0,0,0,0.04), rgba(0,0,0,'+(overlayValue*.40)+'), rgba(0,0,0,'+overlayValue+'))';
        previewContent.style.color=fields.text.value||'#fff';
        previewContent.style.textAlign=fields.align.value||'center';
        previewContent.style.justifyContent=fields.vertical.value==='start'?'flex-start':(fields.vertical.value==='end'?'flex-end':'center');
        previewContent.style.alignItems=fields.align.value==='left'?'flex-start':(fields.align.value==='right'?'flex-end':'center');
        if(fields.cardPadding){fields.cardPadding.value=String(Math.max(parseInt(fields.cardPaddingX.value||fields.cardPadding.value||34,10), parseInt(fields.cardPaddingY.value||fields.cardPadding.value||34,10)));}
        previewContent.style.inset=(fields.cardPaddingY.value||fields.cardPadding.value||34)+'px '+(fields.cardPaddingX.value||fields.cardPadding.value||34)+'px';
        previewContent.style.transform='translate('+(fields.offsetX?fields.offsetX.value:0)+'px,'+(fields.offsetY?fields.offsetY.value:0)+'px)';
        previewInner.style.width=(fields.contentWidth.value||86)+'%';
        previewInner.style.fontFamily=fontStack(fields.font.value);
        previewText.innerHTML=highlightedHtml(quoteText);
        previewSource.textContent=fields.source.value||'';
        previewText.style.fontSize=autoSize(base,quoteText)+'px';
        previewText.style.fontWeight=fields.weight.value||'700';
        previewText.style.lineHeight=fields.lineHeight.value||'1.35';
        previewText.style.letterSpacing=(fields.letterSpacing?fields.letterSpacing.value:0)+'px';
        const shadowMode=fields.textShadow?fields.textShadow.value:'soft';
        previewText.style.textShadow=shadowMode==='off'?'none':(shadowMode==='strong'?'0 4px 18px rgba(0,0,0,.65)':'0 2px 10px rgba(0,0,0,.35)');
        previewSource.style.fontSize=(fields.sourceSize.value||14)+'px';
        previewSource.style.color=(fields.sourceColor&&fields.sourceColor.value)||fields.accent.value||'#38bdf8';
        previewSource.style.fontWeight=(fields.sourceWeight&&fields.sourceWeight.value)||'700';
        previewSource.style.textShadow=previewText.style.textShadow;
        previewSource.style.marginTop=(fields.sourceSpacing?fields.sourceSpacing.value:18)+'px';
        previewSource.style.display=(fields.source.value||'').trim()?'block':'none';
        previewMark.style.display=fields.mark.checked?'block':'none';
        previewMark.style.fontSize=(fields.quoteMarkSize?fields.quoteMarkSize.value:96)+'px';
        previewMark.style.color='rgba(255,255,255,'+((parseInt(fields.quoteMarkOpacity?fields.quoteMarkOpacity.value:16,10)||0)/100)+')';
        previewMark.style.top=''; previewMark.style.bottom=''; previewMark.style.left=''; previewMark.style.right=''; previewMark.style.transform='';
        const markPos=(fields.quoteMarkPosition&&fields.quoteMarkPosition.value)||'top_left';
        if(markPos.indexOf('top')===0) previewMark.style.top='28px';
        if(markPos.indexOf('bottom')===0) previewMark.style.bottom='28px';
        if(markPos.indexOf('center')===0){previewMark.style.top='50%'; previewMark.style.transform='translateY(-50%)';}
        if(markPos.indexOf('right')>-1) previewMark.style.right='28px'; else previewMark.style.left='28px';
        const map={quoteSizeValue:fields.quoteSize.value,sourceSizeValue:fields.sourceSize.value,overlayValue:fields.overlay.value,contentWidthValue:fields.contentWidth.value,cardPaddingValue:fields.cardPadding.value,cardPaddingXValue:(fields.cardPaddingX?fields.cardPaddingX.value:fields.cardPadding.value),cardPaddingYValue:(fields.cardPaddingY?fields.cardPaddingY.value:fields.cardPadding.value),lineHeightValue:fields.lineHeight.value,highlightScaleValue:(fields.highlightScale?fields.highlightScale.value:'1.08'),offsetXValue:(fields.offsetX?fields.offsetX.value:0),offsetYValue:(fields.offsetY?fields.offsetY.value:0),letterSpacingValue:(fields.letterSpacing?fields.letterSpacing.value:0),sourceSpacingValue:(fields.sourceSpacing?fields.sourceSpacing.value:18),quoteMarkSizeValue:(fields.quoteMarkSize?fields.quoteMarkSize.value:96),quoteMarkOpacityValue:(fields.quoteMarkOpacity?fields.quoteMarkOpacity.value:16),borderRadiusValue:(fields.borderRadius?fields.borderRadius.value:26),quoteCharacterCount:(fields.quote.value||'').length+' characters'};
        Object.keys(map).forEach(id=>{const el=document.getElementById(id); if(el)el.textContent=map[id];});
        const label=document.getElementById('quoteFormatLabel'); if(label&&fmt)label.textContent=fmt.closest('label').querySelector('strong').textContent;
    }

    document.querySelectorAll('[data-color-copy]').forEach(input=>input.addEventListener('input',()=>{const c=document.getElementById(input.dataset.colorCopy); if(c&&/^#[0-9A-Fa-f]{6}$/.test(input.value)){c.value=input.value; updatePreview();}}));
    document.querySelectorAll('.quote-palette').forEach(button=>button.addEventListener('click',()=>{fields.bg.value=button.dataset.bg; fields.bg2.value=button.dataset.bg2; fields.text.value=button.dataset.text; if(fields.sourceColor&&button.dataset.source)fields.sourceColor.value=button.dataset.source; if(fields.highlightColor&&button.dataset.highlight)fields.highlightColor.value=button.dataset.highlight; if(fields.accent&&button.dataset.accent)fields.accent.value=button.dataset.accent; fields.preset.value=button.dataset.preset; fields.bgMode.value='gradient'; updatePreview();}));
    const duplicateButton=document.getElementById('duplicateQuoteButton'); if(duplicateButton&&form&&actionInput){duplicateButton.addEventListener('click',()=>{if(!window.confirm('Duplicate this quote design as a new draft?'))return; actionInput.value='duplicate'; form.submit();});}
    const deleteButton=document.getElementById('deleteQuoteButton'); if(deleteButton&&form&&actionInput&&deleteInput){deleteButton.addEventListener('click',()=>{if(!window.confirm('Delete this quote design permanently?'))return; const typed=window.prompt('Type DELETE to confirm.'); if(typed!=='DELETE'){alert('Delete cancelled. You must type DELETE exactly.'); return;} actionInput.value='delete'; deleteInput.value='DELETE'; form.submit();});}
    document.querySelectorAll('input, select, textarea').forEach(field=>{field.addEventListener('input',updatePreview); field.addEventListener('change',updatePreview);});
    updatePreview();
});
</script>
@endpush

@once
@push('styles')
<style>
    [data-dxm-tab] { position: relative; z-index: 5; pointer-events: auto; cursor: pointer; }
    .dxm-tab-panel:not(.is-active) { display: none; }
</style>
@endpush
@endonce


@once
@push('scripts')
<script>
(function () {
    function activateDxmTab(tabName) {
        if (!tabName) return;

        document.querySelectorAll('[data-dxm-tab]').forEach(function (tab) {
            tab.classList.toggle('is-active', tab.getAttribute('data-dxm-tab') === tabName);
            tab.setAttribute('aria-selected', tab.getAttribute('data-dxm-tab') === tabName ? 'true' : 'false');
        });

        document.querySelectorAll('[data-dxm-tab-panel]').forEach(function (panel) {
            var isActive = panel.getAttribute('data-dxm-tab-panel') === tabName;
            panel.classList.toggle('is-active', isActive);
            panel.hidden = !isActive;
            panel.style.display = isActive ? '' : 'none';
        });

        try {
            window.localStorage.setItem('dxm_beginner_studio_active_tab:' + window.location.pathname, tabName);
        } catch (e) {}
    }

    function initDxmTabs() {
        var firstActive = document.querySelector('[data-dxm-tab].is-active');
        var firstTab = document.querySelector('[data-dxm-tab]');
        var tabName = firstActive ? firstActive.getAttribute('data-dxm-tab') : (firstTab ? firstTab.getAttribute('data-dxm-tab') : null);

        try {
            var saved = window.localStorage.getItem('dxm_beginner_studio_active_tab:' + window.location.pathname);
            if (saved && document.querySelector('[data-dxm-tab="' + CSS.escape(saved) + '"]')) {
                tabName = saved;
            }
        } catch (e) {}

        activateDxmTab(tabName);
    }

    document.addEventListener('click', function (event) {
        var button = event.target && event.target.closest ? event.target.closest('[data-dxm-tab]') : null;
        if (!button) return;

        event.preventDefault();
        event.stopPropagation();
        activateDxmTab(button.getAttribute('data-dxm-tab'));
    }, true);

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDxmTabs);
    } else {
        initDxmTabs();
    }
})();
</script>
@endpush
@endonce

