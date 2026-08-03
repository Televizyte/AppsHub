<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('app_routes', function (Blueprint $table) {
            if (!Schema::hasColumn('app_routes', 'path')) {
                $table->string('path', 255)->nullable()->after('title');
            }
        });
    }

    public function down(): void
    {
        Schema::table('app_routes', function (Blueprint $table) {
            if (Schema::hasColumn('app_routes', 'path')) {
                $table->dropColumn('path');
            }
        });
    }
};
