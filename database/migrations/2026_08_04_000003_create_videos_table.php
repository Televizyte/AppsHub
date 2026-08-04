<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('videos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->foreignId('video_channel_id')->constrained('video_channels')->cascadeOnDelete();
            $table->string('slug');
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('source_type', 40)->index();
            $table->string('provider', 80);
            $table->string('provider_video_id')->nullable();
            $table->foreignId('media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->text('external_url')->nullable();
            $table->foreignId('thumbnail_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('mime_type', 120)->nullable();
            $table->unsignedInteger('duration_seconds')->nullable();
            $table->string('aspect_ratio', 30)->nullable();
            $table->boolean('is_live')->default(false)->index();
            $table->string('status', 40)->default('draft')->index();
            $table->string('visibility', 40)->default('public')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('publish_at')->nullable()->index();
            $table->timestamp('published_at')->nullable();
            $table->json('playback_settings_json')->nullable();
            $table->json('settings_json')->nullable();
            $table->timestamps();

            $table->unique(['app_id', 'slug']);
            $table->index(['app_id', 'video_channel_id', 'sort_order']);
            $table->index(['app_id', 'status', 'publish_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('videos');
    }
};
