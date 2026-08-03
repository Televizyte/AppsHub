<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_post_likes', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('app_id')->index();
            $table->unsignedBigInteger('content_post_id')->index();
            $table->unsignedBigInteger('user_id')->index();

            $table->timestamps();

            $table->unique(
                ['app_id', 'content_post_id', 'user_id'],
                'uniq_app_post_user_like'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_post_likes');
    }
};
