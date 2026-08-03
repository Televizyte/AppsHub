<dialog id="{{ $dialogId }}" class="dxm-dialog">
    <form method="POST" action="{{ route('admin.beginner.quizzes.sets.update', $quiz['id']) }}" class="dxm-form">
        @csrf @method('PATCH')
        <div class="dxm-panel-title wide"><div><strong>Edit Quiz Set</strong><small>{{ $quiz['title'] }}</small></div><button type="button" onclick="this.closest('dialog').close()">×</button></div>
        <label>Quiz Title <input name="title" required value="{{ $quiz['title'] }}"></label>
        <label>Key <input name="key" value="{{ $quiz['key'] }}"></label>
        <label class="wide">Subtitle <input name="subtitle" value="{{ $quiz['subtitle'] }}"></label>
        <label>Quiz Type <select name="type">@foreach ($types as $value => $label)<option value="{{ $value }}" @selected($quiz['type'] === $value)>{{ $label }}</option>@endforeach</select></label>
        <label>Difficulty <select name="difficulty">@foreach ($difficulties as $value => $label)<option value="{{ $value }}" @selected($quiz['difficulty'] === $value)>{{ $label }}</option>@endforeach</select></label>
        <label>Status <select name="status">@foreach ($statuses as $value => $label)<option value="{{ $value }}" @selected($quiz['status'] === $value)>{{ $label }}</option>@endforeach</select></label>
        <label>Sort Order <input name="sort_order" type="number" min="0" value="{{ $quiz['sort_order'] }}"></label>
        <label>Source Bucket <input name="source_bucket" value="{{ $quiz['source_bucket'] }}"></label>
        <label>Source Key <input name="source_key" value="{{ $quiz['source_key'] }}"></label>
        <label class="wide">Image URL <input name="image_url" value="{{ $quiz['image_url'] }}"></label>
        <label class="toggle"><input name="is_enabled" type="checkbox" value="1" @checked($quiz['is_enabled'])> Enabled</label>
        <label class="toggle"><input name="allow_retake" type="checkbox" value="1" @checked(($quiz['settings']['allow_retake'] ?? true) === true)> Allow Retake</label>
        <label class="toggle"><input name="show_answers_after_submit" type="checkbox" value="1" @checked(($quiz['settings']['show_answers_after_submit'] ?? false) === true)> Show Answers After Submit</label>
        <div class="actions wide"><button type="submit" class="dxm-btn primary">Save Changes</button><button type="button" class="dxm-btn" onclick="this.closest('dialog').close()">Cancel</button></div>
    </form>
</dialog>
