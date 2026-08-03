<?php

namespace Database\Seeders;

use App\Models\QuizSet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class QuizLevelSeeder extends Seeder
{
    public function run(): void
    {
        if (! Schema::hasTable('quiz_sets') || ! Schema::hasTable('quiz_levels')) {
            return;
        }

        QuizSet::query()->get()->each(fn (QuizSet $set) => $set->ensureDefaultLevels());
    }
}
