<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quiz_questions')) {
            return;
        }

        Schema::table('quiz_questions', function (Blueprint $table) {
            if (! Schema::hasColumn('quiz_questions', 'answer_note')) {
                $table->text('answer_note')->nullable()->after('explanation');
            }

            if (! Schema::hasColumn('quiz_questions', 'bible_reference')) {
                $table->string('bible_reference', 255)->nullable()->after('answer_note');
            }

            if (! Schema::hasColumn('quiz_questions', 'bible_book')) {
                $table->string('bible_book', 80)->nullable()->after('bible_reference');
            }

            if (! Schema::hasColumn('quiz_questions', 'chapter_start')) {
                $table->unsignedSmallInteger('chapter_start')->nullable()->after('bible_book');
            }

            if (! Schema::hasColumn('quiz_questions', 'verse_start')) {
                $table->unsignedSmallInteger('verse_start')->nullable()->after('chapter_start');
            }

            if (! Schema::hasColumn('quiz_questions', 'chapter_end')) {
                $table->unsignedSmallInteger('chapter_end')->nullable()->after('verse_start');
            }

            if (! Schema::hasColumn('quiz_questions', 'verse_end')) {
                $table->unsignedSmallInteger('verse_end')->nullable()->after('chapter_end');
            }

            if (! Schema::hasColumn('quiz_questions', 'study_focus')) {
                $table->string('study_focus', 120)->nullable()->after('verse_end');
            }

            if (! Schema::hasColumn('quiz_questions', 'question_kind')) {
                $table->string('question_kind', 80)->nullable()->after('study_focus');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('quiz_questions')) {
            return;
        }

        Schema::table('quiz_questions', function (Blueprint $table) {
            foreach ([
                'question_kind',
                'study_focus',
                'verse_end',
                'chapter_end',
                'verse_start',
                'chapter_start',
                'bible_book',
                'bible_reference',
                'answer_note',
            ] as $column) {
                if (Schema::hasColumn('quiz_questions', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
