@extends('layouts.beginner')

@section('title', 'Create Section')
@section('eyebrow', 'Beginner Builder')
@section('page_title', 'Create Section')
@section('page_description', 'Build a reusable frontend section for any app tab, page, content engine, quote channel, short channel, article list, book row, link group, or ad slot.')
@section('back_url', $returnUrl)
@section('form_title', 'Unified Section Builder')
@section('form_description', 'Use the tabs below. Keep the page compact while controlling placement, content source, layout, actions, and insertions.')
@section('preview_description', 'Live preview summarizes the destination, source, layout, and frontend route.')

@php
    $meta = $meta ?? [];
    $v = fn ($key, $default = '') => old($key, $meta[$key] ?? $default);
@endphp

@section('form')
    @if ($errors->any())
        <div class="dxm-alert dxm-alert--danger">
            <strong>Please check the form.</strong>
            <span>{{ $errors->first() }}</span>
        </div>
    @endif

    <form method="POST" action="{{ route('admin.beginner.sections.store') }}" id="sectionBuilderForm">
        @csrf

        <input type="hidden" name="return" value="{{ $returnTo }}">
        <input type="hidden" name="current_tab" value="{{ $currentTab ?? $prefillTab ?? 'home' }}">

        <div class="builder-tabs" role="tablist">
            <button type="button" class="builder-tab is-active" data-tab="basic">Basic</button>
            <button type="button" class="builder-tab" data-tab="placement">Placement</button>
            <button type="button" class="builder-tab" data-tab="source">Content Source</button>
            <button type="button" class="builder-tab" data-tab="layout">Layout</button>
            <button type="button" class="builder-tab" data-tab="insertions">Ads / Insertions</button>
        </div>

        <div class="builder-panel is-active" data-panel="basic">
            <div class="builder-grid">
                <div class="field">
                    <label for="title">Section Title</label>
                    <input id="title" name="title" type="text" value="{{ old('title') }}" placeholder="Example: Dr Enenche Quotes">
                </div>
                <div class="field">
                    <label for="subtitle">Section Subtitle</label>
                    <input id="subtitle" name="subtitle" type="text" value="{{ old('subtitle') }}" placeholder="Optional short description">
                </div>
                <div class="field">
                    <label for="key">Section Key</label>
                    <input id="key" name="key" type="text" value="{{ old('key') }}" placeholder="dr_enenche_quotes">
                    <small>Use a stable key. This helps Flutter identify the section consistently.</small>
                </div>
                <div class="field">
                    <label for="sort_order">Display Order</label>
                    <input id="sort_order" name="sort_order" type="number" value="{{ old('sort_order', 0) }}">
                </div>
                <label class="check full">
                    <input type="checkbox" id="is_enabled" name="is_enabled" value="1" @checked(old('is_enabled', true))>
                    <span>Section enabled</span>
                </label>
            </div>
        </div>

        <div class="builder-panel" data-panel="placement">
            <div class="builder-grid">
                <div class="field">
                    <label for="tab_key">Destination Tab</label>
                    <select id="tab_key" name="tab_key">
                        @foreach ($tabs as $value => $label)
                            <option value="{{ $value }}" @selected(old('tab_key', $prefillTab) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="route_key">Page Key / Custom Page</label>
                    <input id="route_key" name="route_key" type="text" value="{{ old('route_key', $prefillRouteKey) }}" placeholder="Leave blank for main tab screen">
                </div>
                <div class="field">
                    <label for="placement_type">Placement Type</label>
                    <select id="placement_type" name="placement_type">
                        @foreach ($placementTypes as $value => $label)
                            <option value="{{ $value }}" @selected(old('placement_type', 'main_tab') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="target_route">Frontend Route</label>
                    <input id="target_route" name="target_route" type="text" value="{{ old('target_route') }}" placeholder="/sod/quotes, /short-videos, /articles, /tools/books">
                    <small>The route Flutter should open when a card/button in this section is tapped.</small>
                </div>
            </div>
        </div>

        <div class="builder-panel" data-panel="source">
            <div class="builder-grid">
                <div class="field full">
                    <label for="source_type">Content Source / Engine</label>
                    <select id="source_type" name="source_type">
                        @foreach (($sourceOptions ?? []) as $value => $label)
                            <option value="{{ $value }}" @selected(old('source_type', 'manual_items') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="source_bucket">Bucket / Content Group</label>
                    <select id="source_bucket" name="source_bucket">
                        <option value="">Auto / not required</option>
                        @foreach (($contentBuckets ?? []) as $value => $label)
                            <option value="{{ $value }}" @selected(old('source_bucket') === $value)>{{ $label }} — {{ $value }}</option>
                        @endforeach
                    </select>
                    <small>For quote channels, choose the exact dynamic quote bucket such as quote_dr_eneches_quotes.</small>
                </div>
                <div class="field">
                    <label for="max_items">Maximum Items</label>
                    <input id="max_items" name="max_items" type="number" min="1" max="60" value="{{ old('max_items', 12) }}">
                </div>
                <div class="field">
                    <label for="source_category">Short Video Category</label>
                    <select id="source_category" name="source_category">
                        <option value="">Any category</option>
                        @foreach (($shortVideoCategories ?? []) as $value => $label)
                            <option value="{{ $value }}" @selected(old('source_category') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="source_channel">Short Channel</label>
                    <select id="source_channel" name="source_channel">
                        <option value="">No channel filter</option>
                        @foreach (($shortVideoChannels ?? []) as $value => $label)
                            <option value="{{ $value }}" @selected(old('source_channel') === $value)>{{ $label }} — {{ $value }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="builder-panel" data-panel="layout">
            <div class="builder-grid">
                <div class="field">
                    <label for="template">Template / Renderer</label>
                    <select id="template" name="template">
                        @foreach ($templates as $value => $label)
                            <option value="{{ $value }}" @selected(old('template') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="layout_variant">Layout Variant</label>
                    <select id="layout_variant" name="layout_variant">
                        @foreach ($layoutVariants as $value => $label)
                            <option value="{{ $value }}" @selected(old('layout_variant', 'default') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="card_style">Card Style</label>
                    <select id="card_style" name="card_style">
                        @foreach ($cardStyles as $value => $label)
                            <option value="{{ $value }}" @selected(old('card_style', 'image') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="scroll_behavior">Scroll Behavior</label>
                    <select id="scroll_behavior" name="scroll_behavior">
                        @foreach ($scrollModes as $value => $label)
                            <option value="{{ $value }}" @selected(old('scroll_behavior', 'none') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field">
                    <label for="columns">Columns</label>
                    <input id="columns" name="columns" type="number" min="1" max="4" value="{{ old('columns', 1) }}">
                </div>
                <div class="field">
                    <label for="size_preset">Size Preset</label>
                    <select id="size_preset" name="size_preset">
                        @foreach ($sizePresets as $value => $label)
                            <option value="{{ $value }}" @selected(old('size_preset', 'normal') === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
        </div>

        <div class="builder-panel" data-panel="insertions">
            <div class="builder-grid">
                <div class="field">
                    <label for="insert_target_bucket">Insert Inside Content Bucket</label>
                    <select id="insert_target_bucket" name="insert_target_bucket">
                        <option value="">No article-list insertion</option>
                        @foreach (($contentBuckets ?? []) as $value => $label)
                            <option value="{{ $value }}" @selected(old('insert_target_bucket') === $value)>{{ $label }} — {{ $value }}</option>
                        @endforeach
                    </select>
                    <small>Use this to insert a quote carousel, short-video row, ad, or promo inside an article/content list.</small>
                </div>
                <div class="field">
                    <label for="insert_after_items">Insert After Every X Items</label>
                    <input id="insert_after_items" name="insert_after_items" type="number" min="0" max="50" value="{{ old('insert_after_items', 0) }}" placeholder="Example: 3">
                </div>
            </div>
            <div class="builder-note">Example: Inside Dunamis articles can show Article 1, Article 2, Article 3, then this quote carousel/ad slot, then continue the article list.</div>
        </div>

        <div class="action-row">
            <a href="{{ $returnUrl }}" class="dxm-btn">← Back</a>
            <button type="submit" class="dxm-btn dxm-btn--primary">Create Section</button>
        </div>
    </form>
@endsection

@section('preview')
    <div class="builder-preview">
        <div class="preview-card">
            <span class="preview-kicker" id="previewSource">Manual items</span>
            <h2 id="previewTitle">Section title preview</h2>
            <p id="previewSubtitle">Choose a content source and layout to preview the section contract.</p>
            <div class="preview-row">
                <span id="previewTab">Home</span>
                <span id="previewTemplate">Layout</span>
                <span id="previewOrder">Order 0</span>
            </div>
        </div>
        <div class="preview-details">
            <div><strong>Page Key:</strong> <span id="previewRouteKey">Main tab screen</span></div>
            <div><strong>Section Key:</strong> <span id="previewKey">section_key_here</span></div>
            <div><strong>Bucket:</strong> <span id="previewBucket">—</span></div>
            <div><strong>Frontend Route:</strong> <span id="previewTargetRoute">—</span></div>
            <div><strong>Insertion:</strong> <span id="previewInsertion">No insertion</span></div>
        </div>
    </div>

    @include('admin.beginner.sections.partials.builder-tabs-script')
@endsection
