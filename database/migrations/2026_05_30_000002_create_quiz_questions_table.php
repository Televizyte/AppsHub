<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quiz_questions')) {
            return;
        }

        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quiz_set_id')->constrained('quiz_sets')->cascadeOnDelete();
            $table->unsignedTinyInteger('level')->default(1)->index();
            $table->text('question_text');
            $table->string('option_a', 1000)->nullable();
            $table->string('option_b', 1000)->nullable();
            $table->string('option_c', 1000)->nullable();
            $table->string('option_d', 1000)->nullable();
            $table->string('correct_option', 10)->nullable();
            $table->text('explanation')->nullable();
            $table->unsignedInteger('points')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_enabled')->default(true)->index();
            $table->json('meta_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
