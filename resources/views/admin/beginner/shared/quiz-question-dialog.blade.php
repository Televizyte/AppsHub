<dialog id="{{ $dialogId }}" class="dxm-dialog">
    <form method="POST" action="{{ route('admin.beginner.quizzes.questions.update', $question['id']) }}" class="dxm-form">
        @csrf
        @method('PATCH')

        <div class="dxm-panel-title wide">
            <div>
                <strong>Edit Question</strong>
                <small>Update answer, note, Bible reference, and study metadata.</small>
            </div>
            <button type="button" onclick="this.closest('dialog').close()">×</button>
        </div>

        @include('admin.beginner.shared.quiz-question-fields', [
            'question' => $question,
            'levels' => $levels,
            'selectedLevelId' => $question['quiz_level_id'] ?? null,
        ])

        <div class="actions wide">
            <button type="submit" class="dxm-btn primary">Update Question</button>
            <button type="button" class="dxm-btn" onclick="this.closest('dialog').close()">Cancel</button>
        </div>
    </form>
</dialog>
