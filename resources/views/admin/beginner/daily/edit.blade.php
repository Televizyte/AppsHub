@extends('layouts.beginner')

@section('title', $kind === 'scripture' ? 'Edit Daily Scripture' : 'Edit Daily Quote')
@section('eyebrow', 'Beginner Daily Designer')
@section('page_title', $kind === 'scripture' ? 'Edit Daily Scripture' : 'Edit Daily Quote')
@section('page_description', 'Design the daily home card using the shared designer engine.')
@section('back_url', $returnUrl)
@section('form_title', $kind === 'scripture' ? 'Daily Scripture Designer' : 'Daily Quote Designer')
@section('form_description', 'Use the same design engine used by quote cards: content, format, design, media, and publishing.')
@section('preview_description', 'Shared designer live preview.')

@php
    $payload = is_array($payload ?? null) ? $payload : [];

    $designerValues = [
        'mode' => old('mode', $payload['mode'] ?? 'manual'),
        'background_mode' => old('background_mode', $payload['background_mode'] ?? 'gradient'),
        'card_format' => old('card_format', $payload['card_format'] ?? 'portrait'),
        'font_family' => old('font_family', $payload['font_family'] ?? 'system'),
        'text_scale_mode' => old('text_scale_mode', $payload['text_scale_mode'] ?? 'auto'),
        'text_color' => old('text_color', $payload['text_color'] ?? '#ffffff'),
        'source_color' => old('source_color', $payload['source_color'] ?? ($payload['accent_color'] ?? '#38bdf8')),
        'bg_color' => old('bg_color', $payload['bg_color'] ?? '#160042'),
        'bg_color_2' => old('bg_color_2', $payload['bg_color_2'] ?? '#e2388a'),
        'accent_color' => old('accent_color', $payload['accent_color'] ?? '#38bdf8'),
        'font_size' => old('font_size', $payload['font_size'] ?? '14'),
        'title_size' => old('title_size', $payload['title_size'] ?? '24'),
        'font_weight' => old('font_weight', $payload['font_weight'] ?? '700'),
        'source_weight' => old('source_weight', $payload['source_weight'] ?? '700'),
        'highlight_phrases' => old('highlight_phrases', $payload['highlight_phrases'] ?? ''),
        'highlight_color' => old('highlight_color', $payload['highlight_color'] ?? '#facc15'),
        'highlight_scale' => old('highlight_scale', $payload['highlight_scale'] ?? '1.08'),
        'highlight_weight' => old('highlight_weight', $payload['highlight_weight'] ?? '800'),
        'text_align' => old('text_align', $payload['text_align'] ?? 'center'),
        'vertical_align' => old('vertical_align', $payload['vertical_align'] ?? 'center'),
        'overlay_strength' => old('overlay_strength', $payload['overlay_strength'] ?? '58'),
        'content_width' => old('content_width', $payload['content_width'] ?? '86'),
        'card_padding' => old('card_padding', $payload['card_padding'] ?? '34'),
        'card_padding_x' => old('card_padding_x', $payload['card_padding_x'] ?? ($payload['card_padding'] ?? '34')),
        'card_padding_y' => old('card_padding_y', $payload['card_padding_y'] ?? ($payload['card_padding'] ?? '34')),
        'line_height' => old('line_height', $payload['line_height'] ?? '1.35'),
        'text_shadow' => old('text_shadow', $payload['text_shadow'] ?? 'soft'),
        'show_quote_mark' => (bool) old('show_quote_mark', $payload['show_quote_mark'] ?? true),
    ];

    $designerKind = $kind === 'scripture' ? 'scripture' : 'daily_quote';

    $resolvedTitle = old('title', $item->title ?? '');
    $resolvedImageUrl = old('image_url', $item->image_url ?? '');
@endphp

@include('admin.beginner.shared.designer.styles')

@section('studio_tabs')
    <button type="button" class="dxm-dock-tab is-active" data-dxm-tab="content">Content</button>
    <button type="button" class="dxm-dock-tab" data-dxm-tab="format">Format</button>
    <button type="button" class="dxm-dock-tab" data-dxm-tab="design">Design</button>
    <button type="button" class="dxm-dock-tab" data-dxm-tab="media">Media</button>
    <button type="button" class="dxm-dock-tab" data-dxm-tab="publishing">Publishing</button>
