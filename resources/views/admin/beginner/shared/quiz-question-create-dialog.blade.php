<dialog id="{{ $dialogId }}" class="dxm-dialog">
    <form method="POST" action="{{ route('admin.beginner.quizzes.questions.store', $quiz['id']) }}" class="dxm-form">
        @csrf

        <div class="dxm-panel-title wide">
            <div>
                <strong>Add Question</strong>
                <small>{{ $quiz['title'] }} · {{ $level['title'] }}</small>
            </div>
            <button type="button" onclick="this.closest('dialog').close()">×</button>
        </div>

        <input type="hidden" name="quiz_level_id" value="{{ $level['id'] }}">
        <input type="hidden" name="level" value="{{ $level['level_number'] }}">

        @include('admin.beginner.shared.quiz-question-fields', [
            'question' => null,
            'levels' => $quiz['levels'],
            'selectedLevelId' => $level['id'],
        ])

        <div class="actions wide">
            <button type="submit" class="dxm-btn primary">Save Question</button>
            <button type="button" class="dxm-btn" onclick="this.closest('dialog').close()">Cancel</button>
        </div>
    </form>
</dialog>
