<dialog id="{{ $dialogId }}" class="dxm-dialog">
    <form method="POST" action="{{ route('admin.beginner.quizzes.levels.import-questions', $level['id']) }}" class="dxm-form" enctype="multipart/form-data">
        @csrf

        <div class="dxm-panel-title wide">
            <div>
                <strong>Bulk Import Bible Questions</strong>
                <small>{{ $quiz['title'] }} · {{ $level['title'] }}. Paste questions or upload a .txt / .json file.</small>
            </div>
            <button type="button" onclick="this.closest('dialog').close()">×</button>
        </div>

        <label>Source / Set Title
            <input name="source_title" placeholder="Example: Genesis Set 1 - Beginner">
        </label>

        <label>Import Format
            <select name="import_format">
                <option value="auto">Auto Detect</option>
                <option value="plain">Plain Text / Document Text</option>
                <option value="json">JSON Questions</option>
            </select>
        </label>

        <label>Default Bible Book
            <input name="default_bible_book" placeholder="Genesis">
        </label>

        <label>Default Study Focus
            <input name="study_focus" placeholder="Creation, Covenant, Character Study, Bible Story">
        </label>

        <label>Question Kind
            <input name="question_kind" placeholder="General Knowledge, Story Study, Deeper Detail">
        </label>

        <label class="toggle">
            <input name="is_enabled" type="checkbox" value="1" checked>
            Enable imported questions immediately
        </label>

        <label class="wide">Upload Question File
            <input type="file" name="source_file" accept=".txt,.json,.csv,text/plain,application/json,text/csv">
            <small style="display:block;margin-top:7px;color:rgba(255,255,255,.58);font-size:11px;line-height:1.45">
                Use .txt for Genesis-style numbered questions, or .json for structured imports. Manual paste below can be used alone or combined with the file.
            </small>
        </label>

        <label class="wide">Paste Questions
            <textarea name="source_text" rows="16" placeholder="Example:

1. What did God create in the beginning?
A. The heavens and the earth
B. The sun and moon
C. Adam and Eve
D. The animals
Correct Answer: A
Answer Note: Genesis opens by declaring God as Creator of the heavens and the earth.
Reference: Genesis 1:1

2. What was the earth like before God began forming it?
A. Filled with cities
B. Without form and void
C. Covered with animals
D. Already complete
Correct Answer: B
Answer Note: The earth was without form and void, and darkness was upon the deep.
Reference: Genesis 1:2"></textarea>
        </label>

        <div class="empty-small wide">
            Supported: paste text, upload .txt, upload .json, or combine file + pasted text. Required fields per question: Question, A-D options, Correct Answer. Recommended: Answer Note and Reference.
        </div>

        <div class="actions wide">
            <button type="submit" class="dxm-btn primary">Import Questions</button>
            <button type="button" class="dxm-btn" onclick="this.closest('dialog').close()">Cancel</button>
        </div>
    </form>
</dialog>
