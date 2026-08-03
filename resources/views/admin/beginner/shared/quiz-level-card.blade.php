@php
    $levelQuestions = collect($level['questions'] ?? []);
    $activeCount = $levelQuestions->where('is_enabled', true)->count();
    $target = max(1, (int) ($level['question_target'] ?? 5));
    $percent = min(100, (int) round(($activeCount / $target) * 100));
    $allLevelsForDialogs = $quiz['all_levels'] ?? $quiz['levels'] ?? [];
@endphp

<div class="level-card" x-data="{open:false}">
    <div class="level-title" x-on:click="open = !open" style="cursor:pointer">
        <div>
            <strong>{{ $level['title'] }} · {{ ucfirst($level['difficulty']) }}</strong>
            <small>{{ $level['description'] ?: 'No description yet.' }}</small>
        </div>
        <span>{{ $activeCount }}/{{ $target }}</span>
    </div>

    <div class="bar"><i style="width:{{ $percent }}%"></i></div>

    <div class="level-body" x-show="open" x-cloak>
        <div class="level-actions">
            <button type="button" x-on:click="document.getElementById('{{ $questionDialogId($quiz['id'], $level['id']) }}').showModal()">+ Question</button>
            <button type="button" class="ai" x-on:click="document.getElementById('{{ $aiDraftDialogId($level['id']) }}').showModal()">AI Draft</button>
            <button type="button" x-on:click="document.getElementById('{{ $bulkImportDialogId($level['id']) }}').showModal()">Bulk Import</button>
            <button type="button" x-on:click="document.getElementById('{{ $editLevelDialogId($level['id']) }}').showModal()">Edit Level</button>

            <form method="POST" action="{{ route('admin.beginner.quizzes.levels.clear-questions', $level['id']) }}" onsubmit="return confirm('Clear all questions inside this level? The level itself will remain.');">
                @csrf
                @method('DELETE')
                <button type="submit">Clear Questions</button>
            </form>

            <form method="POST" action="{{ route('admin.beginner.quizzes.levels.delete', $level['id']) }}" onsubmit="return confirm('Delete this level and all questions inside it?');">
                @csrf
                @method('DELETE')
                <button type="submit">Delete</button>
            </form>
        </div>

        <div class="questions">
            @forelse ($levelQuestions as $question)
                <div class="q">
                    <div>
                        <strong>{{ $question['sort_order'] }}. {{ $question['question_text'] }}</strong>
                        <small>{{ count($question['options']) }} options · Correct: {{ $question['correct_option'] ?: 'Not set' }} · {{ $question['points'] }} pts</small>
                    </div>

                    <div class="q-actions">
                        <button type="button" x-on:click="document.getElementById('{{ $editQuestionDialogId($question['id']) }}').showModal()">Edit</button>

                        <form method="POST" action="{{ route('admin.beginner.quizzes.questions.toggle', $question['id']) }}">
                            @csrf
                            @method('PATCH')
                            <button type="submit">{{ $question['is_enabled'] ? 'ON' : 'OFF' }}</button>
                        </form>

                        <form method="POST" action="{{ route('admin.beginner.quizzes.questions.duplicate', $question['id']) }}">
                            @csrf
                            <button type="submit">Copy</button>
                        </form>

                        <form method="POST" action="{{ route('admin.beginner.quizzes.questions.delete', $question['id']) }}" onsubmit="return confirm('Delete this question?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit">Del</button>
                        </form>
                    </div>
                </div>

                @include('admin.beginner.shared.quiz-question-dialog', [
                    'quiz' => $quiz,
                    'question' => $question,
                    'levels' => $allLevelsForDialogs,
                    'dialogId' => $editQuestionDialogId($question['id']),
                ])
            @empty
                <div class="empty-small">No question yet for this level.</div>
            @endforelse
        </div>
    </div>
</div>

@include('admin.beginner.shared.quiz-level-dialog', [
    'level' => $level,
    'dialogId' => $editLevelDialogId($level['id']),
    'action' => route('admin.beginner.quizzes.levels.update', $level['id']),
    'method' => 'PATCH',
])

@include('admin.beginner.shared.quiz-question-create-dialog', [
    'quiz' => $quiz,
    'level' => $level,
    'dialogId' => $questionDialogId($quiz['id'], $level['id']),
])

@include('admin.beginner.shared.quiz-ai-draft-dialog', [
    'quiz' => $quiz,
    'level' => $level,
    'dialogId' => $aiDraftDialogId($level['id']),
])

@include('admin.beginner.shared.quiz-bulk-import-dialog', [
    'quiz' => $quiz,
    'level' => $level,
    'dialogId' => $bulkImportDialogId($level['id']),
])
