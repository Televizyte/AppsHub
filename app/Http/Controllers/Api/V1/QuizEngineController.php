<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\QuizAttempt;
use App\Models\QuizCategory;
use App\Models\QuizCollection;
use App\Models\QuizLevel;
use App\Models\QuizPack;
use App\Models\QuizQuestion;
use App\Models\QuizUserProgress;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class QuizEngineController extends Controller
{
    public function collections(Request $request, string $appSlug): JsonResponse
    {
        $app = $this->app($appSlug);

        if (! $this->ready(['quiz_collections'])) {
            return response()->json(['ok' => true, 'data' => []]);
        }

        $collections = QuizCollection::query()
            ->withCount(['enabledCategories as category_count', 'enabledPacks as pack_count'])
            ->where(function ($query) use ($app) {
                $query->whereNull('app_id')->orWhere('app_id', $app->id);
            })
            ->where('is_enabled', true)
            ->where('status', 'published')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (QuizCollection $collection) => $this->collectionPayload($collection))
            ->values();

        return response()->json(['ok' => true, 'data' => $collections]);
    }

    public function collection(Request $request, string $appSlug, string $collection): JsonResponse
    {
        $app = $this->app($appSlug);
        $collectionModel = $this->findCollection($app, $collection);

        if (! $collectionModel) {
            return response()->json(['ok' => true, 'data' => null]);
        }

        $collectionModel->load([
            'enabledCategories' => fn ($query) => $query->withCount(['enabledPacks as pack_count']),
        ]);

        $payload = $this->collectionPayload($collectionModel);
        $payload['categories'] = $collectionModel->enabledCategories
            ->map(fn (QuizCategory $category) => $this->categoryPayload($category))
            ->values()
            ->all();

        return response()->json(['ok' => true, 'data' => $payload]);
    }

    public function categories(Request $request, string $appSlug): JsonResponse
    {
        $app = $this->app($appSlug);
        $collection = $this->findCollection($app, (string) $request->query('collection', ''));

        if (! $collection) {
            return response()->json(['ok' => true, 'data' => []]);
        }

        $categories = QuizCategory::query()
            ->withCount(['enabledPacks as pack_count'])
            ->where('quiz_collection_id', $collection->id)
            ->where('is_enabled', true)
            ->where('status', 'published')
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (QuizCategory $category) => $this->categoryPayload($category))
            ->values();

        return response()->json(['ok' => true, 'data' => $categories]);
    }

    public function packs(Request $request, string $appSlug): JsonResponse
    {
        $app = $this->app($appSlug);

        if (! $this->ready(['quiz_packs'])) {
            return response()->json(['ok' => true, 'data' => []]);
        }

        $collection = $this->findCollection($app, (string) $request->query('collection', ''));
        $category = $collection ? $this->findCategory($collection, (string) $request->query('category', '')) : null;

        $packs = QuizPack::query()
            ->with(['collection', 'category'])
            ->withCount(['enabledLevels as level_count'])
            ->where(function ($query) use ($app) {
                $query->whereNull('app_id')->orWhere('app_id', $app->id);
            })
            ->where('is_enabled', true)
            ->where('status', 'published')
            ->when($collection, fn ($query) => $query->where('quiz_collection_id', $collection->id))
            ->when($category, fn ($query) => $query->where('quiz_category_id', $category->id))
            ->when($request->filled('today'), fn ($query) => $query->where('is_today', filter_var($request->query('today'), FILTER_VALIDATE_BOOLEAN)))
            ->when($request->filled('featured'), fn ($query) => $query->where('is_featured', filter_var($request->query('featured'), FILTER_VALIDATE_BOOLEAN)))
            ->when($request->filled('bible_book'), fn ($query) => $query->where('bible_book', $request->query('bible_book')))
            ->when($request->filled('slug'), fn ($query) => $query->where('slug', trim((string) $request->query('slug'))))
            ->when($request->filled('key'), fn ($query) => $query->where('slug', trim((string) $request->query('key'))))
            ->orderByDesc('is_today')
            ->orderByDesc('is_featured')
            ->orderBy('sort_order')
            ->orderByDesc('date')
            ->orderBy('id')
            ->limit(min(max((int) $request->query('per_page', 50), 1), 100))
            ->get()
            ->map(fn (QuizPack $pack) => $this->packPayload($pack))
            ->values();

        return response()->json(['ok' => true, 'data' => $packs]);
    }

    public function pack(Request $request, string $appSlug, QuizPack $pack): JsonResponse
    {
        $app = $this->app($appSlug);
        $this->guardPack($app, $pack);

        $pack->load(['collection', 'category']);
        $pack->loadCount(['enabledLevels as level_count']);

        $payload = $this->packPayload($pack);
        $payload['levels'] = $pack->enabledLevels()
            ->withCount(['enabledQuestions as question_count'])
            ->get()
            ->map(fn (QuizLevel $level) => $this->levelPayload($level, false))
            ->values()
            ->all();

        return response()->json(['ok' => true, 'data' => $payload]);
    }

    public function levels(Request $request, string $appSlug, QuizPack $pack): JsonResponse
    {
        $app = $this->app($appSlug);
        $this->guardPack($app, $pack);

        $levels = $pack->enabledLevels()
            ->withCount(['enabledQuestions as question_count'])
            ->get()
            ->map(fn (QuizLevel $level) => $this->levelPayload($level, false))
            ->values();

        return response()->json(['ok' => true, 'data' => $levels]);
    }

    public function questions(Request $request, string $appSlug, QuizLevel $level): JsonResponse
    {
        $app = $this->app($appSlug);
        $this->guardLevel($app, $level);

        $questions = $level->enabledQuestions()
            ->get()
            ->map(fn (QuizQuestion $question) => $this->questionPayload($question))
            ->values();

        return response()->json(['ok' => true, 'data' => $questions]);
    }


    public function startAttempt(Request $request, string $appSlug): JsonResponse
    {
        $app = $this->app($appSlug);

        if (! $this->ready(['quiz_packs', 'quiz_levels', 'quiz_questions', 'quiz_user_progress'])) {
            return response()->json(['ok' => false, 'error' => 'QUIZ_GAMEPLAY_NOT_READY'], 422);
        }

        $validated = $request->validate([
            'quiz_pack_id' => ['required', 'integer'],
            'quiz_level_id' => ['required', 'integer'],
            'user_id' => ['nullable', 'integer'],
            'device_id' => ['nullable', 'string', 'max:191'],
            'guest_session_id' => ['nullable', 'string', 'max:191'],
            'restart' => ['nullable', 'boolean'],
            'question_limit' => ['nullable', 'integer', 'min:1', 'max:500'],
            'shuffle_questions' => ['nullable', 'boolean'],
            'shuffle_options' => ['nullable', 'boolean'],
        ]);

        $pack = QuizPack::findOrFail((int) $validated['quiz_pack_id']);
        $level = QuizLevel::findOrFail((int) $validated['quiz_level_id']);
        $this->guardPack($app, $pack);
        $this->guardLevel($app, $level);

        if ($level->quiz_pack_id && (int) $level->quiz_pack_id !== (int) $pack->id) {
            return response()->json(['ok' => false, 'error' => 'LEVEL_DOES_NOT_BELONG_TO_PACK'], 422);
        }

        $identity = $this->identity($request, $validated);
        $restart = filter_var($validated['restart'] ?? false, FILTER_VALIDATE_BOOLEAN);

        $progressQuery = QuizUserProgress::query()
            ->where('app_id', $app->id)
            ->where('quiz_pack_id', $pack->id)
            ->where('quiz_level_id', $level->id)
            ->where($identity['column'], $identity['value']);

        $existing = (clone $progressQuery)->latest('updated_at')->first();

        if ($existing && ! $restart && $existing->status === 'in_progress') {
            $meta = $existing->meta_json ?: [];
            if (! empty($meta['question_order']) && is_array($meta['question_order'])) {
                return response()->json([
                    'ok' => true,
                    'mode' => 'continue',
                    'data' => $this->runtimeAttemptPayload($existing, $pack, $level),
                ]);
            }
        }

        if ($restart) {
            (clone $progressQuery)->delete();
        }

        $questions = $level->enabledQuestions()->get();
        $questionIds = $questions->pluck('id')->map(fn ($id) => (int) $id)->values()->all();

        $shuffleQuestions = filter_var($validated['shuffle_questions'] ?? true, FILTER_VALIDATE_BOOLEAN);
        $shuffleOptions = filter_var($validated['shuffle_options'] ?? true, FILTER_VALIDATE_BOOLEAN);

        if ($shuffleQuestions) {
            shuffle($questionIds);
        }

        $limit = (int) ($validated['question_limit'] ?? 0);
        if ($limit <= 0) {
            $limit = (int) ($level->question_target ?: $pack->question_target ?: count($questionIds));
        }
        if ($limit > 0) {
            $questionIds = array_slice($questionIds, 0, $limit);
        }

        $questionMap = $questions->keyBy('id');
        $optionOrders = [];

        foreach ($questionIds as $questionId) {
            /** @var QuizQuestion|null $question */
            $question = $questionMap->get($questionId);
            if (! $question) {
                continue;
            }

            $optionKeys = collect([
                'A' => $question->option_a,
                'B' => $question->option_b,
                'C' => $question->option_c,
                'D' => $question->option_d,
            ])->filter(fn ($value) => filled($value))->keys()->values()->all();

            if ($shuffleOptions) {
                shuffle($optionKeys);
            }

            $optionOrders[(string) $questionId] = $optionKeys;
        }

        $progress = QuizUserProgress::create([
            'app_id' => $app->id,
            $identity['column'] => $identity['value'],
            'quiz_pack_id' => $pack->id,
            'quiz_level_id' => $level->id,
            'current_question_index' => 0,
            'answered_questions_json' => [],
            'score' => 0,
            'status' => 'in_progress',
            'started_at' => now(),
            'meta_json' => [
                'source' => 'phase2a_runtime_attempt',
                'question_order' => $questionIds,
                'option_orders' => $optionOrders,
                'shuffle_questions' => $shuffleQuestions,
                'shuffle_options' => $shuffleOptions,
                'question_count' => count($questionIds),
            ],
        ]);

        return response()->json([
            'ok' => true,
            'mode' => 'start',
            'data' => $this->runtimeAttemptPayload($progress, $pack, $level),
        ]);
    }

    public function answerAttempt(Request $request, string $appSlug): JsonResponse
    {
        $app = $this->app($appSlug);

        if (! $this->ready(['quiz_user_progress'])) {
            return response()->json(['ok' => false, 'error' => 'QUIZ_PROGRESS_NOT_READY'], 422);
        }

        $validated = $request->validate([
            'quiz_pack_id' => ['required', 'integer'],
            'quiz_level_id' => ['required', 'integer'],
            'question_id' => ['required', 'integer'],
            'selected_label' => ['required', 'string', 'max:2'],
            'current_question_index' => ['nullable', 'integer', 'min:0'],
            'user_id' => ['nullable', 'integer'],
            'device_id' => ['nullable', 'string', 'max:191'],
            'guest_session_id' => ['nullable', 'string', 'max:191'],
        ]);

        $pack = QuizPack::findOrFail((int) $validated['quiz_pack_id']);
        $level = QuizLevel::findOrFail((int) $validated['quiz_level_id']);
        $question = QuizQuestion::findOrFail((int) $validated['question_id']);
        $this->guardPack($app, $pack);
        $this->guardLevel($app, $level);

        if ((int) $question->quiz_level_id !== (int) $level->id) {
            return response()->json(['ok' => false, 'error' => 'QUESTION_DOES_NOT_BELONG_TO_LEVEL'], 422);
        }

        $identity = $this->identity($request, $validated);
        $progress = QuizUserProgress::query()
            ->where('app_id', $app->id)
            ->where('quiz_pack_id', $pack->id)
            ->where('quiz_level_id', $level->id)
            ->where($identity['column'], $identity['value'])
            ->latest('updated_at')
            ->first();

        if (! $progress) {
            return response()->json(['ok' => false, 'error' => 'QUIZ_PROGRESS_NOT_FOUND'], 404);
        }

        $meta = $progress->meta_json ?: [];
        $optionOrders = $meta['option_orders'] ?? [];
        $questionOptionOrder = $optionOrders[(string) $question->id] ?? ['A', 'B', 'C', 'D'];

        $selectedLabel = strtoupper(substr((string) $validated['selected_label'], 0, 1));
        $labelIndex = ord($selectedLabel) - 65;
        $selectedOriginalKey = $questionOptionOrder[$labelIndex] ?? null;
        $correctOriginalKey = strtoupper((string) $question->correct_option);
        $isCorrect = $selectedOriginalKey && $selectedOriginalKey === $correctOriginalKey;
        $points = $isCorrect ? (int) ($question->points ?: 1) : 0;

        $answers = $progress->answered_questions_json ?: [];
        $answers = collect($answers)
            ->reject(fn ($answer) => (int) ($answer['question_id'] ?? 0) === (int) $question->id)
            ->values()
            ->all();

        $answers[] = [
            'question_id' => (int) $question->id,
            'selected_label' => $selectedLabel,
            'selected_original_key' => $selectedOriginalKey,
            'correct_original_key' => $correctOriginalKey,
            'is_correct' => (bool) $isCorrect,
            'points' => $points,
            'answered_at' => now()->toISOString(),
        ];

        $questionOrder = $meta['question_order'] ?? [];
        $currentIndex = array_search((int) $question->id, array_map('intval', $questionOrder), true);
        $nextIndex = is_int($currentIndex) ? $currentIndex + 1 : ((int) ($validated['current_question_index'] ?? $progress->current_question_index) + 1);
        $nextIndex = min($nextIndex, max(count($questionOrder), 1));

        $progress->fill([
            'answered_questions_json' => $answers,
            'score' => collect($answers)->sum(fn ($answer) => (int) ($answer['points'] ?? 0)),
            'current_question_index' => $nextIndex,
            'status' => $nextIndex >= count($questionOrder) ? 'ready_to_complete' : 'in_progress',
        ])->save();

        return response()->json([
            'ok' => true,
            'data' => [
                'question_id' => (int) $question->id,
                'selected_label' => $selectedLabel,
                'is_correct' => (bool) $isCorrect,
                'points' => $points,
                'score' => (int) $progress->score,
                'current_question_index' => (int) $progress->current_question_index,
                'status' => $progress->status,
                'correct_label' => $this->labelForOriginalKey($questionOptionOrder, $correctOriginalKey),
                'correct_answer' => $this->optionText($question, $correctOriginalKey),
                'explanation' => $question->explanation,
                'answer_note' => $question->answer_note ?: $question->explanation,
                'bible_reference' => $question->bible_reference,
                'bible_book' => $question->bible_book,
                'chapter_start' => $question->chapter_start,
                'verse_start' => $question->verse_start,
                'chapter_end' => $question->chapter_end,
                'verse_end' => $question->verse_end,
            ],
        ]);
    }

    public function completeAttempt(Request $request, string $appSlug): JsonResponse
    {
        $app = $this->app($appSlug);

        if (! $this->ready(['quiz_user_progress', 'quiz_attempts'])) {
            return response()->json(['ok' => false, 'error' => 'QUIZ_ATTEMPTS_NOT_READY'], 422);
        }

        $validated = $request->validate([
            'quiz_pack_id' => ['required', 'integer'],
            'quiz_level_id' => ['required', 'integer'],
            'user_id' => ['nullable', 'integer'],
            'device_id' => ['nullable', 'string', 'max:191'],
            'guest_session_id' => ['nullable', 'string', 'max:191'],
        ]);

        $pack = QuizPack::findOrFail((int) $validated['quiz_pack_id']);
        $level = QuizLevel::findOrFail((int) $validated['quiz_level_id']);
        $this->guardPack($app, $pack);
        $this->guardLevel($app, $level);

        $identity = $this->identity($request, $validated);
        $progress = QuizUserProgress::query()
            ->where('app_id', $app->id)
            ->where('quiz_pack_id', $pack->id)
            ->where('quiz_level_id', $level->id)
            ->where($identity['column'], $identity['value'])
            ->latest('updated_at')
            ->first();

        if (! $progress) {
            return response()->json(['ok' => false, 'error' => 'QUIZ_PROGRESS_NOT_FOUND'], 404);
        }

        $answers = $progress->answered_questions_json ?: [];
        $total = count($progress->meta_json['question_order'] ?? []) ?: count($answers);
        $correct = collect($answers)->filter(fn ($answer) => (bool) ($answer['is_correct'] ?? false))->count();
        $wrong = max(0, $total - $correct);
        $score = collect($answers)->sum(fn ($answer) => (int) ($answer['points'] ?? 0));
        $percentage = $total > 0 ? (int) round(($correct / $total) * 100) : 0;
        $passMark = (int) (($level->settings_json['pass_mark'] ?? $pack->settings_json['pass_mark'] ?? 50));

        $attempt = QuizAttempt::create([
            'app_id' => $app->id,
            $identity['column'] => $identity['value'],
            'quiz_pack_id' => $pack->id,
            'quiz_level_id' => $level->id,
            'score' => $score,
            'total_questions' => $total,
            'correct_count' => $correct,
            'wrong_count' => $wrong,
            'percentage' => $percentage,
            'passed' => $percentage >= $passMark,
            'answers_json' => $answers,
            'started_at' => $progress->started_at,
            'completed_at' => now(),
            'meta_json' => $progress->meta_json ?: [],
        ]);

        $progress->fill([
            'score' => $score,
            'status' => 'completed',
            'completed_at' => now(),
        ])->save();

        return response()->json(['ok' => true, 'data' => $this->attemptPayload($attempt)]);
    }

    public function saveProgress(Request $request, string $appSlug): JsonResponse
    {
        $app = $this->app($appSlug);

        if (! $this->ready(['quiz_user_progress'])) {
            return response()->json(['ok' => false, 'error' => 'QUIZ_PROGRESS_NOT_READY'], 422);
        }

        $validated = $request->validate([
            'quiz_pack_id' => ['required', 'integer'],
            'quiz_level_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],
            'device_id' => ['nullable', 'string', 'max:191'],
            'guest_session_id' => ['nullable', 'string', 'max:191'],
            'current_question_index' => ['nullable', 'integer', 'min:0'],
            'answered_questions' => ['nullable', 'array'],
            'score' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'max:40'],
            'meta' => ['nullable', 'array'],
        ]);

        $pack = QuizPack::findOrFail((int) $validated['quiz_pack_id']);
        $this->guardPack($app, $pack);

        $identity = $this->identity($request, $validated);

        $progress = QuizUserProgress::query()
            ->where('app_id', $app->id)
            ->where('quiz_pack_id', $pack->id)
            ->when($validated['quiz_level_id'] ?? null, fn ($query, $levelId) => $query->where('quiz_level_id', $levelId))
            ->where($identity['column'], $identity['value'])
            ->first();

        if (! $progress) {
            $progress = new QuizUserProgress([
                'app_id' => $app->id,
                $identity['column'] => $identity['value'],
                'quiz_pack_id' => $pack->id,
                'quiz_level_id' => $validated['quiz_level_id'] ?? null,
                'started_at' => now(),
            ]);
        }

        $progress->fill([
            'current_question_index' => (int) ($validated['current_question_index'] ?? $progress->current_question_index ?? 0),
            'answered_questions_json' => $validated['answered_questions'] ?? $progress->answered_questions_json,
            'score' => (int) ($validated['score'] ?? $progress->score ?? 0),
            'status' => $validated['status'] ?? 'in_progress',
            'completed_at' => (($validated['status'] ?? '') === 'completed') ? now() : $progress->completed_at,
            'meta_json' => $validated['meta'] ?? $progress->meta_json,
        ])->save();

        return response()->json(['ok' => true, 'data' => $this->progressPayload($progress)]);
    }

    public function progress(Request $request, string $appSlug, QuizPack $pack, ?QuizLevel $level = null): JsonResponse
    {
        $app = $this->app($appSlug);
        $this->guardPack($app, $pack);

        if (! $this->ready(['quiz_user_progress'])) {
            return response()->json(['ok' => true, 'data' => null]);
        }

        $identity = $this->identity($request, $request->all());

        $progress = QuizUserProgress::query()
            ->where('app_id', $app->id)
            ->where('quiz_pack_id', $pack->id)
            ->when($level, fn ($query) => $query->where('quiz_level_id', $level->id))
            ->where($identity['column'], $identity['value'])
            ->latest('updated_at')
            ->first();

        return response()->json(['ok' => true, 'data' => $progress ? $this->progressPayload($progress) : null]);
    }

    public function submit(Request $request, string $appSlug): JsonResponse
    {
        $app = $this->app($appSlug);

        if (! $this->ready(['quiz_attempts'])) {
            return response()->json(['ok' => false, 'error' => 'QUIZ_ATTEMPTS_NOT_READY'], 422);
        }

        $validated = $request->validate([
            'quiz_pack_id' => ['required', 'integer'],
            'quiz_level_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],
            'device_id' => ['nullable', 'string', 'max:191'],
            'guest_session_id' => ['nullable', 'string', 'max:191'],
            'score' => ['nullable', 'integer'],
            'total_questions' => ['nullable', 'integer', 'min:0'],
            'correct_count' => ['nullable', 'integer', 'min:0'],
            'wrong_count' => ['nullable', 'integer', 'min:0'],
            'answers' => ['nullable', 'array'],
            'meta' => ['nullable', 'array'],
        ]);

        $pack = QuizPack::findOrFail((int) $validated['quiz_pack_id']);
        $this->guardPack($app, $pack);
        $identity = $this->identity($request, $validated);

        $total = (int) ($validated['total_questions'] ?? 0);
        $correct = (int) ($validated['correct_count'] ?? 0);
        $wrong = (int) ($validated['wrong_count'] ?? max(0, $total - $correct));
        $percentage = $total > 0 ? (int) round(($correct / $total) * 100) : 0;

        $attempt = QuizAttempt::create([
            'app_id' => $app->id,
            $identity['column'] => $identity['value'],
            'quiz_pack_id' => $pack->id,
            'quiz_level_id' => $validated['quiz_level_id'] ?? null,
            'score' => (int) ($validated['score'] ?? $correct),
            'total_questions' => $total,
            'correct_count' => $correct,
            'wrong_count' => $wrong,
            'percentage' => $percentage,
            'passed' => $percentage >= (int) (($pack->settings_json['pass_mark'] ?? 50)),
            'answers_json' => $validated['answers'] ?? [],
            'started_at' => $request->input('started_at') ?: null,
            'completed_at' => now(),
            'meta_json' => $validated['meta'] ?? [],
        ]);

        return response()->json(['ok' => true, 'data' => $this->attemptPayload($attempt)]);
    }

    public function reset(Request $request, string $appSlug): JsonResponse
    {
        $app = $this->app($appSlug);
        $validated = $request->validate([
            'quiz_pack_id' => ['required', 'integer'],
            'quiz_level_id' => ['nullable', 'integer'],
            'user_id' => ['nullable', 'integer'],
            'device_id' => ['nullable', 'string', 'max:191'],
            'guest_session_id' => ['nullable', 'string', 'max:191'],
        ]);

        $pack = QuizPack::findOrFail((int) $validated['quiz_pack_id']);
        $this->guardPack($app, $pack);
        $identity = $this->identity($request, $validated);

        $deleted = QuizUserProgress::query()
            ->where('app_id', $app->id)
            ->where('quiz_pack_id', $pack->id)
            ->when($validated['quiz_level_id'] ?? null, fn ($query, $levelId) => $query->where('quiz_level_id', $levelId))
            ->where($identity['column'], $identity['value'])
            ->delete();

        return response()->json(['ok' => true, 'deleted' => $deleted]);
    }

    protected function collectionPayload(QuizCollection $collection): array
    {
        return [
            'id' => $collection->id,
            'title' => $collection->title,
            'slug' => $collection->slug,
            'key' => $collection->slug,
            'description' => $collection->description,
            'icon' => $collection->icon,
            'image_url' => $collection->image_url,
            'type' => $collection->type,
            'status' => $collection->status,
            'sort_order' => (int) $collection->sort_order,
            'is_enabled' => (bool) $collection->is_enabled,
            'category_count' => (int) ($collection->category_count ?? 0),
            'pack_count' => (int) ($collection->pack_count ?? 0),
            'settings' => $collection->settings_json ?: [],
            'route' => '/quiz/collections/'.$collection->slug,
        ];
    }

    protected function categoryPayload(QuizCategory $category): array
    {
        return [
            'id' => $category->id,
            'quiz_collection_id' => $category->quiz_collection_id,
            'title' => $category->title,
            'slug' => $category->slug,
            'key' => $category->slug,
            'description' => $category->description,
            'icon' => $category->icon,
            'image_url' => $category->image_url,
            'type' => $category->type,
            'status' => $category->status,
            'sort_order' => (int) $category->sort_order,
            'is_enabled' => (bool) $category->is_enabled,
            'pack_count' => (int) ($category->pack_count ?? 0),
            'settings' => $category->settings_json ?: [],
        ];
    }

    protected function packPayload(QuizPack $pack): array
    {
        return [
            'id' => $pack->id,
            'quiz_collection_id' => $pack->quiz_collection_id,
            'quiz_category_id' => $pack->quiz_category_id,
            'quiz_set_id' => $pack->quiz_set_id,
            'quiz_study_group_id' => $pack->quiz_study_group_id,
            'collection' => $pack->relationLoaded('collection') && $pack->collection ? [
                'id' => $pack->collection->id,
                'slug' => $pack->collection->slug,
                'title' => $pack->collection->title,
                'type' => $pack->collection->type,
            ] : null,
            'category' => $pack->relationLoaded('category') && $pack->category ? [
                'id' => $pack->category->id,
                'slug' => $pack->category->slug,
                'title' => $pack->category->title,
                'type' => $pack->category->type,
            ] : null,
            'title' => $pack->title,
            'slug' => $pack->slug,
            'key' => $pack->slug,
            'subtitle' => $pack->subtitle,
            'description' => $pack->description,
            'date' => $pack->date?->toDateString(),
            'source_type' => $pack->source_type,
            'source_id' => $pack->source_id,
            'bible_book' => $pack->bible_book,
            'cover_image_url' => $pack->cover_image_url,
            'image_url' => $pack->cover_image_url,
            'difficulty' => $pack->difficulty,
            'question_target' => (int) $pack->question_target,
            'total_questions' => (int) $pack->total_questions,
            'question_count' => (int) $pack->total_questions,
            'level_count' => (int) ($pack->level_count ?? 0),
            'is_today' => (bool) $pack->is_today,
            'is_featured' => (bool) $pack->is_featured,
            'status' => $pack->status,
            'sort_order' => (int) $pack->sort_order,
            'is_enabled' => (bool) $pack->is_enabled,
            'settings' => $pack->settings_json ?: [],
            'route' => '/quiz/packs/'.$pack->id,
        ];
    }

    protected function levelPayload(QuizLevel $level, bool $includeQuestions = false): array
    {
        $payload = [
            'id' => $level->id,
            'quiz_pack_id' => $level->quiz_pack_id ?? null,
            'quiz_set_id' => $level->quiz_set_id,
            'quiz_study_group_id' => $level->quiz_study_group_id,
            'level_number' => (int) $level->level_number,
            'level' => (int) $level->level_number,
            'title' => $level->title,
            'difficulty' => $level->difficulty,
            'description' => $level->description,
            'question_target' => (int) $level->question_target,
            'question_count' => (int) ($level->question_count ?? 0),
            'sort_order' => (int) $level->sort_order,
            'is_enabled' => (bool) $level->is_enabled,
            'settings' => $level->settings_json ?: [],
            'route' => '/quiz/levels/'.$level->id,
        ];

        if ($includeQuestions) {
            $payload['questions'] = $level->enabledQuestions()
                ->get()
                ->map(fn (QuizQuestion $question) => $this->questionPayload($question))
                ->values()
                ->all();
        }

        return $payload;
    }

    protected function questionPayload(QuizQuestion $question): array
    {
        return [
            'id' => $question->id,
            'quiz_pack_id' => $question->quiz_pack_id ?? null,
            'quiz_level_id' => $question->quiz_level_id,
            'level' => (int) $question->level,
            'question' => $question->question_text,
            'question_text' => $question->question_text,
            'options' => $question->options(),
            'correct_option' => $question->correct_option,
            'points' => (int) $question->points,
            'explanation' => $question->explanation,
            'answer_note' => $question->answer_note ?: $question->explanation,
            'bible_reference' => $question->bible_reference,
            'bible_book' => $question->bible_book,
            'chapter_start' => $question->chapter_start,
            'verse_start' => $question->verse_start,
            'chapter_end' => $question->chapter_end,
            'verse_end' => $question->verse_end,
            'study_focus' => $question->study_focus,
            'question_kind' => $question->question_kind,
        ];
    }

    protected function progressPayload(QuizUserProgress $progress): array
    {
        return [
            'id' => $progress->id,
            'quiz_pack_id' => $progress->quiz_pack_id,
            'quiz_level_id' => $progress->quiz_level_id,
            'current_question_index' => (int) $progress->current_question_index,
            'answered_questions' => $progress->answered_questions_json ?: [],
            'score' => (int) $progress->score,
            'status' => $progress->status,
            'started_at' => $progress->started_at?->toISOString(),
            'completed_at' => $progress->completed_at?->toISOString(),
            'meta' => $progress->meta_json ?: [],
        ];
    }

    protected function attemptPayload(QuizAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'quiz_pack_id' => $attempt->quiz_pack_id,
            'quiz_level_id' => $attempt->quiz_level_id,
            'score' => (int) $attempt->score,
            'total_questions' => (int) $attempt->total_questions,
            'correct_count' => (int) $attempt->correct_count,
            'wrong_count' => (int) $attempt->wrong_count,
            'percentage' => (int) $attempt->percentage,
            'passed' => (bool) $attempt->passed,
            'answers' => $attempt->answers_json ?: [],
            'completed_at' => $attempt->completed_at?->toISOString(),
        ];
    }


    protected function runtimeAttemptPayload(QuizUserProgress $progress, QuizPack $pack, QuizLevel $level): array
    {
        $meta = $progress->meta_json ?: [];
        $questionIds = collect($meta['question_order'] ?? [])->map(fn ($id) => (int) $id)->values()->all();
        $questions = QuizQuestion::query()
            ->whereIn('id', $questionIds)
            ->get()
            ->keyBy('id');

        return [
            'progress' => $this->progressPayload($progress),
            'quiz_pack' => $this->packPayload($pack),
            'level' => $this->levelPayload($level, false),
            'question_count' => count($questionIds),
            'current_question_index' => (int) $progress->current_question_index,
            'answered_questions' => $progress->answered_questions_json ?: [],
            'questions' => collect($questionIds)
                ->map(fn ($id) => $questions->get($id))
                ->filter()
                ->map(fn (QuizQuestion $question) => $this->runtimeQuestionPayload($question, $meta))
                ->values()
                ->all(),
        ];
    }

    protected function runtimeQuestionPayload(QuizQuestion $question, array $meta): array
    {
        $optionOrders = $meta['option_orders'] ?? [];
        $questionOptionOrder = $optionOrders[(string) $question->id] ?? ['A', 'B', 'C', 'D'];

        $options = [];
        foreach (array_values($questionOptionOrder) as $index => $originalKey) {
            $label = chr(65 + $index);
            $text = $this->optionText($question, (string) $originalKey);
            if (! filled($text)) {
                continue;
            }
            $options[] = [
                'label' => $label,
                'key' => $label,
                'text' => $text,
            ];
        }

        return [
            'id' => $question->id,
            'quiz_pack_id' => $question->quiz_pack_id ?? null,
            'quiz_level_id' => $question->quiz_level_id,
            'level' => (int) $question->level,
            'question' => $question->question_text,
            'question_text' => $question->question_text,
            'options' => $options,
            'points' => (int) $question->points,
            'bible_reference' => $question->bible_reference,
            'bible_book' => $question->bible_book,
            'chapter_start' => $question->chapter_start,
            'verse_start' => $question->verse_start,
            'chapter_end' => $question->chapter_end,
            'verse_end' => $question->verse_end,
            'study_focus' => $question->study_focus,
            'question_kind' => $question->question_kind,
        ];
    }

    protected function optionText(QuizQuestion $question, string $originalKey): ?string
    {
        return match (strtoupper($originalKey)) {
            'A' => $question->option_a,
            'B' => $question->option_b,
            'C' => $question->option_c,
            'D' => $question->option_d,
            default => null,
        };
    }

    protected function labelForOriginalKey(array $optionOrder, string $originalKey): ?string
    {
        $index = array_search(strtoupper($originalKey), array_map('strtoupper', $optionOrder), true);
        return is_int($index) ? chr(65 + $index) : null;
    }

    protected function app(string $appSlug): App
    {
        return App::where('slug', $appSlug)->firstOrFail();
    }

    protected function findCollection(App $app, string $idOrSlug): ?QuizCollection
    {
        if (! $this->ready(['quiz_collections']) || trim($idOrSlug) === '') {
            return null;
        }

        return QuizCollection::query()
            ->where(function ($query) use ($app) {
                $query->whereNull('app_id')->orWhere('app_id', $app->id);
            })
            ->where(function ($query) use ($idOrSlug) {
                $query->where('slug', $idOrSlug);
                if (ctype_digit($idOrSlug)) {
                    $query->orWhere('id', (int) $idOrSlug);
                }
            })
            ->first();
    }

    protected function findCategory(QuizCollection $collection, string $idOrSlug): ?QuizCategory
    {
        if (trim($idOrSlug) === '') {
            return null;
        }

        return QuizCategory::query()
            ->where('quiz_collection_id', $collection->id)
            ->where(function ($query) use ($idOrSlug) {
                $query->where('slug', $idOrSlug);
                if (ctype_digit($idOrSlug)) {
                    $query->orWhere('id', (int) $idOrSlug);
                }
            })
            ->first();
    }

    protected function guardPack(App $app, QuizPack $pack): void
    {
        if ($pack->app_id !== null && (int) $pack->app_id !== (int) $app->id) {
            abort(404);
        }
    }

    protected function guardLevel(App $app, QuizLevel $level): void
    {
        if (Schema::hasColumn('quiz_levels', 'quiz_pack_id') && $level->quiz_pack_id) {
            $pack = QuizPack::findOrFail($level->quiz_pack_id);
            $this->guardPack($app, $pack);
            return;
        }

        if ($level->quizSet && $level->quizSet->app_id !== null && (int) $level->quizSet->app_id !== (int) $app->id) {
            abort(404);
        }
    }

    protected function identity(Request $request, array $data): array
    {
        if (! empty($data['user_id'])) {
            return ['column' => 'user_id', 'value' => (int) $data['user_id']];
        }

        if (! empty($data['device_id'])) {
            return ['column' => 'device_id', 'value' => (string) $data['device_id']];
        }

        if (! empty($data['guest_session_id'])) {
            return ['column' => 'guest_session_id', 'value' => (string) $data['guest_session_id']];
        }

        $fallback = $request->header('X-Device-ID') ?: $request->ip();
        return ['column' => 'guest_session_id', 'value' => 'guest:'.sha1((string) $fallback)];
    }

    protected function ready(array $tables): bool
    {
        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                return false;
            }
        }

        return true;
    }
}
