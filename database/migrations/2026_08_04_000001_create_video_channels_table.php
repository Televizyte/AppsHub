<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_channels', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->string('slug');
            $table->string('title');
            $table->text('description')->nullable();
            $table->foreignId('thumbnail_media_asset_id')->nullable()->constrained('media_assets')->nullOnDelete();
            $table->string('status', 40)->default('draft')->index();
            $table->string('visibility', 40)->default('public')->index();
            $table->boolean('is_featured')->default(false)->index();
            $table->unsignedInteger('sort_order')->default(0);
            $table->timestamp('publish_at')->nullable()->index();
            $table->timestamp('published_at')->nullable();
            $table->json('notification_settings_json')->nullable();
            $table->json('settings_json')->nullable();
            $table->timestamps();

            $table->unique(['app_id', 'slug']);
            $table->index(['app_id', 'status', 'publish_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_channels');
    }
};
