<dialog id="{{ $dialogId }}" class="dxm-dialog">
    <form method="POST" action="{{ route('admin.beginner.quizzes.levels.store', $quiz['id']) }}" class="dxm-form">
        @csrf

        <div class="dxm-panel-title wide">
            <div>
                <strong>Add Custom Level</strong>
                <small>Create any level name: Beginner Level 1, Genesis Deeper Study, David Story Level 2, etc.</small>
            </div>
            <button type="button" onclick="this.closest('dialog').close()">×</button>
        </div>

        @include('admin.beginner.shared.quiz-level-fields', ['level' => [
            'level_number' => count($quiz['all_levels'] ?? $quiz['levels'] ?? []) + 1,
            'title' => 'New Level',
            'difficulty' => 'custom',
            'description' => '',
            'question_target' => 20,
            'sort_order' => count($quiz['all_levels'] ?? $quiz['levels'] ?? []) + 1,
            'is_enabled' => true,
            'settings' => [
                'shuffle_questions' => true,
                'shuffle_options' => false,
                'questions_per_session' => 20,
                'study_path' => '',
            ],
        ]])

        <div class="actions wide">
            <button type="submit" class="dxm-btn primary">Create Level</button>
            <button type="button" class="dxm-btn" onclick="this.closest('dialog').close()">Cancel</button>
        </div>
    </form>
</dialog>