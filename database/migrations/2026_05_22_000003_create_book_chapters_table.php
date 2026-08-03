<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('book_chapters')) {
            return;
        }

        Schema::create('book_chapters', function (Blueprint $table) {
            $table->id();
            $table->foreignId('book_id')->constrained('books')->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->string('subtitle')->nullable();
            $table->longText('body_html')->nullable();
            $table->longText('summary')->nullable();
            $table->text('key_thought')->nullable();
            $table->longText('reflection_questions')->nullable();
            $table->longText('prayer_points')->nullable();
            $table->longText('action_steps')->nullable();
            $table->string('memory_verse')->nullable();
            $table->string('status', 40)->default('draft');
            $table->integer('sort_order')->default(0);
            $table->json('meta_json')->nullable();
            $table->timestamps();

            $table->unique(['book_id', 'slug']);
            $table->index(['book_id', 'status', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('book_chapters');
    }
};
