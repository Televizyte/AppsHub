<div class="ve-form-grid">
    <label>
        <span class="ve-label">Status</span>
        <span class="ve-select-wrap">
            <select class="ve-select" wire:model="{{ $formKey }}.status">
                <option value="draft">Draft</option>
                <option value="published">Published</option>
                <option value="archived">Archived</option>
            </select>
        </span>
    </label>

    <label>
        <span class="ve-label">Visibility</span>
        <span class="ve-select-wrap">
            <select class="ve-select" wire:model="{{ $formKey }}.visibility">
                <option value="public">Public</option>
                <option value="unlisted">Unlisted</option>
                <option value="private">Private</option>
            </select>
        </span>
    </label>

    <label>
        <span class="ve-label">Sort Order</span>
        <input class="ve-input" type="number" min="0" wire:model="{{ $formKey }}.sort_order">
    </label>

    <label class="ve-check">
        <input type="checkbox" wire:model="{{ $formKey }}.is_featured">
        <span>Feature this item</span>
    </label>

    @if($showLive)
        <label class="ve-check">
            <input type="checkbox" wire:model="{{ $formKey }}.is_live">
            <span>Mark as live</span>
        </label>
    @endif

    @if($showLive)
        <label class="ve-form-span">
            <span class="ve-label">Schedule publication ({{ \App\Support\Scheduling\AdminScheduleTime::timezone() }})</span>
            <input class="ve-input" type="datetime-local" wire:model="{{ $formKey }}.publish_at">
            <span class="ve-note">Leave blank to publish immediately when status is Published. Clear the value to remove a schedule.</span>
        </label>
    @endif
</div>
