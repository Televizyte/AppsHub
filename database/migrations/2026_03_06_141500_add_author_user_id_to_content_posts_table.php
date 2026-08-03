<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_posts', function (Blueprint $table): void {
            if (! Schema::hasColumn('content_posts', 'author_user_id')) {
                $table->unsignedBigInteger('author_user_id')
                    ->nullable()
                    ->after('author_name');

                $table->index(['app_id', 'author_user_id'], 'content_posts_app_author_idx');
                $table->foreign('author_user_id', 'content_posts_author_user_fk')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('content_posts', function (Blueprint $table): void {
            if (Schema::hasColumn('content_posts', 'author_user_id')) {
                try {
                    $table->dropForeign('content_posts_author_user_fk');
                } catch (\Throwable $e) {
                    //
                }

                try {
                    $table->dropIndex('content_posts_app_author_idx');
                } catch (\Throwable $e) {
                    //
                }

                $table->dropColumn('author_user_id');
            }
        });
    }
};
