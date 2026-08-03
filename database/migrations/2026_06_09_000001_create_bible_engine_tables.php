<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('bible_translations')) {
            Schema::create('bible_translations', function (Blueprint $table) {
                $table->id();
                $table->string('key', 40)->unique();
                $table->string('name');
                $table->string('language', 20)->default('en');
                $table->string('license_type', 60)->default('public_domain');
                $table->text('copyright_note')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->json('meta_json')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bible_books')) {
            Schema::create('bible_books', function (Blueprint $table) {
                $table->id();
                $table->foreignId('translation_id')->constrained('bible_translations')->cascadeOnDelete();
                $table->unsignedSmallInteger('book_number');
                $table->string('testament', 20)->default('old')->index();
                $table->string('name');
                $table->string('short_name', 40)->nullable();
                $table->string('slug')->index();
                $table->unsignedSmallInteger('chapter_count')->default(0);
                $table->json('meta_json')->nullable();
                $table->timestamps();
                $table->unique(['translation_id', 'book_number']);
                $table->unique(['translation_id', 'slug']);
            });
        }

        if (! Schema::hasTable('bible_chapters')) {
            Schema::create('bible_chapters', function (Blueprint $table) {
                $table->id();
                $table->foreignId('translation_id')->constrained('bible_translations')->cascadeOnDelete();
                $table->foreignId('book_id')->constrained('bible_books')->cascadeOnDelete();
                $table->unsignedSmallInteger('chapter_number');
                $table->unsignedSmallInteger('verse_count')->default(0);
                $table->json('meta_json')->nullable();
                $table->timestamps();
                $table->unique(['book_id', 'chapter_number']);
                $table->index(['translation_id', 'chapter_number']);
            });
        }

        if (! Schema::hasTable('bible_verses')) {
            Schema::create('bible_verses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('translation_id')->constrained('bible_translations')->cascadeOnDelete();
                $table->foreignId('book_id')->constrained('bible_books')->cascadeOnDelete();
                $table->foreignId('chapter_id')->constrained('bible_chapters')->cascadeOnDelete();
                $table->string('book_name')->index();
                $table->unsignedSmallInteger('chapter_number');
                $table->unsignedSmallInteger('verse_number');
                $table->string('reference')->index();
                $table->text('text');
                $table->longText('search_text')->nullable();
                $table->json('meta_json')->nullable();
                $table->timestamps();
                $table->unique(['translation_id', 'book_id', 'chapter_number', 'verse_number'], 'bible_verse_unique_ref');
                $table->index(['translation_id', 'book_name', 'chapter_number']);
            });
        }

        if (! Schema::hasTable('bible_topics')) {
            Schema::create('bible_topics', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('slug')->unique();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true)->index();
                $table->json('meta_json')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('bible_topic_verse')) {
            Schema::create('bible_topic_verse', function (Blueprint $table) {
                $table->id();
                $table->foreignId('topic_id')->constrained('bible_topics')->cascadeOnDelete();
                $table->foreignId('verse_id')->constrained('bible_verses')->cascadeOnDelete();
                $table->unsignedSmallInteger('weight')->default(50)->index();
                $table->string('note')->nullable();
                $table->timestamps();
                $table->unique(['topic_id', 'verse_id']);
            });
        }

        if (! Schema::hasTable('scripture_collections')) {
            Schema::create('scripture_collections', function (Blueprint $table) {
                $table->id();
                $table->foreignId('app_id')->nullable()->constrained('apps')->nullOnDelete();
                $table->string('title');
                $table->string('slug')->index();
                $table->text('description')->nullable();
                $table->string('visibility', 20)->default('global')->index();
                $table->boolean('is_active')->default(true)->index();
                $table->json('meta_json')->nullable();
                $table->timestamps();
                $table->unique(['app_id', 'slug']);
            });
        }

        if (! Schema::hasTable('scripture_collection_items')) {
            Schema::create('scripture_collection_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('collection_id')->constrained('scripture_collections')->cascadeOnDelete();
                $table->foreignId('verse_id')->constrained('bible_verses')->cascadeOnDelete();
                $table->unsignedInteger('sort_order')->default(0)->index();
                $table->string('custom_note')->nullable();
                $table->timestamps();
                $table->unique(['collection_id', 'verse_id']);
            });
        }

        if (! Schema::hasTable('app_scripture_settings')) {
            Schema::create('app_scripture_settings', function (Blueprint $table) {
                $table->id();
                $table->foreignId('app_id')->unique()->constrained('apps')->cascadeOnDelete();
                $table->foreignId('default_translation_id')->nullable()->constrained('bible_translations')->nullOnDelete();
                $table->string('daily_scripture_mode', 40)->default('manual');
                $table->json('enabled_topics_json')->nullable();
                $table->json('enabled_collections_json')->nullable();
                $table->boolean('show_reference')->default(true);
                $table->boolean('show_translation')->default(false);
                $table->json('meta_json')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('app_scripture_settings');
        Schema::dropIfExists('scripture_collection_items');
        Schema::dropIfExists('scripture_collections');
        Schema::dropIfExists('bible_topic_verse');
        Schema::dropIfExists('bible_topics');
        Schema::dropIfExists('bible_verses');
        Schema::dropIfExists('bible_chapters');
        Schema::dropIfExists('bible_books');
        Schema::dropIfExists('bible_translations');
    }
};
