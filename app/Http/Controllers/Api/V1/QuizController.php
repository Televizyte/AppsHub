<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\App;
use App\Models\QuizQuestion;
use App\Models\QuizSet;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class QuizController extends Controller
{
    public function index(Request $request, string $appSlug): JsonResponse
    {
        $app = $this->appFromRequest($request, $appSlug);

        if (! $app || ! Schema::hasTable('quiz_sets')) {
            return response()->json(['ok' => true, 'data' => []]);
        }

        $type = trim((string) $request->query('type', ''));

        $sets = QuizSet::query()
            ->withCount(['enabledQuestions'])
            ->where(function ($query) use ($app) {
                $query->whereNull('app_id')->orWhere('app_id', $app->id);
            })
            ->where('is_enabled', true)
            ->where('status', 'published')
            ->when($type !== '', fn ($query) => $query->where('type', $type))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (QuizSet $set) => $this->setPayload($set, false, $request))
            ->values();

        return response()->json(['ok' => true, 'data' => $sets]);
    }

    public function show(Request $request, string $appSlug, string $key): JsonResponse
    {
        $app = $this->appFromRequest($request, $appSlug);

        if (! $app || ! Schema::hasTable('quiz_sets')) {
            return response()->json(['ok' => true, 'data' => $this->placeholder($key)]);
        }

        $with = [
            'enabledLevels.enabledQuestions',
            'enabledQuestions',
        ];

        if ($this->supportsStudyGroups()) {
            $with['studyGroups'] = fn ($query) => $query
                ->where('is_enabled', true)
                ->orderBy('sort_order')
                ->orderBy('id');

            $with['studyGroups.levels'] = fn ($query) => $query
                ->where('is_enabled', true)
                ->orderBy('sort_order')
                ->orderBy('level_number')
                ->orderBy('id');

            $with[] = 'studyGroups.levels.enabledQuestions';
        }

        $set = QuizSet::query()
            ->with($with)
            ->where(function ($query) use ($app) {
                $query->whereNull('app_id')->orWhere('app_id', $app->id);
            })
            ->where('key', $key)
            ->where('is_enabled', true)
            ->first();

        if (! $set) {
            return response()->json(['ok' => true, 'data' => $this->placeholder($key)]);
        }

        return response()->json(['ok' => true, 'data' => $this->setPayload($set, true, $request)]);
    }

    protected function appFromRequest(Request $request, string $appSlug): ?App
    {
        $app = App::query()->where('slug', $appSlug)->first();

        if (! $app) return null;

        $token = (string) $request->header('X-APP-TOKEN', '');

        if ($token === '' || ! hash_equals((string) $app->api_token, $token)) {
            abort(response()->json([
                'ok' => false,
                'error' => 'APP_TOKEN_INVALID',
                'message' => 'Invalid X-APP-TOKEN header.',
            ], 401));
        }

        return $app;
    }

    protected function setPayload(QuizSet $set, bool $includeQuestions, Request $request): array
    {
        $levelCount = Schema::hasTable('quiz_levels') ? $set->enabledLevels()->count() : 0;
        $settings = is_array($set->settings_json) ? $set->settings_json : [];

        $payload = [
            'id' => $set->id,
            'key' => $set->key,
            'title' => $set->title,
            'subtitle' => $set->subtitle,
            'type' => $set->type,
            'difficulty' => $set->difficulty,
            'image_url' => $set->image_url,
            'status' => $set->status,
            'is_enabled' => (bool) $set->is_enabled,
            'question_count' => (int) ($set->enabled_questions_count ?? $set->enabledQuestions()->count()),
            'level_count' => (int) $levelCount,
            'settings' => $settings,
            'session' => [
                'shuffle_questions' => (bool) ($settings['shuffle_questions'] ?? true),
                'shuffle_options' => (bool) ($settings['shuffle_options'] ?? false),
                'questions_per_session' => (int) ($settings['questions_per_session'] ?? 20),
            ],
            'placeholder' => false,
        ];

        if ($includeQuestions) {
            if ($this->supportsStudyGroups()) {
                $studyGroups = $set->relationLoaded('studyGroups')
                    ? $set->studyGroups
                    : $set->studyGroups()
                        ->where('is_enabled', true)
                        ->orderBy('sort_order')
                        ->orderBy('id')
                        ->with(['levels' => fn ($query) => $query
                            ->where('is_enabled', true)
                            ->orderBy('sort_order')
                            ->orderBy('level_number')
                            ->orderBy('id'), 'levels.enabledQuestions'])
                        ->get();

                $payload['study_groups'] = $studyGroups
                    ->map(fn ($group) => $this->studyGroupPayload($group, $settings, $request))
                    ->values()
                    ->all();
            } else {
                $payload['study_groups'] = [];
            }

            if (Schema::hasTable('quiz_levels')) {
                $levels = $set->relationLoaded('enabledLevels')
                    ? $set->enabledLevels
                    : $set->enabledLevels()->with('enabledQuestions')->get();

                $payload['levels'] = $levels
                    ->map(fn ($level) => $this->levelPayload($level, $settings, $request))
                    ->values()
                    ->all();
            }

            $payload['questions'] = $this->sessionQuestions($set->enabledQuestions, $settings, [], $request)
                ->map(fn ($question) => $this->questionPayload($question, (bool) ($settings['shuffle_options'] ?? false)))
                ->values()
                ->all();
        }

        return $payload;
    }

    protected function studyGroupPayload($group, array $setSettings, Request $request): array
    {
        $settings = is_array($group->settings_json) ? $group->settings_json : [];
        $levels = $group->relationLoaded('levels')
            ? $group->levels
            : $group->levels()
                ->where('is_enabled', true)
                ->with('enabledQuestions')
                ->orderBy('sort_order')
                ->orderBy('level_number')
                ->orderBy('id')
                ->get();

        $levelPayloads = $levels
            ->map(fn ($level) => $this->levelPayload($level, $setSettings, $request, $group))
            ->values();

        return [
            'id' => $group->id,
            'key' => $group->key,
            'title' => $group->title,
            'subtitle' => $group->subtitle,
            'type' => $group->type,
            'bible_book' => $group->bible_book,
            'testament' => $group->testament,
            'description' => $group->description,
            'image_url' => $group->image_url,
            'sort_order' => (int) $group->sort_order,
            'is_enabled' => (bool) $group->is_enabled,
            'settings' => $settings,
            'level_count' => (int) $levelPayloads->count(),
            'question_count' => (int) $levelPayloads->sum(fn ($level) => count($level['questions'] ?? [])),
            'levels' => $levelPayloads->all(),
        ];
    }

    protected function levelPayload($level, array $setSettings, Request $request, $studyGroup = null): array
    {
        $levelSettings = is_array($level->settings_json) ? $level->settings_json : [];
        $questions = $this->sessionQuestions($level->enabledQuestions, $setSettings, $levelSettings, $request);

        return [
            'id' => $level->id,
            'quiz_study_group_id' => $level->quiz_study_group_id,
            'study_group_id' => $level->quiz_study_group_id,
            'study_group_key' => $studyGroup?->key,
            'study_group_title' => $studyGroup?->title,
            'bible_book' => $studyGroup?->bible_book ?: ($levelSettings['bible_book'] ?? $levelSettings['study_path'] ?? null),
            'level_number' => (int) $level->level_number,
            'level' => (int) $level->level_number,
            'title' => $level->title,
            'difficulty' => $level->difficulty,
            'description' => $level->description,
            'question_target' => (int) $level->question_target,
            'sort_order' => (int) $level->sort_order,
            'is_enabled' => (bool) $level->is_enabled,
            'settings' => $levelSettings,
            'session' => [
                'shuffle_questions' => (bool) ($levelSettings['shuffle_questions'] ?? $setSettings['shuffle_questions'] ?? true),
                'shuffle_options' => (bool) ($levelSettings['shuffle_options'] ?? $setSettings['shuffle_options'] ?? false),
                'questions_per_session' => (int) ($levelSettings['questions_per_session'] ?? $setSettings['questions_per_session'] ?? $level->question_target ?? 20),
            ],
            'questions' => $questions
                ->map(fn ($question) => $this->questionPayload($question, (bool) ($levelSettings['shuffle_options'] ?? $setSettings['shuffle_options'] ?? false)))
                ->values()
                ->all(),
        ];
    }

    protected function sessionQuestions($questions, array $setSettings, array $levelSettings, Request $request)
    {
        $shuffle = filter_var($request->query('shuffle', $levelSettings['shuffle_questions'] ?? $setSettings['shuffle_questions'] ?? true), FILTER_VALIDATE_BOOLEAN);
        $limit = (int) $request->query('limit', $levelSettings['questions_per_session'] ?? $setSettings['questions_per_session'] ?? 0);

        $questions = collect($questions);

        if ($shuffle) {
            $seed = (string) $request->query('session', now()->format('YmdH'));
            $questions = $questions->sortBy(fn ($question) => crc32($seed . '-' . $question->id))->values();
        }

        if ($limit > 0) {
            $questions = $questions->take($limit);
        }

        return $questions->values();
    }

    protected function questionPayload(QuizQuestion $question, bool $shuffleOptions = false): array
    {
        $options = collect($question->options());

        if ($shuffleOptions) {
            $options = $options->sortBy(fn ($option) => crc32($question->id . '-' . $option['key']))->values();
        }

        return [
            'id' => $question->id,
            'level' => (int) $question->level,
            'question' => $question->question_text,
            'options' => $options->values()->all(),
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

    protected function supportsStudyGroups(): bool
    {
        return Schema::hasTable('quiz_study_groups')
            && Schema::hasTable('quiz_levels')
            && Schema::hasColumn('quiz_levels', 'quiz_study_group_id')
            && method_exists(QuizSet::class, 'studyGroups');
    }

    protected function placeholder(string $key): array
    {
        return [
            'id' => null,
            'key' => $key,
            'title' => str($key)->replace(['_', '-'], ' ')->title()->toString(),
            'subtitle' => 'Quiz content is being prepared.',
            'type' => 'placeholder',
            'difficulty' => 'custom',
            'image_url' => null,
            'status' => 'placeholder',
            'is_enabled' => false,
            'question_count' => 0,
            'level_count' => 0,
            'study_groups' => [],
            'levels' => [],
            'questions' => [],
            'settings' => ['mode' => 'placeholder', 'allow_retake' => true, 'show_answers_after_submit' => false],
            'session' => ['shuffle_questions' => false, 'shuffle_options' => false, 'questions_per_session' => 0],
            'placeholder' => true,
        ];
    }
}
