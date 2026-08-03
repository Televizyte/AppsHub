<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ad_rules', function (Blueprint $table) {
            if (! Schema::hasColumn('ad_rules', 'settings_json')) {
                $table->json('settings_json')->nullable()->after('interstitial_cooldown_seconds');
            }
        });
    }

    public function down(): void
    {
        Schema::table('ad_rules', function (Blueprint $table) {
            if (Schema::hasColumn('ad_rules', 'settings_json')) {
                $table->dropColumn('settings_json');
            }
        });
    }
};
