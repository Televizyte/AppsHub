<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('builder_templates')) {
            return;
        }

        Schema::create('builder_templates', function (Blueprint $table) {
            $table->id();
            $table->string('category', 60)->index();
            $table->string('key', 120)->unique();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->text('description')->nullable();
            $table->string('badge', 80)->nullable();
            $table->string('glyph', 40)->nullable();
            $table->string('tone', 40)->default('cyan');
            $table->string('status', 80)->default('Ready');
            $table->string('apply_mode', 40)->default('append');
            $table->json('payload_json')->nullable();
            $table->json('preview_json')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('builder_templates');
    }
};
