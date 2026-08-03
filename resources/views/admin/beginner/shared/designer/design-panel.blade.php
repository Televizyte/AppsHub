
@php
    $designerValues = $designerValues ?? [];
    $designerFontOptions = $designerFontOptions ?? ['system'=>'System Sans','serif'=>'Classic Serif','georgia'=>'Georgia','impact'=>'Impact / Bold Poster','mono'=>'Monospace'];
    $designerMainSizeName = $designerMainSizeName ?? 'title_size';
    $designerMainSizeLabel = $designerMainSizeLabel ?? 'Main Text Size';
    $designerSupportSizeName = $designerSupportSizeName ?? 'font_size';
    $designerSupportSizeLabel = $designerSupportSizeLabel ?? 'Supporting Text Size';
    $designerMainSizeValue = $designerValues[$designerMainSizeName] ?? '24';
    $designerSupportSizeValue = $designerValues[$designerSupportSizeName] ?? '14';
    $designerSourceColor = $designerValues['source_color'] ?? ($designerValues['accent_color'] ?? '#38bdf8');
    $designerSourceWeight = $designerValues['source_weight'] ?? '700';
    $designerHighlightColor = $designerValues['highlight_color'] ?? '#facc15';
    $designerHighlightScale = $designerValues['highlight_scale'] ?? '1.08';
    $designerHighlightWeight = $designerValues['highlight_weight'] ?? '800';
@endphp

