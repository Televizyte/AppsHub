@php
    $designerKind = $designerKind ?? 'quote';
    $designerPreviewMainId = $designerPreviewMainId ?? 'dxmDesignerMainText';
    $designerPreviewSupportId = $designerPreviewSupportId ?? 'dxmDesignerSupportText';
    $designerPreviewRefId = $designerPreviewRefId ?? 'dxmDesignerRefText';
@endphp

<div class="dxm-designer-preview-shell" data-dxm-designer-root data-designer-kind="{{ $designerKind }}">
    <div class="dxm-designer-preview-toolbar">
        <span data-dxm-format-label>Format Preview</span>
        <span data-dxm-character-count>0 characters</span>
    </div>

    <div class="dxm-designer-card-stage">
        <div class="dxm-designer-card-preview" data-dxm-card-preview>
            <div class="dxm-designer-card-bg" data-dxm-card-bg></div>
            <div class="dxm-designer-card-overlay" data-dxm-card-overlay></div>
            <div class="dxm-designer-mark" data-dxm-card-mark>“</div>

            <div class="dxm-designer-card-content" data-dxm-card-content>
                <div class="dxm-designer-card-inner" data-dxm-card-inner>
                    <div class="dxm-designer-main-text" id="{{ $designerPreviewMainId }}" data-dxm-main-text></div>
                    <div class="dxm-designer-support-text" id="{{ $designerPreviewSupportId }}" data-dxm-support-text></div>

                    @if($designerKind === 'scripture')
                        <div class="dxm-designer-ref-text" id="{{ $designerPreviewRefId }}" data-dxm-ref-text></div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="dxm-designer-preview-note">
        <strong>Frontend-ready designer payload</strong>
        <span>This shared renderer is used by quote, daily scripture, daily quote, and future graphic tools.</span>
    </div>
</div>
