@php
    $settings = $level['settings'] ?? $level['settings_json'] ?? [];
    $studyGroups = collect($quiz['study_groups'] ?? []);
    $selectedGroupId = (int) ($level['quiz_study_group_id'] ?? 0);
@endphp

<label>Level Number
    <input name="level_number" type="number" min="1" max="500" value="{{ $level['level_number'] ?? 1 }}" required>
</label>

<label>Level Name
    <input name="title" value="{{ $level['title'] ?? '' }}" required placeholder="Genesis Beginner Level 1">
</label>

<label>Study Group / Bible Book
    <select name="quiz_study_group_id">
        <option value="">No group yet / General level</option>
        @foreach ($studyGroups as $group)
            <option value="{{ $group['id'] }}" @selected($selectedGroupId === (int) $group['id'])>
                {{ $group['title'] }}{{ !empty($group['type']) ? ' · ' . str_replace('_', ' ', $group['type']) : '' }}
            </option>
        @endforeach
    </select>
</label>

<label>Difficulty / Group Label
    <input name="difficulty" value="{{ $level['difficulty'] ?? 'custom' }}" required placeholder="beginner level 1, medium set 2, advanced study">
</label>

<label>Question Pool Target
    <input name="question_target" type="number" min="1" max="1000" value="{{ $level['question_target'] ?? 20 }}" required>
</label>

<label>Questions Per Play Session
    <input name="questions_per_session" type="number" min="1" max="500" value="{{ $settings['questions_per_session'] ?? ($level['question_target'] ?? 20) }}">
</label>

<label>Sort Order
    <input name="sort_order" type="number" min="0" value="{{ $level['sort_order'] ?? ($level['level_number'] ?? 1) }}">
</label>

<label class="wide">Study Path / Section
    <input name="study_path" value="{{ $settings['study_path'] ?? '' }}" placeholder="Genesis Set 2, David Story, Old Testament Covenant, etc.">
</label>

<label class="wide">Description
    <textarea name="description" rows="3" placeholder="Explain what this level covers.">{{ $level['description'] ?? '' }}</textarea>
</label>

<label class="toggle">
    <input name="shuffle_questions" type="checkbox" value="1" @checked($settings['shuffle_questions'] ?? true)>
    Shuffle questions each play session
</label>

<label class="toggle">
    <input name="shuffle_options" type="checkbox" value="1" @checked($settings['shuffle_options'] ?? false)>
    Shuffle answer options
</label>

<label class="toggle">
    <input name="is_enabled" type="checkbox" value="1" @checked($level['is_enabled'] ?? true)>
    Enable this level
</label>
