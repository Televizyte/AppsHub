<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quiz_collections')) {
            Schema::create('quiz_collections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('app_id')->nullable()->constrained('apps')->cascadeOnDelete();
                $table->string('title');
                $table->string('slug');
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->text('image_url')->nullable();
                $table->string('type')->default('custom')->index();
                $table->string('status')->default('published')->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_enabled')->default(true)->index();
                $table->json('settings_json')->nullable();
                $table->timestamps();

                $table->unique(['app_id', 'slug']);
                $table->index(['app_id', 'type', 'status']);
                $table->index(['app_id', 'sort_order']);
            });
        }

        if (! Schema::hasTable('quiz_categories')) {
            Schema::create('quiz_categories', function (Blueprint $table) {
                $table->id();
                $table->foreignId('app_id')->nullable()->constrained('apps')->cascadeOnDelete();
                $table->foreignId('quiz_collection_id')->constrained('quiz_collections')->cascadeOnDelete();
                $table->string('title');
                $table->string('slug');
                $table->text('description')->nullable();
                $table->string('icon')->nullable();
                $table->text('image_url')->nullable();
                $table->string('type')->default('custom')->index();
                $table->string('status')->default('published')->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_enabled')->default(true)->index();
                $table->json('settings_json')->nullable();
                $table->timestamps();

                $table->unique(['quiz_collection_id', 'slug']);
                $table->index(['app_id', 'type', 'status']);
                $table->index(['quiz_collection_id', 'sort_order']);
            });
        }

        if (! Schema::hasTable('quiz_packs')) {
            Schema::create('quiz_packs', function (Blueprint $table) {
                $table->id();
                $table->foreignId('app_id')->nullable()->constrained('apps')->cascadeOnDelete();
                $table->foreignId('quiz_collection_id')->constrained('quiz_collections')->cascadeOnDelete();
                $table->foreignId('quiz_category_id')->nullable()->constrained('quiz_categories')->nullOnDelete();
                $table->foreignId('quiz_set_id')->nullable()->constrained('quiz_sets')->nullOnDelete();
                $table->foreignId('quiz_study_group_id')->nullable()->constrained('quiz_study_groups')->nullOnDelete();
                $table->string('title');
                $table->string('slug');
                $table->string('subtitle')->nullable();
                $table->text('description')->nullable();
                $table->date('date')->nullable()->index();
                $table->string('source_type')->default('manual')->index();
                $table->unsignedBigInteger('source_id')->nullable()->index();
                $table->string('bible_book')->nullable()->index();
                $table->text('cover_image_url')->nullable();
                $table->string('difficulty')->nullable()->index();
                $table->unsignedInteger('question_target')->default(0);
                $table->unsignedInteger('total_questions')->default(0);
                $table->boolean('is_today')->default(false)->index();
                $table->boolean('is_featured')->default(false)->index();
                $table->string('status')->default('published')->index();
                $table->unsignedInteger('sort_order')->default(0);
                $table->boolean('is_enabled')->default(true)->index();
                $table->json('settings_json')->nullable();
                $table->timestamps();

                $table->unique(['quiz_collection_id', 'slug']);
                $table->index(['app_id', 'status', 'is_enabled']);
                $table->index(['quiz_collection_id', 'quiz_category_id', 'sort_order']);
                $table->index(['quiz_set_id', 'quiz_study_group_id']);
            });
        }

        if (Schema::hasTable('quiz_levels') && ! Schema::hasColumn('quiz_levels', 'quiz_pack_id')) {
            Schema::table('quiz_levels', function (Blueprint $table) {
                $table->foreignId('quiz_pack_id')
                    ->nullable()
                    ->after('quiz_study_group_id')
                    ->constrained('quiz_packs')
                    ->nullOnDelete();
            });
        }

        if (Schema::hasTable('quiz_questions') && ! Schema::hasColumn('quiz_questions', 'quiz_pack_id')) {
            Schema::table('quiz_questions', function (Blueprint $table) {
                $table->foreignId('quiz_pack_id')
                    ->nullable()
                    ->after('quiz_level_id')
                    ->constrained('quiz_packs')
                    ->nullOnDelete();
            });
        }

        if (! Schema::hasTable('quiz_user_progress')) {
            Schema::create('quiz_user_progress', function (Blueprint $table) {
                $table->id();
                $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('device_id')->nullable()->index();
                $table->string('guest_session_id')->nullable()->index();
                $table->foreignId('quiz_pack_id')->constrained('quiz_packs')->cascadeOnDelete();
                $table->foreignId('quiz_level_id')->nullable()->constrained('quiz_levels')->nullOnDelete();
                $table->unsignedInteger('current_question_index')->default(0);
                $table->json('answered_questions_json')->nullable();
                $table->integer('score')->default(0);
                $table->string('status')->default('in_progress')->index();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->json('meta_json')->nullable();
                $table->timestamps();

                $table->index(['app_id', 'quiz_pack_id', 'quiz_level_id']);
                $table->index(['app_id', 'user_id', 'status']);
            });
        }

        if (! Schema::hasTable('quiz_attempts')) {
            Schema::create('quiz_attempts', function (Blueprint $table) {
                $table->id();
                $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('device_id')->nullable()->index();
                $table->string('guest_session_id')->nullable()->index();
                $table->foreignId('quiz_pack_id')->constrained('quiz_packs')->cascadeOnDelete();
                $table->foreignId('quiz_level_id')->nullable()->constrained('quiz_levels')->nullOnDelete();
                $table->integer('score')->default(0);
                $table->unsignedInteger('total_questions')->default(0);
                $table->unsignedInteger('correct_count')->default(0);
                $table->unsignedInteger('wrong_count')->default(0);
                $table->unsignedTinyInteger('percentage')->default(0);
                $table->boolean('passed')->default(false)->index();
                $table->json('answers_json')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('completed_at')->nullable();
                $table->json('meta_json')->nullable();
                $table->timestamps();

                $table->index(['app_id', 'quiz_pack_id', 'quiz_level_id']);
                $table->index(['app_id', 'user_id', 'created_at']);
            });
        }

        $this->seedFoundationFromLegacyData();
    }

    public function down(): void
    {
        if (Schema::hasTable('quiz_questions') && Schema::hasColumn('quiz_questions', 'quiz_pack_id')) {
            Schema::table('quiz_questions', function (Blueprint $table) {
                $table->dropConstrainedForeignId('quiz_pack_id');
            });
        }

        if (Schema::hasTable('quiz_levels') && Schema::hasColumn('quiz_levels', 'quiz_pack_id')) {
            Schema::table('quiz_levels', function (Blueprint $table) {
                $table->dropConstrainedForeignId('quiz_pack_id');
            });
        }

        Schema::dropIfExists('quiz_attempts');
        Schema::dropIfExists('quiz_user_progress');
        Schema::dropIfExists('quiz_packs');
        Schema::dropIfExists('quiz_categories');
        Schema::dropIfExists('quiz_collections');
    }

    private function seedFoundationFromLegacyData(): void
    {
        if (! Schema::hasTable('quiz_sets') || ! Schema::hasTable('quiz_collections')) {
            return;
        }

        $sets = DB::table('quiz_sets')->orderBy('sort_order')->orderBy('id')->get();

        foreach ($sets as $set) {
            $appId = $set->app_id ?? null;
            [$collectionSlug, $collectionTitle, $collectionType] = $this->collectionForSet($set);

            $collectionId = $this->upsertCollection($appId, $collectionSlug, $collectionTitle, $collectionType);
            $this->ensureDefaultCategories($appId, $collectionId, $collectionSlug);

            $groups = Schema::hasTable('quiz_study_groups')
                ? DB::table('quiz_study_groups')->where('quiz_set_id', $set->id)->orderBy('sort_order')->orderBy('id')->get()
                : collect();

            if ($groups->isNotEmpty()) {
                foreach ($groups as $group) {
                    $categorySlug = $this->categorySlugForGroup($collectionSlug, $group);
                    $categoryId = $this->categoryId($collectionId, $categorySlug) ?: $this->categoryId($collectionId, 'general');
                    $packId = $this->upsertPackFromGroup($appId, $collectionId, $categoryId, $set, $group);
                    $this->attachLevelsAndQuestionsToPack($packId, $set->id, $group->id);
                }
            } else {
                $categoryId = $this->categoryId($collectionId, $this->defaultCategorySlug($collectionSlug));
                $packId = $this->upsertPackFromSet($appId, $collectionId, $categoryId, $set);
                $this->attachLevelsAndQuestionsToPack($packId, $set->id, null);
            }
        }
    }

    private function collectionForSet(object $set): array
    {
        $key = strtolower((string) ($set->key ?? ''));
        $type = strtolower((string) ($set->type ?? ''));
        $title = strtolower((string) ($set->title ?? ''));
        $haystack = $key.' '.$type.' '.$title;

        if (str_contains($haystack, 'bible')) {
            return ['bible_quiz', 'Bible Quiz', 'bible'];
        }

        if (str_contains($haystack, 'sod') || str_contains($haystack, 'swd') || str_contains($haystack, 'seed')) {
            return ['sod_quiz', 'SOD Quiz', 'sod'];
        }

        if (str_contains($haystack, 'article')) {
            return ['article_quiz', 'Article Quiz', 'article'];
        }

        return ['custom_quiz', 'Custom Quiz', 'custom'];
    }

    private function upsertCollection(?int $appId, string $slug, string $title, string $type): int
    {
        $now = now();
        $row = DB::table('quiz_collections')->where('app_id', $appId)->where('slug', $slug)->first();

        if ($row) {
            return (int) $row->id;
        }

        return (int) DB::table('quiz_collections')->insertGetId([
            'app_id' => $appId,
            'title' => $title,
            'slug' => $slug,
            'description' => $title.' collection.',
            'icon' => str_contains($slug, 'bible') ? 'bible' : 'quiz',
            'type' => $type,
            'status' => 'published',
            'sort_order' => $this->collectionSort($slug),
            'is_enabled' => true,
            'settings_json' => json_encode(['source' => 'phase1a_legacy_seed']),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    private function ensureDefaultCategories(?int $appId, int $collectionId, string $collectionSlug): void
    {
        $categories = match ($collectionSlug) {
            'bible_quiz' => [
                ['general', 'General Bible Quiz', 'general', 10],
                ['old_testament', 'Old Testament Quiz', 'old_testament', 20],
                ['new_testament', 'New Testament Quiz', 'new_testament', 30],
                ['bible_characters', 'Bible Characters', 'characters', 40],
                ['bible_stories', 'Bible Stories', 'stories', 50],
                ['book_by_book', 'Book-by-Book Bible Quiz', 'book_by_book', 60],
            ],
            'sod_quiz' => [
                ['today', "Today's SOD Quiz", 'today', 10],
                ['previous', 'Previous SOD Quizzes', 'previous', 20],
                ['topic_based', 'Topic-based SOD Quizzes', 'topic_based', 30],
            ],
            'article_quiz' => [
                ['today', "Today's Article Quiz", 'today', 10],
                ['previous', 'Previous Article Quizzes', 'previous', 20],
                ['topic_based', 'Topic-based Article Quizzes', 'topic_based', 30],
            ],
            default => [
                ['general', 'General Quizzes', 'general', 10],
            ],
        };

        foreach ($categories as [$slug, $title, $type, $sort]) {
            if (DB::table('quiz_categories')->where('quiz_collection_id', $collectionId)->where('slug', $slug)->exists()) {
                continue;
            }

            DB::table('quiz_categories')->insert([
                'app_id' => $appId,
                'quiz_collection_id' => $collectionId,
                'title' => $title,
                'slug' => $slug,
                'description' => $title.'.',
                'icon' => $collectionSlug === 'bible_quiz' ? 'bible' : 'quiz',
                'type' => $type,
                'status' => 'published',
                'sort_order' => $sort,
                'is_enabled' => true,
                'settings_json' => json_encode(['source' => 'phase1a_default_category']),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    private function upsertPackFromSet(?int $appId, int $collectionId, ?int $categoryId, object $set): int
    {
        $slug = $this->safeSlug($set->key ?: $set->title ?: 'quiz-set-'.$set->id);
        $row = DB::table('quiz_packs')->where('quiz_collection_id', $collectionId)->where('slug', $slug)->first();

        $payload = [
            'app_id' => $appId,
            'quiz_collection_id' => $collectionId,
            'quiz_category_id' => $categoryId,
            'quiz_set_id' => $set->id,
            'quiz_study_group_id' => null,
            'title' => $set->title ?: Str::headline($slug),
            'slug' => $slug,
            'subtitle' => $set->subtitle ?? null,
            'description' => $set->subtitle ?? null,
            'source_type' => $set->type ?? 'legacy_quiz_set',
            'source_id' => $set->id,
            'cover_image_url' => $set->image_url ?? null,
            'difficulty' => $set->difficulty ?? null,
            'question_target' => 0,
            'total_questions' => $this->questionCount((int) $set->id, null),
            'is_today' => false,
            'is_featured' => false,
            'status' => (($set->status ?? 'draft') === 'published' && ($set->is_enabled ?? true)) ? 'published' : 'draft',
            'sort_order' => (int) ($set->sort_order ?? 0),
            'is_enabled' => (bool) ($set->is_enabled ?? true),
            'settings_json' => json_encode(['legacy_quiz_set_id' => $set->id, 'source' => 'phase1a_legacy_seed']),
            'updated_at' => now(),
        ];

        if ($row) {
            DB::table('quiz_packs')->where('id', $row->id)->update($payload);
            return (int) $row->id;
        }

        $payload['created_at'] = now();
        return (int) DB::table('quiz_packs')->insertGetId($payload);
    }

    private function upsertPackFromGroup(?int $appId, int $collectionId, ?int $categoryId, object $set, object $group): int
    {
        $slug = $this->safeSlug($group->key ?: $group->title ?: 'study-group-'.$group->id);
        $row = DB::table('quiz_packs')->where('quiz_collection_id', $collectionId)->where('slug', $slug)->first();

        $payload = [
            'app_id' => $appId,
            'quiz_collection_id' => $collectionId,
            'quiz_category_id' => $categoryId,
            'quiz_set_id' => $set->id,
            'quiz_study_group_id' => $group->id,
            'title' => $group->title ?: Str::headline($slug),
            'slug' => $slug,
            'subtitle' => $group->subtitle ?? null,
            'description' => $group->description ?? null,
            'source_type' => $group->type ?? $set->type ?? 'legacy_study_group',
            'source_id' => $group->id,
            'bible_book' => $group->bible_book ?? null,
            'cover_image_url' => $group->image_url ?? $set->image_url ?? null,
            'difficulty' => $set->difficulty ?? null,
            'question_target' => 0,
            'total_questions' => $this->questionCount((int) $set->id, (int) $group->id),
            'is_today' => false,
            'is_featured' => false,
            'status' => (($set->status ?? 'draft') === 'published' && ($set->is_enabled ?? true) && ($group->is_enabled ?? true)) ? 'published' : 'draft',
            'sort_order' => (int) ($group->sort_order ?? 0),
            'is_enabled' => (bool) ($group->is_enabled ?? true),
            'settings_json' => json_encode([
                'legacy_quiz_set_id' => $set->id,
                'legacy_study_group_id' => $group->id,
                'source' => 'phase1a_legacy_seed',
            ]),
            'updated_at' => now(),
        ];

        if ($row) {
            DB::table('quiz_packs')->where('id', $row->id)->update($payload);
            return (int) $row->id;
        }

        $payload['created_at'] = now();
        return (int) DB::table('quiz_packs')->insertGetId($payload);
    }

    private function attachLevelsAndQuestionsToPack(int $packId, int $setId, ?int $groupId): void
    {
        if (Schema::hasTable('quiz_levels') && Schema::hasColumn('quiz_levels', 'quiz_pack_id')) {
            $levelQuery = DB::table('quiz_levels')->where('quiz_set_id', $setId);

            if ($groupId !== null && Schema::hasColumn('quiz_levels', 'quiz_study_group_id')) {
                $levelQuery->where('quiz_study_group_id', $groupId);
            } elseif (Schema::hasColumn('quiz_levels', 'quiz_study_group_id')) {
                $levelQuery->whereNull('quiz_study_group_id');
            }

            $levelQuery->update(['quiz_pack_id' => $packId]);
        }

        if (Schema::hasTable('quiz_questions') && Schema::hasColumn('quiz_questions', 'quiz_pack_id')) {
            $questionQuery = DB::table('quiz_questions')->where('quiz_set_id', $setId);

            if ($groupId !== null && Schema::hasTable('quiz_levels') && Schema::hasColumn('quiz_levels', 'quiz_study_group_id') && Schema::hasColumn('quiz_questions', 'quiz_level_id')) {
                $levelIds = DB::table('quiz_levels')
                    ->where('quiz_set_id', $setId)
                    ->where('quiz_study_group_id', $groupId)
                    ->pluck('id')
                    ->all();

                $questionQuery->whereIn('quiz_level_id', $levelIds ?: [-1]);
            }

            $questionQuery->update(['quiz_pack_id' => $packId]);
        }
    }

    private function categorySlugForGroup(string $collectionSlug, object $group): string
    {
        if ($collectionSlug === 'bible_quiz') {
            if (! empty($group->bible_book) || ($group->type ?? '') === 'bible_book') {
                return 'book_by_book';
            }

            return 'general';
        }

        return $this->defaultCategorySlug($collectionSlug);
    }

    private function defaultCategorySlug(string $collectionSlug): string
    {
        return match ($collectionSlug) {
            'sod_quiz', 'article_quiz' => 'previous',
            default => 'general',
        };
    }

    private function categoryId(int $collectionId, string $slug): ?int
    {
        $row = DB::table('quiz_categories')->where('quiz_collection_id', $collectionId)->where('slug', $slug)->first();
        return $row ? (int) $row->id : null;
    }

    private function questionCount(int $setId, ?int $groupId): int
    {
        if (! Schema::hasTable('quiz_questions')) {
            return 0;
        }

        $query = DB::table('quiz_questions')->where('quiz_set_id', $setId)->where('is_enabled', true);

        if ($groupId !== null && Schema::hasTable('quiz_levels') && Schema::hasColumn('quiz_levels', 'quiz_study_group_id') && Schema::hasColumn('quiz_questions', 'quiz_level_id')) {
            $levelIds = DB::table('quiz_levels')
                ->where('quiz_set_id', $setId)
                ->where('quiz_study_group_id', $groupId)
                ->pluck('id')
                ->all();

            $query->whereIn('quiz_level_id', $levelIds ?: [-1]);
        }

        return (int) $query->count();
    }

    private function collectionSort(string $slug): int
    {
        return match ($slug) {
            'bible_quiz' => 10,
            'sod_quiz' => 20,
            'article_quiz' => 30,
            default => 90,
        };
    }

    private function safeSlug(string $value): string
    {
        $slug = Str::slug($value, '_');
        return $slug !== '' ? $slug : 'quiz_pack_'.Str::random(8);
    }
};
