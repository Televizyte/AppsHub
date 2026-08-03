<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quiz_study_groups')) {
            return;
        }

        Schema::create('quiz_study_groups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_set_id')->constrained('quiz_sets')->cascadeOnDelete();
            $table->string('key');
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('type')->default('custom');
            $table->string('bible_book')->nullable();
            $table->string('testament')->nullable();
            $table->text('description')->nullable();
            $table->text('image_url')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_enabled')->default(true);
            $table->json('settings_json')->nullable();
            $table->timestamps();

            $table->unique(['quiz_set_id', 'key']);
            $table->index(['quiz_set_id', 'sort_order']);
            $table->index(['type', 'bible_book']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_study_groups');
    }
};
