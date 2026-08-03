<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quiz_levels')) {
            Schema::create('quiz_levels', function (Blueprint $table) {
                $table->id();
                $table->foreignId('quiz_set_id')->constrained('quiz_sets')->cascadeOnDelete();
                $table->unsignedInteger('level_number')->default(1)->index();
                $table->string('title')->default('Level');
                $table->string('difficulty')->default('easy')->index();
                $table->text('description')->nullable();
                $table->unsignedInteger('question_target')->default(5);
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_enabled')->default(true)->index();
                $table->json('settings_json')->nullable();
                $table->timestamps();

                $table->unique(['quiz_set_id', 'level_number']);
            });
        }

        if (Schema::hasTable('quiz_questions') && ! Schema::hasColumn('quiz_questions', 'quiz_level_id')) {
            Schema::table('quiz_questions', function (Blueprint $table) {
                $table->foreignId('quiz_level_id')
                    ->nullable()
                    ->after('quiz_set_id')
                    ->constrained('quiz_levels')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('quiz_sets') && Schema::hasTable('quiz_levels')) {
            $sets = DB::table('quiz_sets')->select('id')->get();

            foreach ($sets as $set) {
                $this->ensureDefaultLevels((int) $set->id);
            }

            if (Schema::hasColumn('quiz_questions', 'quiz_level_id')) {
                $questions = DB::table('quiz_questions')->whereNull('quiz_level_id')->get();

                foreach ($questions as $question) {
                    $levelNumber = max(1, (int) ($question->level ?? 1));
                    $level = DB::table('quiz_levels')
                        ->where('quiz_set_id', $question->quiz_set_id)
                        ->where('level_number', $levelNumber)
                        ->first();

                    if ($level) {
                        DB::table('quiz_questions')
                            ->where('id', $question->id)
                            ->update(['quiz_level_id' => $level->id]);
                    }
                }
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('quiz_questions') && Schema::hasColumn('quiz_questions', 'quiz_level_id')) {
            Schema::table('quiz_questions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('quiz_level_id');
            });
        }

        Schema::dropIfExists('quiz_levels');
    }

    private function ensureDefaultLevels(int $quizSetId): void
    {
        $defaults = [
            ['level_number' => 1, 'title' => 'Level 1', 'difficulty' => 'easy', 'description' => 'Simple recall questions.', 'question_target' => 5, 'sort_order' => 1],
            ['level_number' => 2, 'title' => 'Level 2', 'difficulty' => 'medium', 'description' => 'Understanding and application questions.', 'question_target' => 5, 'sort_order' => 2],
            ['level_number' => 3, 'title' => 'Level 3', 'difficulty' => 'hard', 'description' => 'Deeper thinking and mastery questions.', 'question_target' => 5, 'sort_order' => 3],
        ];

        foreach ($defaults as $level) {
            $exists = DB::table('quiz_levels')
                ->where('quiz_set_id', $quizSetId)
                ->where('level_number', $level['level_number'])
                ->exists();

            if (! $exists) {
                DB::table('quiz_levels')->insert(array_merge($level, [
                    'quiz_set_id' => $quizSetId,
                    'is_enabled' => true,
                    'settings_json' => json_encode(['mode' => 'manual']),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]));
            }
        }
    }
};
