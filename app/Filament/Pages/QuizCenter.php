<?php

namespace App\Filament\Pages;

use App\Support\AdminAccess;

use App\Models\App;
use App\Models\QuizCollection;
use App\Models\QuizSet;
use App\Support\ActiveApp;
use App\Support\AppCapabilities;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

class QuizCenter extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-question-mark-circle';
    protected static ?string $navigationLabel = 'Quiz Center';
    protected static ?string $navigationGroup = 'Engagement';
    protected static ?int $navigationSort = 20;
    protected static ?string $slug = 'quiz-center';
    protected static string $view = 'filament.pages.quiz-center';

    public static function shouldRegisterNavigation(): bool
    {
        return AdminAccess::page('quiz_center');
    }

    public static function canAccess(): bool
    {
        return AdminAccess::page('quiz_center');
    }

    public ?App $currentApp = null;
    public array $quizSets = [];
    public array $quizCollections = [];
    public array $stats = [];


    public function mount(): void
    {
        $appId = (int) (ActiveApp::ensureId() ?? 0);
        $this->currentApp = $appId > 0 ? App::query()->find($appId) : null;

        if (! Schema::hasTable('quiz_sets')) {
            $this->quizSets = [];
            $this->quizCollections = [];
            $this->stats = [
                'total' => 0,
                'enabled' => 0,
                'published' => 0,
                'draft' => 0,
                'questions' => 0,
                'collections' => 0,
                'packs' => 0,
            ];
            return;
        }

        $relationships = [
            'levels.questions',
            'questions',
        ];

        if (Schema::hasTable('quiz_study_groups')) {
            $relationships[] = 'studyGroups.levels.questions';
        }

        $this->quizCollections = $this->loadQuizCollections($appId);
        $disabledCollectionTypes = collect($this->quizCollections)
            ->filter(fn (array $collection): bool => ! (bool) ($collection['is_enabled'] ?? false) || ($collection['status'] ?? '') !== 'published')
            ->pluck('type')
            ->filter()
            ->values()
            ->all();

        $sets = QuizSet::query()
            ->with($relationships)
            ->withCount(['questions', 'enabledQuestions'])
            ->where(function ($query) use ($appId) {
                $query->whereNull('app_id');

                if ($appId > 0) {
                    $query->orWhere('app_id', $appId);
                }
            })
            ->when(! empty($disabledCollectionTypes), fn ($query) => $query->whereNotIn('type', $disabledCollectionTypes))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        foreach ($sets as $set) {
            if ($set->levels->isEmpty()) {
                $set->ensureDefaultLevels();
                $set->load($relationships);
            }
        }

        $this->quizSets = $sets
            ->map(fn (QuizSet $set): array => $this->mapQuizSet($set))
            ->values()
            ->all();

        $this->stats = [
            'total' => $sets->count(),
            'enabled' => $sets->where('is_enabled', true)->count(),
            'published' => $sets->where('status', 'published')->count(),
            'draft' => $sets->where('status', 'draft')->count(),
            'questions' => $sets->sum('questions_count'),
            'collections' => count($this->quizCollections),
            'packs' => collect($this->quizCollections)->sum('pack_count'),
        ];
    }


    protected function loadQuizCollections(int $appId): array
    {
        if ($appId <= 0 || ! Schema::hasTable('quiz_collections')) {
            return [];
        }

        $relationships = [];

        if (Schema::hasTable('quiz_categories')) {
            $relationships[] = 'categories';
        }

        if (Schema::hasTable('quiz_packs')) {
            $relationships[] = 'packs';
        }

        return QuizCollection::query()
            ->with($relationships)
            ->where('app_id', $appId)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(function (QuizCollection $collection): array {
                return [
                    'id' => (int) $collection->id,
                    'title' => (string) $collection->title,
                    'slug' => (string) $collection->slug,
                    'type' => (string) $collection->type,
                    'status' => (string) $collection->status,
                    'is_enabled' => (bool) $collection->is_enabled,
                    'sort_order' => (int) $collection->sort_order,
                    'category_count' => $collection->relationLoaded('categories') ? $collection->categories->count() : 0,
                    'pack_count' => $collection->relationLoaded('packs') ? $collection->packs->count() : 0,
                    'published_pack_count' => $collection->relationLoaded('packs') ? $collection->packs->where('status', 'published')->where('is_enabled', true)->count() : 0,
                ];
            })
            ->values()
            ->all();
    }

    protected function adminLabelsForQuizType(string $type): array
    {
        $type = strtolower(trim($type));

        return match ($type) {
            'bible' => [
                'context' => 'Bible study quiz',
                'create_pack' => '+ New Bible Quiz Set',
                'group' => '+ Add Bible Book / Study Group',
                'show_group_button' => true,
                'empty_hint' => 'Ungrouped levels can be assigned to Genesis, Exodus, Matthew, Bible Characters, Bible Stories, or any Bible study group from each level’s Edit Level button.',
            ],
            'sod' => [
                'context' => 'Dunamis-specific devotional quiz',
                'create_pack' => '+ New SOD / Devotional Quiz',
                'group' => '+ Add Devotional Series',
                'show_group_button' => false,
                'empty_hint' => 'Create a new SOD quiz set for each devotional/day when you need a separate listing. Add levels and questions inside that quiz set.',
            ],
            'article' => [
                'context' => 'Article-based quiz',
                'create_pack' => '+ New Article Quiz',
                'group' => '+ Add Article Series',
                'show_group_button' => false,
                'empty_hint' => 'Create a new Article Quiz set for each article or topic. Add levels and questions inside that quiz set.',
            ],
            default => [
                'context' => 'Custom quiz collection',
                'create_pack' => '+ New Quiz Set',
                'group' => '+ Add Group',
                'show_group_button' => false,
                'empty_hint' => 'Create a new quiz set when you need a separate listing. Add levels and questions inside that quiz set.',
            ],
        };
    }

    protected function mapQuizSet(QuizSet $set): array
    {
        $allLevels = $set->levels
            ->sortBy([
                ['sort_order', 'asc'],
                ['level_number', 'asc'],
                ['id', 'asc'],
            ])
            ->map(fn ($level): array => $this->mapLevel($level))
            ->values();

        $studyGroups = collect();

        if ($set->relationLoaded('studyGroups')) {
            $studyGroups = $set->studyGroups
                ->sortBy([
                    ['sort_order', 'asc'],
                    ['id', 'asc'],
                ])
                ->map(function ($group): array {
                    $levels = $group->relationLoaded('levels')
                        ? $group->levels
                            ->sortBy([
                                ['sort_order', 'asc'],
                                ['level_number', 'asc'],
                                ['id', 'asc'],
                            ])
                            ->map(fn ($level): array => $this->mapLevel($level))
                            ->values()
                        : collect();

                    return [
                        'id' => (int) $group->id,
                        'quiz_set_id' => (int) $group->quiz_set_id,
                        'key' => (string) $group->key,
                        'title' => (string) $group->title,
                        'subtitle' => (string) ($group->subtitle ?? ''),
                        'type' => (string) ($group->type ?? ''),
                        'bible_book' => (string) ($group->bible_book ?? ''),
                        'testament' => (string) ($group->testament ?? ''),
                        'description' => (string) ($group->description ?? ''),
                        'image_url' => (string) ($group->image_url ?? ''),
                        'sort_order' => (int) $group->sort_order,
                        'is_enabled' => (bool) $group->is_enabled,
                        'levels_count' => $levels->count(),
                        'questions_count' => $this->countQuestionsFromLevels($levels),
                        'enabled_questions_count' => $this->countQuestionsFromLevels($levels, true),
                        'levels' => $levels->all(),
                    ];
                })
                ->values();
        }

        $hasStudyGroups = $studyGroups->isNotEmpty();

        $ungroupedLevels = $allLevels
            ->filter(function (array $level) use ($hasStudyGroups): bool {
                if (! $hasStudyGroups) {
                    return true;
                }

                return empty($level['quiz_study_group_id']);
            })
            ->values();

        return [
            'id' => (int) $set->id,
            'key' => (string) $set->key,
            'title' => (string) $set->title,
            'subtitle' => (string) ($set->subtitle ?? ''),
            'type' => (string) $set->type,
            'source_bucket' => (string) ($set->source_bucket ?? ''),
            'source_key' => (string) ($set->source_key ?? ''),
            'difficulty' => (string) $set->difficulty,
            'status' => (string) $set->status,
            'is_enabled' => (bool) $set->is_enabled,
            'sort_order' => (int) $set->sort_order,
            'image_url' => (string) ($set->image_url ?? ''),
            'settings' => is_array($set->settings_json) ? $set->settings_json : [],
            'admin_labels' => $this->adminLabelsForQuizType((string) $set->type),
            'questions_count' => (int) ($set->questions_count ?? 0),
            'enabled_questions_count' => (int) ($set->enabled_questions_count ?? 0),
            'study_groups' => $studyGroups->all(),
            'levels' => $ungroupedLevels->all(),
            'all_levels' => $allLevels->all(),
            'all_levels_count' => $allLevels->count(),
        ];
    }

    protected function mapLevel($level): array
    {
        return [
            'id' => (int) $level->id,
            'quiz_set_id' => (int) $level->quiz_set_id,
            'quiz_study_group_id' => (int) ($level->quiz_study_group_id ?? 0),
            'level_number' => (int) $level->level_number,
            'title' => (string) $level->title,
            'difficulty' => (string) $level->difficulty,
            'description' => (string) ($level->description ?? ''),
            'question_target' => (int) $level->question_target,
            'sort_order' => (int) $level->sort_order,
            'is_enabled' => (bool) $level->is_enabled,
            'settings' => is_array($level->settings_json ?? null) ? $level->settings_json : [],
            'questions' => $level->relationLoaded('questions')
                ? $level->questions
                    ->sortBy([
                        ['sort_order', 'asc'],
                        ['id', 'asc'],
                    ])
                    ->map(fn ($question): array => $this->mapQuestion($question))
                    ->values()
                    ->all()
                : [],
        ];
    }

    protected function mapQuestion($question): array
    {
        return [
            'id' => (int) $question->id,
            'quiz_level_id' => (int) ($question->quiz_level_id ?? 0),
            'level' => (int) $question->level,
            'question_text' => (string) $question->question_text,
            'option_a' => (string) ($question->option_a ?? ''),
            'option_b' => (string) ($question->option_b ?? ''),
            'option_c' => (string) ($question->option_c ?? ''),
            'option_d' => (string) ($question->option_d ?? ''),
            'options' => method_exists($question, 'options') ? $question->options() : [],
            'correct_option' => (string) ($question->correct_option ?? ''),
            'explanation' => (string) ($question->explanation ?? ''),
            'bible_reference' => (string) ($question->bible_reference ?? ''),
            'bible_book' => (string) ($question->bible_book ?? ''),
            'chapter_start' => (int) ($question->chapter_start ?? 0),
            'verse_start' => (int) ($question->verse_start ?? 0),
            'chapter_end' => (int) ($question->chapter_end ?? 0),
            'verse_end' => (int) ($question->verse_end ?? 0),
            'study_focus' => (string) ($question->study_focus ?? ''),
            'question_kind' => (string) ($question->question_kind ?? ''),
            'points' => (int) ($question->points ?? 1),
            'is_enabled' => (bool) $question->is_enabled,
            'sort_order' => (int) $question->sort_order,
        ];
    }

    protected function countQuestionsFromLevels(Collection $levels, bool $enabledOnly = false): int
    {
        return $levels->sum(function (array $level) use ($enabledOnly): int {
            $questions = collect($level['questions'] ?? []);

            if ($enabledOnly) {
                $questions = $questions->where('is_enabled', true);
            }

            return $questions->count();
        });
    }
}
