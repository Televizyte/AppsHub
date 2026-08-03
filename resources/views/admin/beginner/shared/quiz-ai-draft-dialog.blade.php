<dialog id="{{ $dialogId }}" class="dxm-dialog">
    <form method="POST" action="{{ route('admin.beginner.quizzes.levels.generate-drafts', $level['id']) }}" class="dxm-form">
        @csrf

        <div class="dxm-panel-title wide">
            <div>
                <strong>Content Intelligent AI Questions</strong>
                <small>{{ $quiz['title'] }} · {{ $level['title'] }}. Uses real AI when configured, otherwise falls back to safe local generation.</small>
            </div>
            <button type="button" onclick="this.closest('dialog').close()">×</button>
        </div>

        <label>Source Title
            <input name="source_title" placeholder="Optional article/devotional title">
        </label>

        <label>Question Count
            <input name="question_count" type="number" min="1" max="30" value="{{ max(1, min(5, (int) $level['question_target'])) }}">
        </label>

        <label>Generation Mode
            <select name="generation_mode">
                <option value="mixed">Mixed Mode — recall, understanding, application, reflection</option>
                <option value="exact_extract">Exact Extract — close to source text</option>
                <option value="fine_tune">Fine Tune — clearer learning questions</option>
                <option value="application">Application — practical life questions</option>
                <option value="scripture_reflection">Scripture Reflection — spiritual truth questions</option>
            </select>
        </label>

        <label class="toggle">
            <input name="publish_generated" type="checkbox" value="1">
            Enable immediately after generation
        </label>

        <label class="wide">Source Text
            <textarea name="source_text" rows="10" required placeholder="Paste article, devotional, scripture study, teaching notes, sermon transcript, or lesson content here..."></textarea>
        </label>

        <div class="empty-small wide">
            Provider behavior: set CONTENT_INTELLIGENCE_PROVIDER=openai and OPENAI_API_KEY in .env to use real AI. If no key is configured, AppsHub keeps working with the local fallback generator.
        </div>

        <div class="actions wide">
            <button type="submit" class="dxm-btn primary">Generate Content Intelligent Drafts</button>
            <button type="button" class="dxm-btn" onclick="this.closest('dialog').close()">Cancel</button>
        </div>
    </form>
</dialog>
