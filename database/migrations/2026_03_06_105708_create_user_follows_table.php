<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_follows', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('app_id')->index();

            $table->unsignedBigInteger('follower_user_id')->index();
            $table->unsignedBigInteger('following_user_id')->index();

            $table->timestamps();

            $table->unique(
                ['app_id', 'follower_user_id', 'following_user_id'],
                'uniq_app_follower_following'
            );

            $table->index(
                ['app_id', 'following_user_id'],
                'idx_app_following'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_follows');
    }
};
