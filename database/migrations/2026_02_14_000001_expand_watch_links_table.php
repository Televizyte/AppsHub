<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('watch_links', function (Blueprint $table) {
            // If these columns already exist later, Laravel will error.
            // But currently your SHOW COLUMNS proves they don't exist.

            $table->unsignedBigInteger('app_id')->nullable()->after('id');
            $table->string('title')->nullable()->after('app_id');
            $table->string('type')->nullable()->after('title'); // live|backup|youtube|...
            $table->text('url')->nullable()->after('type');

            $table->boolean('is_enabled')->default(true)->after('url');
            $table->integer('sort_order')->default(0)->after('is_enabled');

            $table->json('meta_json')->nullable()->after('sort_order');

            $table->index(['app_id', 'is_enabled']);
        });
    }

    public function down(): void
    {
        Schema::table('watch_links', function (Blueprint $table) {
            $table->dropIndex(['app_id', 'is_enabled']);

            $table->dropColumn([
                'app_id',
                'title',
                'type',
                'url',
                'is_enabled',
                'sort_order',
                'meta_json',
            ]);
        });
    }
};
