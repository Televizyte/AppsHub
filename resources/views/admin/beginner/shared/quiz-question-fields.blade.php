@php
    $question = $question ?? null;
    $selectedLevelId = $selectedLevelId ?? ($question['quiz_level_id'] ?? null);
    $answerNote = $question['answer_note'] ?? ($question['explanation'] ?? '');
    $bibleBooks = [
        'Genesis','Exodus','Leviticus','Numbers','Deuteronomy','Joshua','Judges','Ruth','1 Samuel','2 Samuel','1 Kings','2 Kings',
        '1 Chronicles','2 Chronicles','Ezra','Nehemiah','Esther','Job','Psalms','Proverbs','Ecclesiastes','Song of Solomon','Isaiah',
        'Jeremiah','Lamentations','Ezekiel','Daniel','Hosea','Joel','Amos','Obadiah','Jonah','Micah','Nahum','Habakkuk','Zephaniah',
        'Haggai','Zechariah','Malachi','Matthew','Mark','Luke','John','Acts','Romans','1 Corinthians','2 Corinthians','Galatians',
        'Ephesians','Philippians','Colossians','1 Thessalonians','2 Thessalonians','1 Timothy','2 Timothy','Titus','Philemon','Hebrews',
        'James','1 Peter','2 Peter','1 John','2 John','3 John','Jude','Revelation'
    ];
@endphp

<label>Question Level
    <select name="quiz_level_id">
        @foreach ($levels as $item)
            <option value="{{ $item['id'] }}" @selected((int) $selectedLevelId === (int) $item['id'])>
                {{ $item['title'] }} @if(!empty($item['difficulty'])) · {{ $item['difficulty'] }} @endif
            </option>
        @endforeach
    </select>
</label>

<label>Legacy Level Number
    <input name="level" type="number" min="1" max="500" value="{{ $question['level'] ?? 1 }}">
</label>

<label class="wide">Question
    <textarea name="question_text" rows="4" required placeholder="Enter the quiz question...">{{ $question['question_text'] ?? '' }}</textarea>
</label>

<label>Option A
    <input name="option_a" value="{{ $question['option_a'] ?? '' }}" placeholder="Option A">
</label>

<label>Option B
    <input name="option_b" value="{{ $question['option_b'] ?? '' }}" placeholder="Option B">
</label>

<label>Option C
    <input name="option_c" value="{{ $question['option_c'] ?? '' }}" placeholder="Option C">
</label>

<label>Option D
    <input name="option_d" value="{{ $question['option_d'] ?? '' }}" placeholder="Option D">
</label>

<label>Correct Option
    <select name="correct_option">
        @foreach (['A','B','C','D'] as $option)
            <option value="{{ $option }}" @selected(($question['correct_option'] ?? 'A') === $option)>Option {{ $option }}</option>
        @endforeach
    </select>
</label>

<label>Points / XP
    <input name="points" type="number" min="0" max="100" value="{{ $question['points'] ?? 1 }}">
</label>

<label>Sort Order
    <input name="sort_order" type="number" min="0" value="{{ $question['sort_order'] ?? 0 }}">
</label>

<label>Question Kind
    <input name="question_kind" value="{{ $question['question_kind'] ?? '' }}" placeholder="recall, lesson, application, memory, story">
</label>

<label class="wide">Answer Note / Study Explanation
    <textarea name="answer_note" rows="3" placeholder="Why this answer is correct and what the learner should understand.">{{ $answerNote }}</textarea>
</label>

<label class="wide">Extra Explanation
    <textarea name="explanation" rows="3" placeholder="Optional extra explanation shown after answer.">{{ $question['explanation'] ?? '' }}</textarea>
</label>

<div class="wide" style="display:grid;gap:10px;padding:12px;border:1px solid rgba(255,255,255,.10);border-radius:18px;background:rgba(255,255,255,.035)">
    <strong style="color:#fff;font-size:13px">Bible Study Reference</strong>
    <div class="dxm-form" style="padding:0">
        <label>Reference Text
            <input name="bible_reference" value="{{ $question['bible_reference'] ?? '' }}" placeholder="Genesis 1:2 or Genesis 1:3–5">
        </label>

        <label>Bible Book
            <input name="bible_book" list="bible-books-list" value="{{ $question['bible_book'] ?? '' }}" placeholder="Genesis">
        </label>

        <label>Start Chapter
            <input name="chapter_start" type="number" min="1" max="200" value="{{ $question['chapter_start'] ?? '' }}">
        </label>

        <label>Start Verse
            <input name="verse_start" type="number" min="1" max="200" value="{{ $question['verse_start'] ?? '' }}">
        </label>

        <label>End Chapter
            <input name="chapter_end" type="number" min="1" max="200" value="{{ $question['chapter_end'] ?? '' }}">
        </label>

        <label>End Verse
            <input name="verse_end" type="number" min="1" max="200" value="{{ $question['verse_end'] ?? '' }}">
        </label>

        <label class="wide">Study Focus
            <input name="study_focus" value="{{ $question['study_focus'] ?? '' }}" placeholder="creation, covenant, faith, obedience, moral lesson, character study">
        </label>
    </div>
</div>

<label class="toggle">
    <input name="is_enabled" type="checkbox" value="1" @checked($question['is_enabled'] ?? false)>
    Enable / publish this question
</label>

<datalist id="bible-books-list">
    @foreach ($bibleBooks as $book)
        <option value="{{ $book }}"></option>
    @endforeach
</datalist>
