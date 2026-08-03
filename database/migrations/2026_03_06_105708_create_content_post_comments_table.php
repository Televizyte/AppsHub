<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('content_post_comments', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('app_id')->index();
            $table->unsignedBigInteger('content_post_id')->index();
            $table->unsignedBigInteger('user_id')->index();

            $table->text('body');

            $table->string('status', 40)->default('published')->index();

            $table->timestamps();

            $table->index(
                ['app_id', 'content_post_id', 'created_at'],
                'idx_app_post_created'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('content_post_comments');
    }
};
