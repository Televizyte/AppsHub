<div class="ve-form-grid">
    <div class="ve-form-span">
        <div class="ve-media-selected">
            <div class="ve-thumb">
                @if($selectedUrl)
                    <img src="{{ $selectedUrl }}" alt="">
                @else
                    No image
                @endif
            </div>
            <div>
                <div class="ve-card-title">Selected Thumbnail</div>
                <div class="ve-note">Choose an existing image or upload through the shared Media Center.</div>
                <div class="ve-actions">
                    <button type="button" class="ve-btn primary" wire:click="openMediaPicker('{{ $target }}')">Open Thumbnail Library</button>
                    @if($selectedUrl)
                        <button type="button" class="ve-btn" wire:click="clearMediaAsset('{{ $target }}')">Clear</button>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
