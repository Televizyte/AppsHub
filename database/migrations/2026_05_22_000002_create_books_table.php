<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('books')) {
            return;
        }

        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->foreignId('book_category_id')->nullable()->constrained('book_categories')->nullOnDelete();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('slug');
            $table->string('author_name')->nullable();
            $table->longText('description')->nullable();
            $table->string('cover_image_url', 1000)->nullable();
            $table->string('book_type', 40)->default('manual');
            $table->string('status', 40)->default('draft');
            $table->string('access_type', 40)->default('free');
            $table->string('file_url', 1000)->nullable();
            $table->string('file_path', 1000)->nullable();
            $table->string('external_url', 1000)->nullable();
            $table->boolean('is_featured')->default(false);
            $table->boolean('is_downloadable')->default(false);
            $table->integer('sort_order')->default(0);
            $table->timestamp('published_at')->nullable();
            $table->json('meta_json')->nullable();
            $table->timestamps();

            $table->unique(['app_id', 'slug']);
            $table->index(['app_id', 'status', 'sort_order']);
            $table->index(['app_id', 'book_type']);
            $table->index(['app_id', 'access_type']);
            $table->index(['app_id', 'is_featured']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
