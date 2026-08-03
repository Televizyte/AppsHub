<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('books')) {
            return;
        }

        Schema::table('books', function (Blueprint $table) {
            if (! Schema::hasColumn('books', 'final_cover_image_url')) {
                $table->string('final_cover_image_url', 1000)->nullable()->after('cover_image_url');
            }

            if (! Schema::hasColumn('books', 'final_cover_image_path')) {
                $table->string('final_cover_image_path', 1000)->nullable()->after('final_cover_image_url');
            }

            if (! Schema::hasColumn('books', 'final_cover_mime')) {
                $table->string('final_cover_mime', 120)->nullable()->after('final_cover_image_path');
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('books')) {
            return;
        }

        Schema::table('books', function (Blueprint $table) {
            foreach (['final_cover_mime', 'final_cover_image_path', 'final_cover_image_url'] as $column) {
                if (Schema::hasColumn('books', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
