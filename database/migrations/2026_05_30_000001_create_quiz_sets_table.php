<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('quiz_sets')) {
            return;
        }

        Schema::create('quiz_sets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->nullable()->constrained('apps')->nullOnDelete();
            $table->string('key')->index();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('type')->default('general')->index();
            $table->string('source_bucket')->nullable()->index();
            $table->string('source_key')->nullable()->index();
            $table->string('image_url', 2048)->nullable();
            $table->string('difficulty')->default('easy');
            $table->string('status')->default('draft')->index();
            $table->boolean('is_enabled')->default(true)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('settings_json')->nullable();
            $table->timestamps();

            $table->unique(['app_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_sets');
    }
};
