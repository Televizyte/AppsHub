<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('content_post_saves')) {
            return;
        }

        Schema::create('content_post_saves', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('app_id');
            $table->unsignedBigInteger('content_post_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();

            $table->unique(
                ['app_id', 'content_post_id', 'user_id'],
                'content_post_saves_unique'
            );
            $table->index(['app_id', 'user_id']);
            $table->index(['app_id', 'content_post_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_post_saves');
    }
};
