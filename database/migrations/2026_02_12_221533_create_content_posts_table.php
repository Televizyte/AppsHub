<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_posts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('app_id')
                ->constrained('apps')
                ->cascadeOnDelete();

            $table->string('bucket');
            $table->string('status')->default('draft');

            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('slug')->nullable();

            $table->string('cover_image_url')->nullable();

            $table->longText('body_html')->nullable();
            $table->json('blocks_json')->nullable();

            $table->json('tags_json')->nullable();
            $table->json('meta_json')->nullable();

            $table->timestamp('publish_at')->nullable();
            $table->timestamp('published_at')->nullable();

            $table->string('author_name')->nullable();

            $table->boolean('is_featured')->default(false);
            $table->integer('sort_order')->default(0);

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_posts');
    }
};
