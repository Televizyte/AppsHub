<dialog id="{{ $dialogId }}" class="dxm-dialog">
    <form method="POST" action="{{ isset($group) && ($group['id'] ?? null) ? route('admin.beginner.quizzes.groups.update', $group['id']) : route('admin.beginner.quizzes.groups.store', $quiz['id']) }}" class="dxm-form">
        @csrf
        @if (isset($group) && ($group['id'] ?? null))
            @method('PATCH')
        @endif

        <div class="dxm-panel-title wide">
            <div>
                <strong>{{ isset($group) && ($group['id'] ?? null) ? 'Edit Study Group / Bible Book' : 'Add Study Group / Bible Book' }}</strong>
                <small>Use this for Genesis, Exodus, David Story, Bible Characters, or any custom study path before levels.</small>
            </div>
            <button type="button" onclick="this.closest('dialog').close()">×</button>
        </div>

        <label>Group Title
            <input name="title" required value="{{ $group['title'] ?? '' }}" placeholder="Genesis">
        </label>

        <label>Key
            <input name="key" value="{{ $group['key'] ?? '' }}" placeholder="genesis">
        </label>

        <label>Type
            <select name="type">
                @foreach (['bible_book' => 'Bible Book', 'bible_story' => 'Bible Story', 'bible_character' => 'Bible Character', 'custom' => 'Custom Group'] as $value => $label)
                    <option value="{{ $value }}" @selected(($group['type'] ?? 'bible_book') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>

        <label>Bible Book
            <input name="bible_book" value="{{ $group['bible_book'] ?? '' }}" placeholder="Genesis">
        </label>

        <label>Testament
            <select name="testament">
                <option value="">Not specific</option>
                <option value="old" @selected(($group['testament'] ?? '') === 'old')>Old Testament</option>
                <option value="new" @selected(($group['testament'] ?? '') === 'new')>New Testament</option>
            </select>
        </label>

        <label>Sort Order
            <input name="sort_order" type="number" min="0" value="{{ $group['sort_order'] ?? 0 }}">
        </label>

        <label class="wide">Subtitle
            <input name="subtitle" value="{{ $group['subtitle'] ?? '' }}" placeholder="Study the book of Genesis level by level">
        </label>

        <label class="wide">Description
            <textarea name="description" rows="3" placeholder="Optional study path note">{{ $group['description'] ?? '' }}</textarea>
        </label>

        <label class="wide">Image URL
            <input name="image_url" value="{{ $group['image_url'] ?? '' }}" placeholder="Optional cover image">
        </label>

        <label class="toggle wide">
            <input name="is_enabled" type="checkbox" value="1" @checked((bool) ($group['is_enabled'] ?? true))>
            Enabled
        </label>

        <div class="actions wide">
            <button type="submit" class="dxm-btn primary">Save Group</button>
            <button type="button" class="dxm-btn" onclick="this.closest('dialog').close()">Cancel</button>
        </div>
    </form>
</dialog>
