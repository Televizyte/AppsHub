<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_views', function (Blueprint $table) {
            $table->id();

            $table->foreignId('app_id')->constrained('apps')->cascadeOnDelete();
            $table->foreignId('content_post_id')->constrained('content_posts')->cascadeOnDelete();

            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('session_id', 120)->nullable()->index();

            $table->string('source', 60)->nullable()->index();
            $table->string('platform', 30)->nullable()->index();

            $table->string('device_type', 30)->nullable();
            $table->string('app_version', 40)->nullable();
            $table->string('os_version', 40)->nullable();

            $table->ipAddress('ip_address')->nullable();
            $table->text('user_agent')->nullable();

            $table->json('meta_json')->nullable();

            $table->timestamp('viewed_at')->nullable()->index();
            $table->timestamps();

            $table->index(['app_id', 'content_post_id']);
            $table->index(['app_id', 'user_id']);
            $table->index(['app_id', 'viewed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_views');
    }
};
