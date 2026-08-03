<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Session 1 seed: Dunamis TV (Source of Truth app)
        $this->call(DunamisTvSeeder::class);

        // NOTE:
        // We are NOT creating a default "Test User" here.
        // Filament admin accounts should be created intentionally (we'll do that in Session 2 / Admin setup).
    }
}