@endsection

@section('form')
    @if (session('status'))
        <div class="dxm-alert">{{ session('status') }}</div>
    @endif

    @if ($errors->any())
        <div class="dxm-alert dxm-alert--danger">
            <strong>Please fix these issues:</strong>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" enctype="multipart/form-data" action="{{ route('admin.beginner.daily.update', ['kind' => $kind]) }}" id="dailyDesignerForm">
        @csrf
        @method('PUT')
        <input type="hidden" name="return" value="{{ $returnTo ?? 'home' }}">

        <div class="dxm-tab-panel is-active" data-dxm-tab-panel="content">
            <div class="dxm-designer-panel">
                <h3>{{ $kind === 'scripture' ? 'Scripture Content' : 'Quote Content' }}</h3>
                <p>{{ $kind === 'scripture' ? 'Scripture has scripture text, short note, and Bible reference.' : 'Daily quote has quote text and source/tag.' }}</p>

                <div class="dxm-designer-grid">
                    <div class="dxm-designer-field">
                        <label for="title">Backend Card Title</label>
                        <input id="title" name="title" type="text" value="{{ $resolvedTitle }}" spellcheck="true" autocomplete="off">
                    </div>

                    <div class="dxm-designer-field">
                        <label for="mode">Display Mode</label>
                        <select id="mode" name="mode" data-dxm-designer-input>
                            <option value="manual" @selected($designerValues['mode'] === 'manual')>Manual — use this text</option>
                            <option value="auto" @selected($designerValues['mode'] === 'auto')>Auto — rotation ready</option>
                        </select>
                    </div>

                    @if ($kind === 'scripture')
                        <div class="dxm-designer-field dxm-designer-full">
                            <label for="verse">Scripture Text</label>
                            <textarea id="verse" name="verse" rows="9" placeholder="Enter the scripture text here" spellcheck="true" lang="en" autocomplete="off" data-dxm-designer-input>{{ old('verse', $payload['verse'] ?? '') }}</textarea>
                        </div>

                        <div class="dxm-designer-field dxm-designer-full">
                            <label for="note">Short Note</label>
                            <textarea id="note" name="note" rows="5" placeholder="Add a short encouragement or explanation" spellcheck="true" lang="en" autocomplete="off" data-dxm-designer-input>{{ old('note', $payload['note'] ?? '') }}</textarea>
                        </div>

                        <div class="dxm-designer-field dxm-designer-full">
                            <label for="highlight_phrases">Highlight Words / Phrases</label>
                            <textarea id="highlight_phrases" name="highlight_phrases" rows="3" placeholder="Separate words or phrases with commas or new lines." data-dxm-designer-input>{{ $designerValues['highlight_phrases'] }}</textarea>
                            <small>Highlight important words with a different color/size in the quick preview.</small>
                        </div>

                        <div class="dxm-designer-field dxm-designer-full">
                            <label for="ref">Bible Reference</label>
                            <input id="ref" name="ref" type="text" value="{{ old('ref', $payload['ref'] ?? '') }}" placeholder="Example: Jeremiah 29:11" spellcheck="true" autocomplete="off" data-dxm-designer-input>
                        </div>
                    @else
                        <div class="dxm-designer-field dxm-designer-full">
                            <label for="quote">Quote Text</label>
                            <textarea id="quote" name="quote" rows="9" placeholder="Enter the quote here" spellcheck="true" lang="en" autocomplete="off" data-dxm-designer-input>{{ old('quote', $payload['quote'] ?? '') }}</textarea>
                        </div>

                        <div class="dxm-designer-field dxm-designer-full">
                            <label for="source">Source / Tag</label>
                            <input id="source" name="source" type="text" value="{{ old('source', $payload['source'] ?? '') }}" placeholder="Example: Dunamis TV • Inspire" spellcheck="true" autocomplete="off" data-dxm-designer-input>
                        </div>

                        <div class="dxm-designer-field dxm-designer-full">
                            <label for="highlight_phrases">Highlight Words / Phrases</label>
                            <textarea id="highlight_phrases" name="highlight_phrases" rows="3" placeholder="Separate words or phrases with commas or new lines." data-dxm-designer-input>{{ $designerValues['highlight_phrases'] }}</textarea>
                            <small>Highlight important words with a different color/size in the quick preview.</small>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="dxm-tab-panel" data-dxm-tab-panel="format">
            @include('admin.beginner.shared.designer.format-panel', [
                'designerValues' => $designerValues,
                'designerFormatOptions' => $formatOptions ?? null,
            ])
        </div>

        <div class="dxm-tab-panel" data-dxm-tab-panel="design">
            @include('admin.beginner.shared.designer.design-panel', [
                'designerValues' => $designerValues,
                'designerFontOptions' => $fontOptions ?? null,
                'designerMainSizeName' => 'title_size',
                'designerMainSizeLabel' => 'Main Text Size',
                'designerSupportSizeName' => 'font_size',
                'designerSupportSizeLabel' => 'Supporting Text Size',
            ])
        </div>

        <div class="dxm-tab-panel" data-dxm-tab-panel="media">
            <div class="dxm-designer-panel">
                <h3>Background Media</h3>
                <p>Upload a new background, select from the active app media library, or paste an image URL.</p>

                <div class="dxm-designer-grid">
                    <div class="dxm-designer-field dxm-designer-full">
                        <label for="image_file">Quick Upload New Background</label>
                        <input id="image_file" name="image_file" type="file" accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
                    </div>

                    <div class="dxm-designer-field dxm-designer-full">
                        <label for="image_url">Image URL / Storage Path</label>
                        <input id="image_url" name="image_url" type="text" value="{{ $resolvedImageUrl }}" placeholder="Paste URL or select from Media Library" spellcheck="false" data-dxm-designer-input>
                        <small>When background mode is set to Image, this is the image used for the card.</small>
                    </div>

                    <div class="dxm-designer-field dxm-designer-full">
                        @include('admin.beginner.partials.media-picker', [
                            'pickerId' => 'dailyMediaCenter',
                            'inputId' => 'image_url',
                            'selectName' => 'media_asset_id',
                            'assets' => $mediaAssets ?? collect(),
                            'title' => $kind === 'scripture' ? 'Daily Scripture Background Library' : 'Daily Quote Background Library',
                            'subtitle' => 'Browse, upload, preview, and select the background image for this daily card.',
                            'buttonLabel' => 'Open Background Library',
                            'uploadBucket' => 'daily',
                            'uploadLabel' => $kind === 'scripture' ? 'Daily Scripture Background' : 'Daily Quote Background',
                            'emptyText' => 'No daily images found yet. Upload one from the modal.'
                        ])
                    </div>
                </div>
            </div>
        </div>

        <div class="dxm-tab-panel" data-dxm-tab-panel="publishing">
            <div class="dxm-designer-panel">
                <h3>Publishing</h3>
                <p>Save this daily card for the active app. The frontend receives the design payload from backend.</p>

                <div class="dxm-designer-grid">
                    <div class="dxm-designer-field">
                        <label>Status</label>
                        <input type="text" value="Enabled" disabled>
                    </div>

                    <div class="dxm-designer-field">
                        <label>Display Order</label>
                        <input type="text" value="10" disabled>
                    </div>
                </div>
            </div>
        </div>

        <div class="dxm-submit">
            <a href="{{ $returnUrl }}" class="dxm-btn">← {{ ($returnTo ?? 'home') === 'quote-engine' ? 'Back to Quote Engine' : 'Back to Home' }}</a>
            <button type="submit" class="dxm-btn dxm-btn--primary">Save Changes</button>
        </div>
    </form>
@endsection

@section('preview')
    @include('admin.beginner.shared.designer.preview-card', [
        'designerKind' => $designerKind,
        'designerPreviewMainId' => $kind === 'scripture' ? 'dailyPreviewVerse' : 'dailyPreviewQuote',
        'designerPreviewSupportId' => $kind === 'scripture' ? 'dailyPreviewNote' : 'dailyPreviewSource',
        'designerPreviewRefId' => 'dailyPreviewRef',
    ])
@endsection

@include('admin.beginner.shared.designer.scripts')