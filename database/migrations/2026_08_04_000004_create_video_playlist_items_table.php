<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('video_playlist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->foreignId('video_playlist_id')->constrained('video_playlists')->cascadeOnDelete();
            $table->foreignId('video_id')->constrained('videos')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('settings_json')->nullable();
            $table->timestamps();

            $table->unique(['video_playlist_id', 'video_id']);
            $table->index(['app_id', 'video_playlist_id', 'sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('video_playlist_items');
    }
};
