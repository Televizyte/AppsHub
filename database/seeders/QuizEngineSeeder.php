<?php

namespace Database\Seeders;

use App\Models\App;
use App\Models\QuizSet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class QuizEngineSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('quiz_sets')) {
            return;
        }

        foreach (App::query()->get() as $app) {
            $this->seedForApp((int) $app->id);
        }
    }

    protected function seedForApp(int $appId): void
    {
        $sets = [
            ['key' => 'sod_quiz', 'title' => 'SOD Quiz', 'subtitle' => 'Test what you learned from the devotional.', 'type' => 'sod', 'source_bucket' => 'sod', 'sort_order' => 10],
            ['key' => 'article_quiz', 'title' => 'Article Quiz', 'subtitle' => 'Create questions from any article or teaching.', 'type' => 'article', 'source_bucket' => 'articles', 'sort_order' => 20],
            ['key' => 'bible_quiz', 'title' => 'Bible Quiz', 'subtitle' => 'Create Bible-based learning questions.', 'type' => 'bible', 'source_bucket' => 'bible', 'sort_order' => 30],
        ];

        foreach ($sets as $set) {
            QuizSet::query()->updateOrCreate(
                ['app_id' => $appId, 'key' => $set['key']],
                array_merge($set, [
                    'app_id' => $appId,
                    'difficulty' => 'easy',
                    'status' => 'draft',
                    'is_enabled' => true,
                    'settings_json' => [
                        'mode' => 'manual',
                        'allow_retake' => true,
                        'show_answers_after_submit' => false,
                    ],
                ])
            );
        }
    }
}
