<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (!Schema::hasColumn('users', 'active_app_id')) {
                $table->unsignedBigInteger('active_app_id')->nullable()->after('remember_token');
                $table->index('active_app_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'active_app_id')) {
                $table->dropIndex(['active_app_id']);
                $table->dropColumn('active_app_id');
            }
        });
    }
};