<div class="dxm-designer-panel">
    <h3>Design Controls</h3>
    <p>Choose background mode, typography, alignment, color, overlay, and quick palettes.</p>

    <div class="dxm-designer-grid">
        <div class="dxm-designer-field">
            <label for="background_mode">Background Mode</label>
            <select id="background_mode" name="background_mode" data-dxm-designer-input>
                <option value="image" @selected(($designerValues['background_mode'] ?? 'gradient') === 'image')>Image</option>
                <option value="gradient" @selected(($designerValues['background_mode'] ?? 'gradient') === 'gradient')>Gradient</option>
                <option value="solid" @selected(($designerValues['background_mode'] ?? 'gradient') === 'solid')>Solid Color</option>
            </select>
        </div>

        <div class="dxm-designer-field">
            <label for="font_family">Font Family</label>
            <select id="font_family" name="font_family" data-dxm-designer-input>
                @foreach($designerFontOptions as $value => $label)
                    <option value="{{ $value }}" @selected(($designerValues['font_family'] ?? 'system') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>

        <div class="dxm-designer-field"><label for="text_align">Text Alignment</label><select id="text_align" name="text_align" data-dxm-designer-input><option value="left" @selected(($designerValues['text_align'] ?? 'center') === 'left')>Left</option><option value="center" @selected(($designerValues['text_align'] ?? 'center') === 'center')>Center</option><option value="right" @selected(($designerValues['text_align'] ?? 'center') === 'right')>Right</option></select></div>
        <div class="dxm-designer-field"><label for="vertical_align">Vertical Position</label><select id="vertical_align" name="vertical_align" data-dxm-designer-input><option value="start" @selected(($designerValues['vertical_align'] ?? 'center') === 'start')>Top</option><option value="center" @selected(($designerValues['vertical_align'] ?? 'center') === 'center')>Center</option><option value="end" @selected(($designerValues['vertical_align'] ?? 'center') === 'end')>Bottom</option></select></div>
        <div class="dxm-designer-field"><label for="font_weight">Font Weight</label><select id="font_weight" name="font_weight" data-dxm-designer-input><option value="400" @selected(($designerValues['font_weight'] ?? '700') === '400')>Normal</option><option value="500" @selected(($designerValues['font_weight'] ?? '700') === '500')>Medium Soft</option><option value="600" @selected(($designerValues['font_weight'] ?? '700') === '600')>Medium</option><option value="700" @selected(($designerValues['font_weight'] ?? '700') === '700')>Bold</option><option value="800" @selected(($designerValues['font_weight'] ?? '700') === '800')>Extra Bold</option></select></div>
        <div class="dxm-designer-field"><label for="text_shadow">Text Shadow</label><select id="text_shadow" name="text_shadow" data-dxm-designer-input><option value="off" @selected(($designerValues['text_shadow'] ?? 'soft') === 'off')>Off / Clean Text</option><option value="soft" @selected(($designerValues['text_shadow'] ?? 'soft') === 'soft')>Soft Shadow</option><option value="strong" @selected(($designerValues['text_shadow'] ?? 'soft') === 'strong')>Strong Shadow</option></select><small>Turn off when using light background and dark text.</small></div>

        <div class="dxm-designer-field dxm-designer-full">
            <strong>Highlight words are controlled from the Content/Quote tab.</strong>
            <span>Use the controls below only to style the saved highlight phrases. This prevents duplicate highlight boxes from overwriting each other during save.</span>
        </div>

        <div class="dxm-designer-field">
            <label for="source_weight">Source / Reference Weight</label>
            <select id="source_weight" name="source_weight" data-dxm-designer-input>
                <option value="400" @selected($designerSourceWeight === '400')>Normal</option>
                <option value="600" @selected($designerSourceWeight === '600')>Medium</option>
                <option value="700" @selected($designerSourceWeight === '700')>Bold</option>
                <option value="800" @selected($designerSourceWeight === '800')>Extra Bold</option>
            </select>
        </div>

        <div class="dxm-designer-field">
            <label for="highlight_scale">Highlight Size Boost</label>
            <input id="highlight_scale" name="highlight_scale" type="range" min="0.90" max="1.50" step="0.01" value="{{ $designerHighlightScale }}" data-dxm-designer-input>
            <small><span data-dxm-display="highlight_scale">{{ $designerHighlightScale }}</span>x</small>
        </div>

        <div class="dxm-designer-field">
            <label for="highlight_weight">Highlight Weight</label>
            <select id="highlight_weight" name="highlight_weight" data-dxm-designer-input>
                <option value="600" @selected($designerHighlightWeight === '600')>Medium</option>
                <option value="700" @selected($designerHighlightWeight === '700')>Bold</option>
                <option value="800" @selected($designerHighlightWeight === '800')>Extra Bold</option>
                <option value="900" @selected($designerHighlightWeight === '900')>Black</option>
            </select>
        </div>

        <div class="dxm-designer-field"><label for="{{ $designerMainSizeName }}">{{ $designerMainSizeLabel }}</label><input id="{{ $designerMainSizeName }}" name="{{ $designerMainSizeName }}" type="range" min="10" max="64" value="{{ $designerMainSizeValue }}" data-dxm-designer-input data-dxm-main-size><small><span data-dxm-display="{{ $designerMainSizeName }}">{{ $designerMainSizeValue }}</span>px</small></div>
        <div class="dxm-designer-field"><label for="{{ $designerSupportSizeName }}">{{ $designerSupportSizeLabel }}</label><input id="{{ $designerSupportSizeName }}" name="{{ $designerSupportSizeName }}" type="range" min="8" max="34" value="{{ $designerSupportSizeValue }}" data-dxm-designer-input data-dxm-support-size><small><span data-dxm-display="{{ $designerSupportSizeName }}">{{ $designerSupportSizeValue }}</span>px</small></div>
        <div class="dxm-designer-field"><label for="overlay_strength">Overlay Strength</label><input id="overlay_strength" name="overlay_strength" type="range" min="0" max="95" value="{{ $designerValues['overlay_strength'] ?? '58' }}" data-dxm-designer-input><small><span data-dxm-display="overlay_strength">{{ $designerValues['overlay_strength'] ?? '58' }}</span>%</small></div>

        @foreach([['bg_color','Background Color 1','#160042'],['bg_color_2','Background Color 2','#e2388a'],['text_color','Text Color','#ffffff'],['source_color','Source / Reference Color','#38bdf8'],['highlight_color','Highlight Color','#facc15'],['accent_color','Accent Color','#38bdf8']] as $colorField)
            <div class="dxm-designer-field">
                <label for="{{ $colorField[0] }}">{{ $colorField[1] }}</label>
                <div class="dxm-designer-color-row">
                    <input id="{{ $colorField[0] }}" name="{{ $colorField[0] }}" type="color" value="{{ $designerValues[$colorField[0]] ?? $colorField[2] }}" data-dxm-designer-input>
                    <input type="text" value="{{ $designerValues[$colorField[0]] ?? $colorField[2] }}" data-color-copy="{{ $colorField[0] }}" spellcheck="false">
                </div>
            </div>
        @endforeach

        <label class="dxm-designer-check dxm-designer-full">
            <input type="checkbox" id="show_quote_mark" name="show_quote_mark" value="1" data-dxm-designer-input @checked((bool) ($designerValues['show_quote_mark'] ?? true))>
            <span>Show large decorative quote mark</span>
        </label>

        <div class="dxm-designer-field dxm-designer-full">
            <label>Quick Palettes</label>
            @include('admin.beginner.shared.designer.palettes')
        </div>
    </div>
</div>
