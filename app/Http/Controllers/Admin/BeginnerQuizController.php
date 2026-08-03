<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuizCategory;
use App\Models\QuizCollection;
use App\Models\QuizLevel;
use App\Models\QuizPack;
use App\Models\QuizQuestion;
use App\Models\QuizSet;
use App\Models\QuizStudyGroup;
use App\Support\ActiveApp;
use App\Support\ContentIntelligentQuizGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class BeginnerQuizController extends Controller
{
    public function storeSet(Request $request): RedirectResponse
    {
        $appId = (int) (ActiveApp::ensureId() ?? 0);
        abort_if($appId <= 0, 422, 'No active app selected.');

        $data = $this->validateSet($request);

        $set = QuizSet::query()->create([
            'app_id' => $appId,
            'key' => $this->safeKey($data['key'] ?: $data['title']),
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'type' => $data['type'],
            'source_bucket' => $data['source_bucket'] ?? null,
            'source_key' => $data['source_key'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'difficulty' => $data['difficulty'] ?? 'custom',
            'status' => $data['status'] ?? 'draft',
            'is_enabled' => (bool) ($data['is_enabled'] ?? true),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'settings_json' => $this->quizSettingsFromRequest($request),
        ]);

        $set->ensureDefaultLevels();
        $this->syncQuizSetToEngine($set->fresh());

        return $this->back('Quiz set created and registered as a quiz pack.');
    }

    public function updateSet(Request $request, QuizSet $quizSet): RedirectResponse
    {
        $this->ensureScoped($quizSet);
        $data = $this->validateSet($request);

        $quizSet->update([
            'key' => $this->safeKey($data['key'] ?: $data['title']),
            'title' => $data['title'],
            'subtitle' => $data['subtitle'] ?? null,
            'type' => $data['type'],
            'source_bucket' => $data['source_bucket'] ?? null,
            'source_key' => $data['source_key'] ?? null,
            'image_url' => $data['image_url'] ?? null,
            'difficulty' => $data['difficulty'] ?? 'custom',
            'status' => $data['status'] ?? 'draft',
            'is_enabled' => (bool) ($data['is_enabled'] ?? false),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'settings_json' => $this->quizSettingsFromRequest($request, $quizSet),
        ]);

        $this->syncQuizSetToEngine($quizSet->fresh());

        return $this->back('Quiz set updated and quiz pack aligned.');
    }

    public function toggleSetStatus(QuizSet $quizSet): RedirectResponse
    {
        $this->ensureScoped($quizSet);

        $quizSet->update([
            'status' => $quizSet->status === 'published' ? 'draft' : 'published',
            'is_enabled' => true,
        ]);

        $this->syncQuizSetToEngine($quizSet->fresh());

        return $this->back($quizSet->status === 'published' ? 'Quiz published.' : 'Quiz moved to draft.');
    }

    public function deleteSet(QuizSet $quizSet): RedirectResponse
    {
        $this->ensureScoped($quizSet);

        if (Schema::hasTable('quiz_packs')) {
            QuizPack::query()
                ->where('quiz_set_id', $quizSet->id)
                ->update([
                    'status' => 'archived',
                    'is_enabled' => false,
                    'updated_at' => now(),
                ]);
        }

        $quizSet->delete();

        return $this->back('Quiz set deleted. Related quiz pack was archived.');
    }

    public function storeGroup(Request $request, QuizSet $quizSet): RedirectResponse
    {
        $this->ensureScoped($quizSet);
        $data = $this->validateStudyGroup($request);

        if ($this->studyGroupKeyExists($quizSet, $data['key'])) {
            return $this->back('A study group or Bible book with the key "' . $data['key'] . '" already exists. Use the existing group, edit it, or choose a different key.');
        }

        $quizSet->studyGroups()->create($data);

        return $this->back('Study group saved.');
    }

    public function updateGroup(Request $request, QuizStudyGroup $quizStudyGroup): RedirectResponse
    {
        $this->ensureScoped($quizStudyGroup->quizSet);
        $data = $this->validateStudyGroup($request, $quizStudyGroup);

        if ($this->studyGroupKeyExists($quizStudyGroup->quizSet, $data['key'], (int) $quizStudyGroup->id)) {
            return $this->back('Another study group already uses the key "' . $data['key'] . '". Use a different key.');
        }

        $quizStudyGroup->update($data);

        return $this->back('Study group updated.');
    }

    public function deleteGroup(QuizStudyGroup $quizStudyGroup): RedirectResponse
    {
        $this->ensureScoped($quizStudyGroup->quizSet);

        if ($quizStudyGroup->levels()->exists()) {
            return $this->back('Move or delete levels under this group before deleting it.');
        }

        $quizStudyGroup->delete();

        return $this->back('Study group deleted.');
    }
    public function storeLevel(Request $request, QuizSet $quizSet): RedirectResponse
    {
        $this->ensureScoped($quizSet);
        $data = $this->validateLevel($request);
        $data['quiz_study_group_id'] = $this->validatedStudyGroupId($request, $quizSet);

        if ($this->levelNumberExists($quizSet, (int) $data['level_number'])) {
            return $this->back('Another level already uses Legacy Level Number ' . $data['level_number'] . '. Change the number or edit the existing level.');
        }

        $quizSet->levels()->create($data);

        return $this->back('Quiz level added.');
    }

    public function updateLevel(Request $request, QuizLevel $quizLevel): RedirectResponse
    {
        $this->ensureScoped($quizLevel->quizSet);
        $data = $this->validateLevel($request);
        $data['quiz_study_group_id'] = $this->validatedStudyGroupId($request, $quizLevel->quizSet);

        if ($this->levelNumberExists($quizLevel->quizSet, (int) $data['level_number'], (int) $quizLevel->id)) {
            return $this->back('Another level already uses Legacy Level Number ' . $data['level_number'] . '. Change the number or edit the existing level.');
        }

        $quizLevel->update($data);

        return $this->back('Quiz level updated.');
    }

    public function clearLevelQuestions(QuizLevel $quizLevel): RedirectResponse
    {
        $this->ensureScoped($quizLevel->quizSet);

        $deleted = $quizLevel->questions()->count();
        $quizLevel->questions()->delete();

        return $this->back($deleted . ' question(s) cleared from this level.');
    }

    public function deleteLevel(QuizLevel $quizLevel): RedirectResponse
    {
        $this->ensureScoped($quizLevel->quizSet);

        $deleted = $quizLevel->questions()->count();
        $quizLevel->questions()->delete();
        $quizLevel->delete();

        return $this->back('Quiz level deleted. ' . $deleted . ' question(s) were removed with it.');
    }

    public function storeQuestion(Request $request, QuizSet $quizSet): RedirectResponse
    {
        $this->ensureScoped($quizSet);

        $data = $this->validateQuestion($request, $quizSet);

        $quizSet->questions()->create($data);

        return $this->back('Question added.');
    }

    public function updateQuestion(Request $request, QuizQuestion $quizQuestion): RedirectResponse
    {
        $this->ensureScoped($quizQuestion->quizSet);

        $quizQuestion->update($this->validateQuestion($request, $quizQuestion->quizSet));

        return $this->back('Question updated.');
    }

    public function toggleQuestion(QuizQuestion $quizQuestion): RedirectResponse
    {
        $this->ensureScoped($quizQuestion->quizSet);

        $quizQuestion->update(['is_enabled' => ! (bool) $quizQuestion->is_enabled]);

        return $this->back($quizQuestion->is_enabled ? 'Question enabled.' : 'Question disabled.');
    }

    public function duplicateQuestion(QuizQuestion $quizQuestion): RedirectResponse
    {
        $this->ensureScoped($quizQuestion->quizSet);

        $copy = $quizQuestion->replicate();
        $copy->question_text = $quizQuestion->question_text . ' (Copy)';
        $copy->sort_order = ((int) $quizQuestion->sort_order) + 1;
        $copy->is_enabled = false;
        $copy->save();

        return $this->back('Question duplicated as draft.');
    }

    public function deleteQuestion(QuizQuestion $quizQuestion): RedirectResponse
    {
        $this->ensureScoped($quizQuestion->quizSet);
        $quizQuestion->delete();

        return $this->back('Question deleted.');
    }

    public function generateDraftQuestions(Request $request, QuizLevel $quizLevel, ContentIntelligentQuizGenerator $generator): RedirectResponse
    {
        $quizSet = $quizLevel->quizSet;
        $this->ensureScoped($quizSet);

        $data = $request->validate([
            'source_title' => ['nullable', 'string', 'max:255'],
            'source_text' => ['required', 'string', 'min:30', 'max:30000'],
            'question_count' => ['required', 'integer', 'min:1', 'max:30'],
            'generation_mode' => ['required', 'string', 'max:40'],
            'publish_generated' => ['nullable'],
        ]);

        $questions = $generator->generate(
            sourceText: $data['source_text'],
            sourceTitle: $data['source_title'] ?? '',
            count: (int) $data['question_count'],
            mode: (string) $data['generation_mode'],
            level: $quizLevel
        );

        if (empty($questions)) {
            return $this->back('No draft question was generated. Please add more source text.');
        }

        $latestOrder = (int) $quizLevel->questions()->max('sort_order');
        $created = 0;
        $provider = filled((string) env('OPENAI_API_KEY')) && in_array(strtolower((string) env('CONTENT_INTELLIGENCE_PROVIDER', 'local')), ['openai', 'auto'], true)
            ? 'openai'
            : 'local_fallback';

        foreach ($questions as $index => $question) {
            $meta = [
                'source' => 'content_intelligent_ai_draft',
                'provider' => $provider,
                'generation_mode' => $data['generation_mode'],
                'source_title' => $data['source_title'] ?? null,
                'review_required' => ! $request->boolean('publish_generated'),
                'draft_quality' => $question['draft_quality'] ?? 'needs_review',
                'note' => 'Generated by Content Intelligent Quiz Generator. Review and edit before publishing.',
            ];

            $quizSet->questions()->create([
                'quiz_level_id' => $quizLevel->id,
                'level' => (int) $quizLevel->level_number,
                'question_text' => $question['question_text'],
                'option_a' => $question['option_a'],
                'option_b' => $question['option_b'],
                'option_c' => $question['option_c'],
                'option_d' => $question['option_d'],
                'correct_option' => $question['correct_option'] ?? 'A',
                'explanation' => $question['explanation'],
                'answer_note' => $question['answer_note'] ?? $question['explanation'] ?? null,
                'bible_reference' => $question['bible_reference'] ?? null,
                'bible_book' => $question['bible_book'] ?? null,
                'chapter_start' => $question['chapter_start'] ?? null,
                'verse_start' => $question['verse_start'] ?? null,
                'chapter_end' => $question['chapter_end'] ?? null,
                'verse_end' => $question['verse_end'] ?? null,
                'study_focus' => $question['study_focus'] ?? null,
                'question_kind' => $question['question_kind'] ?? null,
                'points' => max(1, (int) $quizLevel->level_number),
                'sort_order' => $latestOrder + $index + 1,
                'is_enabled' => $request->boolean('publish_generated'),
                'meta_json' => $meta,
            ]);

            $created++;
        }

        return $this->back($created . ' Content Intelligent draft question(s) generated for review.');
    }

    public function importQuestions(Request $request, QuizLevel $quizLevel): RedirectResponse
    {
        $quizSet = $quizLevel->quizSet;
        $this->ensureScoped($quizSet);

        $data = $request->validate([
            'source_title' => ['nullable', 'string', 'max:255'],
            'source_text' => ['nullable', 'string', 'max:250000'],
            'source_file' => ['nullable', 'file', 'max:2048', 'mimes:txt,json,csv'],
            'import_format' => ['nullable', 'string', 'max:40'],
            'default_bible_book' => ['nullable', 'string', 'max:80'],
            'study_focus' => ['nullable', 'string', 'max:120'],
            'question_kind' => ['nullable', 'string', 'max:80'],
            'is_enabled' => ['nullable'],
        ]);

        $pastedText = trim((string) ($data['source_text'] ?? ''));
        $uploadedText = $this->readUploadedQuizImportFile($request);
        $sourceText = trim($uploadedText . ($uploadedText !== '' && $pastedText !== '' ? "\n\n" : '') . $pastedText);

        if ($sourceText === '') {
            return $this->back('Please paste questions or upload a .txt / .json question file.');
        }

        $items = $this->parseBulkQuizQuestions(
            $sourceText,
            (string) ($data['import_format'] ?? 'auto')
        );

        if (empty($items)) {
            return $this->back('No valid question was detected. Check the format and try again.');
        }

        $latestOrder = (int) $quizLevel->questions()->max('sort_order');
        $created = 0;
        $skipped = 0;

        foreach ($items as $index => $item) {
            $reference = $this->normalizeImportedBibleReference(
                trim((string) ($item['bible_reference'] ?? '')),
                trim((string) ($data['default_bible_book'] ?? ''))
            );
            $questionText = trim((string) ($item['question_text'] ?? ''));

            if ($questionText === '') {
                $skipped++;
                continue;
            }

            $exists = $quizLevel->questions()
                ->where('question_text', $questionText)
                ->when($reference !== '', fn ($query) => $query->where('bible_reference', $reference))
                ->exists();

            if ($exists) {
                $skipped++;
                continue;
            }

            $referenceParts = $this->parseBibleReference(
                $reference,
                (string) ($data['default_bible_book'] ?? '')
            );

            $quizSet->questions()->create([
                'quiz_level_id' => $quizLevel->id,
                'level' => (int) $quizLevel->level_number,
                'question_text' => $questionText,
                'option_a' => $item['option_a'] ?? '',
                'option_b' => $item['option_b'] ?? '',
                'option_c' => $item['option_c'] ?? '',
                'option_d' => $item['option_d'] ?? '',
                'correct_option' => strtoupper(substr((string) ($item['correct_option'] ?? 'A'), 0, 1)) ?: 'A',
                'explanation' => $item['explanation'] ?? $item['answer_note'] ?? '',
                'answer_note' => $item['answer_note'] ?? $item['explanation'] ?? '',
                'bible_reference' => $reference ?: ($referenceParts['bible_reference'] ?? ''),
                'bible_book' => $referenceParts['bible_book'] ?: ($data['default_bible_book'] ?? null),
                'chapter_start' => $referenceParts['chapter_start'],
                'verse_start' => $referenceParts['verse_start'],
                'chapter_end' => $referenceParts['chapter_end'],
                'verse_end' => $referenceParts['verse_end'],
                'study_focus' => $item['study_focus'] ?? $data['study_focus'] ?? null,
                'question_kind' => $item['question_kind'] ?? $data['question_kind'] ?? 'Bulk Import',
                'points' => max(1, (int) $quizLevel->level_number),
                'sort_order' => $latestOrder + $index + 1,
                'is_enabled' => $request->boolean('is_enabled'),
                'meta_json' => [
                    'source' => 'bulk_import',
                    'source_title' => $data['source_title'] ?? null,
                    'import_format' => $data['import_format'] ?? 'auto',
                    'uploaded_file' => $request->hasFile('source_file') ? $request->file('source_file')->getClientOriginalName() : null,
                    'imported_at' => now()->toDateTimeString(),
                ],
            ]);

            $created++;
        }

        return $this->back("Bulk import completed. {$created} question(s) imported; {$skipped} skipped.");
    }

    protected function readUploadedQuizImportFile(Request $request): string
    {
        if (! $request->hasFile('source_file')) {
            return '';
        }

        $file = $request->file('source_file');

        if (! $file || ! $file->isValid()) {
            return '';
        }

        $extension = strtolower((string) $file->getClientOriginalExtension());
        $allowed = ['txt', 'json', 'csv'];

        if (! in_array($extension, $allowed, true)) {
            return '';
        }

        $content = file_get_contents($file->getRealPath());

        if (! is_string($content)) {
            return '';
        }

        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?: $content;

        return trim($content);
    }

    protected function parseBulkQuizQuestions(string $text, string $format = 'auto'): array
    {
        $text = trim(str_replace(["\r\n", "\r"], "\n", $text));

        if ($text === '') {
            return [];
        }

        if (in_array($format, ['auto', 'json'], true)) {
            $jsonItems = $this->parseBulkQuizJson($text);
            if (! empty($jsonItems)) {
                return $jsonItems;
            }
        }

        return $this->parseBulkQuizPlainText($text);
    }

    protected function parseBulkQuizJson(string $text): array
    {
        $decoded = json_decode($text, true);

        if (! is_array($decoded)) {
            return [];
        }

        $items = $decoded['questions'] ?? $decoded;

        if (! is_array($items)) {
            return [];
        }

        return collect($items)
            ->map(function ($item) {
                if (! is_array($item)) {
                    return null;
                }

                return [
                    'question_text' => trim((string) ($item['question_text'] ?? $item['question'] ?? $item['text'] ?? '')),
                    'option_a' => trim((string) ($item['option_a'] ?? $item['A'] ?? data_get($item, 'options.A') ?? '')),
                    'option_b' => trim((string) ($item['option_b'] ?? $item['B'] ?? data_get($item, 'options.B') ?? '')),
                    'option_c' => trim((string) ($item['option_c'] ?? $item['C'] ?? data_get($item, 'options.C') ?? '')),
                    'option_d' => trim((string) ($item['option_d'] ?? $item['D'] ?? data_get($item, 'options.D') ?? '')),
                    'correct_option' => strtoupper(trim((string) ($item['correct_option'] ?? $item['correct_answer'] ?? $item['answer'] ?? 'A'))),
                    'answer_note' => trim((string) ($item['answer_note'] ?? $item['note'] ?? $item['explanation'] ?? '')),
                    'explanation' => trim((string) ($item['explanation'] ?? $item['answer_note'] ?? $item['note'] ?? '')),
                    'bible_reference' => trim((string) ($item['bible_reference'] ?? $item['reference'] ?? $item['ref'] ?? '')),
                    'study_focus' => trim((string) ($item['study_focus'] ?? $item['focus'] ?? '')),
                    'question_kind' => trim((string) ($item['question_kind'] ?? $item['kind'] ?? '')),
                ];
            })
            ->filter(fn ($item) => is_array($item) && $item['question_text'] !== '')
            ->values()
            ->all();
    }

    protected function parseBulkQuizPlainText(string $text): array
    {
        $text = $this->normalizeBulkQuizImportText($text);

        if ($text === '') {
            return [];
        }

        $parts = preg_split('/\n(?=\s*\d+\s*[\.\)]\s+)/', $text) ?: [];
        $items = [];

        foreach ($parts as $part) {
            $item = $this->parseBulkQuizBlock($part);

            if ($item && trim($item['question_text']) !== '') {
                $items[] = $item;
            }
        }

        if (empty($items)) {
            $item = $this->parseBulkQuizBlock($text);

            if ($item && trim($item['question_text']) !== '') {
                $items[] = $item;
            }
        }

        return $items;
    }

    protected function normalizeBulkQuizImportText(string $text): string
    {
        $text = html_entity_decode(strip_tags($text));
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        $text = preg_replace('/[ \t]+/', ' ', $text) ?: $text;
        $text = preg_replace('/\s+((?:\d+)\s*[\.\)]\s+)/', "\n$1", $text) ?: $text;
        $text = preg_replace('/\s+(Correct\s*(?:Answer|Option)?\s*[:\-])/i', "\n$1", $text) ?: $text;
        $text = preg_replace('/\s+((?:Answer\s*Note|Note|Explanation|Description|Why)\s*[:\-])/i', "\n$1", $text) ?: $text;
        $text = preg_replace('/\s+((?:Bible\s*)?(?:Reference|Ref|Scripture\s*Reference)\s*[:\-])/i', "\n$1", $text) ?: $text;
        $text = preg_replace('/\n{3,}/', "\n\n", $text) ?: $text;

        return trim($text);
    }

    protected function parseBulkQuizBlock(string $block): ?array
    {
        $block = trim($block);

        if ($block === '') {
            return null;
        }

        $block = preg_replace('/^\s*\d+\s*[\.\)]\s*/', '', $block) ?: $block;

        $questionText = $this->extractQuestionText($block);
        $options = $this->extractBulkOptions($block);
        $correct = $this->extractBulkValue($block, '/(?:^|\n)\s*(?:Correct\s*(?:Answer|Option)?|Answer)\s*[:\-]\s*(?:Option\s*)?([ABCD])\b/i', 1);
        $answerNote = $this->extractBulkValue($block, '/(?:^|\n)\s*(?:Answer\s*Note|Note|Explanation|Description|Why)\s*[:\-]\s*(.+?)(?=\n\s*(?:Bible\s*)?(?:Reference|Ref|Scripture\s*Reference)\s*[:\-]|\n\s*(?:Study\s*Focus|Focus|Topic)\s*[:\-]|\n\s*(?:Question\s*Kind|Kind|Style)\s*[:\-]|\z)/is', 1);
        $reference = $this->extractBulkValue($block, '/(?:^|\n)\s*(?:Bible\s*)?(?:Reference|Ref|Scripture\s*Reference)\s*[:\-]\s*(.+?)(?=\n\s*(?:Study\s*Focus|Focus|Topic)\s*[:\-]|\n\s*(?:Question\s*Kind|Kind|Style)\s*[:\-]|\z)/is', 1);

        if ($reference === '') {
            $reference = $this->guessBibleReferenceFromBlock($block);
        }
        $studyFocus = $this->extractBulkValue($block, '/(?:^|\n)\s*(?:Study\s*Focus|Focus|Topic)\s*[:\-]\s*(.+?)(?=\n\s*(?:Question\s*Kind|Kind|Style)\s*[:\-]|\z)/is', 1);
        $questionKind = $this->extractBulkValue($block, '/(?:^|\n)\s*(?:Question\s*Kind|Kind|Style)\s*[:\-]\s*(.+?)\s*$/is', 1);

        if ($questionText === '' && ! empty($options)) {
            $questionText = trim(strtok($block, "\n") ?: '');
        }

        return [
            'question_text' => $questionText,
            'option_a' => $options['A'] ?? '',
            'option_b' => $options['B'] ?? '',
            'option_c' => $options['C'] ?? '',
            'option_d' => $options['D'] ?? '',
            'correct_option' => strtoupper(substr(trim($correct ?: 'A'), 0, 1)),
            'answer_note' => trim($answerNote),
            'explanation' => trim($answerNote),
            'bible_reference' => trim($reference),
            'study_focus' => trim($studyFocus),
            'question_kind' => trim($questionKind),
        ];
    }

    protected function extractQuestionText(string $block): string
    {
        $patterns = [
            '/\A(.+?)(?=\n\s*(?:Option\s*)?A\s*[\.\):\-]\s+)/is',
            '/\A(.+?)(?=\n\s*A\s*[\.\):\-]\s+)/is',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $block, $match)) {
                return trim(preg_replace('/\s+/', ' ', $match[1]) ?: $match[1]);
            }
        }

        $firstLine = trim(strtok($block, "\n") ?: '');

        return trim(preg_replace('/^\s*\d+\s*[\.\)]\s*/', '', $firstLine) ?: $firstLine);
    }

    protected function extractBulkOptions(string $block): array
    {
        $options = ['A' => '', 'B' => '', 'C' => '', 'D' => ''];

        foreach (['A', 'B', 'C', 'D'] as $index => $key) {
            $next = $index < 3 ? ['A', 'B', 'C', 'D'][$index + 1] : null;
            $lookahead = $next
                ? '(?=\n\s*(?:Option\s*)?' . $next . '\s*[\.\):\-]\s+)'
                : '(?=\n\s*(?:Correct\s*(?:Answer|Option)?|Answer)\s*[:\-]|\n\s*(?:Answer\s*Note|Note|Explanation|Description|Why)\s*[:\-]|\n\s*(?:Bible\s*)?(?:Reference|Ref|Scripture\s*Reference)\s*[:\-]|\z)';

            $pattern = '/(?:^|\n)\s*(?:Option\s*)?' . $key . '\s*[\.\):\-]\s*(.+?)' . $lookahead . '/is';

            if (preg_match($pattern, $block, $match)) {
                $options[$key] = trim(preg_replace('/\s+/', ' ', $match[1]) ?: $match[1]);
            }
        }

        return $options;
    }

    protected function extractBulkValue(string $block, string $pattern, int $group = 1): string
    {
        if (! preg_match($pattern, $block, $match)) {
            return '';
        }

        return trim(preg_replace('/\s+/', ' ', $match[$group] ?? '') ?: ($match[$group] ?? ''));
    }

    protected function normalizeImportedBibleReference(string $reference, string $defaultBook = ''): string
    {
        $reference = trim($reference);
        $reference = preg_replace('/\s+/', ' ', $reference) ?: $reference;
        $reference = preg_replace('/^(?:Bible\s*)?(?:Reference|Ref|Scripture\s*Reference)\s*[:\-]\s*/i', '', $reference) ?: $reference;
        $reference = trim($reference, " \t\n\r\0\x0B.;,");

        if ($reference === '') return '';

        if (! preg_match('/^[1-3]?\s*[A-Za-z]/', $reference) && trim($defaultBook) !== '') {
            $reference = trim($defaultBook) . ' ' . $reference;
        }

        return trim($reference);
    }

    protected function guessBibleReferenceFromBlock(string $block): string
    {
        $knownBooks = [
            'Genesis','Exodus','Leviticus','Numbers','Deuteronomy','Joshua','Judges','Ruth',
            '1 Samuel','2 Samuel','1 Kings','2 Kings','1 Chronicles','2 Chronicles','Ezra','Nehemiah','Esther',
            'Job','Psalms','Psalm','Proverbs','Ecclesiastes','Song of Solomon','Isaiah','Jeremiah','Lamentations',
            'Ezekiel','Daniel','Hosea','Joel','Amos','Obadiah','Jonah','Micah','Nahum','Habakkuk','Zephaniah',
            'Haggai','Zechariah','Malachi','Matthew','Mark','Luke','John','Acts','Romans','1 Corinthians',
            '2 Corinthians','Galatians','Ephesians','Philippians','Colossians','1 Thessalonians','2 Thessalonians',
            '1 Timothy','2 Timothy','Titus','Philemon','Hebrews','James','1 Peter','2 Peter','1 John','2 John',
            '3 John','Jude','Revelation',
        ];

        foreach ($knownBooks as $book) {
            $pattern = '/\b' . preg_quote($book, '/') . '\s+\d+(?::\d+(?:\s*[-–]\s*(?:(?:\d+)\s*:)?\s*\d+)?)?\b/i';
            if (preg_match($pattern, $block, $match)) return trim($match[0]);
        }

        return '';
    }
    protected function parseBibleReference(string $reference, string $defaultBook = ''): array
    {
        $reference = trim($reference);
        $default = [
            'bible_reference' => $reference,
            'bible_book' => trim($defaultBook) ?: null,
            'chapter_start' => null,
            'verse_start' => null,
            'chapter_end' => null,
            'verse_end' => null,
        ];

        if ($reference === '') {
            return $default;
        }

        if (! preg_match('/^\s*((?:[1-3]\s*)?[A-Za-z][A-Za-z\s]+?)\s+(\d+)(?::(\d+)(?:\s*[-–]\s*(?:(\d+)\s*:)?\s*(\d+))?)?\s*$/', $reference, $match)) {
            return $default;
        }

        $book = trim(preg_replace('/\s+/', ' ', $match[1]) ?: $match[1]);
        $chapterStart = isset($match[2]) ? (int) $match[2] : null;
        $verseStart = isset($match[3]) && $match[3] !== '' ? (int) $match[3] : null;
        $chapterEnd = isset($match[4]) && $match[4] !== '' ? (int) $match[4] : $chapterStart;
        $verseEnd = isset($match[5]) && $match[5] !== '' ? (int) $match[5] : $verseStart;

        return [
            'bible_reference' => $reference,
            'bible_book' => $book,
            'chapter_start' => $chapterStart,
            'verse_start' => $verseStart,
            'chapter_end' => $chapterEnd,
            'verse_end' => $verseEnd,
        ];
    }

    public function enableLevelDrafts(QuizLevel $quizLevel): RedirectResponse
    {
        $this->ensureScoped($quizLevel->quizSet);

        $count = 0;

        $quizLevel->questions()
            ->where('is_enabled', false)
            ->get()
            ->each(function (QuizQuestion $question) use (&$count) {
                $meta = is_array($question->meta_json) ? $question->meta_json : [];
                $meta['review_required'] = false;
                $meta['reviewed_at'] = now()->toDateTimeString();

                $question->update([
                    'is_enabled' => true,
                    'meta_json' => $meta,
                ]);

                $count++;
            });

        return $this->back($count . ' draft question(s) enabled for this level.');
    }

    protected function validateSet(Request $request): array
    {
        return $request->validate([
            'key' => ['nullable', 'string', 'max:160'],
            'title' => ['required', 'string', 'max:255'],
            'subtitle' => ['nullable', 'string', 'max:500'],
            'type' => ['required', 'string', 'max:80'],
            'source_bucket' => ['nullable', 'string', 'max:120'],
            'source_key' => ['nullable', 'string', 'max:180'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'difficulty' => ['nullable', 'string', 'max:80'],
            'status' => ['nullable', 'string', 'max:40'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_enabled' => ['nullable'],
            'show_answers_after_submit' => ['nullable'],
            'allow_retake' => ['nullable'],
            'shuffle_questions' => ['nullable'],
            'shuffle_options' => ['nullable'],
            'questions_per_session' => ['nullable', 'integer', 'min:1', 'max:500'],
            'session_mode' => ['nullable', 'string', 'max:80'],
        ]);
    }

    protected function levelNumberExists(QuizSet $quizSet, int $levelNumber, ?int $ignoreId = null): bool
    {
        return $quizSet->levels()
            ->where('level_number', $levelNumber)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }
    protected function studyGroupKeyExists(QuizSet $quizSet, string $key, ?int $ignoreId = null): bool
    {
        return $quizSet->studyGroups()
            ->where('key', $key)
            ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
            ->exists();
    }
    protected function validateStudyGroup(Request $request, ?QuizStudyGroup $existing = null): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:160'],
            'key' => ['nullable', 'string', 'max:160'],
            'subtitle' => ['nullable', 'string', 'max:255'],
            'type' => ['nullable', 'string', 'max:80'],
            'bible_book' => ['nullable', 'string', 'max:80'],
            'testament' => ['nullable', 'string', 'max:40'],
            'description' => ['nullable', 'string', 'max:2000'],
            'image_url' => ['nullable', 'string', 'max:2000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_enabled' => ['nullable'],
        ]);

        $data['key'] = $this->safeKey($data['key'] ?: $data['title']);
        $data['type'] = $data['type'] ?: 'custom';
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_enabled'] = (bool) ($data['is_enabled'] ?? false);
        $data['settings_json'] = [
            'mode' => 'study_group',
            'created_from' => 'quiz_center',
        ];

        return $data;
    }

    protected function validatedStudyGroupId(Request $request, QuizSet $quizSet): ?int
    {
        $groupId = (int) $request->input('quiz_study_group_id', 0);

        if ($groupId <= 0) {
            return null;
        }

        return $quizSet->studyGroups()->whereKey($groupId)->exists() ? $groupId : null;
    }
    protected function validateLevel(Request $request): array
    {
        $data = $request->validate([
            'level_number' => ['required', 'integer', 'min:1', 'max:500'],
            'title' => ['required', 'string', 'max:255'],
            'difficulty' => ['required', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:1000'],
            'question_target' => ['required', 'integer', 'min:1', 'max:1000'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_enabled' => ['nullable'],
            'shuffle_questions' => ['nullable'],
            'shuffle_options' => ['nullable'],
            'questions_per_session' => ['nullable', 'integer', 'min:1', 'max:500'],
            'study_path' => ['nullable', 'string', 'max:120'],
        ]);

        $data['sort_order'] = (int) ($data['sort_order'] ?? $data['level_number']);
        $data['is_enabled'] = (bool) ($data['is_enabled'] ?? false);
        $data['settings_json'] = [
            'mode' => 'custom_level',
            'shuffle_questions' => $request->boolean('shuffle_questions', true),
            'shuffle_options' => $request->boolean('shuffle_options', false),
            'questions_per_session' => (int) ($data['questions_per_session'] ?? $data['question_target']),
            'study_path' => $data['study_path'] ?? null,
        ];

        unset($data['shuffle_questions'], $data['shuffle_options'], $data['questions_per_session'], $data['study_path']);

        return $data;
    }

    protected function validateQuestion(Request $request, QuizSet $quizSet): array
    {
        $data = $request->validate([
            'quiz_level_id' => ['nullable', 'integer'],
            'level' => ['required', 'integer', 'min:1', 'max:500'],
            'question_text' => ['required', 'string', 'max:5000'],
            'option_a' => ['nullable', 'string', 'max:1000'],
            'option_b' => ['nullable', 'string', 'max:1000'],
            'option_c' => ['nullable', 'string', 'max:1000'],
            'option_d' => ['nullable', 'string', 'max:1000'],
            'correct_option' => ['nullable', 'string', 'max:10'],
            'explanation' => ['nullable', 'string', 'max:5000'],
            'answer_note' => ['nullable', 'string', 'max:5000'],
            'bible_reference' => ['nullable', 'string', 'max:255'],
            'bible_book' => ['nullable', 'string', 'max:80'],
            'chapter_start' => ['nullable', 'integer', 'min:1', 'max:200'],
            'verse_start' => ['nullable', 'integer', 'min:1', 'max:200'],
            'chapter_end' => ['nullable', 'integer', 'min:1', 'max:200'],
            'verse_end' => ['nullable', 'integer', 'min:1', 'max:200'],
            'study_focus' => ['nullable', 'string', 'max:120'],
            'question_kind' => ['nullable', 'string', 'max:80'],
            'points' => ['nullable', 'integer', 'min:0', 'max:100'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_enabled' => ['nullable'],
        ]);

        $level = null;

        if (! empty($data['quiz_level_id'])) {
            $level = $quizSet->levels()->where('id', (int) $data['quiz_level_id'])->first();
        }

        if (! $level) {
            $level = $quizSet->levels()->where('level_number', (int) $data['level'])->first();
        }

        $data['quiz_level_id'] = $level?->id;
        $data['level'] = $level ? (int) $level->level_number : (int) $data['level'];
        $data['correct_option'] = strtoupper((string) ($data['correct_option'] ?? ''));
        $data['answer_note'] = $data['answer_note'] ?? $data['explanation'] ?? null;
        $data['points'] = (int) ($data['points'] ?? 1);
        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);
        $data['is_enabled'] = (bool) ($data['is_enabled'] ?? false);

        return $data;
    }

    protected function quizSettingsFromRequest(Request $request, ?QuizSet $quizSet = null): array
    {
        $existing = is_array($quizSet?->settings_json) ? $quizSet->settings_json : [];

        return array_merge($existing, [
            'mode' => $request->input('session_mode', $existing['mode'] ?? 'custom_pool'),
            'show_answers_after_submit' => $request->boolean('show_answers_after_submit'),
            'allow_retake' => $request->boolean('allow_retake', true),
            'shuffle_questions' => $request->boolean('shuffle_questions', true),
            'shuffle_options' => $request->boolean('shuffle_options', false),
            'questions_per_session' => (int) $request->input('questions_per_session', $existing['questions_per_session'] ?? 20),
        ]);
    }


    protected function syncQuizSetToEngine(?QuizSet $quizSet): void
    {
        if (! $quizSet || ! Schema::hasTable('quiz_collections') || ! Schema::hasTable('quiz_categories') || ! Schema::hasTable('quiz_packs')) {
            return;
        }

        $appId = (int) ($quizSet->app_id ?? 0);
        if ($appId <= 0) {
            return;
        }

        $profile = $this->quizTypeProfile((string) $quizSet->type, (string) $quizSet->title);

        $collection = QuizCollection::query()->updateOrCreate(
            [
                'app_id' => $appId,
                'slug' => $profile['collection_slug'],
            ],
            [
                'title' => $profile['collection_title'],
                'description' => $profile['collection_title'] . ' collection.',
                'icon' => $profile['icon'],
                'image_url' => null,
                'type' => $profile['type'],
                'status' => 'published',
                'sort_order' => $profile['sort_order'],
                'is_enabled' => true,
                'settings_json' => [
                    'source' => 'quiz_center_phase1b_sync',
                    'app_specific' => true,
                    'note' => 'Collections are app-specific. SOD/Devotional should not be treated as a global engine default.',
                ],
            ]
        );

        $category = QuizCategory::query()->updateOrCreate(
            [
                'app_id' => $appId,
                'quiz_collection_id' => $collection->id,
                'slug' => $profile['category_slug'],
            ],
            [
                'title' => $profile['category_title'],
                'description' => $profile['category_title'] . '.',
                'icon' => $profile['icon'],
                'image_url' => null,
                'type' => $profile['category_type'],
                'status' => 'published',
                'sort_order' => $profile['category_sort_order'],
                'is_enabled' => true,
                'settings_json' => [
                    'source' => 'quiz_center_phase1b_sync',
                ],
            ]
        );

        $totalQuestions = (int) $quizSet->questions()->count();
        $questionTarget = (int) $quizSet->levels()->sum('question_target');

        QuizPack::query()->updateOrCreate(
            [
                'app_id' => $appId,
                'quiz_set_id' => $quizSet->id,
                'quiz_study_group_id' => null,
            ],
            [
                'quiz_collection_id' => $collection->id,
                'quiz_category_id' => $category->id,
                'title' => (string) $quizSet->title,
                'slug' => $this->safeKey((string) ($quizSet->key ?: $quizSet->title)),
                'subtitle' => $quizSet->subtitle,
                'description' => $quizSet->subtitle,
                'date' => null,
                'source_type' => $profile['source_type'],
                'source_id' => $quizSet->id,
                'bible_book' => null,
                'cover_image_url' => $quizSet->image_url,
                'difficulty' => $quizSet->difficulty ?: 'custom',
                'question_target' => $questionTarget,
                'total_questions' => $totalQuestions,
                'is_today' => false,
                'is_featured' => false,
                'status' => $quizSet->status ?: 'draft',
                'sort_order' => (int) $quizSet->sort_order,
                'is_enabled' => (bool) $quizSet->is_enabled,
                'settings_json' => array_merge(is_array($quizSet->settings_json) ? $quizSet->settings_json : [], [
                    'source' => 'quiz_center_phase1b_sync',
                    'legacy_quiz_set_id' => $quizSet->id,
                    'collection_slug' => $collection->slug,
                    'category_slug' => $category->slug,
                ]),
            ]
        );
    }

    protected function quizTypeProfile(string $type, string $title = ''): array
    {
        $type = strtolower(trim($type)) ?: 'custom';

        return match ($type) {
            'bible' => [
                'type' => 'bible',
                'collection_slug' => 'bible_quiz',
                'collection_title' => 'Bible Quiz',
                'category_slug' => 'general',
                'category_title' => 'General Bible Quiz',
                'category_type' => 'general',
                'source_type' => 'bible',
                'icon' => 'bible',
                'sort_order' => 10,
                'category_sort_order' => 10,
            ],
            'article' => [
                'type' => 'article',
                'collection_slug' => 'article_quiz',
                'collection_title' => 'Article Quiz',
                'category_slug' => 'topic_based',
                'category_title' => 'Topic-based Article Quizzes',
                'category_type' => 'topic_based',
                'source_type' => 'article',
                'icon' => 'quiz',
                'sort_order' => 30,
                'category_sort_order' => 30,
            ],
            'sod' => [
                'type' => 'sod',
                'collection_slug' => 'sod_quiz',
                'collection_title' => 'SOD Quiz',
                'category_slug' => 'previous',
                'category_title' => 'Previous SOD Quizzes',
                'category_type' => 'previous',
                'source_type' => 'sod',
                'icon' => 'quiz',
                'sort_order' => 20,
                'category_sort_order' => 20,
            ],
            'daily', 'devotional', 'content' => [
                'type' => 'daily',
                'collection_slug' => 'daily_devotional_quiz',
                'collection_title' => 'Daily Devotional Quiz',
                'category_slug' => 'daily_quizzes',
                'category_title' => 'Daily Quizzes',
                'category_type' => 'daily',
                'source_type' => 'content',
                'icon' => 'quiz',
                'sort_order' => 25,
                'category_sort_order' => 10,
            ],
            'general' => [
                'type' => 'general',
                'collection_slug' => 'general_quiz',
                'collection_title' => 'General Quiz',
                'category_slug' => 'general',
                'category_title' => 'General Quiz Packs',
                'category_type' => 'general',
                'source_type' => 'manual',
                'icon' => 'quiz',
                'sort_order' => 40,
                'category_sort_order' => 10,
            ],
            default => [
                'type' => 'custom',
                'collection_slug' => 'custom_quiz',
                'collection_title' => 'Custom Quiz',
                'category_slug' => 'custom',
                'category_title' => 'Custom Quiz Packs',
                'category_type' => 'custom',
                'source_type' => 'manual',
                'icon' => 'quiz',
                'sort_order' => 50,
                'category_sort_order' => 10,
            ],
        };
    }

    protected function ensureScoped(QuizSet $quizSet): void
    {
        $appId = (int) (ActiveApp::ensureId() ?? 0);
        abort_if($appId <= 0 || ((int) $quizSet->app_id !== 0 && (int) $quizSet->app_id !== $appId), 403);
    }

    protected function safeKey(string $value): string
    {
        return Str::slug($value, '_');
    }

    protected function back(string $message): RedirectResponse
    {
        $tab = trim((string) request()->input('return_tab', request()->query('quiz_tab', 'all')));
        $scroll = (int) request()->input('return_scroll', request()->query('quiz_scroll', 0));

        if ($tab === '') {
            $tab = 'all';
        }

        $query = http_build_query([
            'quiz_tab' => $tab,
            'quiz_scroll' => max(0, $scroll),
        ]);

        return redirect()
            ->to(url('/admin/quiz-center') . '?' . $query)
            ->with('status', $message);
    }
}
