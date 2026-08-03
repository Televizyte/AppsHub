<?php

namespace App\Console\Commands;

use App\Models\QuizSet;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SeedQuizStudyGroups extends Command
{
    protected $signature = 'appshub:seed-quiz-study-groups';

    protected $description = 'Create default quiz study groups/Bible book groups and attach existing book-by-book levels.';

    public function handle(): int
    {
        if (! Schema::hasTable('quiz_study_groups') || ! Schema::hasColumn('quiz_levels', 'quiz_study_group_id')) {
            $this->error('Study group tables/columns are missing. Run migrations first.');
            return self::FAILURE;
        }

        $count = 0;

        QuizSet::query()
            ->where(function ($query) {
                $query->where('key', 'bible_books')
                    ->orWhere('source_key', 'books')
                    ->orWhere('title', 'like', '%Book by Book%');
            })
            ->with('levels')
            ->get()
            ->each(function (QuizSet $set) use (&$count) {
                $group = $set->studyGroups()->firstOrCreate(
                    ['key' => 'genesis'],
                    [
                        'title' => 'Genesis',
                        'subtitle' => 'Study Genesis level by level.',
                        'type' => 'bible_book',
                        'bible_book' => 'Genesis',
                        'testament' => 'old',
                        'description' => 'Book-by-book Bible quiz path for Genesis.',
                        'sort_order' => 1,
                        'is_enabled' => true,
                        'settings_json' => ['mode' => 'bible_book', 'book' => 'Genesis'],
                    ]
                );

                $set->levels()
                    ->whereNull('quiz_study_group_id')
                    ->get()
                    ->each(function ($level) use ($group, &$count) {
                        $settings = $level->settings_json ?: [];
                        $studyPath = strtolower((string) ($settings['study_path'] ?? ''));

                        if ($studyPath === 'genesis' || str_contains(strtolower($level->title), 'genesis') || $group->key === 'genesis') {
                            $level->update(['quiz_study_group_id' => $group->id]);
                            $count++;
                        }
                    });
            });

        $this->info("Quiz study groups seeded. Levels attached: {$count}");

        return self::SUCCESS;
    }
}
