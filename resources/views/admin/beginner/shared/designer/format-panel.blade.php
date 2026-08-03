@php
    $designerFormatOptions = $designerFormatOptions ?? [
        'portrait'=>['label'=>'Portrait 4:5','ratio'=>'4 / 5'],
        'square'=>['label'=>'Square 1:1','ratio'=>'1 / 1'],
        'story'=>['label'=>'Story 9:16','ratio'=>'9 / 16'],
        'landscape'=>['label'=>'Landscape 16:9','ratio'=>'16 / 9'],
        'wide'=>['label'=>'Wide Banner 21:9','ratio'=>'21 / 9'],
        'classic'=>['label'=>'Classic 3:2','ratio'=>'3 / 2'],
    ];
    $designerValues = $designerValues ?? [];
@endphp

<div class="dxm-designer-panel">
    <h3>Format & Canvas</h3>
    <p>Choose the canvas size and how text should fit inside the card.</p>

    <div class="dxm-designer-format-list">
        @foreach($designerFormatOptions as $value => $option)
            <label class="dxm-designer-format-option">
                <input type="radio" name="card_format" value="{{ $value }}" data-format-ratio="{{ $option['ratio'] }}" @checked(($designerValues['card_format'] ?? 'portrait') === $value)>
                <span><strong>{{ $option['label'] }}</strong><small>{{ $option['ratio'] }}</small></span>
            </label>
        @endforeach
    </div>

    <div class="dxm-designer-grid" style="margin-top:14px;">
        <div class="dxm-designer-field">
            <label for="text_scale_mode">Text Scale Mode</label>
            <select id="text_scale_mode" name="text_scale_mode" data-dxm-designer-input>
                <option value="auto" @selected(($designerValues['text_scale_mode'] ?? 'auto') === 'auto')>Auto-fit text</option>
                <option value="manual" @selected(($designerValues['text_scale_mode'] ?? 'auto') === 'manual')>Manual size</option>
            </select>
        </div>

        <div class="dxm-designer-field">
            <label for="content_width">Content Width</label>
            <input id="content_width" name="content_width" type="range" min="45" max="100" value="{{ $designerValues['content_width'] ?? '86' }}" data-dxm-designer-input>
            <small><span data-dxm-display="content_width">{{ $designerValues['content_width'] ?? '86' }}</span>%</small>
        </div>

        <div class="dxm-designer-field">
            <label for="card_padding_x">Horizontal Padding</label>
            <input id="card_padding_x" name="card_padding_x" type="range" min="8" max="120" value="{{ $designerValues['card_padding_x'] ?? ($designerValues['card_padding'] ?? '34') }}" data-dxm-designer-input>
            <small><span data-dxm-display="card_padding_x">{{ $designerValues['card_padding_x'] ?? ($designerValues['card_padding'] ?? '34') }}</span>px left/right</small>
            <input type="hidden" id="card_padding" name="card_padding" value="{{ $designerValues['card_padding'] ?? '34' }}">
        </div>

        <div class="dxm-designer-field">
            <label for="card_padding_y">Vertical Padding</label>
            <input id="card_padding_y" name="card_padding_y" type="range" min="8" max="120" value="{{ $designerValues['card_padding_y'] ?? ($designerValues['card_padding'] ?? '34') }}" data-dxm-designer-input>
            <small><span data-dxm-display="card_padding_y">{{ $designerValues['card_padding_y'] ?? ($designerValues['card_padding'] ?? '34') }}</span>px top/bottom</small>
        </div>

        <div class="dxm-designer-field">
            <label for="line_height">Line Height</label>
            <input id="line_height" name="line_height" type="range" min="1" max="2" step="0.05" value="{{ $designerValues['line_height'] ?? '1.35' }}" data-dxm-designer-input>
            <small><span data-dxm-display="line_height">{{ $designerValues['line_height'] ?? '1.35' }}</span></small>
        </div>
    </div>
</div>