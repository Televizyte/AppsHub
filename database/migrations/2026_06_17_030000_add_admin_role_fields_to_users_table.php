<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'admin_role')) {
                $table->string('admin_role')->nullable()->after('active_app_id');
            }

            if (! Schema::hasColumn('users', 'admin_permissions')) {
                $table->json('admin_permissions')->nullable()->after('admin_role');
            }

            if (! Schema::hasColumn('users', 'assigned_app_ids')) {
                $table->json('assigned_app_ids')->nullable()->after('admin_permissions');
            }

            if (! Schema::hasColumn('users', 'admin_is_active')) {
                $table->boolean('admin_is_active')->default(true)->after('assigned_app_ids');
            }

            if (! Schema::hasColumn('users', 'last_login_at')) {
                $table->timestamp('last_login_at')->nullable()->after('admin_is_active');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            foreach (['last_login_at', 'admin_is_active', 'assigned_app_ids', 'admin_permissions', 'admin_role'] as $column) {
                if (Schema::hasColumn('users', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
