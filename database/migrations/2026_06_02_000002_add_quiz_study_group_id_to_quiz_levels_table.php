<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('quiz_levels')) {
            return;
        }

        Schema::table('quiz_levels', function (Blueprint $table) {
            if (! Schema::hasColumn('quiz_levels', 'quiz_study_group_id')) {
                $table->foreignId('quiz_study_group_id')
                    ->nullable()
                    ->after('quiz_set_id')
                    ->constrained('quiz_study_groups')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('quiz_levels') || ! Schema::hasColumn('quiz_levels', 'quiz_study_group_id')) {
            return;
        }

        Schema::table('quiz_levels', function (Blueprint $table) {
            $table->dropConstrainedForeignId('quiz_study_group_id');
        });
    }
};
