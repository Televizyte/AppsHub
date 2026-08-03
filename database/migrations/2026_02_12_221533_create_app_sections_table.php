<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_sections', function (Blueprint $table) {
            $table->id();

            $table->foreignId('app_id')
                ->constrained('apps')
                ->cascadeOnDelete();

            $table->string('tab_key');
            $table->string('route_key')->nullable();

            $table->string('key');
            $table->string('title');
            $table->string('subtitle')->nullable();

            $table->string('template');

            $table->integer('sort_order')->default(0);
            $table->boolean('is_enabled')->default(true);

            $table->json('visibility_json')->nullable();
            $table->json('empty_state_json')->nullable();
            $table->json('meta_json')->nullable();

            $table->timestamps();

            $table->unique(['app_id', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_sections');
    }
};
