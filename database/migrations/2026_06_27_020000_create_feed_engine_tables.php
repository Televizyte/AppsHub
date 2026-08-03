<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('feed_posts')) {
            Schema::create('feed_posts', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('app_id')->index();
                $table->string('bucket', 80)->default('all')->index();
                $table->string('post_type', 80)->default('text_post')->index();
                $table->string('source_engine', 80)->nullable()->index();
                $table->string('source_id', 120)->nullable()->index();
                $table->string('title')->nullable();
                $table->longText('body')->nullable();
                $table->text('excerpt')->nullable();
                $table->text('thumbnail_url')->nullable();
                $table->text('media_url')->nullable();
                $table->string('deep_link')->nullable()->index();
                $table->string('cta_label', 80)->nullable();
                $table->string('status', 40)->default('draft')->index();
                $table->string('approval_status', 40)->default('approved')->index();
                $table->string('visibility', 40)->default('public')->index();
                $table->boolean('is_pinned')->default(false)->index();
                $table->boolean('is_featured')->default(false)->index();
                $table->integer('sort_order')->default(0)->index();
                $table->timestamp('published_at')->nullable()->index();
                $table->string('created_by_type', 40)->default('admin')->index();
                $table->unsignedBigInteger('created_by_id')->nullable()->index();
                $table->json('meta_json')->nullable();
                $table->timestamps();

                $table->index(['app_id', 'status', 'published_at']);
                $table->index(['app_id', 'bucket', 'status']);
                $table->index(['app_id', 'post_type', 'status']);
            });
        }

        if (! Schema::hasTable('feed_comments')) {
            Schema::create('feed_comments', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('app_id')->index();
                $table->unsignedBigInteger('feed_post_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->unsignedBigInteger('parent_id')->nullable()->index();
                $table->text('body');
                $table->string('status', 40)->default('published')->index();
                $table->text('moderation_note')->nullable();
                $table->json('meta_json')->nullable();
                $table->timestamps();

                $table->index(['app_id', 'feed_post_id', 'status']);
            });
        }

        if (! Schema::hasTable('feed_reactions')) {
            Schema::create('feed_reactions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('app_id')->index();
                $table->unsignedBigInteger('feed_post_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->string('type', 40)->default('like')->index();
                $table->json('meta_json')->nullable();
                $table->timestamps();

                $table->unique(['feed_post_id', 'user_id', 'type'], 'feed_reactions_unique_user_type');
                $table->index(['app_id', 'feed_post_id', 'type']);
            });
        }

        if (! Schema::hasTable('feed_saves')) {
            Schema::create('feed_saves', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('app_id')->index();
                $table->unsignedBigInteger('feed_post_id')->index();
                $table->unsignedBigInteger('user_id')->index();
                $table->json('meta_json')->nullable();
                $table->timestamps();

                $table->unique(['feed_post_id', 'user_id'], 'feed_saves_unique_user');
                $table->index(['app_id', 'feed_post_id']);
            });
        }

        if (! Schema::hasTable('feed_shares')) {
            Schema::create('feed_shares', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('app_id')->index();
                $table->unsignedBigInteger('feed_post_id')->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('channel', 80)->nullable()->index();
                $table->json('meta_json')->nullable();
                $table->timestamps();

                $table->index(['app_id', 'feed_post_id']);
            });
        }

        if (! Schema::hasTable('feed_reports')) {
            Schema::create('feed_reports', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('app_id')->index();
                $table->unsignedBigInteger('feed_post_id')->nullable()->index();
                $table->unsignedBigInteger('feed_comment_id')->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('reason', 120)->nullable();
                $table->text('details')->nullable();
                $table->string('status', 40)->default('open')->index();
                $table->json('meta_json')->nullable();
                $table->timestamps();

                $table->index(['app_id', 'status']);
            });
        }

        if (! Schema::hasTable('feed_settings')) {
            Schema::create('feed_settings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('app_id')->unique();
                $table->boolean('is_enabled')->default(true);
                $table->boolean('auto_approve_system_posts')->default(true);
                $table->boolean('auto_approve_tool_posts')->default(false);
                $table->boolean('auto_approve_text_posts')->default(false);
                $table->boolean('comments_enabled')->default(true);
                $table->boolean('comments_require_approval')->default(false);
                $table->boolean('user_text_posts_enabled')->default(false);
                $table->boolean('external_media_uploads_enabled')->default(false);
                $table->json('settings_json')->nullable();
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('feed_settings');
        Schema::dropIfExists('feed_reports');
        Schema::dropIfExists('feed_shares');
        Schema::dropIfExists('feed_saves');
        Schema::dropIfExists('feed_reactions');
        Schema::dropIfExists('feed_comments');
        Schema::dropIfExists('feed_posts');
    }
};
