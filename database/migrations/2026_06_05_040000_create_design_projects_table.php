<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('design_projects')) {
            return;
        }

        Schema::create('design_projects', function (Blueprint $table) {
            $table->id();

            $table->foreignId('app_id')
                ->nullable()
                ->constrained('apps')
                ->nullOnDelete();

            $table->string('scope', 40)->default('app')->index();
            $table->string('design_type', 60)->default('general')->index();
            $table->string('template_category', 80)->nullable()->index();

            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('slug')->nullable()->index();

            $table->string('status', 40)->default('draft')->index();
            $table->boolean('is_template')->default(false)->index();
            $table->boolean('is_active')->default(true)->index();

            $table->json('canvas_json')->nullable();
            $table->json('layers_json')->nullable();
            $table->json('settings_json')->nullable();
            $table->json('preview_json')->nullable();

            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();

            $table->integer('sort_order')->default(0)->index();
            $table->timestamps();

            $table->index(['app_id', 'design_type']);
            $table->index(['is_template', 'template_category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('design_projects');
    }
};
